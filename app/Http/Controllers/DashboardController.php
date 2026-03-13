<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $today = today();

        $branchId = null;
        if ($user->hasRole('branch_manager')) {
            $branchId = $user->branch_id;
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
        }

        $todayRecords = AttendanceRecord::where('date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("
                SUM(status = 'present') as present,
                SUM(status = 'late')    as late,
                SUM(status = 'absent')  as absent,
                SUM(status = 'on_leave') as on_leave,
                COUNT(*) as total
            ")->first();

        $pendingLeaves = Leave::where('status', 'pending')
            ->when($user->hasRole('branch_manager') && $branchId, fn ($q) =>
                $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId))
            )
            ->when($user->hasRole('employee'), fn ($q) =>
                $q->where('employee_id', $user->employee?->id)
            )
            ->count();
        $totalEmployees = Employee::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $branches = Branch::where('is_active', true)->get();

        $recentAttendance = AttendanceRecord::with(['employee', 'branch'])
            ->where('date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('time_in')
            ->limit(10)
            ->get();

        $recentLeaves = Leave::with('employee')
            ->when($user->hasRole('employee'),
                fn ($q) => $q->where('employee_id', $user->employee?->id),
                fn ($q) => $q->when($branchId, fn ($q2) =>
                    $q2->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId))
                )
            )
            ->latest()
            ->limit(5)
            ->get();

        $weeklyTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $row  = AttendanceRecord::where('date', $date)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->selectRaw("SUM(status='absent') as absent, SUM(status='present') as present, SUM(status='late') as late")
                ->first();
            $weeklyTrend->push([
                'date'    => $date->format('D d'),
                'present' => $row->present ?? 0,
                'late'    => $row->late ?? 0,
                'absent'  => $row->absent ?? 0,
            ]);
        }

        return view('dashboard.index', compact(
            'todayRecords', 'pendingLeaves', 'totalEmployees',
            'branches', 'recentAttendance', 'weeklyTrend', 'branchId', 'recentLeaves'
        ));
    }
}
