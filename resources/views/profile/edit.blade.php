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

        {{-- ── Appearance ── --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header py-3" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%)">
                <h6 class="mb-0 fw-semibold text-white"><i class="bi bi-display me-2"></i>Appearance &amp; UI</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.875rem">Choose which interface version to display. Saved to your session.</p>
                @php $currentVer = session('ui_version', 'v2'); @endphp
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-3 h-100 d-flex flex-column" style="{{ $currentVer === 'v1' ? 'border-color:#4f8ef7 !important;background:rgba(79,142,247,.04)' : '' }}">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:32px;height:32px;border-radius:8px;background:#1e293b;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="bi bi-layout-sidebar text-white" style="font-size:.9rem"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.875rem">Classic <span class="badge bg-secondary ms-1" style="font-size:.65rem">v1</span></div>
                                    <div class="text-muted" style="font-size:.75rem">Dark sidebar · Bootstrap style</div>
                                </div>
                                @if($currentVer === 'v1')
                                <span class="ms-auto badge" style="background:#4f8ef7;font-size:.65rem">Active</span>
                                @endif
                            </div>
                            <p class="text-muted mb-3" style="font-size:.78rem">Traditional admin panel layout with dark sidebar navigation and minimal card-based design.</p>
                            @if($currentVer !== 'v1')
                            <form action="{{ route('ui.version') }}" method="POST" class="mt-auto">
                                @csrf <input type="hidden" name="version" value="v1">
                                <button type="submit" class="btn btn-sm w-100" style="background:#1e293b;color:#fff;border-radius:8px">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Switch to Classic v1
                                </button>
                            </form>
                            @else
                            <div class="mt-auto text-center" style="font-size:.78rem;color:#4f8ef7;font-weight:600"><i class="bi bi-check-circle me-1"></i>Currently active</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-3 h-100 d-flex flex-column" style="{{ $currentVer === 'v2' ? 'border-color:#696cff !important;background:rgba(105,108,255,.04)' : '' }}">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:32px;height:32px;border-radius:8px;background:#696cff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="bi bi-stars text-white" style="font-size:.9rem"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.875rem">Modern <span class="badge bg-primary ms-1" style="font-size:.65rem">v2</span></div>
                                    <div class="text-muted" style="font-size:.75rem">Sneat · Fully featured</div>
                                </div>
                                @if($currentVer === 'v2')
                                <span class="ms-auto badge bg-primary" style="font-size:.65rem">Active</span>
                                @endif
                            </div>
                            <p class="text-muted mb-3" style="font-size:.78rem">Feature-rich modern UI with theme customizer, dark mode, collapsible sidebar, and advanced styling.</p>
                            @if($currentVer !== 'v2')
                            <form action="{{ route('ui.version') }}" method="POST" class="mt-auto">
                                @csrf <input type="hidden" name="version" value="v2">
                                <button type="submit" class="btn btn-primary btn-sm w-100" style="border-radius:8px">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Switch to Modern v2
                                </button>
                            </form>
                            @else
                            <div class="mt-auto text-center" style="font-size:.78rem;color:#696cff;font-weight:600"><i class="bi bi-check-circle me-1"></i>Currently active</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
