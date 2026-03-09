@extends('layouts.app')
@section('title','Leave Requests')
@section('breadcrumb')<li class="breadcrumb-item active">Leave Management</li>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Leave Requests</h4>
    @can('submit-leave')
    <a href="{{ route('leaves.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Request Leave
    </a>
    @endcan
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pending" type="button">
            Pending <span class="badge bg-warning text-dark ms-1">{{ $pending->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#approved" type="button">
            Approved <span class="badge bg-success ms-1">{{ $approved->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#denied" type="button">
            Denied <span class="badge bg-danger ms-1">{{ $denied->count() }}</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Pending Tab -->
    <div class="tab-pane fade show active" id="pending">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Submitted</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $leave)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $leave->employee->full_name }}</div>
                                    <small class="text-muted">{{ $leave->employee->employee_no }}</small>
                                </td>
                                <td>{{ $leave->employee->branch->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($leave->leave_type) }}</span></td>
                                <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                <td>{{ $leave->days }}</td>
                                <td><small>{{ Str::limit($leave->reason, 40) }}</small></td>
                                <td><small>{{ $leave->created_at->diffForHumans() }}</small></td>
                                <td>
                                    @can('approve-leave')
                                    <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this leave request?')">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('leaves.deny', $leave) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deny this leave request?')">
                                            <i class="bi bi-x-circle"></i> Deny
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No pending leave requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Tab -->
    <div class="tab-pane fade" id="approved">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Approved By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approved as $leave)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $leave->employee->full_name }}</div>
                                    <small class="text-muted">{{ $leave->employee->employee_no }}</small>
                                </td>
                                <td>{{ $leave->employee->branch->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($leave->leave_type) }}</span></td>
                                <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                <td>{{ $leave->days }}</td>
                                <td><small>{{ Str::limit($leave->reason, 40) }}</small></td>
                                <td>{{ $leave->reviewer->name ?? '—' }}</td>
                                <td><small>{{ $leave->updated_at->format('M d, Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No approved leave requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Denied Tab -->
    <div class="tab-pane fade" id="denied">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Denied By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($denied as $leave)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $leave->employee->full_name }}</div>
                                    <small class="text-muted">{{ $leave->employee->employee_no }}</small>
                                </td>
                                <td>{{ $leave->employee->branch->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($leave->leave_type) }}</span></td>
                                <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                <td>{{ $leave->days }}</td>
                                <td><small>{{ Str::limit($leave->reason, 40) }}</small></td>
                                <td>{{ $leave->reviewer->name ?? '—' }}</td>
                                <td><small>{{ $leave->updated_at->format('M d, Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No denied leave requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($leaveBalance)
<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-light py-3">
        <h6 class="mb-0 fw-semibold">My Leave Balance</h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="border-end pe-3">
                    <div class="text-muted small">Sick Leave</div>
                    <h4 class="mb-0 text-primary">{{ $leaveBalance->sick_leave_balance ?? 0 }}</h4>
                    <small class="text-muted">days</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border-end pe-3">
                    <div class="text-muted small">Vacation Leave</div>
                    <h4 class="mb-0 text-success">{{ $leaveBalance->vacation_leave_balance ?? 0 }}</h4>
                    <small class="text-muted">days</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border-end pe-3">
                    <div class="text-muted small">Emergency Leave</div>
                    <h4 class="mb-0 text-warning">{{ $leaveBalance->emergency_leave_balance ?? 0 }}</h4>
                    <small class="text-muted">days</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Other Leave</div>
                <h4 class="mb-0 text-info">{{ $leaveBalance->other_leave_balance ?? 0 }}</h4>
                <small class="text-muted">days</small>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
