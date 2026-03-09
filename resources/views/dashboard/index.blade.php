@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')
<li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Dashboard</h5>
    <small class="text-muted">{{ now()->format('l, F d Y') }}</small>
</div>

{{-- Branch filter (admin/hr only) --}}
@canany(['view branches'])
<form method="GET" class="d-flex gap-2 mb-3">
    <select name="branch_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">All Branches</option>
        @foreach($branches as $b)
        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
    </select>
</form>
@endcanany

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-check"></i></div>
                <div>
                    <div class="fs-4 fw-bold">{{ $todayRecords->present ?? 0 }}</div>
                    <small class="text-muted">Present Today</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock"></i></div>
                <div>
                    <div class="fs-4 fw-bold">{{ $todayRecords->late ?? 0 }}</div>
                    <small class="text-muted">Late Today</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-person-x"></i></div>
                <div>
                    <div class="fs-4 fw-bold">{{ $todayRecords->absent ?? 0 }}</div>
                    <small class="text-muted">Absent Today</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalEmployees }}</div>
                    <small class="text-muted">Active Staff</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Weekly Chart --}}
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-1"></i> 7-Day Attendance Trend</h6>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="120"></canvas>
            </div>
        </div>
    </div>
    {{-- Quick Stats --}}
    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-1"></i> Recent Time-Ins</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height:270px;overflow-y:auto">
                    @forelse($recentAttendance as $r)
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold">{{ $r->employee->full_name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $r->branch->name ?? '' }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-{{ $r->status }} small">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span>
                                <div class="text-muted" style="font-size:.72rem">{{ $r->time_in ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-muted small">No attendance recorded yet today.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingLeaves > 0)
<div class="alert alert-warning mt-3 d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>There {{ $pendingLeaves == 1 ? 'is' : 'are' }} <strong>{{ $pendingLeaves }}</strong> pending leave request(s) awaiting approval.
    <a href="{{ route('leaves.index', ['status' => 'pending']) }}" class="alert-link ms-1">Review now</a></span>
</div>
@endif
@endsection

@push('scripts')
<script>
const labels = @json($weeklyTrend->pluck('date'));
const present = @json($weeklyTrend->pluck('present'));
const late    = @json($weeklyTrend->pluck('late'));
const absent  = @json($weeklyTrend->pluck('absent'));

new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Present', data: present, backgroundColor: '#19875480' },
            { label: 'Late',    data: late,    backgroundColor: '#ffc10780' },
            { label: 'Absent',  data: absent,  backgroundColor: '#dc354580' },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
    }
});
</script>
@endpush
