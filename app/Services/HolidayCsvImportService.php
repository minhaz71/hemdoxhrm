<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HolidayCsvImportService
{
    private const REQUIRED_COLUMNS = [
        'title',
        'reason',
        'holiday_year',
        'start_date',
        'end_date',
        'type',
        'branch_id',
        'department_id',
        'employee_emails',
        'notify_before_days',
        'status',
    ];

    public function __construct(
        private readonly HolidayService $holidays,
        private readonly SecurityAuditService $audit,
    ) {}

    public function formData(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::active()->with('branch:id,name')->orderBy('name')->get(['id', 'branch_id', 'name', 'code']),
            'employees' => Employee::active()
                ->with('user:id,email')
                ->orderBy('first_name')
                ->limit(200)
                ->get(['id', 'user_id', 'first_name', 'last_name', 'employee_code', 'organization_employee_code']),
        ];
    }

    public function import(UploadedFile $file, User $actor): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            $report = $this->emptyReport('Unable to read uploaded CSV file.');
            $this->recordAudit($actor, $file->getClientOriginalName(), $report);

            return $report;
        }

        $headers = $this->readHeaders($handle);
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        if ($missing !== []) {
            fclose($handle);

            $report = $this->emptyReport('Missing required column(s): ' . implode(', ', $missing));
            $this->recordAudit($actor, $file->getClientOriginalName(), $report);

            return $report;
        }

        $created = 0;
        $failedRows = [];
        $createdRows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $data = $this->mapRow($headers, $row);
            $validation = $this->validateRow($data);

            if ($validation['errors'] !== []) {
                $failedRows[] = $this->failedRow($rowNumber, $data, $validation['errors']);
                continue;
            }

            try {
                $holiday = $this->holidays->create($validation['data'], $actor);
                $created++;
                $createdRows[] = [
                    'row' => $rowNumber,
                    'title' => $holiday->title,
                    'type' => $holiday->type,
                    'dates' => $holiday->start_date->toDateString() . ' - ' . $holiday->end_date->toDateString(),
                ];
            } catch (\Throwable $e) {
                $failedRows[] = $this->failedRow($rowNumber, $data, [$this->friendlyError($e)]);
            }
        }

        fclose($handle);

        $report = [
            'created' => $created,
            'failed' => count($failedRows),
            'failed_rows' => $failedRows,
            'created_rows' => $createdRows,
            'file_name' => $file->getClientOriginalName(),
            'imported_at' => now()->format('Y-m-d H:i:s'),
        ];

        $this->recordAudit($actor, $file->getClientOriginalName(), $report);

        return $report;
    }

    public function sampleDownloadPath(): string
    {
        $path = storage_path('app/samples/holiday_import_sample.csv');
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'w');
        fputcsv($handle, self::REQUIRED_COLUMNS);

        $year = (int) now()->format('Y');
        $start = Carbon::create($year, 12, 16)->toDateString();
        $end = Carbon::create($year, 12, 16)->toDateString();

        fputcsv($handle, [
            'Company Holiday',
            'Global company holiday',
            $year,
            $start,
            $end,
            'global',
            '',
            '',
            '',
            1,
            'active',
        ]);

        Branch::active()->orderBy('name')->limit(10)->get(['id', 'name'])->each(function (Branch $branch) use ($handle, $year) {
            fputcsv($handle, [
                "Branch Holiday - {$branch->name}",
                "Use branch_id {$branch->id}",
                $year,
                "{$year}-12-17",
                "{$year}-12-17",
                'branch',
                $branch->id,
                '',
                '',
                1,
                'active',
            ]);
        });

        Department::active()->orderBy('name')->limit(10)->get(['id', 'name'])->each(function (Department $department) use ($handle, $year) {
            fputcsv($handle, [
                "Department Holiday - {$department->name}",
                "Use department_id {$department->id}",
                $year,
                "{$year}-12-18",
                "{$year}-12-18",
                'department',
                '',
                $department->id,
                '',
                1,
                'active',
            ]);
        });

        $emails = Employee::active()
            ->whereHas('user')
            ->with('user:id,email')
            ->limit(8)
            ->get()
            ->pluck('user.email')
            ->filter()
            ->implode(', ');

        if ($emails !== '') {
            fputcsv($handle, [
                'Selected Employee Holiday',
                'Comma-separated employee emails',
                $year,
                "{$year}-12-19",
                "{$year}-12-19",
                'employee_specific',
                '',
                '',
                $emails,
                1,
                'active',
            ]);
        }

        fclose($handle);

        return $path;
    }

    private function readHeaders($handle): array
    {
        $headerRow = fgetcsv($handle) ?: [];

        return array_map(function ($header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);

            return strtolower(trim($header));
        }, $headerRow);
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            $mapped[$header] = trim((string) ($row[$index] ?? ''));
        }

        return $mapped;
    }

    private function validateRow(array $row): array
    {
        $row['status'] = $row['status'] !== '' ? strtolower($row['status']) : 'active';
        $row['type'] = strtolower($row['type'] ?? '');

        $validator = Validator::make($row, [
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'holiday_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'type' => ['required', Rule::in(['global', 'branch', 'department', 'employee_specific'])],
            'branch_id' => ['nullable', 'required_if:type,branch', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'required_if:type,department', 'integer', 'exists:departments,id'],
            'notify_before_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validator->after(function ($validator) use ($row) {
            $this->validateYearMatchesStartDate($validator, $row);
            $this->validateEmployeeEmails($validator, $row);
            $this->validateNoOverlap($validator, $row);
        });

        if ($validator->fails()) {
            return ['data' => [], 'errors' => $validator->errors()->all()];
        }

        $employeeIds = $row['type'] === 'employee_specific'
            ? $this->employeeIdsForEmails($this->splitEmails($row['employee_emails'] ?? ''))
            : [];

        return [
            'errors' => [],
            'data' => [
                'title' => $row['title'],
                'reason' => $row['reason'] ?: null,
                'holiday_year' => (int) $row['holiday_year'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'type' => $row['type'],
                'branch_id' => $row['type'] === 'branch' ? (int) $row['branch_id'] : null,
                'department_id' => $row['type'] === 'department' ? (int) $row['department_id'] : null,
                'employee_ids' => $employeeIds,
                'notify_before_days' => $row['notify_before_days'] !== '' ? (int) $row['notify_before_days'] : null,
                'status' => $row['status'],
            ],
        ];
    }

    private function validateYearMatchesStartDate($validator, array $row): void
    {
        if (($row['holiday_year'] ?? '') === '' || ! $this->validDate($row['start_date'] ?? '')) {
            return;
        }

        $startYear = (int) Carbon::createFromFormat('Y-m-d', $row['start_date'])->format('Y');

        if ((int) $row['holiday_year'] !== $startYear) {
            $validator->errors()->add('holiday_year', "holiday_year must match start_date year ({$startYear}).");
        }
    }

    private function validateEmployeeEmails($validator, array $row): void
    {
        if (($row['type'] ?? '') !== 'employee_specific') {
            return;
        }

        $emails = $this->splitEmails($row['employee_emails'] ?? '');

        if ($emails === []) {
            $validator->errors()->add('employee_emails', 'employee_emails is required for employee_specific holidays.');
            return;
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add('employee_emails', "Invalid employee email: {$email}.");
            }
        }

        if ($validator->errors()->has('employee_emails')) {
            return;
        }

        $found = Employee::whereHas('user', fn ($query) => $query->whereIn('email', $emails))
            ->with('user:id,email')
            ->get()
            ->pluck('user.email')
            ->map(fn ($email) => strtolower($email))
            ->all();

        $missing = array_diff($emails, $found);

        if ($missing !== []) {
            $validator->errors()->add('employee_emails', 'Employee email(s) not found: ' . implode(', ', $missing) . '.');
        }
    }

    private function validateNoOverlap($validator, array $row): void
    {
        if (($row['type'] ?? '') === 'employee_specific') {
            return;
        }

        if (! $this->validDate($row['start_date'] ?? '') || ! $this->validDate($row['end_date'] ?? '') || ($row['type'] ?? '') === '') {
            return;
        }

        $query = Holiday::where('status', 'active')
            ->where('type', $row['type'])
            ->whereDate('start_date', '<=', $row['end_date'])
            ->whereDate('end_date', '>=', $row['start_date']);

        if ($row['type'] === 'branch') {
            if (($row['branch_id'] ?? '') === '') {
                return;
            }
            $query->where('branch_id', $row['branch_id']);
        }

        if ($row['type'] === 'department') {
            if (($row['department_id'] ?? '') === '') {
                return;
            }
            $query->where('department_id', $row['department_id']);
        }

        if ($query->exists()) {
            $validator->errors()->add('start_date', 'An active holiday of the same type and scope already covers this date range.');
        }
    }

    private function employeeIdsForEmails(array $emails): array
    {
        return Employee::whereHas('user', fn ($query) => $query->whereIn('email', $emails))
            ->pluck('id')
            ->all();
    }

    private function splitEmails(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function failedRow(int $rowNumber, array $data, array $errors): array
    {
        return [
            'row' => $rowNumber,
            'title' => $data['title'] ?? '',
            'type' => $data['type'] ?? '',
            'errors' => $errors,
        ];
    }

    private function friendlyError(\Throwable $e): string
    {
        return app()->hasDebugModeEnabled()
            ? $e->getMessage()
            : 'Unable to create holiday from this row.';
    }

    private function emptyReport(string $error): array
    {
        return [
            'created' => 0,
            'failed' => 1,
            'failed_rows' => [
                ['row' => 0, 'title' => '', 'type' => '', 'errors' => [$error]],
            ],
            'created_rows' => [],
            'file_name' => null,
            'imported_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function recordAudit(User $actor, string $fileName, array $report): void
    {
        $this->audit->info('holiday.csv_import', request(), $actor, null, [
            'file_name' => $fileName,
            'created' => $report['created'],
            'failed' => $report['failed'],
            'failed_rows' => collect($report['failed_rows'])->pluck('row')->all(),
        ]);
    }
}
