@extends('layouts.app')
@section('title', 'Employees')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/employees.css') }}">
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people me-1"></i> Employees</h5>
        @can('create employees')
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md">
            <i class="bi bi-plus-lg me-1"></i> Add Employee
        </a>
        @endcan
    </div>

    <div class="card-datatable table-responsive">
        <table id="employeeTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
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
    var branches = {!! json_encode(\App\Models\Branch::where('is_active', true)->orderBy('name')->get(['id','name'])) !!};

    var table = $('#employeeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('employees.data') }}',
            data: function (d) {
                d.branch_id = $('#empBranch').val();
                d.status    = $('#empStatus').val();
            }
        },
        columns: [
            { data: 'employee_no', orderable: true },
            { data: 'name',        orderable: false },
            { data: 'position',    orderable: true },
            { data: 'branch',      orderable: false },
            { data: 'type',        orderable: false },
            { data: 'status',      orderable: false },
            { data: 'actions',     orderable: false, searchable: false },
        ],
        pageLength: 10,
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ employees',
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next:     '<i class="bi bi-chevron-right"></i>',
            }
        },
        dom: "<'dt-top-bar'lf>t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        initComplete: function () {
            var api = this.api();
            var $wrapper = $('#employeeTable_wrapper');

            $wrapper.find('.dataTables_length select').addClass('form-select form-select-sm');
            $wrapper.find('.dataTables_filter input').addClass('form-control form-control-sm');

            // Build branch options
            var branchOpts = '<option value="">All Branches</option>';
            branches.forEach(function (b) {
                branchOpts += '<option value="' + b.id + '">' + b.name + '</option>';
            });

            var $branch = $('<select id="empBranch" class="form-select form-select-sm">'+branchOpts+'</select>');
            var $status = $('<select id="empStatus" class="form-select form-select-sm">'
                + '<option value="">All Status</option>'
                + '<option value="active">Active</option>'
                + '<option value="inactive">Inactive</option>'
                + '</select>');

            $wrapper.find('.dataTables_filter').addClass('d-flex align-items-center gap-2').append($branch, $status);

            $branch.add($status).on('change', function () {
                api.draw();
            });
        }
    });

    $(document).on('click', '.swal-delete-btn', function () {
        var form = $(this).closest('.swal-delete-form');
        var name = $(this).data('name') || 'this employee';
        Swal.fire({
            title: 'Delete employee?',
            text: 'Are you sure you want to delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
        }).then(function (result) {
            if (result.isConfirmed) { form.submit(); }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
