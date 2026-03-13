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
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : null;
        $branches = Branch::where('is_active', true)->get();

        $pendingCorrections = AttendanceCorrection::with('attendanceRecord.employee')
            ->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->whereHas('attendanceRecord', fn ($r) => $r->where('branch_id', $branchId)))
            ->get();

        $summary = AttendanceRecord::whereDate('date', now()->toDateString())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("
                SUM(CASE WHEN status = 'present'  THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late'     THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent'   THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as on_leave
            ")->first();

        return view('attendance.manage', compact('branches', 'branchId', 'pendingCorrections', 'summary'));
    }

    public function data(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;

        $query = AttendanceRecord::with(['employee.position', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->date_from, fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('date', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status));

        // Global search
        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', fn ($e) =>
                    $e->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_no', 'like', "%{$search}%")
                )->orWhereHas('branch', fn ($b) =>
                    $b->where('name', 'like', "%{$search}%")
                );
            });
        }

        $total    = AttendanceRecord::count();
        $filtered = $query->count();

        // Ordering
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('date', $orderDir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $records = $query->skip($start)->take($length)->get();

        $statusMap = [
            'present'  => ['bg-label-success', 'Present'],
            'late'     => ['bg-label-warning', 'Late'],
            'absent'   => ['bg-label-danger',  'Absent'],
            'on_leave' => ['bg-label-info',    'On Leave'],
            'half_day' => ['bg-label-secondary','Half Day'],
        ];

        $data = $records->map(function ($rec) use ($statusMap) {
            $initial = strtoupper(substr($rec->employee->first_name ?? 'U', 0, 1));
            [$badge, $label] = $statusMap[$rec->status] ?? ['bg-label-secondary', ucfirst($rec->status)];

            $timeIn  = $rec->time_in  ? \Carbon\Carbon::parse($rec->time_in)->format('h:i A')  : '—';
            $timeOut = $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('h:i A') : '—';
            $hours   = $rec->hours_worked ? number_format($rec->hours_worked, 1).'h' : '—';

            $editBtn = '<button class="btn btn-sm btn-icon btn-outline-primary rounded-pill edit-btn"
                data-id="'.$rec->id.'"
                data-name="'.e($rec->employee->full_name ?? '').'"
                data-date="'.$rec->date->format('F d, Y').'"
                data-time-in="'.($rec->time_in ?? '').'"
                data-time-out="'.($rec->time_out ?? '').'"
                data-status="'.($rec->status ?? '').'"
                data-notes="'.e($rec->notes ?? '').'"
                data-bs-toggle="modal" data-bs-target="#editModal"
                title="Edit"><i class="bx bx-edit"></i></button>';

            return [
                'employee' => '<div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">'.$initial.'</span></div>
                    <div><div class="fw-semibold lh-1">'.e($rec->employee->full_name ?? '—').'</div>
                    <small class="text-muted">'.e($rec->employee->employee_no ?? '').'</small></div></div>',
                'branch'   => e($rec->branch->name ?? '—'),
                'date'     => '<span class="fw-semibold">'.$rec->date->format('M d').'</span><small class="text-muted d-block">'.$rec->date->format('Y').'</small>',
                'time_in'  => $timeIn,
                'time_out' => $timeOut,
                'hours'    => $hours,
                'status'   => '<span class="badge '.$badge.'">'.$label.'</span>',
                'actions'  => $editBtn,
            ];
        });

        $summary = AttendanceRecord::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->date_from, fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('date', '<=', $request->date_to))
            ->selectRaw("
                SUM(CASE WHEN status = 'present'  THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late'     THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent'   THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as on_leave
            ")->first();

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
            'summary'         => $summary,
        ]);
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
        $base = AttendanceCorrection::with(['attendanceRecord.employee', 'corrector', 'approver'])->latest();

        $pending  = (clone $base)->where('status', 'pending')->paginate(20, ['*'], 'pendingPage');
        $approved = (clone $base)->where('status', 'approved')->paginate(20, ['*'], 'approvedPage');
        $denied   = (clone $base)->where('status', 'denied')->paginate(20, ['*'], 'deniedPage');

        return view('attendance.corrections', compact('pending', 'approved', 'denied'));
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
