@extends('layouts.app')
@section('title', 'Manage Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
    <i class="bx bx-check-circle me-2 fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary Cards --}}
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-success">
                        <i class="bx bx-user-check bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0" id="statPresent">{{ $summary->present ?? 0 }}</h3>
                <small class="text-muted">Present</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                        <i class="bx bx-time bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0" id="statLate">{{ $summary->late ?? 0 }}</h3>
                <small class="text-muted">Late</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-danger">
                        <i class="bx bx-user-x bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0" id="statAbsent">{{ $summary->absent ?? 0 }}</h3>
                <small class="text-muted">Absent</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="avatar avatar-lg mx-auto mb-2">
                    <span class="avatar-initial rounded-circle bg-label-info">
                        <i class="bx bx-calendar-check bx-sm"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0" id="statOnLeave">{{ $summary->on_leave ?? 0 }}</h3>
                <small class="text-muted">On Leave</small>
            </div>
        </div>
    </div>
</div>

{{-- Main Card --}}
<div class="card shadow-sm border-0{{ $pendingCorrections->count() ? ' mb-4' : '' }}">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h5 class="mb-0 fw-bold flex-shrink-0" id="recordsTitle">
                Manage Attendance Records &mdash; {{ now()->format('F d, Y') }}
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <input type="date" id="filterDate" class="form-control form-control-sm" style="width:150px" value="{{ now()->format('Y-m-d') }}">
                @hasanyrole(['admin', 'superadmin', 'hr'])
                <select id="filterBranch" class="form-select form-select-sm" style="width:145px">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @endhasanyrole
                <select id="filterStatus" class="form-select form-select-sm" style="width:120px">
                    <option value="">All Status</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="on_leave">On Leave</option>
                    <option value="half_day">Half Day</option>
                </select>
                <input type="search" id="employeeSearch" class="form-control form-control-sm" style="width:180px" placeholder="Search employee...">
                <button class="btn btn-sm btn-outline-primary flex-shrink-0" id="refreshBtn">
                    <i class="bx bx-refresh me-1"></i> Refresh
                </button>
                @if($pendingCorrections->count())
                <a href="#corrections" class="btn btn-sm btn-warning flex-shrink-0">
                    <i class="bx bx-bell me-1"></i> {{ $pendingCorrections->count() }} Pending
                </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="manageTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                    <th>Status</th>
                    @can('edit attendance')<th class="text-center">Actions</th>@endcan
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Pending Corrections --}}
@if($pendingCorrections->count())
<div class="card shadow-sm border-0 mt-4" id="corrections">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold text-warning">
            <i class="bx bx-error-circle me-1"></i> Pending Corrections
        </h6>
        <span class="badge bg-warning text-dark">{{ $pendingCorrections->count() }}</span>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Date</th>
                    <th>Original</th>
                    <th>Corrected To</th>
                    <th>Reason</th>
                    <th class="text-center pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingCorrections as $corr)
                @php $emp = $corr->attendanceRecord->employee ?? null; @endphp
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle bg-label-warning">
                                    {{ strtoupper(substr($emp->first_name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                            <div class="fw-semibold">{{ $emp->full_name ?? '' }}</div>
                        </div>
                    </td>
                    <td>{{ $corr->attendanceRecord->date->format('M d, Y') }}</td>
                    <td>
                        <small class="d-block text-muted">In: {{ $corr->old_time_in ? \Carbon\Carbon::parse($corr->old_time_in)->format('h:i A') : '' }}</small>
                        <small class="d-block text-muted">Out: {{ $corr->old_time_out ? \Carbon\Carbon::parse($corr->old_time_out)->format('h:i A') : '' }}</small>
                    </td>
                    <td>
                        <small class="d-block text-success fw-semibold">In: {{ $corr->new_time_in ? \Carbon\Carbon::parse($corr->new_time_in)->format('h:i A') : '' }}</small>
                        <small class="d-block text-success fw-semibold">Out: {{ $corr->new_time_out ? \Carbon\Carbon::parse($corr->new_time_out)->format('h:i A') : '' }}</small>
                    </td>
                    <td><small class="text-muted">{{ Str::limit($corr->reason, 50) }}</small></td>
                    <td class="text-center pe-4">
                        @can('approve-attendance-corrections')
                        <div class="d-flex justify-content-center gap-2">
                            <form method="POST" action="{{ route('attendance.corrections.approve', $corr) }}" class="swal-approve-form">
                                @csrf
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 swal-approve-btn">
                                    <i class="bx bx-check me-1"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('attendance.corrections.deny', $corr) }}" class="swal-deny-form">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 swal-deny-btn">
                                    <i class="bx bx-x me-1"></i> Deny
                                </button>
                            </form>
                        </div>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Single Edit Modal --}}
@can('edit attendance')
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Edit Attendance</h5>
                        <small class="text-muted" id="editModalSubtitle"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Time In</label>
                            <input type="time" name="time_in" id="editTimeIn" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Time Out</label>
                            <input type="time" name="time_out" id="editTimeOut" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="on_leave">On Leave</option>
                                <option value="half_day">Half Day</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Reason for Edit <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control" placeholder="Brief reason for this correction..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Notes</label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const canEdit = @json(auth()->user()->can('edit attendance'));

    const columns = [
        { data: 'employee', orderable: false },
        { data: 'branch',   orderable: false },
        { data: 'time_in',  orderable: false },
        { data: 'time_out', orderable: false },
        { data: 'hours',    orderable: false },
        { data: 'status',   orderable: false },
    ];

    if (canEdit) {
        columns.push({ data: 'actions', orderable: false, searchable: false, className: 'text-center' });
    }

    const table = $('#manageTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('attendance.manage.data') }}',
            data: function (d) {
                d.date_from = $('#filterDate').val();
                d.date_to   = $('#filterDate').val();
                d.branch_id = $('#filterBranch').val() || '';
                d.status    = $('#filterStatus').val() || '';
            },
            dataSrc: function (json) {
                if (json.summary) {
                    $('#statPresent').text(json.summary.present  || 0);
                    $('#statLate').text(json.summary.late        || 0);
                    $('#statAbsent').text(json.summary.absent    || 0);
                    $('#statOnLeave').text(json.summary.on_leave || 0);
                }
                const val = $('#filterDate').val();
                if (val) {
                    const d = new Date(val + 'T00:00:00');
                    $('#recordsTitle').text('Manage Attendance Records \u2014 ' + d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' }));
                }
                return json.data;
            },
        },
        columns: columns,
        pageLength: 25,
        order: [[2, 'asc']],
        dom: "t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        language: {
            search: '',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ records',
            infoEmpty: 'No attendance records found.',
            emptyTable: 'No attendance records found.',
            paginate: {
                previous: '<i class="bx bx-chevron-left"></i>',
                next:     '<i class="bx bx-chevron-right"></i>',
            },
        },
    });

    // Filters
    $('#filterDate, #filterBranch, #filterStatus').on('change', function () {
        table.ajax.reload(null, false);
    });

    $('#employeeSearch').on('input', function () {
        table.search(this.value).draw();
    });

    $('#refreshBtn').on('click', function () {
        const $btn = $(this);
        $btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Refreshing...').prop('disabled', true);
        table.ajax.reload(function () {
            $btn.html('<i class="bx bx-refresh me-1"></i> Refresh').prop('disabled', false);
        }, false);
    });

    // Edit modal population
    $(document).on('click', '.edit-btn', function () {
        const btn     = $(this);
        const timeIn  = btn.data('time-in');
        const timeOut = btn.data('time-out');

        $('#editModalSubtitle').text(btn.data('name') + ' \u2014 ' + btn.data('date'));
        $('#editForm').attr('action', '/attendance/manage/' + btn.data('id'));
        $('#editTimeIn').val(timeIn  ? String(timeIn).substring(0, 5)  : '');
        $('#editTimeOut').val(timeOut ? String(timeOut).substring(0, 5) : '');
        $('#editStatus').val(btn.data('status'));
        $('#editNotes').val(btn.data('notes'));
        $('input[name="reason"]', '#editForm').val('');
    });

    $(document).on('click', '.swal-approve-btn', function () {
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Approve this correction?',
            text: 'The attendance record will be updated immediately.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve',
        }).then(function (result) { if (result.isConfirmed) form.submit(); });
    });

    $(document).on('click', '.swal-deny-btn', function () {
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Deny this correction?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, deny',
        }).then(function (result) { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endpush
