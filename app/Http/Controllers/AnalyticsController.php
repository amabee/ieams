<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchId = $request->branch_id;
        $period   = $request->period ?? '30';

        return view('analytics.index', compact('branches', 'branchId', 'period'));
    }

    public function data(Request $request)
    {
        $branchId = $request->branch_id;
        $days     = (int) ($request->period ?? 30);
        $start    = today()->subDays($days - 1);

        $base = AttendanceRecord::where('date', '>=', $start)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        // --- Summary ---
        $total   = (clone $base)->count();
        $present = (clone $base)->where('status', 'present')->count();
        $late    = (clone $base)->where('status', 'late')->count();
        $absent  = (clone $base)->where('status', 'absent')->count();
        $onLeave = (clone $base)->where('status', 'on_leave')->count();

        $summary = [
            'total_records'   => $total,
            'attendance_rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
            'late_rate'       => $total > 0 ? round($late / $total * 100, 1) : 0,
            'absence_rate'    => $total > 0 ? round($absent / $total * 100, 1) : 0,
        ];

        // --- Status totals (doughnut) ---
        $status = [
            'present'  => $present,
            'late'     => $late,
            'absent'   => $absent,
            'on_leave' => $onLeave,
        ];

        // --- Daily trend (line chart) ---
        $trendRaw = (clone $base)
            ->selectRaw('date,
                SUM(status="present") as present,
                SUM(status="late")    as late,
                SUM(status="absent")  as absent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trend = [
            'labels'  => $trendRaw->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'))->values(),
            'present' => $trendRaw->pluck('present')->values(),
            'late'    => $trendRaw->pluck('late')->values(),
            'absent'  => $trendRaw->pluck('absent')->values(),
        ];

        // --- By Branch (bar chart) ---
        $branchRaw = (clone $base)
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->with('branch:id,name')
            ->get();

        $byBranch = [
            'labels' => $branchRaw->map(fn ($r) => $r->branch->name ?? 'Unknown')->values(),
            'counts' => $branchRaw->pluck('total')->values(),
        ];

        // --- Punctuality over time (on-time rate % per day) ---
        $punctualityRaw = (clone $base)
            ->selectRaw('date,
                ROUND(SUM(status="present") / COUNT(*) * 100, 1) as rate')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $punctuality = [
            'labels' => $punctualityRaw->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'))->values(),
            'rates'  => $punctualityRaw->pluck('rate')->values(),
        ];

        // --- Top Performers ---
        $topPerformers = AttendanceRecord::where('date', '>=', $start)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('employee_id,
                SUM(status="present") as present_days,
                SUM(status="late")    as late_days,
                COUNT(*)              as total_days')
            ->groupBy('employee_id')
            ->orderByRaw('SUM(status="present") DESC')
            ->limit(10)
            ->with('employee:id,first_name,last_name,employee_no,branch_id', 'employee.branch:id,name')
            ->get()
            ->map(fn ($r) => [
                'name'            => $r->employee->full_name ?? '—',
                'employee_no'     => $r->employee->employee_no ?? '—',
                'branch'          => $r->employee->branch->name ?? '—',
                'present_days'    => $r->present_days,
                'late_days'       => $r->late_days,
                'attendance_rate' => $r->total_days > 0
                    ? round(($r->present_days + $r->late_days) / $r->total_days * 100, 1)
                    : 0,
            ]);

        return response()->json(compact('summary', 'status', 'trend', 'byBranch', 'punctuality', 'topPerformers'));
    }
}
