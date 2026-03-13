@extends('layouts.app')
@section('title', 'Edit Position')
@section('content')
<div class="card shadow-sm border-0" style="max-width: 560px;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0 fw-semibold">Edit Position</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('positions.update', $position) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title', $position->title) }}"
                       class="form-control @error('title') is-invalid @enderror" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Department</label>
                <select name="department" class="form-select @error('department') is-invalid @enderror">
                    <option value="">— None —</option>
                    @foreach ([
                        'Management', 'Human Resources', 'Finance', 'Information Technology',
                        'Sales & Marketing', 'Operations', 'Logistics', 'Customer Service',
                        'Security', 'Veterinary Services', 'Grooming', 'Training'
                    ] as $dept)
                    <option value="{{ $dept }}" {{ old('department', $position->department) === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
                @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active"
                       value="1" {{ old('is_active', $position->is_active) ? 'checked' : '' }}>
                <label class="form-check-label small" for="is_active">Active</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Position</button>
                <a href="{{ route('positions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
