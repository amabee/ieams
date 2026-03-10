@extends('layouts.app')
@section('title', 'Audit Logs')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-1"></i> Audit Logs</h5>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <select id="filterEvent" class="form-select form-select-sm" style="width:140px">
                <option value="">All Events</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
            </select>
            <input type="text" id="filterCauser" class="form-control form-control-sm" placeholder="User..." style="width:140px">
            <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:145px">
            <input type="date" id="filterTo" class="form-control form-control-sm" style="width:145px">
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table id="auditTable" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Event</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Details</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Single shared modal --}}
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journal-text me-1"></i> Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Event:</dt>
                    <dd class="col-sm-9"><span id="mdEvent" class="badge bg-secondary"></span></dd>

                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9" id="mdDescription"></dd>

                    <dt class="col-sm-3">User:</dt>
                    <dd class="col-sm-9" id="mdUser"></dd>

                    <dt class="col-sm-3">Timestamp:</dt>
                    <dd class="col-sm-9" id="mdTimestamp"></dd>

                    <dt class="col-sm-3">Subject Type:</dt>
                    <dd class="col-sm-9" id="mdSubjectType"></dd>

                    <dt class="col-sm-3">Subject ID:</dt>
                    <dd class="col-sm-9" id="mdSubjectId"></dd>
                </dl>
                <div id="mdPropsWrap" class="mt-3" style="display:none">
                    <h6 class="fw-bold">Properties:</h6>
                    <pre id="mdProperties" class="bg-light p-3 rounded small" style="max-height:300px;overflow-y:auto"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const table = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("audit-logs.data") }}',
            data: function (d) {
                d.filter_event  = $('#filterEvent').val();
                d.filter_causer = $('#filterCauser').val();
                d.filter_from   = $('#filterFrom').val();
                d.filter_to     = $('#filterTo').val();
            }
        },
        columns: [
            { data: 'timestamp',   orderable: false },
            { data: 'event',       orderable: false },
            { data: 'description', orderable: false },
            { data: 'user',        orderable: false, searchable: false },
            { data: 'subject',     orderable: false, searchable: false },
            { data: 'details',     orderable: false, searchable: false },
        ],
        pageLength: 25,
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ logs',
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next:     '<i class="bi bi-chevron-right"></i>',
            }
        },
        dom: "<'dt-top-bar px-3 pt-3 pb-2'lf>t<'d-flex align-items-center justify-content-between flex-wrap px-3 pb-2'ip>",
        initComplete: function () {
            $('#auditTable_wrapper .dataTables_length select').addClass('form-select form-select-sm');
            $('#auditTable_wrapper .dataTables_filter input').addClass('form-control form-control-sm');
        }
    });

    // Reload on filter change
    $('#filterEvent, #filterFrom, #filterTo').on('change', function () { table.draw(); });

    let causerTimer;
    $('#filterCauser').on('input', function () {
        clearTimeout(causerTimer);
        causerTimer = setTimeout(() => table.draw(), 400);
    });

    // Populate shared modal from data attributes
    $('#logModal').on('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        $('#mdEvent').text(btn.dataset.event);
        $('#mdDescription').text(btn.dataset.description);
        $('#mdUser').text(btn.dataset.user);
        $('#mdTimestamp').text(btn.dataset.timestamp);
        $('#mdSubjectType').text(btn.dataset.subjectType);
        $('#mdSubjectId').text(btn.dataset.subjectId);

        const props = btn.dataset.properties;
        if (props) {
            $('#mdProperties').text(props);
            $('#mdPropsWrap').show();
        } else {
            $('#mdPropsWrap').hide();
        }
    });
});
</script>
@endpush
