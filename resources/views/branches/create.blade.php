@extends('layouts.app')
@section('title','Add Branch')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
<li class="breadcrumb-item active">Add Branch</li>
@endsection
@section('content')
<div class="card shadow-sm border-0" style="max-width:640px">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0 fw-semibold">New Branch</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('branches.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Branch Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Address</label>
                <input type="text" name="address" value="{{ old('address') }}" class="form-control">
            </div>
            <div class="row g-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Contact No.</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Manager</label>
                <select name="manager_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($managers as $m)
                    <option value="{{ $m->id }}" {{ old('manager_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                <label class="form-check-label small" for="is_active">Active</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Branch</button>
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
