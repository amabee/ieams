<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Notifications\LeaveRequestedNotification;
use App\Notifications\LeaveStatusChangedNotification;
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

        $all      = $query->get();
        $pending  = $all->where('status', 'pending');
        $approved = $all->where('status', 'approved');
        $denied   = $all->where('status', 'denied');

        $leaveBalance = $employee ? LeaveBalance::where('employee_id', $employee->id)->first() : null;

        return view('leaves.index', compact('pending', 'approved', 'denied', 'leaveBalance'));
    }

    public function create()
    {
        $user         = auth()->user();
        $employee     = $user->employee;
        $leaveBalance = $employee ? LeaveBalance::where('employee_id', $employee->id)->first() : null;

        return view('leaves.create', compact('leaveBalance'));
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

        $leave = Leave::create(array_merge($validated, [
            'employee_id' => $employee->id,
            'total_days'  => $totalDays,
            'status'      => 'pending',
        ]));

        // Notify users who can approve leaves
        $leave->load('employee.branch');
        $approvers = User::permission('approve leaves')
            ->where('id', '!=', auth()->id())
            ->get();
        foreach ($approvers as $approver) {
            if ($approver->hasRole('branch_manager') && $approver->branch_id !== $employee->branch_id) {
                continue;
            }
            $approver->notify(new LeaveRequestedNotification($leave));
        }

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

        $this->notifyEmployee($leave, 'approved');

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

    private function notifyEmployee(Leave $leave, string $status, ?string $comment = null): void
    {
        $employeeUser = User::where('employee_id', $leave->employee_id)->first();
        $employeeUser?->notify(new LeaveStatusChangedNotification($leave, $status, $comment));
    }

    public function deny(Request $request, Leave $leave)
    {
        $leave->update([
            'status'         => 'denied',
            'reviewed_by'    => auth()->id(),
            'review_comment' => $request->comment,
        ]);

        $this->notifyEmployee($leave, 'denied', $request->comment);

        return back()->with('success', 'Leave denied.');
    }
}
