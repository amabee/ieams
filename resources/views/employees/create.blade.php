@extends('layouts.app')
@section('title', 'Add Employee')
@section('content')

<div class="row g-4">

    {{-- Photo / avatar column --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold">Profile Photo</h6>
            </div>
            <div class="card-body text-center">
                <div id="photoPreview" class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:96px;height:96px;font-size:2rem;overflow:hidden">
                    <i class="bi bi-person"></i>
                </div>
                <label class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-upload me-1"></i> Upload Photo
                    <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" form="employeeForm">
                </label>
                <p class="text-muted small mt-2 mb-0">JPG, PNG. Max 2 MB.</p>
            </div>
        </div>
    </div>

    {{-- Main form column --}}
    <div class="col-md-9">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-plus me-1"></i> New Employee</h6>
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body">
                <form id="employeeForm" action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Section: Basic Information --}}
                    <p class="text-muted small fw-semibold text-uppercase mb-2">Basic Information</p>
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
                            <label class="form-label fw-semibold small">Birthdate</label>
                            <input type="date" name="birthdate" value="{{ old('birthdate') }}" class="form-control @error('birthdate') is-invalid @enderror">
                            @error('birthdate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">— Select —</option>
                                <option value="male"   {{ old('gender') == 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other"  {{ old('gender') == 'other'  ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Civil Status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">— Select —</option>
                                @foreach(['single'=>'Single','married'=>'Married','widowed'=>'Widowed','divorced'=>'Divorced','separated'=>'Separated'] as $val => $label)
                                <option value="{{ $val }}" {{ old('civil_status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Contact No.</label>
                            <input type="text" name="contact_no" value="{{ old('contact_no') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Hire Date <span class="text-danger">*</span></label>
                            <input type="date" name="hire_date" value="{{ old('hire_date') }}" class="form-control @error('hire_date') is-invalid @enderror" required>
                            @error('hire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Section: Employment Details --}}
                    <p class="text-muted small fw-semibold text-uppercase mb-2">Employment Details</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Position <span class="text-danger">*</span></label>
                            <select name="position_id" class="form-select @error('position_id') is-invalid @enderror" required>
                                <option value="">— Select Position —</option>
                                @foreach($positions as $dept => $group)
                                <optgroup label="{{ $dept }}">
                                    @foreach($group as $pos)
                                    <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>{{ $pos->title }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            @error('position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                                <option value="full_time"   {{ old('employment_type') == 'full_time'   ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time"   {{ old('employment_type') == 'part_time'   ? 'selected' : '' }}>Part Time</option>
                                <option value="contractual" {{ old('employment_type') == 'contractual' ? 'selected' : '' }}>Contractual</option>
                            </select>
                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Basic Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="basic_salary" value="{{ old('basic_salary') }}" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" placeholder="0.00">
                                @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
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
                    </div>

                    <hr class="my-4">

                    {{-- Section: Government IDs --}}
                    <p class="text-muted small fw-semibold text-uppercase mb-2">Government IDs</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">SSS No.</label>
                            <input type="text" name="sss_no" value="{{ old('sss_no') }}" class="form-control" placeholder="XX-XXXXXXX-X">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PhilHealth No.</label>
                            <input type="text" name="philhealth_no" value="{{ old('philhealth_no') }}" class="form-control" placeholder="XX-XXXXXXXXX-X">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Pag-IBIG No.</label>
                            <input type="text" name="pagibig_no" value="{{ old('pagibig_no') }}" class="form-control" placeholder="XXXX-XXXX-XXXX">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">TIN No.</label>
                            <input type="text" name="tin_no" value="{{ old('tin_no') }}" class="form-control" placeholder="XXX-XXX-XXX">
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Section: System Account --}}
                    <p class="text-muted small fw-semibold text-uppercase mb-2">System Account <span class="fw-normal">(Optional)</span></p>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="create_account" id="createAccount" value="1" {{ old('create_account') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="createAccount">Create login account for this employee</label>
                        </div>
                    </div>
                    <div id="accountFields" style="{{ old('create_account') ? '' : 'display:none' }}">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> A secure password will be auto-generated and emailed to the employee.
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                                <input type="email" name="account_email" value="{{ old('account_email') }}" class="form-control @error('account_email') is-invalid @enderror">
                                @error('account_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Role <span class="text-danger">*</span></label>
                                <select name="account_role" class="form-select @error('account_role') is-invalid @enderror">
                                    <option value="">— Select Role —</option>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('account_role') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                                @error('account_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Employee</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.getElementById('photoInput').addEventListener('change', function () {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        var preview = document.getElementById('photoPreview');
        preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
    };
    reader.readAsDataURL(file);
});
document.getElementById('createAccount').addEventListener('change', function () {
    document.getElementById('accountFields').style.display = this.checked ? '' : 'none';
});
</script>
@endpush
@endsection

