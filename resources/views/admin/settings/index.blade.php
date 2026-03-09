@extends('layouts.app')
@section('title','System Settings')
@section('breadcrumb')
<li class="breadcrumb-item">Admin</li>
<li class="breadcrumb-item active">Settings</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 fw-bold">System Settings</h4>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2"></i>Organization Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Organization Name</label>
                            <input type="text" name="organization_name" class="form-control" 
                                   value="{{ old('organization_name', $settings['organization_name'] ?? 'IEAMS') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" 
                                   value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $settings['address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock me-2"></i>Attendance Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Late Threshold (minutes)</label>
                            <input type="number" name="late_threshold_minutes" class="form-control" 
                                   value="{{ old('late_threshold_minutes', $settings['late_threshold_minutes'] ?? 15) }}" min="1" max="60">
                            <small class="text-muted">Employees are marked late after this many minutes</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grace Period (minutes)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" 
                                   value="{{ old('grace_period_minutes', $settings['grace_period_minutes'] ?? 5) }}" min="0" max="30">
                            <small class="text-muted">Grace period before marking as late</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Auto Mark Absent After (hrs)</label>
                            <input type="number" name="auto_absent_hours" class="form-control" 
                                   value="{{ old('auto_absent_hours', $settings['auto_absent_hours'] ?? 4) }}" min="1" max="12">
                            <small class="text-muted">Hours after shift start to mark absent</small>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_time_out" id="requireTimeOut" 
                               value="1" {{ old('require_time_out', $settings['require_time_out'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="requireTimeOut">Require Time Out</label>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-warning text-dark py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bell me-2"></i>Alert Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Late Alert Threshold (%)</label>
                            <input type="number" name="late_alert_threshold" class="form-control" 
                                   value="{{ old('late_alert_threshold', $settings['late_alert_threshold'] ?? 20) }}" min="5" max="50">
                            <small class="text-muted">Alert when late rate exceeds this %</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Absent Alert Threshold (%)</label>
                            <input type="number" name="absent_alert_threshold" class="form-control" 
                                   value="{{ old('absent_alert_threshold', $settings['absent_alert_threshold'] ?? 15) }}" min="5" max="50">
                            <small class="text-muted">Alert when absence rate exceeds this %</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Alert Recipients</label>
                            <input type="text" name="alert_emails" class="form-control" 
                                   value="{{ old('alert_emails', $settings['alert_emails'] ?? '') }}" placeholder="email1@example.com, email2@example.com">
                            <small class="text-muted">Comma-separated email addresses</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-info text-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Forecasting Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Alpha (Level)</label>
                            <input type="number" name="forecast_alpha" class="form-control" step="0.1" 
                                   value="{{ old('forecast_alpha', $settings['forecast_alpha'] ?? 0.3) }}" min="0" max="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Beta (Trend)</label>
                            <input type="number" name="forecast_beta" class="form-control" step="0.1" 
                                   value="{{ old('forecast_beta', $settings['forecast_beta'] ?? 0.1) }}" min="0" max="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gamma (Seasonal)</label>
                            <input type="number" name="forecast_gamma" class="form-control" step="0.1" 
                                   value="{{ old('forecast_gamma', $settings['forecast_gamma'] ?? 0.2) }}" min="0" max="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Forecast Horizon (days)</label>
                            <input type="number" name="forecast_horizon" class="form-control" 
                                   value="{{ old('forecast_horizon', $settings['forecast_horizon'] ?? 30) }}" min="7" max="90">
                        </div>
                    </div>
                    <div class="alert alert-light mb-0">
                        <small><strong>Note:</strong> Holt-Winters smoothing parameters control how the forecast responds to historical data. 
                        Lower values make the forecast more stable, higher values make it more responsive to recent changes.</small>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-secondary text-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-week me-2"></i>Leave Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Annual Sick Leave</label>
                            <input type="number" name="annual_sick_leave" class="form-control" 
                                   value="{{ old('annual_sick_leave', $settings['annual_sick_leave'] ?? 15) }}" min="0" max="30">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Annual Vacation Leave</label>
                            <input type="number" name="annual_vacation_leave" class="form-control" 
                                   value="{{ old('annual_vacation_leave', $settings['annual_vacation_leave'] ?? 15) }}" min="0" max="30">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Annual Emergency Leave</label>
                            <input type="number" name="annual_emergency_leave" class="form-control" 
                                   value="{{ old('annual_emergency_leave', $settings['annual_emergency_leave'] ?? 5) }}" min="0" max="15">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Annual Other Leave</label>
                            <input type="number" name="annual_other_leave" class="form-control" 
                                   value="{{ old('annual_other_leave', $settings['annual_other_leave'] ?? 5) }}" min="0" max="15">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save me-2"></i> Save Settings
                </button>
                <button type="reset" class="btn btn-secondary btn-lg">
                    <i class="bi bi-arrow-clockwise me-2"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
