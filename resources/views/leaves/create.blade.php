@extends('layouts.app')
@section('title','Request Leave')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Request Leave</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('leaves.store') }}">
                    @csrf
                    
                    @if($leaveBalance)
                    <div class="alert alert-info mb-4">
                        <strong>Your Leave Balance:</strong>
                        <div class="row mt-2">
                            <div class="col-3"><small>Sick: <strong>{{ $leaveBalance->sick_leave_balance }}</strong></small></div>
                            <div class="col-3"><small>Vacation: <strong>{{ $leaveBalance->vacation_leave_balance }}</strong></small></div>
                            <div class="col-3"><small>Emergency: <strong>{{ $leaveBalance->emergency_leave_balance }}</strong></small></div>
                            <div class="col-3"><small>Other: <strong>{{ $leaveBalance->other_leave_balance }}</strong></small></div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select @error('leave_type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="sick" {{ old('leave_type')=='sick'?'selected':'' }}>Sick Leave</option>
                            <option value="vacation" {{ old('leave_type')=='vacation'?'selected':'' }}>Vacation Leave</option>
                            <option value="emergency" {{ old('leave_type')=='emergency'?'selected':'' }}>Emergency Leave</option>
                            <option value="maternity" {{ old('leave_type')=='maternity'?'selected':'' }}>Maternity Leave</option>
                            <option value="paternity" {{ old('leave_type')=='paternity'?'selected':'' }}>Paternity Leave</option>
                            <option value="unpaid" {{ old('leave_type')=='unpaid'?'selected':'' }}>Unpaid Leave</option>
                            <option value="other" {{ old('leave_type')=='other'?'selected':'' }}>Other</option>
                        </select>
                        @error('leave_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                            @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                            @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" 
                                  rows="4" placeholder="Please provide a reason for your leave request" required>{{ old('reason') }}</textarea>
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Provide as much detail as possible for your leave request.</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Note:</strong> Your leave request will be subject to approval by your manager or HR. 
                        You will be notified once your request is reviewed.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Submit Request
                        </button>
                        <a href="{{ route('leaves.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light py-3">
                <h6 class="mb-0 fw-semibold">Leave Request Guidelines</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li>Submit leave requests at least 3 days in advance for non-emergency leaves</li>
                    <li>Emergency and sick leaves can be filed on the same day with proper documentation</li>
                    <li>Maternity/Paternity leaves require at least 30 days advance notice</li>
                    <li>Ensure you have sufficient leave balance before submitting</li>
                    <li>Contact your manager if you need urgent leave approval</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
