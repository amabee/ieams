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

        // Daily trend
        $trend = AttendanceRecord::where('date', '>=', $start)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('date, 
                SUM(status="present") as present,
                SUM(status="late")    as late,
                SUM(status="absent")  as absent,
                SUM(status="on_leave") as on_leave')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Branch comparison
        $branchStats = AttendanceRecord::where('date', '>=', $start)
            ->selectRaw('branch_id,
                COUNT(*) as total,
                SUM(status="absent") as absent,
                SUM(status="late")   as late,
                ROUND(SUM(status="absent")/COUNT(*)*100,1) as absence_rate')
            ->groupBy('branch_id')
            ->with('branch:id,name')
            ->get();

        // Top absentees
        $topAbsentees = AttendanceRecord::where('date', '>=', $start)
            ->where('status', 'absent')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('employee_id, COUNT(*) as absent_count')
            ->groupBy('employee_id')
            ->orderByDesc('absent_count')
            ->limit(10)
            ->with('employee:id,first_name,last_name,position')
            ->get()
            ->map(fn ($r) => [
                'name'         => $r->employee->full_name ?? '—',
                'position'     => $r->employee->position ?? '—',
                'absent_count' => $r->absent_count,
            ]);

        // Day-of-week absenteeism heatmap
        $heatmap = AttendanceRecord::where('date', '>=', $start)
            ->where('status', 'absent')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('DAYOFWEEK(date) as dow, COUNT(*) as count')
            ->groupBy('dow')
            ->pluck('count', 'dow');

        return response()->json([
            'trend'       => $trend,
            'branchStats' => $branchStats,
            'topAbsentees' => $topAbsentees,
            'heatmap'     => $heatmap,
        ]);
    }
}