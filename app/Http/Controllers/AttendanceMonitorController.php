<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceMonitorController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $date     = $request->date ?? today()->format('Y-m-d');
        $branches = Branch::where('is_active', true)->get();

        $summary = AttendanceRecord::where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("
                SUM(status = 'present') as present,
                SUM(status = 'late')    as late,
                SUM(status = 'absent')  as absent,
                SUM(status = 'on_leave') as on_leave
            ")->first();

        $records = AttendanceRecord::with('employee', 'branch')
            ->where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('time_in')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.monitor', compact('records', 'summary', 'branches', 'branchId', 'date'));
    }

    public function data(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $date     = $request->date ?? today()->format('Y-m-d');

        $records = AttendanceRecord::with('employee:id,first_name,last_name,position', 'branch:id,name')
            ->where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(fn ($r) => [
                'employee'     => $r->employee->full_name ?? '—',
                'position'     => $r->employee->position ?? '—',
                'branch'       => $r->branch->name ?? '—',
                'time_in'      => $r->time_in ?? '—',
                'time_out'     => $r->time_out ?? '—',
                'hours_worked' => $r->hours_worked ?? '—',
                'status'       => $r->status,
            ]);

        return response()->json($records);
    }
}