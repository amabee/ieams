@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">

        <h4 class="fw-bold mb-3">My Profile</h4>

        {{-- ── Profile Information ── --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-circle me-2"></i>Profile Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                    @if(session('status') === 'profile-updated')
                    <span class="ms-2 text-success small"><i class="bi bi-check-circle me-1"></i>Saved successfully.</span>
                    @endif
                </form>
            </div>
        </div>

        {{-- ── Update Password ── --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Update Password</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input id="current_password" name="current_password" type="password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input id="password" name="password" type="password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-lock me-1"></i> Update Password
                    </button>
                    @if(session('status') === 'password-updated')
                    <span class="ms-2 text-success small"><i class="bi bi-check-circle me-1"></i>Password updated.</span>
                    @endif
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
