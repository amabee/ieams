<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;

        $todayRecord = null;
        if ($employee) {
            $todayRecord = AttendanceRecord::where('employee_id', $employee->id)
                ->where('date', today())
                ->first();
        }

        $recentRecords = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->orderByDesc('date')->limit(14)->get()
            : collect();

        return view('attendance.record', compact('employee', 'todayRecord', 'recentRecords'));
    }

    public function timeIn(Request $request, AttendanceService $attendance)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }

        try {
            $attendance->recordTimeIn($employee, $user->id);
            return back()->with('success', 'Time-in recorded successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function timeOut(Request $request, AttendanceService $attendance)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }

        try {
            $attendance->recordTimeOut($employee, $user->id);
            return back()->with('success', 'Time-out recorded successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}