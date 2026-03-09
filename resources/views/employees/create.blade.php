@extends('layouts.app')
@section('title','Add Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
<li class="breadcrumb-item active">Add Employee</li>
@endsection
@section('content')
<div class="card shadow-sm border-0" style="max-width:760px">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0 fw-semibold">New Employee</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Employee No. <span class="text-danger">*</span></label>
                    <input type="text" name="employee_no" value="{{ old('employee_no') }}" class="form-control @error('employee_no') is-invalid @enderror" required>
                    @error('employee_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Position <span class="text-danger">*</span></label>
                    <input type="text" name="position" value="{{ old('position') }}" class="form-control @error('position') is-invalid @enderror" required>
                    @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Employment Type <span class="text-danger">*</span></label>
                    <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                        <option value="full_time" {{ old('employment_type') == 'full_time' ? 'selected' : '' }}>Full Time</option>
                        <option value="part_time" {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="contractual" {{ old('employment_type') == 'contractual' ? 'selected' : '' }}>Contractual</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">— Select Branch —</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Shift</label>
                    <select name="shift_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->start_time }} – {{ $s->end_time }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Hire Date <span class="text-danger">*</span></label>
                    <input type="date" name="hire_date" value="{{ old('hire_date') }}" class="form-control @error('hire_date') is-invalid @enderror" required>
                    @error('hire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Contact No.</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Profile Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
