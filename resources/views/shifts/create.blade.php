@extends('layouts.app')
@section('title','Add Shift')
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0 fw-semibold">New Shift</h6></div>
    <div class="card-body">
        <form action="{{ route('shifts.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Shift Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" class="form-control @error('start_time') is-invalid @enderror" required>
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" class="form-control @error('end_time') is-invalid @enderror" required>
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Late Threshold (minutes)</label>
                <input type="number" name="late_threshold_minutes" value="{{ old('late_threshold_minutes', 15) }}" class="form-control" min="0" max="120">
                <div class="form-text">Employees arriving this many minutes after shift start are marked Late.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Branch (leave empty for all)</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Shift</button>
                <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
