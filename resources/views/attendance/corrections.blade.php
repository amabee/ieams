@extends('layouts.app')
@section('title', 'Attendance Corrections')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .nav-tabs .nav-link { color: var(--bs-body-color); background: transparent !important; }
    .nav-tabs .nav-link.active { color: var(--bs-primary) !important; background: transparent !important; border-bottom: 2px solid var(--bs-primary) !important; }
    .nav-tabs .nav-link:hover:not(.active) { color: var(--bs-primary) !important; }
    .nav-tabs { border-bottom-color: var(--bs-border-color) !important; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
    <i class="bx bx-check-circle me-2 fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                    Pending
                    @if($pending->total())
                    <span class="badge bg-warning text-dark ms-1">{{ $pending->total() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                    Approved
                    @if($approved->total())
                    <span class="badge bg-label-success ms-1">{{ $approved->total() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabDenied" type="button">
                    Denied
                    @if($denied->total())
                    <span class="badge bg-label-danger ms-1">{{ $denied->total() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <a href="{{ route('attendance.manage') }}" class="btn btn-outline-secondary btn-sm ms-3 flex-shrink-0">
            <i class="bx bx-arrow-back me-1"></i> Manage Attendance
        </a>
    </div>

    <div class="tab-content">

        {{-- Pending Tab --}}
        <div class="tab-pane fade show active" id="tabPending">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Date</th>
                            <th>Original</th>
                            <th>Corrected To</th>
                            <th>Reason</th>
                            <th>Requested By</th>
                            <th>Submitted</th>
                            @can('approve attendance correction')
                            <th class="text-center pe-4">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $corr)
                        @php $emp = $corr->attendanceRecord->employee ?? null; @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-warning">
                                            {{ strtoupper(substr($emp->first_name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $emp->full_name ?? '—' }}</div>
                                        <small class="text-muted">{{ $emp->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $corr->attendanceRecord->date->format('M d, Y') }}</td>
                            <td>
                                <small class="d-block text-muted">In: {{ $corr->old_time_in ? \Carbon\Carbon::parse($corr->old_time_in)->format('h:i A') : '—' }}</small>
                                <small class="d-block text-muted">Out: {{ $corr->old_time_out ? \Carbon\Carbon::parse($corr->old_time_out)->format('h:i A') : '—' }}</small>
                                @if($corr->old_status)
                                <small class="d-block text-muted">Status: {{ ucfirst($corr->old_status) }}</small>
                                @endif
                            </td>
                            <td>
                                <small class="d-block text-success fw-semibold">In: {{ $corr->new_time_in ? \Carbon\Carbon::parse($corr->new_time_in)->format('h:i A') : '—' }}</small>
                                <small class="d-block text-success fw-semibold">Out: {{ $corr->new_time_out ? \Carbon\Carbon::parse($corr->new_time_out)->format('h:i A') : '—' }}</small>
                                @if($corr->new_status)
                                <small class="d-block text-success fw-semibold">Status: {{ ucfirst($corr->new_status) }}</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ Str::limit($corr->reason, 60) }}</small></td>
                            <td><small class="text-muted">{{ $corr->corrector->name ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $corr->created_at->diffForHumans() }}</small></td>
                            @can('approve-attendance-corrections')
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <form method="POST" action="{{ route('attendance.corrections.approve', $corr) }}" class="swal-approve-form">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-success swal-approve-btn" title="Approve">
                                            <i class="bx bx-check me-1"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('attendance.corrections.deny', $corr) }}" class="swal-deny-form">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-outline-danger swal-deny-btn" title="Deny">
                                            <i class="bx bx-x me-1"></i> Deny
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bx bx-check-circle bx-lg d-block mb-2 text-success"></i>
                                No pending correction requests.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pending->hasPages())
            <div class="d-flex justify-content-end px-3 py-2">
                {{ $pending->links() }}
            </div>
            @endif
        </div>

        {{-- Approved Tab --}}
        <div class="tab-pane fade" id="tabApproved">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Date</th>
                            <th>Original</th>
                            <th>Corrected To</th>
                            <th>Reason</th>
                            <th>Approved By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approved as $corr)
                        @php $emp = $corr->attendanceRecord->employee ?? null; @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            {{ strtoupper(substr($emp->first_name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $emp->full_name ?? '—' }}</div>
                                        <small class="text-muted">{{ $emp->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $corr->attendanceRecord->date->format('M d, Y') }}</td>
                            <td>
                                <small class="d-block text-muted">In: {{ $corr->old_time_in ? \Carbon\Carbon::parse($corr->old_time_in)->format('h:i A') : '—' }}</small>
                                <small class="d-block text-muted">Out: {{ $corr->old_time_out ? \Carbon\Carbon::parse($corr->old_time_out)->format('h:i A') : '—' }}</small>
                            </td>
                            <td>
                                <small class="d-block text-success fw-semibold">In: {{ $corr->new_time_in ? \Carbon\Carbon::parse($corr->new_time_in)->format('h:i A') : '—' }}</small>
                                <small class="d-block text-success fw-semibold">Out: {{ $corr->new_time_out ? \Carbon\Carbon::parse($corr->new_time_out)->format('h:i A') : '—' }}</small>
                            </td>
                            <td><small class="text-muted">{{ Str::limit($corr->reason, 60) }}</small></td>
                            <td><small class="text-muted">{{ $corr->approver->name ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $corr->updated_at->format('M d, Y') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No approved corrections.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($approved->hasPages())
            <div class="d-flex justify-content-end px-3 py-2">
                {{ $approved->links() }}
            </div>
            @endif
        </div>

        {{-- Denied Tab --}}
        <div class="tab-pane fade" id="tabDenied">
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Date</th>
                            <th>Requested</th>
                            <th>Reason</th>
                            <th>Denied By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($denied as $corr)
                        @php $emp = $corr->attendanceRecord->employee ?? null; @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-danger">
                                            {{ strtoupper(substr($emp->first_name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-1">{{ $emp->full_name ?? '—' }}</div>
                                        <small class="text-muted">{{ $emp->employee_no ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $corr->attendanceRecord->date->format('M d, Y') }}</td>
                            <td>
                                <small class="d-block">In: {{ $corr->new_time_in ? \Carbon\Carbon::parse($corr->new_time_in)->format('h:i A') : '—' }}</small>
                                <small class="d-block">Out: {{ $corr->new_time_out ? \Carbon\Carbon::parse($corr->new_time_out)->format('h:i A') : '—' }}</small>
                            </td>
                            <td><small class="text-muted">{{ Str::limit($corr->reason, 60) }}</small></td>
                            <td><small class="text-muted">{{ $corr->approver->name ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $corr->updated_at->format('M d, Y') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No denied corrections.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($denied->hasPages())
            <div class="d-flex justify-content-end px-3 py-2">
                {{ $denied->links() }}
            </div>
            @endif
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    $(document).on('click', '.swal-approve-btn', function () {
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Approve this correction?',
            text: 'The attendance record will be updated immediately.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve',
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });

    $(document).on('click', '.swal-deny-btn', function () {
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Deny this correction?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, deny',
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush
