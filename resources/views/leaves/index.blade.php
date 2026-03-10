@extends('layouts.app')
@section('title', 'Leave Requests')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .nav-tabs .nav-link {
        color: var(--bs-body-color);
        background: transparent !important;
    }
    .nav-tabs .nav-link.active {
        color: var(--bs-primary) !important;
        background: transparent !important;
        border-bottom: 2px solid var(--bs-primary) !important;
    }
    .nav-tabs .nav-link:hover:not(.active) {
        color: var(--bs-primary) !important;
    }
    .nav-tabs {
        border-bottom-color: var(--bs-border-color) !important;
    }
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
        @can('create leaves')
        <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm ms-3 flex-shrink-0">
            <i class="bx bx-plus me-1"></i> Request Leave
        </a>
        @endcan
    </div>
    <div class="tab-content">

        {{-- Pending Tab --}}
        @php $canApprove = auth()->user()?->can('approve leaves'); @endphp
        <div class="tab-pane fade show active" id="tabPending">
            <div class="card-datatable table-responsive">
                <table id="tablePending" class="table table-hover align-middle w-100">
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
                            @if($canApprove)<th class="text-center pe-4">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $leave)
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
                            @if($canApprove)
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    <form class="swal-approve-form" method="POST" action="{{ route('leaves.approve', $leave) }}">
                                        @csrf
                                        <button type="button" class="btn btn-icon btn-sm btn-success swal-approve-btn" title="Approve">
                                            <i class="bx bx-check"></i>
                                        </button>
                                    </form>
                                    <form class="swal-deny-form" method="POST" action="{{ route('leaves.deny', $leave) }}">
                                        @csrf
                                        <input type="hidden" name="comment" class="deny-comment-input">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-danger swal-deny-btn" title="Deny">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Approved Tab --}}
        <div class="tab-pane fade" id="tabApproved">
            <div class="card-datatable table-responsive">
                <table id="tableApproved" class="table table-hover align-middle w-100">
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
                        @foreach($approved as $leave)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Denied Tab --}}
        <div class="tab-pane fade" id="tabDenied">
            <div class="card-datatable table-responsive">
                <table id="tableDenied" class="table table-hover align-middle w-100">
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
                            <th>Denial Reason</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($denied as $leave)
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
                            <td>
                                @if($leave->review_comment)
                                <small class="text-danger">{{ $leave->review_comment }}</small>
                                @else
                                <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $leave->updated_at->format('M d, Y') }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const dtConfig = {
        pageLength: 25,
        dom: "<'dt-top-bar'lf>t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            emptyTable: 'No records found.',
            zeroRecords: 'No matching records found.',
            paginate: {
                previous: '<i class="bx bx-chevron-left"></i>',
                next:     '<i class="bx bx-chevron-right"></i>',
            },
        },
        columnDefs: [{ orderable: false, targets: -1 }],
    };

    const tPending  = $('#tablePending').DataTable(dtConfig);
    const tApproved = $('#tableApproved').DataTable(dtConfig);
    const tDenied   = $('#tableDenied').DataTable(dtConfig);

    // DataTables in hidden tabs need a redraw when the tab is shown
    $('[data-bs-target="#tabApproved"]').on('shown.bs.tab', function () {
        tApproved.columns.adjust().draw(false);
    });
    $('[data-bs-target="#tabDenied"]').on('shown.bs.tab', function () {
        tDenied.columns.adjust().draw(false);
    });

    // Approve confirmation
    $(document).on('click', '.swal-approve-btn', function () {
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Approve Leave?',
            text: 'This will approve the employee\'s leave request.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
        }).then(result => {
            if (result.isConfirmed) form[0].submit();
        });
    });

    // Deny confirmation
    $(document).on('click', '.swal-deny-btn', function () {
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Deny Leave?',
            text: 'Please provide a reason for the denial.',
            input: 'textarea',
            inputPlaceholder: 'Enter reason for denial...',
            inputAttributes: { rows: 3 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Deny',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            inputValidator: (value) => {
                if (!value || !value.trim()) return 'Please provide a reason for the denial.';
            },
        }).then(result => {
            if (result.isConfirmed) {
                form.find('.deny-comment-input').val(result.value);
                form[0].submit();
            }
        });
    });
});
</script>
@endpush
