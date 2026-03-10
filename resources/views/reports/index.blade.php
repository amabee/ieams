@extends('layouts.app')
@section('title','Reports')
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 sticky-top" style="top:20px">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Generate Report</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('reports.generate') }}" id="reportForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Report Type</label>
                        <select name="report_type" class="form-select" required>
                            <option value="daily">Daily Attendance</option>
                            <option value="weekly">Weekly Attendance</option>
                            <option value="monthly">Monthly Attendance</option>
                            <option value="annual">Annual Attendance</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" name="date_from" class="form-control" 
                                       value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}" required>
                                <small class="form-text text-muted">From</small>
                            </div>
                            <div class="col-6">
                                <input type="date" name="date_to" class="form-control" 
                                       value="{{ request('date_to', now()->format('Y-m-d')) }}" required>
                                <small class="form-text text-muted">To</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee (Optional)</label>
                        <input type="text" name="employee_id" class="form-control" placeholder="Employee name or number">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Format</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="format" id="formatPdf" value="pdf" checked>
                            <label class="btn btn-outline-danger" for="formatPdf">
                                <i class="bi bi-file-pdf"></i> PDF
                            </label>
                            <input type="radio" class="btn-check" name="format" id="formatExcel" value="excel">
                            <label class="btn btn-outline-success" for="formatExcel">
                                <i class="bi bi-file-excel"></i> Excel
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-download me-2"></i> Generate Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Quick Reports</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-week text-primary fs-2"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">This Week's Attendance</h6>
                                        <p class="text-muted small mb-2">Generate attendance report for current week</p>
                                        <a href="{{ route('reports.generate', ['report_type'=>'weekly','date_from'=>now()->startOfWeek()->format('Y-m-d'),'date_to'=>now()->endOfWeek()->format('Y-m-d'),'format'=>'pdf']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-pdf me-1"></i> PDF
                                        </a>
                                        <a href="{{ route('reports.generate', ['report_type'=>'weekly','date_from'=>now()->startOfWeek()->format('Y-m-d'),'date_to'=>now()->endOfWeek()->format('Y-m-d'),'format'=>'excel']) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-excel me-1"></i> Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-month text-success fs-2"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">This Month's Attendance</h6>
                                        <p class="text-muted small mb-2">Generate attendance report for current month</p>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->endOfMonth()->format('Y-m-d'),'format'=>'pdf']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-pdf me-1"></i> PDF
                                        </a>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->endOfMonth()->format('Y-m-d'),'format'=>'excel']) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-excel me-1"></i> Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-graph-up text-info fs-2"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Attendance Summary</h6>
                                        <p class="text-muted small mb-2">Statistical summary of attendance</p>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->format('Y-m-d'),'format'=>'pdf']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-pdf me-1"></i> PDF
                                        </a>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->format('Y-m-d'),'format'=>'excel']) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-excel me-1"></i> Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-x text-warning fs-2"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Leave Report</h6>
                                        <p class="text-muted small mb-2">Summary of leave requests</p>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->format('Y-m-d'),'format'=>'pdf']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-pdf me-1"></i> PDF
                                        </a>
                                        <a href="{{ route('reports.generate', ['report_type'=>'monthly','date_from'=>now()->startOfMonth()->format('Y-m-d'),'date_to'=>now()->format('Y-m-d'),'format'=>'excel']) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-excel me-1"></i> Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Report Description</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Attendance Report</dt>
                    <dd class="col-sm-8">Detailed list of all attendance records with time in/out, hours worked, and status.</dd>

                    <dt class="col-sm-4">Attendance Summary</dt>
                    <dd class="col-sm-8">Statistical overview showing present, late, absent counts by employee and branch.</dd>

                    <dt class="col-sm-4">Leave Report</dt>
                    <dd class="col-sm-8">Summary of all leave requests including status, type, and duration.</dd>

                    <dt class="col-sm-4">Employee Report</dt>
                    <dd class="col-sm-8 mb-0">Complete employee information including branch assignment and employment status.</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
