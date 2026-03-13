@extends('layouts.app')
@section('title', 'Positions')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase me-1"></i> Positions</h5>
        @can('create positions')
        <a href="{{ route('positions.create') }}" class="btn btn-primary btn-md">
            <i class="bi bi-plus-lg me-1"></i> Add Position
        </a>
        @endcan
    </div>
    <div class="card-datatable table-responsive">
        <table id="positionTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Employees</th>
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
    $('#positionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('positions.data') }}',
        columns: [
            { data: 'title' },
            { data: 'department' },
            { data: 'employees_count', orderable: false },
            { data: 'status',          orderable: false },
            { data: 'actions',         orderable: false, searchable: false },
        ],
        pageLength: 10,
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ positions',
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next:     '<i class="bi bi-chevron-right"></i>',
            }
        },
        dom: "<'dt-top-bar'lf>t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        initComplete: function () {
            $('#positionTable_wrapper .dataTables_length select').addClass('form-select form-select-sm');
            $('#positionTable_wrapper .dataTables_filter input').addClass('form-control form-control-sm');
        }
    });

    $(document).on('click', '.swal-delete-btn', function () {
        var form = $(this).closest('.swal-delete-form');
        var name = $(this).data('name') || 'this item';
        Swal.fire({
            title: 'Delete position?',
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
