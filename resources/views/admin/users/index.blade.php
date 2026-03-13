@extends('layouts.app')
@section('title', 'User Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people me-1"></i> User Management</h5>
        @can('create users')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-md">
            <i class="bi bi-plus-lg me-1"></i> Add User
        </a>
        @endcan
    </div>

    <div class="card-datatable table-responsive">
        <table id="userTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Activity</th>
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
    var roles    = {!! json_encode(\Spatie\Permission\Models\Role::orderBy('name')->get(['name'])) !!};
    var branches = {!! json_encode(\App\Models\Branch::where('is_active', true)->orderBy('name')->get(['id','name'])) !!};

    var table = $('#userTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.users.data') }}',
            data: function (d) {
                d.role   = $('#userRole').val();
                d.status = $('#userStatus').val();
            }
        },
        columns: [
            { data: 'name',     orderable: true },
            { data: 'email',    orderable: true },
            { data: 'employee', orderable: false },
            { data: 'branch',   orderable: false },
            { data: 'roles',    orderable: false },
            { data: 'status',   orderable: true },
            { data: 'updated',  orderable: true },
            { data: 'actions',  orderable: false, searchable: false },
        ],
        pageLength: 10,
        language: {
            search: '',
            searchPlaceholder: 'Search name or email...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next:     '<i class="bi bi-chevron-right"></i>',
            }
        },
        dom: "<'dt-top-bar'lf>t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        initComplete: function () {
            var api = this.api();
            var $wrapper = $('#userTable_wrapper');

            $wrapper.find('.dataTables_length select').addClass('form-select form-select-sm');
            $wrapper.find('.dataTables_filter input').addClass('form-control form-control-sm');

            var roleOpts = '<option value="">All Roles</option>';
            roles.forEach(function (r) {
                roleOpts += '<option value="' + r.name + '">' + (r.name.charAt(0).toUpperCase() + r.name.slice(1)) + '</option>';
            });

            var $role   = $('<select id="userRole" class="form-select form-select-sm">' + roleOpts + '</select>');
            var $status = $('<select id="userStatus" class="form-select form-select-sm">'
                + '<option value="">All Status</option>'
                + '<option value="1">Active</option>'
                + '<option value="0">Inactive</option>'
                + '</select>');

            $wrapper.find('.dataTables_filter').addClass('d-flex align-items-center gap-2').append($role, $status);

            $role.add($status).on('change', function () { api.draw(); });
        }
    });

    $(document).on('click', '.swal-delete-btn', function () {
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Delete this user?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
