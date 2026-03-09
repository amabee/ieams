@extends('layouts.app')
@section('title', 'Attendance Monitor')

@section('content')
{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            @hasanyrole(['admin', 'superadmin', 'hr'])
            <div class="col-md-4">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endhasanyrole
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bx bx-filter-alt me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

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

{{-- Live Records Table --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Attendance Records — {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h5>
        <button class="btn btn-sm btn-outline-primary" id="refreshBtn" onclick="refreshData()">
            <i class="bx bx-refresh me-1"></i> Refresh
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="recordsBody">
                @forelse($records as $record)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ substr($record->employee->first_name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $record->employee->full_name ?? '—' }}</div>
                                <small class="text-muted">{{ $record->employee->position ?? '' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $record->branch->name ?? '—' }}</td>
                    <td>{{ $record->time_in ?? '—' }}</td>
                    <td>{{ $record->time_out ?? '—' }}</td>
                    <td>{{ $record->hours_worked ? number_format($record->hours_worked, 1).'h' : '—' }}</td>
                    <td>
                        @php
                            $statusClass = match($record->status) {
                                'present' => 'bg-label-success',
                                'late' => 'bg-label-warning',
                                'absent' => 'bg-label-danger',
                                'on_leave' => 'bg-label-info',
                                default => 'bg-label-secondary',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No attendance records for this date.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">
        {{ $records->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Auto-refresh every 60 seconds
    let autoRefresh = setInterval(refreshData, 60000);

    function refreshData() {
        const btn = document.getElementById('refreshBtn');
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Refreshing...';
        btn.disabled = true;

        fetch('{{ route('attendance.monitor.data') }}?date={{ $date }}&branch_id={{ $branchId }}')
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('recordsBody');
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No attendance records for this date.</td></tr>';
                    return;
                }
                const statusBadge = (s) => {
                    const classes = { present: 'bg-label-success', late: 'bg-label-warning', absent: 'bg-label-danger', on_leave: 'bg-label-info' };
                    return `<span class="badge ${classes[s] || 'bg-label-secondary'}">${s.replace('_',' ')}</span>`;
                };
                tbody.innerHTML = data.map(r => `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">${r.employee.charAt(0)}</span></div>
                                <div><div class="fw-semibold">${r.employee}</div><small class="text-muted">${r.position}</small></div>
                            </div>
                        </td>
                        <td>${r.branch}</td>
                        <td>${r.time_in}</td>
                        <td>${r.time_out}</td>
                        <td>${r.hours_worked !== '—' ? r.hours_worked + 'h' : '—'}</td>
                        <td>${statusBadge(r.status)}</td>
                    </tr>
                `).join('');
            })
            .finally(() => {
                btn.innerHTML = '<i class="bx bx-refresh me-1"></i> Refresh';
                btn.disabled = false;
            });
    }
</script>
@endpush
@endsection
