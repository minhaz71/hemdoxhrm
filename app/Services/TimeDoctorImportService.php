<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\TimeDoctorDailyRecord;
use App\Models\TimeDoctorImport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeDoctorImportService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SettingService $settings,
        private readonly HolidayCalendarService $calendar,
    ) {}

    private const REQUIRED_COLUMNS = [
        'Name',
        'Email',
        'Employee ID',
        'User groups',
        'Date',
        'Time tracked',
        'Idle minutes',
        'Idle minutes %',
        'Start time',
        'End time',
    ];

    public function import(UploadedFile $file, User $importedBy): TimeDoctorImport
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $parsed = $this->readCsv($file->getRealPath());
        $this->validateHeaders($parsed['headers']);

        $storedPath = $file->storeAs(
            'time-doctor-imports',
            now()->format('Ymd_His') . '_' . $hash . '.csv',
            'local'
        );

        if (! $storedPath) {
            throw ValidationException::withMessages(['csv_file' => 'Unable to store uploaded CSV file.']);
        }

        return DB::transaction(function () use ($file, $hash, $storedPath, $parsed, $importedBy) {
            $import = TimeDoctorImport::create([
                'imported_by' => $importedBy->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_hash' => $hash,
                'headers' => $parsed['headers'],
                'total_rows' => count($parsed['rows']),
                'imported_at' => now(),
            ]);

            $stats = [
                'processed_rows' => 0,
                'created_records' => 0,
                'updated_records' => 0,
                'attendance_synced' => 0,
                'skipped_rows' => 0,
                'errors' => [],
            ];

            foreach ($parsed['rows'] as $lineNumber => $row) {
                try {
                    $result = DB::transaction(fn () => $this->processRow($import, $row));
                    $stats['processed_rows']++;
                    $stats[$result['record_created'] ? 'created_records' : 'updated_records']++;
                    if ($result['attendance_synced']) {
                        $stats['attendance_synced']++;
                    }
                } catch (ValidationException $e) {
                    $stats['skipped_rows']++;
                    $stats['errors'][] = [
                        'line' => $lineNumber,
                        'email' => $row['Email'] ?? null,
                        'message' => collect($e->errors())->flatten()->first(),
                    ];
                } catch (\Throwable $e) {
                    $stats['skipped_rows']++;
                    $stats['errors'][] = [
                        'line' => $lineNumber,
                        'email' => $row['Email'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $import->update($stats);

            return $import->fresh(['importer']);
        });
    }

    public function paginateImports(int $perPage = 15)
    {
        return TimeDoctorImport::with('importer')
            ->latest('imported_at')
            ->paginate($perPage);
    }

    public function paginateRecords(TimeDoctorImport $import, int $perPage = 50)
    {
        return $import->records()
            ->with(['employee.user', 'attendance', 'leave'])
            ->orderBy('work_date')
            ->orderBy('email')
            ->paginate($perPage);
    }

    private function processRow(TimeDoctorImport $import, array $row): array
    {
        $email = strtolower(trim((string) ($row['Email'] ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages(['email' => 'Email is missing.']);
        }

        $employee = Employee::whereHas('user', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$email]))
            ->with('user')
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages(['email' => "No employee matched {$email}."]);
        }

        $workDate = $this->parseDate($row['Date'] ?? null);
        $trackedMinutes = $this->parseDurationToMinutes($row['Time tracked'] ?? '');
        $idleMinutes = $this->parseDurationToMinutes($row['Idle minutes'] ?? '');
        $productiveMinutes = max(0, $trackedMinutes - $idleMinutes);
        $productivityPercentage = $trackedMinutes > 0 ? round(($productiveMinutes / $trackedMinutes) * 100, 2) : 0;
        $startTime = $this->parseTime($row['Start time'] ?? null);
        $endTime = $this->parseTime($row['End time'] ?? null);
        $leave = $this->approvedLeaveForDate($employee, $workDate);
        $workedOnLeave = $leave !== null && $trackedMinutes > 0;
        $attendanceStatus = $trackedMinutes <= 0 && $this->calendar->isNonWorkingDay($employee, $workDate)
            ? 'present'
            : $this->attendanceStatus($trackedMinutes, $startTime);

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $workDate->toDateString(),
            ],
            [
                'check_in' => $startTime,
                'check_out' => $endTime,
                'status' => $attendanceStatus,
                'source' => 'time_doctor',
                'time_doctor_worked_minutes' => $trackedMinutes,
                'time_doctor_idle_minutes' => $idleMinutes,
                'note' => $workedOnLeave
                    ? "Synced from Time Doctor import #{$import->id}. Worked hours were found during approved leave."
                    : "Synced from Time Doctor import #{$import->id}.",
            ]
        );

        $payload = [
            'time_doctor_import_id' => $import->id,
            'attendance_id' => $attendance->id,
            'leave_id' => $leave?->id,
            'name' => $row['Name'] ?? null,
            'email' => $email,
            'time_doctor_employee_id' => $row['Employee ID'] ?? null,
            'user_groups' => $row['User groups'] ?? null,
            'time_tracked_minutes' => $trackedMinutes,
            'idle_minutes' => $idleMinutes,
            'idle_minutes_percent' => $this->parsePercent($row['Idle minutes %'] ?? '0'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'active_minutes' => $productiveMinutes,
            'activity_percent' => $productivityPercentage,
            'productive_minutes' => $productiveMinutes,
            'productivity_percentage' => $productivityPercentage,
            'attendance_status' => $attendanceStatus,
            'worked_on_leave' => $workedOnLeave,
            'raw_row' => $this->requiredRawRow($row),
        ];

        $record = TimeDoctorDailyRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate->toDateString())
            ->first();

        $created = false;
        if ($record) {
            $record->update($payload);
        } else {
            $record = TimeDoctorDailyRecord::create($payload + [
                'employee_id' => $employee->id,
                'work_date' => $workDate->toDateString(),
            ]);
            $created = true;
        }

        $this->notifyWorkedOnLeave($record->fresh(['employee.user', 'leave']), $trackedMinutes);

        return [
            'record_created' => $created,
            'attendance_synced' => $attendance->wasRecentlyCreated || $attendance->wasChanged(),
        ];
    }

    private function approvedLeaveForDate(Employee $employee, Carbon $workDate): ?Leave
    {
        return Leave::approved()
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $workDate->toDateString())
            ->whereDate('end_date', '>=', $workDate->toDateString())
            ->first();
    }

    private function notifyWorkedOnLeave(TimeDoctorDailyRecord $record, int $trackedMinutes): void
    {
        if (! $record->worked_on_leave || $record->penalty_warning_sent_at) {
            return;
        }

        $subject = 'Time Doctor activity found during approved leave';
        $body = implode("\n\n", [
            "Time Doctor shows {$this->formatMinutes($trackedMinutes)} of tracked work on {$record->work_date->format('M j, Y')}, while you had an approved leave record for that date.",
            'HR will review this attendance exception. A penalty or leave adjustment may apply if company policy requires it.',
            'If this is incorrect, please contact HR with supporting details.',
        ]);

        $this->notifications->sendWarning($record->employee, $subject, $body);
        $record->update(['penalty_warning_sent_at' => now()]);
    }

    private function requiredRawRow(array $row): array
    {
        return collect(self::REQUIRED_COLUMNS)
            ->mapWithKeys(fn (string $column) => [$column => $row[$column] ?? null])
            ->all();
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['csv_file' => 'Unable to read uploaded CSV file.']);
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw ValidationException::withMessages(['csv_file' => 'CSV file has no header row.']);
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $rows = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? null;
            }
            $rows[$line] = $row;
        }

        fclose($handle);

        return compact('headers', 'rows');
    }

    private function validateHeaders(array $headers): void
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        if ($missing) {
            throw ValidationException::withMessages([
                'csv_file' => 'Missing required Time Doctor columns: ' . implode(', ', $missing),
            ]);
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function parseDate(?string $value): Carbon
    {
        $value = trim((string) $value);
        foreach (['n/j/y', 'm/d/y', 'Y-m-d', 'n/j/Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // Try next format.
            }
        }

        throw ValidationException::withMessages(['date' => "Invalid date value: {$value}"]);
    }

    private function parseTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['h:i A', 'g:i A', 'H:i', 'H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Throwable) {
                // Try next format.
            }
        }

        return null;
    }

    private function parseDurationToMinutes(?string $value): int
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || $value === '0' || $value === '0m') {
            return 0;
        }

        if (str_starts_with($value, '<')) {
            return 1;
        }

        $minutes = 0;
        if (preg_match('/(\d+(?:\.\d+)?)\s*h/', $value, $m)) {
            $minutes += (int) round(((float) $m[1]) * 60);
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*m/', $value, $m)) {
            $minutes += (int) round((float) $m[1]);
        }

        if ($minutes === 0 && is_numeric($value)) {
            return (int) round((float) $value);
        }

        return $minutes;
    }

    private function parsePercent(?string $value): float
    {
        $value = str_replace('%', '', trim((string) $value));

        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }

    private function attendanceStatus(int $trackedMinutes, ?string $startTime): string
    {
        if ($trackedMinutes <= 0) {
            return 'absent';
        }

        if ($trackedMinutes < $this->requiredDailyMinutes()) {
            return 'underworked';
        }

        if ($startTime && $startTime > Attendance::LATE_THRESHOLD) {
            return 'late';
        }

        return 'present';
    }

    private function requiredDailyMinutes(): int
    {
        return max(1, (int) $this->settings->get('work_hours_per_day', 8)) * 60;
    }
}
