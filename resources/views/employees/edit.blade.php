@extends('layouts.app')
@section('title','Edit Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="card shadow-sm border-0" style="max-width:760px">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0 fw-semibold">Edit Employee — {{ $employee->full_name }}</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Employee No. <span class="text-danger">*</span></label>
                    <input type="text" name="employee_no" value="{{ old('employee_no', $employee->employee_no) }}" class="form-control @error('employee_no') is-invalid @enderror" required>
                    @error('employee_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $employee->middle_name) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Position <span class="text-danger">*</span></label>
                    <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="form-control @error('position') is-invalid @enderror" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Employment Type</label>
                    <select name="employment_type" class="form-select">
                        @foreach(['full_time','part_time','contractual'] as $type)
                        <option value="{{ $type }}" {{ old('employment_type', $employee->employment_type) == $type ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $employee->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Shift</label>
                    <select name="shift_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ old('shift_id', $employee->shift_id) == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->start_time }} – {{ $s->end_time }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Hire Date</label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Contact No.</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $employee->contact_no) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Address</label>
                    <input type="text" name="address" value="{{ old('address', $employee->address) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Profile Photo</label>
                    @if($employee->photo_path)
                    <div class="mb-1"><img src="{{ Storage::url($employee->photo_path) }}" style="height:48px" class="rounded"></div>
                    @endif
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
