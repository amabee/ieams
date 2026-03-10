@extends('layouts.app')
@section('title','Attendance Forecasting')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Attendance Forecasting</h4>
    <div class="d-flex gap-2 align-items-center">
        <select id="branchFilter" class="form-select form-select-sm" style="width:200px">
            <option value="" {{ !$branchId ? 'selected' : '' }}>All Branches</option>
            @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $b->id == $branchId ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select id="horizonFilter" class="form-select form-select-sm" style="width:140px">
            <option value="7">7-Day View</option>
            <option value="14">14-Day View</option>
            <option value="30" selected>30-Day View</option>
        </select>
        @can('run forecast')
        <form method="POST" action="{{ route('forecasting.run') }}" class="d-inline" id="runForecastForm">
            @csrf
            <input type="hidden" name="branch_id" id="runBranchId" value="{{ $branchId }}">
            <button type="submit" class="btn btn-sm btn-primary"
                onclick="return confirm('Generate forecast for the selected branch?')">
                <i class="bi bi-lightning-charge me-1"></i> Run Forecast
            </button>
        </form>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="alert alert-info d-flex gap-2 align-items-start" id="fallbackAlert" style="display:none!important">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
    <div><strong>Limited Data Mode:</strong> Not enough historical records for full Holt-Winters modelling — a moving average fallback was used. Accuracy will improve as more attendance data accumulates.</div>
</div>

{{-- Empty state (shown when no forecast data exists) --}}
<div id="emptyState" style="display:none">
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-bar-chart-line text-muted" style="font-size:3rem"></i>
            <h5 class="mt-3 mb-1">No Forecast Available</h5>
            <p class="text-muted mb-3">No forecast has been generated for this branch yet. Click <strong>Run Forecast</strong> to generate one.</p>
            @can('run forecast')
            <button type="button" class="btn btn-primary" onclick="document.getElementById('runForecastForm').submit()">
                <i class="bi bi-lightning-charge me-1"></i> Generate Now
            </button>
            @endcan
        </div>
    </div>
</div>

<div id="forecastContent">
    {{-- Summary Stats --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">Attendance Forecast</h6>
            <span id="modelBadge" class="badge bg-primary-subtle text-primary"></span>
        </div>
        <div class="card-body">
            <canvas id="forecastChart" height="80"></canvas>
        </div>
        <div class="card-footer bg-light">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="text-muted small">Historical Data Points</div>
                    <h5 class="mb-0 fw-bold" id="historicalCount">—</h5>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Forecast Horizon</div>
                    <h5 class="mb-0 fw-bold" id="horizonLabel">—</h5>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Avg Predicted Absenteeism</div>
                    <h5 class="mb-0 fw-bold text-danger" id="avgForecast">—</h5>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Last Updated</div>
                    <h5 class="mb-0 fw-bold small" id="lastUpdate">—</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Insights --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold">Forecast Insights</h6>
                </div>
                <div class="card-body" id="insightsPanel">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm"></div> Loading...
                    </div>
                </div>
            </div>
        </div>

        {{-- Parameters --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold">Forecasting Parameters</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">Algorithm</dt>
                        <dd class="col-sm-7" id="paramAlgo">Holt-Winters Triple Exponential Smoothing</dd>

                        <dt class="col-sm-5">α (level)</dt>
                        <dd class="col-sm-7" id="paramAlpha">—</dd>

                        <dt class="col-sm-5">β (trend)</dt>
                        <dd class="col-sm-7" id="paramBeta">—</dd>

                        <dt class="col-sm-5">γ (seasonal)</dt>
                        <dd class="col-sm-7" id="paramGamma">—</dd>

                        <dt class="col-sm-5">Seasonality</dt>
                        <dd class="col-sm-7">7 days (weekly)</dd>

                        <dt class="col-sm-5">Confidence Level</dt>
                        <dd class="col-sm-7" id="paramConfidence">—</dd>

                        <dt class="col-sm-5">Update Frequency</dt>
                        <dd class="col-sm-7 mb-0">Daily at 1:00 AM</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Interpretation --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2 text-warning"></i>AI Interpretation</h6>
            <button class="btn btn-sm btn-outline-primary" id="interpretBtn" onclick="interpretForecast()">
                <i class="bi bi-magic me-1"></i> Interpret Data
            </button>
        </div>
        <div class="card-body" id="interpretPanel">
            <p class="text-muted mb-0 small">Click <strong>Interpret Data</strong> to get a plain-English summary of the current forecast, including trend analysis and recommended actions.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
let forecastChart;
let currentData = null;

function loadForecast() {
    const branchId = document.getElementById('branchFilter').value;
    const horizon  = document.getElementById('horizonFilter').value;

    // Keep the run-forecast hidden input in sync (only exists for users with run-forecast permission)
    const runInput = document.getElementById('runBranchId');
    if (runInput) runInput.value = branchId;

    fetch(`{{ route('forecasting.data') }}?branch_id=${branchId}&horizon=${horizon}`)
        .then(r => r.json())
        .then(data => {
            currentData = data;

            const hasForecasts = data.forecast && data.forecast.length > 0;
            document.getElementById('emptyState').style.display    = hasForecasts ? 'none'  : 'block';
            document.getElementById('forecastContent').style.display = hasForecasts ? 'block' : 'none';

            if (!hasForecasts) return;

            // Fallback warning
            const fallbackAlert = document.getElementById('fallbackAlert');
            fallbackAlert.style.display = data.usedFallback ? 'flex' : 'none';

            // Model badge
            const badge = document.getElementById('modelBadge');
            badge.textContent = data.usedFallback ? 'Moving Average' : 'Holt-Winters';
            badge.className = 'badge ' + (data.usedFallback ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary');

            // Summary stats
            document.getElementById('historicalCount').textContent = data.historical.length + ' days';
            document.getElementById('horizonLabel').textContent    = horizon + ' Days';
            document.getElementById('avgForecast').textContent     = data.avgForecast + ' absent';
            document.getElementById('lastUpdate').textContent      = data.lastUpdate ?? 'Never';

            // Dynamic params
            document.getElementById('paramAlpha').textContent      = data.params.alpha;
            document.getElementById('paramBeta').textContent       = data.params.beta;
            document.getElementById('paramGamma').textContent      = data.params.gamma;
            document.getElementById('paramConfidence').textContent = data.confidence + '%';
            document.getElementById('paramAlgo').textContent       = data.usedFallback
                ? 'Moving Average (fallback)' : 'Holt-Winters Triple Exponential Smoothing';

            // Chart
            buildChart(data);

            // Insights
            buildInsights(data.insights);

            // Reset interpret panel
            document.getElementById('interpretPanel').innerHTML =
                '<p class="text-muted mb-0 small">Click <strong>Interpret Data</strong> to get a plain-English summary of the current forecast, including trend analysis and recommended actions.</p>';
        })
        .catch(err => {
            console.error('Failed to load forecast:', err);
            document.getElementById('insightsPanel').innerHTML =
                '<div class="alert alert-danger mb-0">Failed to load forecast data. Please try again.</div>';
        });
}

function buildChart(data) {
    if (forecastChart) forecastChart.destroy();

    const histLabels     = data.historical.map(d => d.date);
    const forecastLabels = data.forecast.map(d => d.date);
    const allLabels      = [...histLabels, ...forecastLabels];
    const nHist          = histLabels.length;
    const nFcast         = forecastLabels.length;

    forecastChart = new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: allLabels,
            datasets: [
                // CI upper bound (invisible line, fills down to lower)
                {
                    label: 'Upper Bound',
                    data: [...Array(nHist).fill(null), ...data.forecast.map(d => d.upper)],
                    borderColor: 'transparent',
                    backgroundColor: 'rgba(23,162,184,0.12)',
                    fill: '+1',
                    pointRadius: 0,
                    tension: 0.3,
                    order: 3
                },
                // CI lower bound
                {
                    label: 'Lower Bound',
                    data: [...Array(nHist).fill(null), ...data.forecast.map(d => d.lower)],
                    borderColor: 'transparent',
                    backgroundColor: 'rgba(23,162,184,0.12)',
                    fill: false,
                    pointRadius: 0,
                    tension: 0.3,
                    order: 4
                },
                // Historical absenteeism
                {
                    label: 'Historical Absenteeism',
                    data: [...data.historical.map(d => d.count), ...Array(nFcast).fill(null)],
                    borderColor: '#1e2a3a',
                    backgroundColor: 'rgba(30,42,58,0.08)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2,
                    order: 1
                },
                // Forecasted absenteeism
                {
                    label: 'Forecasted Absenteeism',
                    data: [...Array(nHist).fill(null), ...data.forecast.map(d => d.predicted)],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23,162,184,0.08)',
                    borderDash: [5, 5],
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2,
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        filter: item => !['Upper Bound','Lower Bound'].includes(item.text)
                    }
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            if (['Upper Bound','Lower Bound'].includes(ctx.dataset.label)) return null;
                            return `${ctx.dataset.label}: ${Math.round(ctx.parsed.y)} employees`;
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Absent Employees' } },
                x: { title: { display: true, text: 'Date' } }
            }
        }
    });
}

function buildInsights(ins) {
    const insights = document.getElementById('insightsPanel');
    let html = '<ul class="list-unstyled mb-0">';

    if (ins.trend === 'increasing') {
        html += '<li class="mb-2"><i class="bi bi-arrow-up-circle-fill text-danger me-2"></i><strong>Rising absenteeism:</strong> Absences are expected to increase — consider proactive HR intervention.</li>';
    } else if (ins.trend === 'decreasing') {
        html += '<li class="mb-2"><i class="bi bi-arrow-down-circle-fill text-success me-2"></i><strong>Declining absenteeism:</strong> Absence rates are forecasted to drop — current initiatives appear to be working.</li>';
    } else {
        html += '<li class="mb-2"><i class="bi bi-dash-circle-fill text-info me-2"></i><strong>Stable pattern:</strong> Absenteeism is expected to remain consistent over the forecast period.</li>';
    }

    if (ins.peakDay) {
        html += `<li class="mb-2"><i class="bi bi-calendar-x-fill text-warning me-2"></i><strong>Highest absence day:</strong> ${ins.peakDay} historically has the most absences.</li>`;
    }
    if (ins.lowDay) {
        html += `<li class="mb-2"><i class="bi bi-calendar-check-fill text-success me-2"></i><strong>Best attendance day:</strong> ${ins.lowDay} typically has the lowest absenteeism.</li>`;
    }
    if (ins.avgChange !== null && ins.avgChange !== undefined) {
        const sign = ins.avgChange > 0 ? '+' : '';
        const color = ins.avgChange > 0 ? 'text-danger' : 'text-success';
        html += `<li class="mb-0"><i class="bi bi-graph-up me-2 ${color}"></i><strong>Trend vs recent period:</strong> <span class="${color}">${sign}${ins.avgChange}%</span> compared to last 7 days.</li>`;
    }

    html += '</ul>';
    insights.innerHTML = html;
}

function interpretForecast() {
    if (!currentData || !currentData.forecast.length) return;

    const btn   = document.getElementById('interpretBtn');
    const panel = document.getElementById('interpretPanel');
    const branchName = document.getElementById('branchFilter').selectedOptions[0].text;
    const horizon = document.getElementById('horizonFilter').value;

    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner-border spinner-border-sm me-1"></span> Interpreting...';
    panel.innerHTML = '<div class="text-muted small"><div class="spinner-border spinner-border-sm me-2"></div>Generating interpretation...</div>';

    fetch('{{ route('forecasting.interpret') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({
            branch_name:   branchName,
            horizon:       parseInt(horizon),
            trend:         currentData.insights.trend,
            avg_forecast:  currentData.avgForecast,
            avg_change:    currentData.insights.avgChange ?? null,
            peak_day:      currentData.insights.peakDay  ?? null,
            low_day:       currentData.insights.lowDay   ?? null,
            used_fallback: currentData.usedFallback,
            confidence:    currentData.confidence,
        })
    })
    .then(r => r.json())
    .then(res => {
        panel.innerHTML = `<p class="mb-0">${res.interpretation}</p>`;
    })
    .catch(() => {
        panel.innerHTML = '<div class="alert alert-danger mb-0">Failed to generate interpretation. Please try again.</div>';
    })
    .finally(() => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-magic me-1"></i> Interpret Data';
    });
}

document.getElementById('branchFilter').addEventListener('change', loadForecast);
document.getElementById('horizonFilter').addEventListener('change', loadForecast);
loadForecast();
</script>
@endpush

