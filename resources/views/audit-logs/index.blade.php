@extends('layouts.app')
@section('title','Audit Logs')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Audit Logs</h4>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="event" class="form-select form-select-sm">
                    <option value="">All Events</option>
                    <option value="created" {{ request('event')=='created'?'selected':'' }}>Created</option>
                    <option value="updated" {{ request('event')=='updated'?'selected':'' }}>Updated</option>
                    <option value="deleted" {{ request('event')=='deleted'?'selected':'' }}>Deleted</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <input type="text" name="causer" class="form-control form-control-sm" placeholder="User..." value="{{ request('causer') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>Event</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th width="80">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <div>{{ $log->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                        </td>
                        <td>
                            @php
                            $badge = match($log->event) {
                                'created' => 'bg-success',
                                'updated' => 'bg-primary',
                                'deleted' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($log->event) }}</span>
                        </td>
                        <td>{{ $log->description }}</td>
                        <td>
                            @if($log->causer)
                            <div class="fw-semibold">{{ $log->causer->name }}</div>
                            <small class="text-muted">{{ $log->causer->email }}</small>
                            @else
                            <span class="text-muted">System</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ class_basename($log->subject_type ?? '') }}</small>
                            @if($log->subject_id)
                            <div>#{{ $log->subject_id }}</div>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $logs->links() }}
    </div>
</div>

@foreach($logs as $log)
<div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-3">Event:</dt>
                    <dd class="col-sm-9"><span class="badge bg-secondary">{{ ucfirst($log->event) }}</span></dd>
                    
                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9">{{ $log->description }}</dd>
                    
                    <dt class="col-sm-3">User:</dt>
                    <dd class="col-sm-9">{{ $log->causer->name ?? 'System' }}</dd>
                    
                    <dt class="col-sm-3">Timestamp:</dt>
                    <dd class="col-sm-9">{{ $log->created_at->format('F d, Y h:i:s A') }}</dd>
                    
                    <dt class="col-sm-3">Subject Type:</dt>
                    <dd class="col-sm-9">{{ $log->subject_type ?? '—' }}</dd>
                    
                    <dt class="col-sm-3">Subject ID:</dt>
                    <dd class="col-sm-9">{{ $log->subject_id ?? '—' }}</dd>
                </dl>
                
                @if($log->properties && count($log->properties))
                <h6 class="fw-bold mt-3">Properties:</h6>
                <pre class="bg-light p-3 rounded small" style="max-height:300px;overflow-y:auto">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
