<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        // Employees only see their own leaves
        $query = Leave::with('employee.branch')->latest();
        if ($user->hasRole('employee') && $employee) {
            $query->where('employee_id', $employee->id);
        } elseif ($user->hasRole('branch_manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $user->branch_id));
        }

        $leaves = $query->paginate(15);
        return view('leaves.index', compact('leaves'));
    }

    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile found for your account.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $totalDays = Carbon::parse($validated['start_date'])
            ->diffInWeekdays(Carbon::parse($validated['end_date'])) + 1;

        Leave::create(array_merge($validated, [
            'employee_id' => $employee->id,
            'total_days'  => $totalDays,
            'status'      => 'pending',
        ]));

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted.');
    }

    public function show(Leave $leave)
    {
        $leave->load('employee.branch', 'reviewer');
        return view('leaves.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        return view('leaves.edit', compact('leave'));
    }

    public function update(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Cannot edit a processed leave request.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,other',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $leave->update($validated);
        return redirect()->route('leaves.index')->with('success', 'Leave request updated.');
    }

    public function destroy(Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Cannot delete a processed leave request.');
        }
        $leave->delete();
        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled.');
    }

    public function approve(Leave $leave)
    {
        $leave->update(['status' => 'approved', 'reviewed_by' => auth()->id()]);

        // Mark attendance records as on_leave for approved dates
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                AttendanceRecord::updateOrCreate(
                    ['employee_id' => $leave->employee_id, 'date' => $date->format('Y-m-d')],
                    [
                        'branch_id'      => $leave->employee->branch_id,
                        'status'         => 'on_leave',
                        'is_manual_entry' => true,
                    ]
                );
            }
        }

        // Update leave balance
        LeaveBalance::updateOrCreate(
            ['employee_id' => $leave->employee_id, 'leave_type' => $leave->leave_type, 'year' => now()->year],
            ['total_days' => 15]
        );
        LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type', $leave->leave_type)
            ->where('year', now()->year)
            ->increment('used_days', $leave->total_days);

        return back()->with('success', 'Leave approved.');
    }

    public function deny(Request $request, Leave $leave)
    {
        $leave->update([
            'status'         => 'denied',
            'reviewed_by'    => auth()->id(),
            'review_comment' => $request->comment,
        ]);
        return back()->with('success', 'Leave denied.');
    }
}