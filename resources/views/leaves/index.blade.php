@extends('layouts.app')
@section('title', 'Leave Requests')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
    <i class="bx bx-check-circle me-2 fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Leave Balance Cards (employees only) --}}
@if($leaveBalance)
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-danger">
                        <i class="bx bx-plus-medical bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">{{ $leaveBalance->sick_leave_balance ?? 0 }}</h3>
                <small class="text-muted">Sick Leave</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-success">
                        <i class="bx bx-sun bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">{{ $leaveBalance->vacation_leave_balance ?? 0 }}</h3>
                <small class="text-muted">Vacation Leave</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                        <i class="bx bx-error bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">{{ $leaveBalance->emergency_leave_balance ?? 0 }}</h3>
                <small class="text-muted">Emergency Leave</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-info">
                        <i class="bx bx-calendar bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">{{ $leaveBalance->other_leave_balance ?? 0 }}</h3>
                <small class="text-muted">Other Leave</small>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Main Card --}}
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                    Pending
                    @if($pending->count())
                    <span class="badge bg-warning text-dark ms-1">{{ $pending->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                    Approved
                    @if($approved->count())
                    <span class="badge bg-label-success ms-1">{{ $approved->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabDenied" type="button">
                    Denied
                    @if($denied->count())
                    <span class="badge bg-label-danger ms-1">{{ $denied->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        @can('submit-leave')
        <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm ms-3 flex-shrink-0">
            <i class="bx bx-plus me-1"></i> Request Leave
        </a>
        @endcan
    </div>
    <div class="tab-content">

        {{-- Pending Tab --}}
        <div class="tab-pane fade show active" id="tabPending">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Submitted</th>
                            @can('approve-leave')<th class="text-center pe-4">Actions</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $leave)
                        @php $initial = strtoupper(substr($leave->employee->first_name ?? 'U', 0, 1)); @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-warning">{{ $initial }}</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $leave->employee->full_name }}</div>
                                        <small class="text-muted">{{ $leave->employee->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $leave->employee->branch->name ?? '' }}</td>
                            <td>
                                @php
                                    $typeBadge = ['sick'=>'bg-label-danger','vacation'=>'bg-label-success','emergency'=>'bg-label-warning','other'=>'bg-label-info'];
                                    $typeLabel = ['sick'=>'Sick','vacation'=>'Vacation','emergency'=>'Emergency','other'=>'Other'];
                                @endphp
                                <span class="badge {{ $typeBadge[$leave->leave_type] ?? 'bg-label-secondary' }}">
                                    {{ $typeLabel[$leave->leave_type] ?? ucfirst($leave->leave_type) }}
                                </span>
                            </td>
                            <td>{{ $leave->start_date->format('M d, Y') }}</td>
                            <td>{{ $leave->end_date->format('M d, Y') }}</td>
                            <td><span class="badge bg-label-secondary">{{ $leave->total_days ?? $leave->days ?? '' }} days</span></td>
                            <td><small class="text-muted">{{ Str::limit($leave->reason, 40) }}</small></td>
                            <td><small class="text-muted">{{ $leave->created_at->diffForHumans() }}</small></td>
                            @can('approve-leave')
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <form method="POST" action="{{ route('leaves.approve', $leave) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3"
                                            onclick="return confirm('Approve this leave request?')">
                                            <i class="bx bx-check me-1"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('leaves.deny', $leave) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            onclick="return confirm('Deny this leave request?')">
                                            <i class="bx bx-x me-1"></i> Deny
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bx bx-calendar-x d-block mb-2" style="font-size:2rem"></i>
                                No pending leave requests.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Approved Tab --}}
        <div class="tab-pane fade" id="tabApproved">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
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
                        @php $initial = strtoupper(substr($leave->employee->first_name ?? 'U', 0, 1)); @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-success">{{ $initial }}</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $leave->employee->full_name }}</div>
                                        <small class="text-muted">{{ $leave->employee->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $leave->employee->branch->name ?? '' }}</td>
                            <td>
                                <span class="badge {{ $typeBadge[$leave->leave_type] ?? 'bg-label-secondary' }}">
                                    {{ $typeLabel[$leave->leave_type] ?? ucfirst($leave->leave_type) }}
                                </span>
                            </td>
                            <td>{{ $leave->start_date->format('M d, Y') }}</td>
                            <td>{{ $leave->end_date->format('M d, Y') }}</td>
                            <td><span class="badge bg-label-secondary">{{ $leave->total_days ?? $leave->days ?? '' }} days</span></td>
                            <td><small class="text-muted">{{ Str::limit($leave->reason, 40) }}</small></td>
                            <td>{{ $leave->reviewer->name ?? '' }}</td>
                            <td><small class="text-muted">{{ $leave->updated_at->format('M d, Y') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bx bx-calendar-check d-block mb-2" style="font-size:2rem"></i>
                                No approved leave requests.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Denied Tab --}}
        <div class="tab-pane fade" id="tabDenied">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
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
                        @php $initial = strtoupper(substr($leave->employee->first_name ?? 'U', 0, 1)); @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-danger">{{ $initial }}</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $leave->employee->full_name }}</div>
                                        <small class="text-muted">{{ $leave->employee->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $leave->employee->branch->name ?? '' }}</td>
                            <td>
                                <span class="badge {{ $typeBadge[$leave->leave_type] ?? 'bg-label-secondary' }}">
                                    {{ $typeLabel[$leave->leave_type] ?? ucfirst($leave->leave_type) }}
                                </span>
                            </td>
                            <td>{{ $leave->start_date->format('M d, Y') }}</td>
                            <td>{{ $leave->end_date->format('M d, Y') }}</td>
                            <td><span class="badge bg-label-secondary">{{ $leave->total_days ?? $leave->days ?? '' }} days</span></td>
                            <td><small class="text-muted">{{ Str::limit($leave->reason, 40) }}</small></td>
                            <td>{{ $leave->reviewer->name ?? '' }}</td>
                            <td><small class="text-muted">{{ $leave->updated_at->format('M d, Y') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bx bx-calendar-x d-block mb-2" style="font-size:2rem"></i>
                                No denied leave requests.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@endsection
