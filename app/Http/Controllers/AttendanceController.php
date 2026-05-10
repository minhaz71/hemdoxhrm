<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    // GET /attendance — today's board
    public function index(Request $request)
    {
        $date    = $request->get('date', today()->toDateString());
        $summary = $this->attendanceService->todaySummary();
        $records = $this->attendanceService->paginateByDate($date);

        // All active employees with today's marked flag (for AJAX mark panel)
        $employees = Employee::active()
            ->with(['attendances' => fn ($q) => $q->today()])
            ->orderBy('first_name')
            ->get();

        return view('attendance.index', compact('summary', 'records', 'employees', 'date'));
    }

    // POST /attendance — AJAX mark attendance
    public function store(MarkAttendanceRequest $request): JsonResponse
    {
        $employee = Employee::findOrFail($request->employee_id);

        $attendance = $this->attendanceService->mark($employee, $request->validated());

        return response()->json([
            'success'    => true,
            'message'    => "{$employee->full_name} marked {$attendance->status}.",
            'status'     => $attendance->status,
            'check_in'   => $attendance->check_in,
            'attendance' => $attendance,
        ]);
    }

    // PATCH /attendance/{attendance}/checkout — AJAX check-out
    public function checkout(Attendance $attendance): JsonResponse
    {
        $attendance = $this->attendanceService->checkOut($attendance);

        return response()->json([
            'success'   => true,
            'message'   => 'Checked out successfully.',
            'check_out' => $attendance->check_out,
            'working_hours' => $attendance->working_hours,
        ]);
    }

    // GET /attendance/{employee}/history
    public function history(Employee $employee)
    {
        $this->authorize('viewHistory', $employee);

        $records = $this->attendanceService->employeeHistory($employee);

        return view('attendance.history', compact('employee', 'records'));
    }

    // GET /attendance/my — employee self-service attendance history
    public function my()
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        return redirect()->route('attendance.history', $employee);
    }

    // GET /attendance/{attendance}/edit
    public function edit(Attendance $attendance)
    {
        $this->authorize('update', $attendance);

        $attendance->load('employee');

        return view('attendance.edit', compact('attendance'));
    }

    // PATCH /attendance/{attendance}
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $this->authorize('update', $attendance);

        $attendance->update($request->validated());

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record updated.');
    }

    // DELETE /attendance/{attendance}
    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete', $attendance);

        $attendance->delete();

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record deleted.');
    }
}
