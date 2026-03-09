@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
@php
    $userName = auth()->user()->name ?? 'there';
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $present  = $todayRecords->present  ?? 0;
    $late     = $todayRecords->late     ?? 0;
    $absent   = $todayRecords->absent   ?? 0;
    $onLeave  = $todayRecords->on_leave ?? 0;
    $total    = $present + $late + $absent + $onLeave;
    $rate     = $total > 0 ? round(($present + $late) / $total * 100) : 0;
    $wkPresent = $weeklyTrend->pluck('present')->toArray();
    $wkLate    = $weeklyTrend->pluck('late')->toArray();
    $wkAbsent  = $weeklyTrend->pluck('absent')->toArray();
@endphp

{{-- Branch filter --}}
@hasanyrole(['admin','superadmin','hr'])
<div class="d-flex align-items-center gap-2 mb-4">
    <i class="bx bx-filter-alt text-primary fs-5"></i>
    <form method="GET" class="d-flex gap-2 mb-0">
        <select name="branch_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All Branches</option>
            @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </form>
    <small class="text-muted ms-auto">{{ now()->format('l, F d, Y') }}</small>
</div>
@endhasanyrole


<div class="row g-4 mb-4">

    {{-- Welcome Card --}}
    <div class="col-12 col-xl-8">
        <div class="card welcome-card h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center" style="max-width:62%">
                <h4 class="fw-bold mb-1">{{ $greeting }}, {{ $userName }}! 👋</h4>
                <p class="mb-4 opacity-75">
                    @if($total > 0)
                        Today you have <strong>{{ $present }}</strong> present and <strong>{{ $absent }}</strong> absent.
                        Overall attendance rate is <strong>{{ $rate }}%</strong>.
                    @else
                        No attendance records yet for today. Start recording now!
                    @endif
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('attendance.monitor') }}" class="btn btn-light btn-sm px-3">
                        <i class="bx bx-desktop me-1"></i> Live Monitor
                    </a>
                    <a href="{{ route('attendance.record') }}" class="btn btn-outline-light btn-sm px-3">
                        <i class="bx bx-time me-1"></i> Record
                    </a>
                </div>
            </div>
            <img src="{{ asset('assets/img/illustrations/man-with-laptop-light.png') }}"
                 alt="illustration" class="welcome-img d-none d-md-block">
        </div>
    </div>

    {{-- Two tall stacked stat cards with sparklines --}}
    <div class="col-12 col-xl-4">
        <div class="d-flex flex-column gap-4 h-100">

            {{-- Present Today --}}
            <div class="card sparkline-card flex-fill border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase" style="letter-spacing:.04em">Present Today</p>
                            <div class="stat-num text-success">{{ $present }}</div>
                            <small class="text-muted">out of {{ $totalEmployees }} staff</small>
                        </div>
                        <span class="badge bg-label-success rounded-pill px-2">
                            <i class="bx bx-user-check me-1"></i>{{ $total > 0 ? round($present/$totalEmployees*100) : 0 }}%
                        </span>
                    </div>
                    <canvas id="sparkPresent" class="sparkline-canvas"></canvas>
                </div>
            </div>

            {{-- Active Staff --}}
            <div class="card sparkline-card flex-fill border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase" style="letter-spacing:.04em">Active Staff</p>
                            <div class="stat-num text-primary">{{ $totalEmployees }}</div>
                            <small class="text-muted">total employees</small>
                        </div>
                        <span class="badge bg-label-primary rounded-pill px-2">
                            <i class="bx bx-group me-1"></i>Active
                        </span>
                    </div>
                    <canvas id="sparkStaff" class="sparkline-canvas"></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══ ROW 2: Big chart | Rate gauge | Late & Absent mini cards ══════════ --}}
<div class="row g-4 mb-4">

    {{-- 7-Day Attendance Trend --}}
    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header d-flex align-items-start justify-content-between border-bottom-0 pt-4 pb-0 px-4">
                <div>
                    <h5 class="mb-0 fw-semibold">Attendance Trend</h5>
                    <small class="text-muted">7-day daily breakdown</small>
                </div>
                <span class="badge bg-label-primary">{{ now()->format('M Y') }}</span>
            </div>
            <div class="card-body px-3 pt-2 pb-3">
                <canvas id="weeklyChart" height="165"></canvas>
            </div>
        </div>
    </div>

    {{-- Attendance Rate Gauge --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="mb-0 fw-semibold">Attendance Rate</h5>
                <small class="text-muted">Today's overall rate</small>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3 px-4">
                {{-- SVG arc gauge --}}
                <div class="gauge-svg-wrap mb-2">
                    <svg width="160" height="160" viewBox="0 0 160 160">
                        <circle cx="80" cy="80" r="62" fill="none" stroke="#f0f0f0" stroke-width="14"/>
                        <circle cx="80" cy="80" r="62" fill="none"
                            stroke="url(#gaugeGrad)" stroke-width="14"
                            stroke-linecap="round"
                            stroke-dasharray="{{ round(2 * 3.14159 * 62 * $rate / 100) }} 999"
                            transform="rotate(-90 80 80)"/>
                        <defs>
                            <linearGradient id="gaugeGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#696cff"/>
                                <stop offset="100%" stop-color="#03c3ec"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="gauge-label">
                        <div class="fs-2 fw-bold">{{ $rate }}%</div>
                        <small class="text-muted">Attended</small>
                    </div>
                </div>
                <p class="text-muted mb-3 small text-center">
                    {{ $total > 0 ? "$total total records today" : "No records yet today" }}
                </p>
                <div class="row w-100 text-center g-0">
                    @foreach([
                        ['v'=>$present, 'l'=>'Present',  'c'=>'success'],
                        ['v'=>$late,    'l'=>'Late',     'c'=>'warning'],
                        ['v'=>$absent,  'l'=>'Absent',   'c'=>'danger'],
                        ['v'=>$onLeave, 'l'=>'On Leave', 'c'=>'info'],
                    ] as $item)
                    <div class="col-3">
                        <div class="fw-bold fs-5">{{ $item['v'] }}</div>
                        <small class="text-muted" style="font-size:.7rem">{{ $item['l'] }}</small>
                        <div class="mt-1"><span class="badge bg-label-{{ $item['c'] }}" style="font-size:.6rem">.</span></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Late & Absent mini stat cards --}}
    <div class="col-12 col-xl-3">
        <div class="d-flex flex-column gap-4 h-100">

            {{-- Late --}}
            <div class="card sparkline-card flex-fill border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase" style="letter-spacing:.04em">Late Today</p>
                            <div class="stat-num text-warning">{{ $late }}</div>
                        </div>
                        <span class="badge bg-label-warning"><i class="bx bx-time"></i></span>
                    </div>
                    <canvas id="sparkLate" class="sparkline-canvas"></canvas>
                </div>
            </div>

            {{-- Absent --}}
            <div class="card sparkline-card flex-fill border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase" style="letter-spacing:.04em">Absent Today</p>
                            <div class="stat-num text-danger">{{ $absent }}</div>
                        </div>
                        <span class="badge bg-label-danger"><i class="bx bx-user-x"></i></span>
                    </div>
                    <canvas id="sparkAbsent" class="sparkline-canvas"></canvas>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- ═══ ROW 3: Today's breakdown | Pending Leaves | Recent Time-Ins ════════ --}}
<div class="row g-4">

    {{-- Today's Breakdown (Order Statistics style) --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="mb-0 fw-semibold">Today's Breakdown</h5>
                <small class="text-muted">{{ now()->format('l, d M') }}</small>
            </div>
            <div class="card-body px-4 pt-3 pb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div>
                        <div class="display-6 fw-bold lh-1">{{ $total ?: $totalEmployees }}</div>
                        <small class="text-muted">Total {{ $total ? 'Records' : 'Employees' }}</small>
                    </div>
                    <div class="ms-auto">
                        <div class="ring-wrap">
                            <canvas id="rateDonut"></canvas>
                            <div class="ring-label">
                                <div class="fw-bold" style="font-size:1rem">{{ $rate }}%</div>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach([
                    ['label'=>'Present',  'count'=>$present,  'color'=>'#71dd37', 'icon'=>'bx-user-check',  'cls'=>'success'],
                    ['label'=>'Late',     'count'=>$late,     'color'=>'#ffab00', 'icon'=>'bx-time',        'cls'=>'warning'],
                    ['label'=>'Absent',   'count'=>$absent,   'color'=>'#ff3e1d', 'icon'=>'bx-user-x',      'cls'=>'danger'],
                    ['label'=>'On Leave', 'count'=>$onLeave,  'color'=>'#03c3ec', 'icon'=>'bx-calendar',    'cls'=>'info'],
                ] as $row)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="txn-icon bg-label-{{ $row['cls'] }} text-{{ $row['cls'] }}">
                            <i class="bx {{ $row['icon'] }}"></i>
                        </div>
                        <span class="fw-semibold small">{{ $row['label'] }}</span>
                    </div>
                    <span class="fw-bold">{{ $row['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Pending Leaves + Quick Actions --}}
    <div class="col-12 col-lg-4">
        <div class="d-flex flex-column gap-4 h-100">

            @hasanyrole(['hr','admin','superadmin'])
            <div class="card border-0 shadow-sm flex-fill">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h5 class="mb-0 fw-semibold">Pending Leaves</h5>
                            <small class="text-muted">Awaiting approval</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-calendar-check"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end gap-3 mb-3">
                        <div class="display-5 fw-bold lh-1 {{ $pendingLeaves > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $pendingLeaves }}
                        </div>
                        @if($pendingLeaves > 0)
                            <span class="badge bg-label-warning mb-1">Needs attention</span>
                        @else
                            <span class="badge bg-label-success mb-1">All clear</span>
                        @endif
                    </div>
                    @if($pendingLeaves > 0)
                    <a href="{{ route('leaves.index', ['status'=>'pending']) }}" class="btn btn-warning btn-sm w-100">
                        <i class="bx bx-check-circle me-1"></i> Review Requests
                    </a>
                    @else
                    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bx bx-list-ul me-1"></i> View All Leaves
                    </a>
                    @endif
                </div>
            </div>
            @endhasanyrole

            <div class="card border-0 shadow-sm flex-fill">
                <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="mb-0 fw-semibold">Quick Actions</h5>
                </div>
                <div class="card-body px-4 pt-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('attendance.record') }}" class="btn btn-label-primary w-100 d-flex flex-column align-items-center py-3 gap-1">
                                <i class="bx bx-time-five fs-4"></i><small style="font-size:.74rem">Time In/Out</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('attendance.monitor') }}" class="btn btn-label-info w-100 d-flex flex-column align-items-center py-3 gap-1">
                                <i class="bx bx-desktop fs-4"></i><small style="font-size:.74rem">Monitor</small>
                            </a>
                        </div>
                        @hasanyrole(['hr','admin','superadmin'])
                        <div class="col-6">
                            <a href="{{ route('reports.index') }}" class="btn btn-label-success w-100 d-flex flex-column align-items-center py-3 gap-1">
                                <i class="bx bx-file fs-4"></i><small style="font-size:.74rem">Reports</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('employees.index') }}" class="btn btn-label-warning w-100 d-flex flex-column align-items-center py-3 gap-1">
                                <i class="bx bx-group fs-4"></i><small style="font-size:.74rem">Employees</small>
                            </a>
                        </div>
                        @endhasanyrole
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Recent Time-Ins (Transactions style) --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between border-bottom-0 pt-4 pb-0 px-4">
                <div>
                    <h5 class="mb-0 fw-semibold">Recent Time-Ins</h5>
                    <small class="text-muted">Today's latest entries</small>
                </div>
                <a href="{{ route('attendance.monitor') }}" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <div class="card-body px-4 pt-3 pb-2">
                @forelse($recentAttendance as $r)
                <div class="d-flex align-items-center {{ !$loop->last ? 'mb-3' : '' }}">
                    <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($r->employee->first_name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small lh-sm">{{ $r->employee->full_name ?? '—' }}</div>
                        <small class="text-muted">{{ $r->branch->name ?? '' }}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge badge-{{ $r->status }} d-block mb-1">
                            {{ ucfirst(str_replace('_',' ',$r->status)) }}
                        </span>
                        <small class="text-muted" style="font-size:.7rem">{{ $r->time_in ?? '—' }}</small>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bx bx-calendar-x d-block fs-3 mb-1"></i>
                    No records today.
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const wkLabels  = @json($weeklyTrend->pluck('date'));
const wkPresent = @json($weeklyTrend->pluck('present'));
const wkLate    = @json($weeklyTrend->pluck('late'));
const wkAbsent  = @json($weeklyTrend->pluck('absent'));

// ── Helper: sparkline (area line chart) ──────────────────────────────────────
function makeSparkline(id, data, color) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: wkLabels,
            datasets: [{ data, borderColor: color, borderWidth: 2,
                fill: true, backgroundColor: color + '22',
                pointRadius: 0, tension: 0.4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
            elements: { line: { borderCapStyle: 'round' } }
        }
    });
}

makeSparkline('sparkPresent', wkPresent, '#71dd37');
makeSparkline('sparkStaff',   wkPresent.map((v,i) => v + wkLate[i]), '#696cff');
makeSparkline('sparkLate',    wkLate,    '#ffab00');
makeSparkline('sparkAbsent',  wkAbsent,  '#ff3e1d');

// ── 7-Day stacked bar chart ──────────────────────────────────────────────────
new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: wkLabels,
        datasets: [
            { label:'Present', data:wkPresent, backgroundColor:'rgba(113,221,55,.85)', borderRadius:4, borderSkipped:false },
            { label:'Late',    data:wkLate,    backgroundColor:'rgba(255,171,0,.85)',  borderRadius:4, borderSkipped:false },
            { label:'Absent',  data:wkAbsent,  backgroundColor:'rgba(255,62,29,.85)',  borderRadius:4, borderSkipped:false },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position:'top', align:'end' }, tooltip: { mode:'index' } },
        scales: {
            x: { stacked:true, grid:{ display:false } },
            y: { stacked:true, beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } }
        }
    }
});

// ── Rate donut (small, in breakdown card) ────────────────────────────────────
const rTotal = {{ $present + $late + $absent + $onLeave }};
new Chart(document.getElementById('rateDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Present','Late','Absent','On Leave'],
        datasets: [{
            data: rTotal > 0 ? [{{ $present }},{{ $late }},{{ $absent }},{{ $onLeave }}] : [1,0,0,0],
            backgroundColor: ['#71dd37','#ffab00','#ff3e1d','#03c3ec'],
            borderWidth: 0, hoverOffset: 4,
        }]
    },
    options: {
        cutout: '72%', responsive: true,
        plugins: { legend:{ display:false }, tooltip:{ enabled: rTotal > 0 } }
    }
});
</script>
@endpush
