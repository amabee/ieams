@extends('layouts.app')
@section('title','Attendance Forecasting')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Attendance Forecasting</h4>
    <div class="d-flex gap-2">
        <select id="branchFilter" class="form-select form-select-sm" style="width:200px">
            @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        @can('run-forecast')
        <form method="POST" action="{{ route('forecasting.run') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Run attendance forecast for all branches? This may take a few moments.')">
                <i class="bi bi-lightning-charge me-1"></i> Run Forecast
            </button>
        </form>
        @endcan
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>About Forecasting:</strong> This system uses Holt-Winters Triple Exponential Smoothing to predict future attendance patterns based on historical data. 
    The forecast considers weekly seasonality and provides a 30-day projection to help with workforce planning.
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold">Attendance Forecast</h6>
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
                <h5 class="mb-0 fw-bold">30 Days</h5>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Avg Predicted Attendance</div>
                <h5 class="mb-0 fw-bold text-primary" id="avgForecast">—</h5>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Last Updated</div>
                <h5 class="mb-0 fw-bold" id="lastUpdate">—</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Forecast Insights</h6>
            </div>
            <div class="card-body" id="insightsPanel">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm"></div> Loading insights...
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Forecasting Parameters</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Algorithm</dt>
                    <dd class="col-sm-7">Holt-Winters Triple Exponential Smoothing</dd>
                    
                    <dt class="col-sm-5">Smoothing Parameters</dt>
                    <dd class="col-sm-7">α (level): 0.3, β (trend): 0.1, γ (seasonal): 0.2</dd>
                    
                    <dt class="col-sm-5">Seasonality Period</dt>
                    <dd class="col-sm-7">7 days (weekly pattern)</dd>
                    
                    <dt class="col-sm-5">Forecast Horizon</dt>
                    <dd class="col-sm-7">30 days ahead</dd>
                    
                    <dt class="col-sm-5">Update Frequency</dt>
                    <dd class="col-sm-7 mb-0">Daily at 1:00 AM (via scheduled task)</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
let forecastChart;

function loadForecast() {
    const branchId = document.getElementById('branchFilter').value;
    
    fetch(`{{ route('forecasting.data') }}?branch_id=${branchId}`)
        .then(r => r.json())
        .then(data => {
            // Update summary stats
            document.getElementById('historicalCount').textContent = data.historical.length;
            document.getElementById('avgForecast').textContent = Math.round(data.avgForecast);
            document.getElementById('lastUpdate').textContent = data.lastUpdate;
            
            // Build chart
            if (forecastChart) forecastChart.destroy();
            forecastChart = new Chart(document.getElementById('forecastChart'), {
                type: 'line',
                data: {
                    labels: [...data.historical.map(d => d.date), ...data.forecast.map(d => d.date)],
                    datasets: [
                        {
                            label: 'Historical Attendance',
                            data: [...data.historical.map(d => d.count), ...Array(data.forecast.length).fill(null)],
                            borderColor: '#1e2a3a',
                            backgroundColor: 'rgba(30,42,58,0.1)',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Forecasted Attendance',
                            data: [...Array(data.historical.length).fill(null), ...data.forecast.map(d => d.predicted)],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23,162,184,0.1)',
                            borderDash: [5, 5],
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + Math.round(context.parsed.y) + ' employees';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Employees' } },
                        x: { title: { display: true, text: 'Date' } }
                    }
                }
            });
            
            // Generate insights
            const insights = document.getElementById('insightsPanel');
            let html = '<ul class="list-unstyled mb-0">';
            
            if (data.insights.trend === 'increasing') {
                html += '<li class="mb-2"><i class="bi bi-arrow-up-circle-fill text-success me-2"></i> <strong>Increasing trend:</strong> Attendance is expected to rise in the coming weeks.</li>';
            } else if (data.insights.trend === 'decreasing') {
                html += '<li class="mb-2"><i class="bi bi-arrow-down-circle-fill text-danger me-2"></i> <strong>Decreasing trend:</strong> Attendance may decline. Consider investigating potential issues.</li>';
            } else {
                html += '<li class="mb-2"><i class="bi bi-dash-circle-fill text-info me-2"></i> <strong>Stable trend:</strong> Attendance is expected to remain consistent.</li>';
            }
            
            if (data.insights.peakDay) {
                html += `<li class="mb-2"><i class="bi bi-calendar-check-fill text-primary me-2"></i> <strong>Peak day:</strong> ${data.insights.peakDay} typically has the highest attendance.</li>`;
            }
            
            if (data.insights.lowDay) {
                html += `<li class="mb-2"><i class="bi bi-calendar-x-fill text-warning me-2"></i> <strong>Low day:</strong> ${data.insights.lowDay} typically has lower attendance.</li>`;
            }
            
            if (data.insights.avgChange) {
                html += `<li class="mb-0"><i class="bi bi-graph-up me-2"></i> <strong>Average change:</strong> ${data.insights.avgChange > 0 ? '+' : ''}${data.insights.avgChange}% compared to last period.</li>`;
            }
            
            html += '</ul>';
            insights.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load forecast:', err);
            document.getElementById('insightsPanel').innerHTML = '<div class="alert alert-danger mb-0">Failed to load forecast data. Please try again.</div>';
        });
}

document.getElementById('branchFilter').addEventListener('change', loadForecast);
loadForecast();
</script>
@endpush
