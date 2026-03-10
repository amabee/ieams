@extends('layouts.app')
@section('title', 'Attendance Monitor')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

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
                <h3 class="fw-bold mb-0">{{ $summary->present ?? 0 }}</h3>
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
                <h3 class="fw-bold mb-0">{{ $summary->late ?? 0 }}</h3>
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
                <h3 class="fw-bold mb-0">{{ $summary->absent ?? 0 }}</h3>
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
                <h3 class="fw-bold mb-0">{{ $summary->on_leave ?? 0 }}</h3>
                <small class="text-muted">On Leave</small>
            </div>
        </div>
    </div>
</div>

{{-- DataTable Card --}}
<div class="card shadow-sm border-0">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h5 class="mb-0 fw-bold flex-shrink-0">
                Attendance Records &mdash; {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <input type="date" id="dateFilter" class="form-control form-control-sm" style="width:150px" value="{{ $date }}">
                @hasanyrole(['admin', 'superadmin', 'hr'])
                <select id="branchFilter" class="form-select form-select-sm" style="width:150px">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @endhasanyrole
                <select id="statusFilter" class="form-select form-select-sm" style="width:130px">
                    <option value="">All Status</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="on_leave">On Leave</option>
                </select>
                <input type="search" id="employeeSearch" class="form-control form-control-sm" style="width:180px" placeholder="Search employee...">
                <button class="btn btn-sm btn-outline-primary flex-shrink-0" id="refreshBtn">
                    <i class="bx bx-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="attendanceTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                    <th>Status</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const statusBadge = (s) => {
        const map = {
            present:  ['bg-label-success', 'Present'],
            late:     ['bg-label-warning', 'Late'],
            absent:   ['bg-label-danger',  'Absent'],
            on_leave: ['bg-label-info',    'On Leave'],
        };
        const [cls, label] = map[s] || ['bg-label-secondary', s];
        return `<span class="badge ${cls}">${label}</span>`;
    };

    const table = $('#attendanceTable').DataTable({
        processing: true,
        ajax: {
            url: '{{ route('attendance.monitor.data') }}',
            data: function (d) {
                d.date      = $('#dateFilter').val();
                d.branch_id = $('#branchFilter').val() ?? '';
            },
            dataSrc: 'data',
        },
        columns: [
            {
                data: 'employee',
                render: (data, type, row) =>
                    `<div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded-circle bg-label-primary">${row.initial}</span>
                        </div>
                        <div>
                            <div class="fw-semibold">${data}</div>
                            <small class="text-muted">${row.position}</small>
                        </div>
                    </div>`,
            },
            { data: 'branch' },
            { data: 'time_in' },
            { data: 'time_out' },
            {
                data: 'hours_worked',
                render: (data) => data ? `${data}h` : '&mdash;',
            },
            {
                data: 'status',
                render: (data) => statusBadge(data),
            },
        ],
        pageLength: 25,
        order: [[2, 'asc']], // sort by Time In
        language: {
            search: '',
            searchPlaceholder: 'Search employee or branch...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ records',
            infoEmpty: 'No attendance records for this date.',
            emptyTable: 'No attendance records for this date.',
            paginate: {
                previous: '<i class="bx bx-chevron-left"></i>',
                next:     '<i class="bx bx-chevron-right"></i>',
            },
        },
        dom: "t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        initComplete: function () {
            const $wrapper = $('#attendanceTable_wrapper');
            $wrapper.find('.dataTables_length').hide();

            // Status filter
            $('#statusFilter').on('change', function () {
                table.column(5).search(this.value).draw();
            });

            // Employee search
            $('#employeeSearch').on('input', function () {
                table.column(0).search(this.value).draw();
            });

            // Date / Branch reload
            $('#dateFilter, #branchFilter').on('change', function () {
                table.ajax.reload(null, false);
            });
        },
    });

    // Refresh button
    $('#refreshBtn').on('click', function () {
        const $btn = $(this);
        $btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Refreshing...').prop('disabled', true);
        table.ajax.reload(function () {
            $btn.html('<i class="bx bx-refresh me-1"></i> Refresh').prop('disabled', false);
        }, false); // false = keep current page position
    });

    // Auto-refresh every 60 seconds
    setInterval(function () {
        table.ajax.reload(null, false);
    }, 60000);
});
</script>
@endpush
