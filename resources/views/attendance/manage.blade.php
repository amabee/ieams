@extends('layouts.app')
@section('title','Manage Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Attendance Management</h4>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('attendance.manage') }}" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Branch</label>
                <select name="branch" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch')==$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Employee</label>
                <input type="text" name="employee" class="form-control form-control-sm" placeholder="Search..." value="{{ request('employee') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

@if($pendingCorrections->count())
<div class="alert alert-warning">
    <strong><i class="bi bi-exclamation-triangle me-1"></i> {{ $pendingCorrections->count() }} pending correction(s)</strong>
    <a href="#corrections" class="alert-link">Review below</a>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold">Attendance Records</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Shift</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td>{{ $rec->date->format('M d, Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $rec->employee->full_name }}</div>
                            <small class="text-muted">{{ $rec->employee->employee_no }}</small>
                        </td>
                        <td>{{ $rec->branch->name }}</td>
                        <td><small>{{ $rec->employee->shift->name ?? '—' }}</small></td>
                        <td>{{ $rec->time_in ?? '—' }}</td>
                        <td>{{ $rec->time_out ?? '—' }}</td>
                        <td>{{ $rec->hours_worked ?? '—' }}</td>
                        <td><span class="badge badge-{{ $rec->status }}">{{ ucfirst(str_replace('_',' ',$rec->status)) }}</span></td>
                        <td><small>{{ Str::limit($rec->notes, 30) }}</small></td>
                        <td>
                            @can('edit-attendance')
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $rec->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No attendance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $records->links() }}
    </div>
</div>

@if($pendingCorrections->count())
<div class="card shadow-sm border-0 mt-4" id="corrections">
    <div class="card-header bg-warning bg-opacity-10 py-3">
        <h6 class="mb-0 fw-semibold text-warning"><i class="bi bi-exclamation-triangle me-1"></i> Pending Corrections</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Original</th>
                        <th>Corrected</th>
                        <th>Reason</th>
                        <th>Requested By</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingCorrections as $corr)
                    <tr>
                        <td>{{ $corr->attendanceRecord->date->format('M d, Y') }}</td>
                        <td>{{ $corr->attendanceRecord->employee->full_name }}</td>
                        <td>
                            <small>In: {{ $corr->attendanceRecord->time_in ?? '—' }}</small><br>
                            <small>Out: {{ $corr->attendanceRecord->time_out ?? '—' }}</small>
                        </td>
                        <td>
                            <small>In: {{ $corr->corrected_time_in ?? '—' }}</small><br>
                            <small>Out: {{ $corr->corrected_time_out ?? '—' }}</small>
                        </td>
                        <td><small>{{ $corr->reason }}</small></td>
                        <td><small>{{ $corr->corrector->name ?? '—' }}</small></td>
                        <td>
                            @can('approve-attendance-corrections')
                            <form method="POST" action="{{ route('attendance.corrections.approve', $corr) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this correction?')">
                                    <i class="bi bi-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('attendance.corrections.deny', $corr) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deny this correction?')">
                                    <i class="bi bi-x"></i> Deny
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@foreach($records as $rec)
<div class="modal fade" id="editModal{{ $rec->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('attendance.update', $rec) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Attendance - {{ $rec->employee->full_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Time In</label>
                        <input type="time" name="time_in" class="form-control" value="{{ $rec->time_in }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time Out</label>
                        <input type="time" name="time_out" class="form-control" value="{{ $rec->time_out }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="present" {{ $rec->status=='present'?'selected':'' }}>Present</option>
                            <option value="late" {{ $rec->status=='late'?'selected':'' }}>Late</option>
                            <option value="absent" {{ $rec->status=='absent'?'selected':'' }}>Absent</option>
                            <option value="on_leave" {{ $rec->status=='on_leave'?'selected':'' }}>On Leave</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $rec->notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
