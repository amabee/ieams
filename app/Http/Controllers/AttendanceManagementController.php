<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceManagementController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $branches = Branch::where('is_active', true)->get();

        $records = AttendanceRecord::with('employee', 'branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->date_from, fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('date', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        return view('attendance.manage', compact('records', 'branches', 'branchId', 'employees'));
    }

    public function edit(AttendanceRecord $record)
    {
        $record->load('employee', 'branch');
        return view('attendance.edit', compact('record'));
    }

    public function update(Request $request, AttendanceRecord $record)
    {
        $validated = $request->validate([
            'time_in'  => 'required',
            'time_out' => 'nullable|after:time_in',
            'status'   => 'required|in:present,late,absent,on_leave,half_day',
            'notes'    => 'nullable|string|max:500',
            'reason'   => 'required|string|max:500',
        ]);

        // Log the correction
        AttendanceCorrection::create([
            'attendance_record_id' => $record->id,
            'corrected_by'         => auth()->id(),
            'old_time_in'          => $record->time_in,
            'old_time_out'         => $record->time_out,
            'old_status'           => $record->status,
            'new_time_in'          => $validated['time_in'],
            'new_time_out'         => $validated['time_out'] ?? null,
            'new_status'           => $validated['status'],
            'reason'               => $validated['reason'],
            'status'               => 'approved',
            'approved_by'          => auth()->id(),
        ]);

        $hours = null;
        if ($validated['time_in'] && $validated['time_out']) {
            $hours = app(\App\Services\AttendanceService::class)
                ->calculateHours($validated['time_in'], $validated['time_out']);
        }

        $record->update([
            'time_in'         => $validated['time_in'],
            'time_out'        => $validated['time_out'] ?? null,
            'status'          => $validated['status'],
            'notes'           => $validated['notes'] ?? null,
            'hours_worked'    => $hours,
            'is_manual_entry' => true,
        ]);

        return redirect()->route('attendance.manage')->with('success', 'Attendance record updated.');
    }

    public function corrections(Request $request)
    {
        $corrections = AttendanceCorrection::with(['attendanceRecord.employee', 'corrector'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('attendance.corrections', compact('corrections'));
    }

    public function approve(AttendanceCorrection $correction)
    {
        $correction->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        $correction->attendanceRecord->update([
            'time_in'      => $correction->new_time_in,
            'time_out'     => $correction->new_time_out,
            'status'       => $correction->new_status,
            'is_manual_entry' => true,
        ]);
        return back()->with('success', 'Correction approved.');
    }

    public function deny(AttendanceCorrection $correction)
    {
        $correction->update(['status' => 'denied', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Correction denied.');
    }
}