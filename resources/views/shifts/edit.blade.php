@extends('layouts.app')
@section('title','Edit Shift')
@section('content')
<div class="card shadow-sm border-0" style="max-width:540px">
    <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0 fw-semibold">Edit Shift — {{ $shift->name }}</h6></div>
    <div class="card-body">
        <form action="{{ route('shifts.update', $shift) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold small">Shift Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $shift->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" value="{{ old('start_time', $shift->start_time) }}" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" value="{{ old('end_time', $shift->end_time) }}" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Late Threshold (minutes)</label>
                <input type="number" name="late_threshold_minutes" value="{{ old('late_threshold_minutes', $shift->late_threshold_minutes) }}" class="form-control" min="0" max="120">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('branch_id', $shift->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Shift</button>
                <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
