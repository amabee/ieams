@extends('layouts.app')
@section('title','Analytics')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Attendance Analytics</h4>
    <div class="d-flex gap-2">
        <select id="branchFilter" class="form-select form-select-sm" style="width:180px">
            <option value="">All Branches</option>
            @foreach($branches as $b)
            <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
        <select id="periodFilter" class="form-select form-select-sm" style="width:150px">
            <option value="7">Last 7 Days</option>
            <option value="30" selected>Last 30 Days</option>
            <option value="90">Last 90 Days</option>
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Avg Attendance Rate</div>
                        <h3 class="mb-0 fw-bold text-success" id="avgRate">—</h3>
                    </div>
                    <i class="bi bi-graph-up-arrow text-success fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Avg Late Rate</div>
                        <h3 class="mb-0 fw-bold text-warning" id="avgLate">—</h3>
                    </div>
                    <i class="bi bi-clock text-warning fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Avg Absence Rate</div>
                        <h3 class="mb-0 fw-bold text-danger" id="avgAbsent">—</h3>
                    </div>
                    <i class="bi bi-x-circle text-danger fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Total Records</div>
                        <h3 class="mb-0 fw-bold text-primary" id="totalRecords">—</h3>
                    </div>
                    <i class="bi bi-file-earmark-text text-primary fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Attendance Trend</h6>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="80"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Status Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Attendance by Branch</h6>
            </div>
            <div class="card-body">
                <canvas id="branchChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Daily Punctuality Rate</h6>
            </div>
            <div class="card-body">
                <canvas id="punctualityChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold">Top Performing Employees</h6>
    </div>
    <div class="card-body">
        <table class="table table-hover w-100" id="topPerformersTable">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Present Days</th>
                    <th>Late Days</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
let trendChart, statusChart, branchChart, punctualityChart;

const performersTable = $('#topPerformersTable').DataTable({
    columns: [
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (data, type, row, meta) => {
                const i = meta.row;
                const cls = i === 0 ? 'bg-warning text-dark' : i === 1 ? 'bg-secondary' : 'bg-light text-dark';
                return `<span class="badge ${cls}">${i + 1}</span>`;
            }
        },
        {
            data: null,
            render: data => `<div class="fw-semibold">${data.name}</div><small class="text-muted">${data.employee_no}</small>`
        },
        { data: 'branch' },
        {
            data: 'present_days',
            render: data => `<span class="badge bg-success">${data}</span>`
        },
        {
            data: 'late_days',
            render: data => `<span class="badge bg-warning text-dark">${data}</span>`
        },
        {
            data: 'attendance_rate',
            render: data => `<strong>${data}%</strong>`
        }
    ],
    order: [[5, 'desc']],
    pageLength: 10,
    language: { emptyTable: 'No data available' }
});

function loadAnalytics() {
    const branch = document.getElementById('branchFilter').value;
    const period = document.getElementById('periodFilter').value;
    
    fetch(`{{ route('analytics.data') }}?branch=${branch}&period=${period}`)
        .then(r => r.json())
        .then(data => {
            // Update summary cards
            document.getElementById('avgRate').textContent = data.summary.attendance_rate + '%';
            document.getElementById('avgLate').textContent = data.summary.late_rate + '%';
            document.getElementById('avgAbsent').textContent = data.summary.absence_rate + '%';
            document.getElementById('totalRecords').textContent = data.summary.total_records;
            
            // Trend Chart (Line)
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [
                        { label: 'Present', data: data.trend.present, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension: 0.3 },
                        { label: 'Late', data: data.trend.late, borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,0.1)', tension: 0.3 },
                        { label: 'Absent', data: data.trend.absent, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension: 0.3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } }
            });
            
            // Status Doughnut Chart
            if (statusChart) statusChart.destroy();
            statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Late', 'Absent', 'On Leave'],
                    datasets: [{
                        data: [data.status.present, data.status.late, data.status.absent, data.status.on_leave],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
            });
            
            // Branch Bar Chart
            if (branchChart) branchChart.destroy();
            branchChart = new Chart(document.getElementById('branchChart'), {
                type: 'bar',
                data: {
                    labels: data.byBranch.labels,
                    datasets: [{
                        label: 'Attendance Count',
                        data: data.byBranch.counts,
                        backgroundColor: '#1e2a3a'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } }
            });
            
            // Punctuality Chart
            if (punctualityChart) punctualityChart.destroy();
            punctualityChart = new Chart(document.getElementById('punctualityChart'), {
                type: 'line',
                data: {
                    labels: data.punctuality.labels,
                    datasets: [{
                        label: 'On-Time Rate (%)',
                        data: data.punctuality.rates,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, max: 100 } } }
            });
            
            // Top Performers Table
            performersTable.clear().rows.add(data.topPerformers).draw();
        })
        .catch(err => console.error('Analytics load failed:', err));
}

document.getElementById('branchFilter').addEventListener('change', loadAnalytics);
document.getElementById('periodFilter').addEventListener('change', loadAnalytics);
loadAnalytics();
</script>
@endpush
