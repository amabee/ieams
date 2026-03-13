@php
    // Global UI version — set by superadmin in System Settings, stored in DB.
    // Synced to session so sub-views using session('ui_version') work correctly.
    $uiV = \App\Models\SystemSetting::get('ui_version', 'v2');
    session(['ui_version' => $uiV]);
@endphp
@if($uiV === 'v1')
@php $v1Unread = auth()->user()->unreadNotifications->count(); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — IEAMS Classic</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @stack('styles')
    <style>
        :root {
            --v1-sidebar-bg: #1e293b;
            --v1-accent:     #4f8ef7;
            --v1-accent-dk:  #3b78e7;
            --v1-sidebar-w:  248px;
            --v1-topbar-h:   58px;
            --v1-body-bg:    #f1f5f9;
            --v1-tr:         all .18s cubic-bezier(.4,0,.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--v1-body-bg); color: #1e293b; font-size: .9rem; }

        /* ── SIDEBAR ── */
        .v1-sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--v1-sidebar-w);
            background: var(--v1-sidebar-bg);
            z-index: 1030;
            display: flex; flex-direction: column;
            overflow-y: auto; overflow-x: hidden;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }
        .v1-sidebar::-webkit-scrollbar { width: 3px; }
        .v1-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        .v1-logo {
            display: flex; align-items: center; gap: .75rem;
            padding: 0 1.25rem; min-height: var(--v1-topbar-h);
            border-bottom: 1px solid rgba(255,255,255,.06);
            text-decoration: none; flex-shrink: 0;
        }
        .v1-logo-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: var(--v1-accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #fff; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(79,142,247,.35);
        }
        .v1-logo-text { color: #f1f5f9; font-weight: 700; font-size: 1.05rem; letter-spacing: .2px; line-height: 1.2; }
        .v1-logo-ver  { color: var(--v1-accent); font-size: .6rem; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }

        .v1-nav-label {
            padding: 1rem 1.25rem .3rem;
            font-size: .63rem; font-weight: 700;
            color: rgba(148,163,184,.5);
            text-transform: uppercase; letter-spacing: 1.1px;
        }
        .v1-nav { list-style: none; padding: 0; margin: 0; padding-bottom: 1rem; }
        .v1-nav li a {
            display: flex; align-items: center; gap: .6rem;
            padding: .5rem 1.25rem;
            font-size: .845rem; color: #94a3b8;
            text-decoration: none;
            border-left: 3px solid transparent;
            margin: 1px 4px 1px 0;
            border-radius: 0 8px 8px 0;
            transition: var(--v1-tr);
            min-height: 42px;
            position: relative; overflow: hidden;
        }
        .v1-nav li a i { font-size: 1rem; width: 1.1rem; text-align: center; flex-shrink: 0; }
        .v1-nav li a:hover { background: rgba(255,255,255,.07); color: #e2e8f0; border-left-color: rgba(79,142,247,.3); }
        .v1-nav li a.active { background: rgba(79,142,247,.14); color: var(--v1-accent); border-left-color: var(--v1-accent); font-weight: 600; }
        /* Nav ripple */
        .v1-nav li a::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.06); transform: scale(0); border-radius: 50%; transition: transform .4s ease, opacity .4s ease; opacity: 0; }
        .v1-nav li a:active::after { transform: scale(4); opacity: 1; transition: 0s; }

        /* ── TOPBAR ── */
        .v1-topbar {
            position: fixed; top: 0; left: var(--v1-sidebar-w); right: 0;
            height: var(--v1-topbar-h);
            background: #fff; border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 8px rgba(0,0,0,.06);
            z-index: 1020;
            display: flex; align-items: center; gap: .75rem;
            padding: 0 1.5rem;
            transition: left .3s cubic-bezier(.4,0,.2,1);
        }
        .v1-topbar-toggle {
            width: 36px; height: 36px; border-radius: 8px;
            border: none; background: transparent; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #64748b;
            transition: var(--v1-tr);
        }
        .v1-topbar-toggle:hover { background: #f1f5f9; color: #1e293b; }
        .v1-topbar-title { flex: 1; font-weight: 600; font-size: 1rem; color: #1e293b; }

        .v1-topbar-btn {
            width: 36px; height: 36px; border-radius: 9px;
            border: none; background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; color: #64748b;
            position: relative; text-decoration: none;
            transition: var(--v1-tr);
        }
        .v1-topbar-btn:hover { background: #f1f5f9; color: #1e293b; }

        .v1-ver-pill {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .65rem; border-radius: 20px;
            font-size: .7rem; font-weight: 700; letter-spacing: .3px;
            cursor: pointer;
            border: 1.5px solid var(--v1-accent); color: var(--v1-accent);
            background: rgba(79,142,247,.08);
            transition: var(--v1-tr); white-space: nowrap;
        }
        .v1-ver-pill:hover { background: var(--v1-accent); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79,142,247,.3); }

        /* ── SHELL ── */
        .v1-wrap { margin-left: var(--v1-sidebar-w); padding-top: var(--v1-topbar-h); min-height: 100vh; display: flex; flex-direction: column; transition: margin-left .3s cubic-bezier(.4,0,.2,1); }
        .v1-content { flex: 1; padding: 1.5rem; }
        .v1-footer { text-align: center; padding: .85rem; font-size: .75rem; color: #94a3b8; border-top: 1px solid #e2e8f0; background: #fff; }

        /* ── CARDS ── */
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.05), 0 4px 16px rgba(0,0,0,.04); transition: transform .2s, box-shadow .2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.1); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: .9rem 1.25rem; font-weight: 600; border-radius: 12px 12px 0 0 !important; }
        .card-body { padding: 1.25rem; }

        /* ── TABLES ── */
        .table { font-size: .85rem; }
        .table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
        .table tbody tr { transition: background .15s; }
        .table tbody tr:hover { background: rgba(79,142,247,.04) !important; }

        /* ── BUTTONS ── */
        .btn { border-radius: 8px; font-weight: 500; font-size: .855rem; transition: var(--v1-tr); }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.12); }
        .btn:active { transform: translateY(0); box-shadow: none; }
        .btn-primary { background: var(--v1-accent); border-color: var(--v1-accent); }
        .btn-primary:hover { background: var(--v1-accent-dk); border-color: var(--v1-accent-dk); }
        /* Ripple */
        .btn { overflow: hidden; position: relative; }
        .btn::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.2); transform: scale(0); border-radius: 50%; transition: transform .4s ease, opacity .4s ease; opacity: 0; }
        .btn:active::after { transform: scale(3); opacity: 1; transition: 0s; }

        /* ── FORMS ── */
        .form-control, .form-select { border-radius: 8px; border-color: #e2e8f0; font-size: .875rem; transition: border-color .18s, box-shadow .18s; }
        .form-control:focus, .form-select:focus { border-color: var(--v1-accent); box-shadow: 0 0 0 .2rem rgba(79,142,247,.18); }

        /* ── BADGES & ALERTS ── */
        .badge { border-radius: 6px; font-size: .7rem; font-weight: 600; }
        .alert { border: none; border-radius: 10px; border-left: 4px solid; }
        .alert-success { border-left-color: #22c55e; background: #f0fdf4; color: #166534; }
        .alert-danger  { border-left-color: #ef4444; background: #fef2f2; color: #991b1b; }

        /* ── DROPDOWN MENU ── */
        .dropdown-menu { border: none; box-shadow: 0 10px 40px rgba(0,0,0,.12); border-radius: 12px; font-size: .855rem; }
        .dropdown-item { border-radius: 6px; margin: 1px 6px; padding: .45rem .75rem; transition: background .15s; }
        .dropdown-item:hover { background: #f1f5f9; }

        /* ── OVERLAY (mobile) ── */
        .v1-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1028; backdrop-filter: blur(1px); }
        .v1-overlay.open { display: block; }

        /* ── DataTables ── */
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link { background-color: var(--v1-accent) !important; border-color: var(--v1-accent) !important; }
        .dataTables_wrapper .dataTables_paginate .page-item:not(.disabled):not(.active) .page-link:hover { background-color: rgba(79,142,247,.08) !important; color: var(--v1-accent) !important; }

        /* ── RESPONSIVE ── */
        @media (max-width: 991.98px) {
            .v1-sidebar { transform: translateX(-100%); }
            .v1-sidebar.open { transform: translateX(0); }
            .v1-topbar { left: 0; }
            .v1-wrap { margin-left: 0; }
            .v1-topbar-toggle { display: flex !important; }
        }

        /* ── SNEAT CLASS POLYFILLS (so other pages render cleanly) ── */
        .bg-label-primary   { background: rgba(79,142,247,.12) !important; color: #2563eb !important; border-radius: 6px; }
        .bg-label-success   { background: rgba(34,197,94,.12)  !important; color: #16a34a !important; border-radius: 6px; }
        .bg-label-warning   { background: rgba(245,158,11,.18) !important; color: #b45309 !important; border-radius: 6px; }
        .bg-label-danger    { background: rgba(239,68,68,.12)  !important; color: #dc2626 !important; border-radius: 6px; }
        .bg-label-info      { background: rgba(59,130,246,.12) !important; color: #0369a1 !important; border-radius: 6px; }
        .bg-label-secondary { background: rgba(100,116,139,.12)!important; color: #475569 !important; border-radius: 6px; }
        .btn-label-primary  { background: rgba(79,142,247,.12) !important; color: #2563eb !important; border: none; }
        .btn-label-success  { background: rgba(34,197,94,.12)  !important; color: #16a34a !important; border: none; }
        .btn-label-warning  { background: rgba(245,158,11,.18) !important; color: #b45309 !important; border: none; }
        .btn-label-danger   { background: rgba(239,68,68,.12)  !important; color: #dc2626 !important; border: none; }
        .btn-label-info     { background: rgba(59,130,246,.12) !important; color: #0369a1 !important; border: none; }
        .bg-label-pink  { background: rgba(236,72,153,.12)  !important; color: #be185d !important; border-radius: 6px; }
        .btn-label-secondary { background: rgba(100,116,139,.12) !important; color: #475569 !important; border: none; }
        .avatar      { display: inline-flex; width: 36px; height: 36px; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .avatar-sm   { width: 28px; height: 28px; border-radius: 6px; }
        .avatar-md   { width: 42px; height: 42px; border-radius: 8px; }
        .avatar-lg   { width: 54px; height: 54px; border-radius: 10px; font-size: 1.1rem; }
        .avatar-xl   { width: 70px; height: 70px; border-radius: 12px; font-size: 1.4rem; }
        .avatar-online::before { content: ''; position: absolute; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; border: 2px solid #fff; bottom: 0; right: 0; }
        .avatar-initial { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; font-weight: 700; font-size: .82rem; }
        .badge-present  { background: rgba(34,197,94,.15);  color: #16a34a; }
        .badge-late     { background: rgba(245,158,11,.2);  color: #b45309; }
        .badge-absent   { background: rgba(239,68,68,.15);  color: #dc2626; }
        .badge-on_leave { background: rgba(59,130,246,.15); color: #0369a1; }
        /* Suppress dashboard.css gradient card in V1 */
        .welcome-card { background: var(--v1-accent) !important; }
    </style>
</head>
<body>

<div class="v1-overlay" id="v1Overlay"></div>

{{-- ═══ SIDEBAR ═══ --}}
<aside class="v1-sidebar" id="v1Sidebar">
    <a href="{{ route('dashboard') }}" class="v1-logo">
        <div class="v1-logo-icon"><i class="bi bi-activity"></i></div>
        <div><div class="v1-logo-text">IEAMS</div><div class="v1-logo-ver">Classic v1</div></div>
    </a>

    <ul class="v1-nav pt-2">

        <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-house-door"></i><span>Dashboard</span>
        </a></li>

        @hasanyrole(['branch_manager','admin','superadmin'])
        <li><div class="v1-nav-label">Management</div></li>
        <li><a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i><span>Branches</span>
        </a></li>
        @endhasanyrole

        @can('view positions')
        <li><a href="{{ route('positions.index') }}" class="{{ request()->routeIs('positions.*') ? 'active' : '' }}">
            <i class="bi bi-briefcase"></i><span>Positions</span>
        </a></li>
        @endcan

        @hasanyrole(['hr','admin','superadmin'])
        <li><a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i><span>Employees</span>
        </a></li>
        @endhasanyrole

        <li><div class="v1-nav-label">Attendance</div></li>
        <li><a href="{{ route('attendance.record') }}" class="{{ request()->routeIs('attendance.record') ? 'active' : '' }}">
            <i class="bi bi-clock"></i><span>Time In/Out</span>
        </a></li>

        @hasanyrole(['branch_manager','hr','admin','superadmin'])
        <li><a href="{{ route('attendance.monitor') }}" class="{{ request()->routeIs('attendance.monitor') ? 'active' : '' }}">
            <i class="bi bi-display"></i><span>Monitor</span>
        </a></li>
        <li><a href="{{ route('attendance.manage') }}" class="{{ request()->routeIs('attendance.manage') ? 'active' : '' }}">
            <i class="bi bi-pencil-square"></i><span>Manage</span>
        </a></li>
        <li>
            @php $v1PendingCorr = \App\Models\AttendanceCorrection::where('status','pending')->count(); @endphp
            <a href="{{ route('attendance.corrections') }}" class="{{ request()->routeIs('attendance.corrections') ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat"></i><span>Corrections</span>
                @if($v1PendingCorr)<span class="badge bg-warning text-dark ms-auto">{{ $v1PendingCorr }}</span>@endif
            </a>
        </li>
        @endhasanyrole

        <li><div class="v1-nav-label">Leaves</div></li>
        <li><a href="{{ route('leaves.index') }}" class="{{ request()->routeIs('leaves.index') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i><span>My Leaves</span>
        </a></li>
        <li><a href="{{ route('leaves.create') }}" class="{{ request()->routeIs('leaves.create') ? 'active' : '' }}">
            <i class="bi bi-calendar-plus"></i><span>Request Leave</span>
        </a></li>

        @hasanyrole(['hr','admin','superadmin'])
        <li><div class="v1-nav-label">Reports</div></li>
        <li><a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span>
        </a></li>
        <li><a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i><span>Analytics</span>
        </a></li>
        <li><a href="{{ route('forecasting.index') }}" class="{{ request()->routeIs('forecasting.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i><span>Forecasting</span>
        </a></li>
        @endhasanyrole

        @hasanyrole(['admin','superadmin'])
        <li><div class="v1-nav-label">System</div></li>
        <li><a href="{{ route('shifts.index') }}" class="{{ request()->routeIs('shifts.*') ? 'active' : '' }}">
            <i class="bi bi-stopwatch"></i><span>Shifts</span>
        </a></li>
        <li><a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i><span>Notifications</span>
        </a></li>
        <li><a href="{{ route('audit-logs.index') }}" class="{{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i><span>Audit Logs</span>
        </a></li>
        <li><a href="{{ route('backups.index') }}" class="{{ request()->routeIs('backups.*') ? 'active' : '' }}">
            <i class="bi bi-server"></i><span>Backups</span>
        </a></li>
        @endhasanyrole

        @hasrole('superadmin')
        <li><div class="v1-nav-label">Administration</div></li>
        <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i><span>Users</span>
        </a></li>
        <li><a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a></li>
        @endhasrole

        <li><div class="v1-nav-label">Account</div></li>
        <li><a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i><span>My Profile</span>
        </a></li>

    </ul>
</aside>

{{-- ═══ MAIN WRAPPER ═══ --}}
<div class="v1-wrap" id="v1Main">

    {{-- TOP BAR --}}
    <header class="v1-topbar">
        <button class="v1-topbar-toggle" id="v1SidebarBtn" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>

        <div class="v1-topbar-title">@yield('title', 'Dashboard')</div>

        <div class="d-flex align-items-center gap-2">

            {{-- Notifications --}}
            <div class="dropdown">
                <button class="v1-topbar-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell"></i>
                    @if($v1Unread > 0)
                    <span id="v1NotifBadge" class="position-absolute badge bg-danger rounded-pill" style="font-size:.55rem;top:3px;right:3px;padding:.2em .35em;min-width:16px">{{ $v1Unread > 9 ? '9+' : $v1Unread }}</span>
                    @else
                    <span id="v1NotifBadge" class="position-absolute badge bg-danger rounded-pill" style="font-size:.55rem;top:3px;right:3px;padding:.2em .35em;min-width:16px;display:none">0</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-0" style="width:300px;border-radius:14px;overflow:hidden">
                    <li class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid #f1f5f9;background:#fafbfc">
                        <span style="font-size:.82rem;font-weight:700;color:#1e293b">Notifications</span>
                        @if($v1Unread > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">@csrf
                            <button class="btn btn-sm py-0 px-2" style="font-size:.7rem;background:#f1f5f9;border-radius:6px;border:none">Mark all read</button>
                        </form>
                        @endif
                    </li>
                    <div style="max-height:290px;overflow-y:auto">
                        @forelse(auth()->user()->unreadNotifications->take(5) as $notif)
                        <li>
                            <a class="dropdown-item px-3 py-2" href="{{ route('notifications.index') }}" style="white-space:normal;border-bottom:1px solid #f8fafc">
                                <div style="font-size:.8rem;font-weight:600;color:#1e293b">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $notif->data['message'] ?? '' }}</div>
                                <div class="text-muted" style="font-size:.7rem">{{ $notif->created_at->diffForHumans() }}</div>
                            </a>
                        </li>
                        @empty
                        <li class="text-center py-4" style="color:#94a3b8;font-size:.82rem">
                            <i class="bi bi-bell-slash d-block fs-4 mb-1"></i>All caught up!
                        </li>
                        @endforelse
                    </div>
                    <li style="border-top:1px solid #f1f5f9">
                        <a class="dropdown-item text-center py-2" style="font-size:.8rem;color:var(--v1-accent);font-weight:600" href="{{ route('notifications.index') }}">View all notifications</a>
                    </li>
                </ul>
            </div>

            {{-- User menu --}}
            <div class="dropdown">
                <button class="v1-topbar-btn d-flex align-items-center gap-2" style="width:auto;padding:.2rem .5rem;border-radius:10px" data-bs-toggle="dropdown">
                    <div style="width:30px;height:30px;border-radius:8px;background:var(--v1-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="d-none d-md-block text-start" style="line-height:1.2">
                        <div style="font-size:.8rem;font-weight:600;color:#1e293b">{{ auth()->user()->name }}</div>
                        <div style="font-size:.68rem;color:#94a3b8;text-transform:capitalize">{{ auth()->user()->roles->pluck('name')->first() ?? 'Employee' }}</div>
                    </div>
                    <i class="bi bi-chevron-down d-none d-md-inline" style="font-size:.65rem;color:#94a3b8"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:185px">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2 text-muted"></i>My Profile</a></li>
                    @hasrole('superadmin')
                    <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}"><i class="bi bi-gear me-2 text-muted"></i>Settings</a></li>
                    @endhasrole
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log Out</button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>
    </header>

    {{-- CONTENT --}}
    <main class="v1-content">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="v1-footer">
        &copy; {{ date('Y') }} IEAMS &mdash; Employee Attendance Management System &mdash;
        <span style="color:var(--v1-accent);font-weight:600">Classic v1</span>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
<script>
(function () {
    var sidebarBtn = document.getElementById('v1SidebarBtn');
    var sidebar    = document.getElementById('v1Sidebar');
    var overlay    = document.getElementById('v1Overlay');
    if (sidebarBtn) {
        sidebarBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }
    setTimeout(function () {
        document.querySelectorAll('.alert:not([data-no-autodismiss])').forEach(function (a) {
            try { new bootstrap.Alert(a).close(); } catch (e) {}
        });
    }, 5000);
}());
</script>
@auth
<script>
(function () {
    var POLL_URL  = '{{ route('notifications.poll') }}';
    var INTERVAL  = {{ config('notifications.poll_interval', 5000) }};
    var CSRF      = '{{ csrf_token() }}';
    var lastCount = {{ auth()->user()->unreadNotifications()->count() }};

    function updateBadge(count) {
        var badge = document.getElementById('v1NotifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    function poll() {
        fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.unread !== lastCount) {
                    lastCount = data.unread;
                    updateBadge(data.unread);
                }
            }).catch(function () {});
    }

    setInterval(poll, INTERVAL);
}());
</script>
@endauth

</body>
</html>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/') }}/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'IEAMS') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <!-- Bootstrap Icons (for page compatibility) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <!-- Custom compatibility styles for existing pages -->
    <style>
        .stat-card { border: none !important; border-radius: 0.5rem; }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .table-responsive-stack tr td:first-child { font-weight: 600; }
        @media (max-width: 575.98px) {
            .table-responsive-stack thead { display: none; }
            .table-responsive-stack tr { display: block; margin-bottom: 1rem; border: 1px solid #dee2e6; border-radius: .375rem; }
            .table-responsive-stack td { display: flex; justify-content: space-between; padding: .5rem .75rem; border: 0; border-bottom: 1px solid #f0f0f0; }
            .table-responsive-stack td::before { content: attr(data-label); font-weight: 600; margin-right: 1rem; }
        }

        /* ===== Sidebar Collapse — Sneat Pro style ===== */
        #layout-menu { transition: width 0.35s ease !important; }
        .layout-page  { transition: padding-left 0.35s ease; }

        /* Navbar toggle button (desktop) */
        .sidebar-toggler {
            cursor: pointer;
            color: #697a8d;
            font-size: 1.375rem;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            margin-right: 0.75rem;
            transition: color 0.2s ease;
        }
        .sidebar-toggler:hover { color: #566a7f; }

        /* ---- COLLAPSED (desktop only) ---- */
        @media (min-width: 1200px) {
            html.layout-menu-collapsed #layout-menu {
                width: 6rem !important;
                overflow: visible !important;
            }
            /* Clip the scrollable inner list */
            html.layout-menu-collapsed #layout-menu .menu-inner,
            html.layout-menu-collapsed #layout-menu .ps {
                overflow: hidden !important;
            }
            /* Shift content area */
            html.layout-menu-collapsed.layout-menu-fixed .layout-page {
                padding-left: 6rem !important;
            }

            /* ---- icon-only strip (collapsed, not hovering) ---- */

            /* KEY FIX: Sneat sets width:16.25rem on menu-item/header — override to strip width */
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-inner > .menu-item,
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-block,
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-inner > .menu-header {
                width: 6rem !important;
            }
            /* Hide labels, section headers, submenus */
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu [data-i18n],
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-header,
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand-text,
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-sub {
                display: none !important;
            }
            /* Align icon within the 6rem strip */
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-link {
                width: 6rem !important;
                padding-left: 1.4rem !important;
                padding-right: 0.5rem !important;
                justify-content: flex-start !important;
            }
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .menu-icon {
                margin-right: 0 !important;
                font-size: 1.25rem !important;
                min-width: 1.875rem !important;
                text-align: center;
            }
            /* Center app logo when collapsed */
            html.layout-menu-collapsed:not(.layout-menu-hover) #layout-menu .app-brand-link {
                margin: 0 auto !important;
            }

            /* ---- HOVER EXPAND: full menu as overlay ---- */
            html.layout-menu-collapsed.layout-menu-hover #layout-menu {
                width: 16.25rem !important;
                overflow: hidden !important;
                box-shadow: 0.375rem 0 1.5rem rgba(34,48,74,0.15);
                z-index: 1200 !important;
            }
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">
    @stack('styles')
    <style>
        /* DataTables Bootstrap5: active pagination follows customizer primary color */
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link,
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link:hover {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: #fff !important;
        }
        .dataTables_wrapper .dataTables_paginate .page-item:not(.disabled):not(.active) .page-link:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
            border-color: transparent !important;
            color: var(--bs-primary) !important;
        }
        /* Fallback for non-Bootstrap DataTables rendering */
        .dataTables_wrapper .paginate_button.current,
        .dataTables_wrapper .paginate_button.current:hover {
            background: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    @php
        $unread = auth()->user()->unreadNotifications->count();
    @endphp

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="{{ route('dashboard') }}" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <i class="bx bx-pulse text-primary" style="font-size: 2rem;"></i>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">IEAMS</span>
                    </a>

                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>

                    @hasanyrole(['branch_manager', 'admin', 'superadmin'])
                    <!-- Branches -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                        <a href="{{ route('branches.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-buildings"></i>
                            <div data-i18n="Branches">Branches</div>
                        </a>
                    </li>
                    @endhasanyrole

                    @can('view positions')
                    <!-- Positions -->
                    <li class="menu-item {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                        <a href="{{ route('positions.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-briefcase"></i>
                            <div data-i18n="Positions">Positions</div>
                        </a>
                    </li>
                    @endcan

                    @hasanyrole(['hr', 'admin', 'superadmin'])
                    <!-- Employees -->
                    <li class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Employees">Employees</div>
                        </a>
                    </li>
                    @endhasanyrole

                    <!-- Attendance -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Attendance</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('attendance.record') ? 'active open' : '' }}">
                        <a href="{{ route('attendance.record') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Time In/Out">Time In/Out</div>
                        </a>
                    </li>

                    @hasanyrole(['branch_manager', 'hr', 'admin', 'superadmin'])
                    <li class="menu-item {{ request()->routeIs('attendance.monitor') ? 'active' : '' }}">
                        <a href="{{ route('attendance.monitor') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-desktop"></i>
                            <div data-i18n="Monitor">Monitor</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('attendance.manage') ? 'active' : '' }}">
                        <a href="{{ route('attendance.manage') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-edit"></i>
                            <div data-i18n="Manage">Manage</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('attendance.corrections') ? 'active' : '' }}">
                        <a href="{{ route('attendance.corrections') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-revision"></i>
                            <div data-i18n="Corrections">Corrections</div>
                            @php $pendingCorr = \App\Models\AttendanceCorrection::where('status','pending')->count(); @endphp
                            @if($pendingCorr)
                            <div class="badge bg-warning text-dark rounded-pill ms-auto">{{ $pendingCorr }}</div>
                            @endif
                        </a>
                    </li>
                    @endhasanyrole

                    <!-- Leaves -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Leave Management</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-calendar"></i>
                            <div data-i18n="Leaves">Leaves</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('leaves.index') ? 'active' : '' }}">
                                <a href="{{ route('leaves.index') }}" class="menu-link">
                                    <div data-i18n="My Leaves">My Leaves</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('leaves.create') ? 'active' : '' }}">
                                <a href="{{ route('leaves.create') }}" class="menu-link">
                                    <div data-i18n="Request Leave">Request Leave</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    @hasanyrole(['hr', 'admin', 'superadmin'])
                    <!-- Reports & Analytics -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Reports</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <a href="{{ route('reports.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-file"></i>
                            <div data-i18n="Reports">Reports</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                        <a href="{{ route('analytics.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-line-chart"></i>
                            <div data-i18n="Analytics">Analytics</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('forecasting.*') ? 'active' : '' }}">
                        <a href="{{ route('forecasting.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-trending-up"></i>
                            <div data-i18n="Forecasting">Forecasting</div>
                        </a>
                    </li>
                    @endhasanyrole

                    @hasanyrole(['admin', 'superadmin'])
                    <!-- System -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">System</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                        <a href="{{ route('shifts.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-timer"></i>
                            <div data-i18n="Shifts">Shifts</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <a href="{{ route('notifications.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-bell"></i>
                            <div data-i18n="Notifications">Notifications</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                        <a href="{{ route('audit-logs.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-history"></i>
                            <div data-i18n="Audit Logs">Audit Logs</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                        <a href="{{ route('backups.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-data"></i>
                            <div data-i18n="Backups">Backups</div>
                        </a>
                    </li>
                    @endhasanyrole

                    @hasrole('superadmin')
                    <!-- Admin -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Administration</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.users.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-check"></i>
                            <div data-i18n="User Management">Users</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-cog"></i>
                            <div data-i18n="Settings">Settings</div>
                        </a>
                    </li>
                    @endhasrole
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <!-- Mobile hamburger -->
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Desktop sidebar toggle -->
                        <button id="sidebarToggleBtn" class="sidebar-toggler d-none d-xl-flex" title="Toggle sidebar">
                            <i class="bx bx-menu"></i>
                        </button>

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- Notifications -->
                            <li class="nav-item navbar-dropdown dropdown-notifications dropdown me-3 me-xl-1">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="bx bx-bell bx-sm"></i>
                                    @if($unread > 0)
                                    <span class="badge bg-danger rounded-pill badge-notifications">{{ $unread > 9 ? '9+' : $unread }}</span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end py-0">
                                    <li class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center py-3">
                                            <h5 class="text-body mb-0 me-auto">Notifications</h5>
                                            @if($unread > 0)
                                            <form method="POST" action="{{ route('notifications.read-all') }}" class="ms-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem">
                                                    Mark all read
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </li>
                                    <li class="dropdown-notifications-list scrollable-container">
                                        <ul class="list-group list-group-flush" id="notif-list">
                                            @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                            <li class="list-group-item list-group-item-action dropdown-notifications-item px-3 py-2"
                                                data-notif-id="{{ $notification->id }}">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="avatar flex-shrink-0">
                                                        <span class="avatar-initial rounded-circle bg-label-{{ $notification->data['color'] ?? 'primary' }}">
                                                            <i class="bx {{ $notification->data['icon'] ?? 'bx-bell' }}"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <h6 class="mb-0 text-truncate">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                                            <button type="button"
                                                                class="btn-close btn-close-sm ms-2 flex-shrink-0 mark-read-btn"
                                                                title="Mark as read"
                                                                data-id="{{ $notification->id }}"
                                                                data-url="{{ route('notifications.read', $notification->id) }}"
                                                                style="font-size:0.6rem"></button>
                                                        </div>
                                                        <p class="mb-0 small text-muted text-truncate">{{ $notification->data['message'] ?? '' }}</p>
                                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </li>
                                            @empty
                                            <li class="list-group-item" id="notif-empty">
                                                <div class="text-center py-3">
                                                    <i class="bx bx-bell-off d-block mb-1 text-muted" style="font-size:1.5rem"></i>
                                                    <span class="text-muted small">You're all caught up!</span>
                                                </div>
                                            </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="dropdown-menu-footer border-top">
                                        <a href="{{ route('notifications.index') }}" class="dropdown-item d-flex justify-content-center p-3 small">
                                            View all notifications
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ Notifications -->

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-primary">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <span class="avatar-initial rounded-circle bg-label-primary">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block">{{ auth()->user()->name }}</span>
                                                    <small class="text-muted text-capitalize">{{ auth()->user()->roles->pluck('name')->first() ?? 'Employee' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    @hasrole('superadmin')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    @endhasrole
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx bx-power-off me-2"></i>
                                                <span class="align-middle">Log Out</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Alerts -->
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <!-- Main Content -->
                        @yield('content')
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <!-- <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                © {{ date('Y') }}, made with ❤️ by <strong>IEAMS Team</strong>
                            </div>
                            <div>
                                <a href="#" class="footer-link me-4">License</a>
                                <a href="#" class="footer-link me-4">Documentation</a>
                                <a href="#" class="footer-link">Support</a>
                            </div>
                        </div>
                    </footer> -->
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

<!-- ===== Template Customizer ===== -->
<button id="customizerFab" title="Customize">
    <i class="bi bi-sliders"></i>
</button>

<div id="customizerPanel">
    <div class="customizer-header">
        <div>
            <h6>Template Customizer</h6>
            <small>Customize &amp; preview in real time</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="customizer-close" id="customizerReset" title="Reset to defaults"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="customizer-close" id="customizerClose"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    <div class="customizer-body">

        <!-- THEMING -->
        <span class="customizer-section-label">Theming</span>

        <div class="customizer-group-label">Primary Color</div>
        <div class="color-swatches mb-3">
            <div class="color-swatch active" data-color="purple"  style="background:#696cff; color:#696cff" title="Purple"></div>
            <div class="color-swatch" data-color="teal"   style="background:#03c3ec; color:#03c3ec" title="Teal"></div>
            <div class="color-swatch" data-color="amber"  style="background:#ffab00; color:#ffab00" title="Amber"></div>
            <div class="color-swatch" data-color="rose"   style="background:#ff3e1d; color:#ff3e1d" title="Rose"></div>
            <div class="color-swatch" data-color="blue"   style="background:#0d6efd; color:#0d6efd" title="Blue"></div>
            <div class="color-swatch custom-swatch" data-color="custom" title="Custom">
                <i class="bi bi-palette text-white"></i>
                <input type="color" id="customColorPicker" style="opacity:0;position:absolute;width:100%;height:100%;cursor:pointer;border:none;padding:0" value="#696cff">
            </div>
        </div>

        <div class="customizer-group-label">Theme</div>
        <div class="option-cards mb-3" id="themeOptions">
            <div class="option-card active" data-theme="light" style="width:90px">
                <div style="background:#f5f5f9;padding:.5rem .75rem;font-size:.65rem;font-weight:700;opacity:.5">LIGHT</div>
                <div style="background:#f5f5f9;height:40px;display:flex;gap:4px;padding:4px 6px">
                    <div style="width:24px;background:#fff;border-radius:2px"></div>
                    <div style="flex:1;background:#fff;border-radius:2px"></div>
                </div>
                <div class="option-card-label">Light</div>
            </div>
            <div class="option-card" data-theme="dark" style="width:90px">
                <div style="background:#2b2c40;padding:.5rem .75rem;font-size:.65rem;font-weight:700;opacity:.5;color:#fff">DARK</div>
                <div style="background:#2b2c40;height:40px;display:flex;gap:4px;padding:4px 6px">
                    <div style="width:24px;background:#3b3c53;border-radius:2px"></div>
                    <div style="flex:1;background:#3b3c53;border-radius:2px"></div>
                </div>
                <div class="option-card-label">Dark</div>
            </div>
            <div class="option-card" data-theme="system" style="width:90px">
                <div style="background:linear-gradient(90deg,#f5f5f9 50%,#2b2c40 50%);padding:.5rem .75rem;font-size:.65rem;font-weight:700;opacity:.5">SYS</div>
                <div style="background:linear-gradient(90deg,#f5f5f9 50%,#2b2c40 50%);height:40px;display:flex;gap:4px;padding:4px 6px">
                    <div style="width:24px;background:rgba(255,255,255,.5);border-radius:2px"></div>
                    <div style="flex:1;background:rgba(255,255,255,.3);border-radius:2px"></div>
                </div>
                <div class="option-card-label">System</div>
            </div>
        </div>

        <div class="customizer-group-label">Skin</div>
        <div class="option-cards mb-3" id="skinOptions">
            <div class="option-card active" data-skin="default" style="width:120px">
                <div style="height:60px;background:#f5f5f9;display:flex;gap:4px;padding:6px">
                    <div style="width:28px;background:#fff;border-radius:2px"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:3px">
                        <div style="height:8px;background:#fff;border-radius:2px"></div>
                        <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                    </div>
                </div>
                <div class="option-card-label">Default</div>
            </div>
            <div class="option-card" data-skin="bordered" style="width:120px">
                <div style="height:60px;background:#f5f5f9;display:flex;gap:4px;padding:6px">
                    <div style="width:28px;background:#fff;border-radius:2px;border:1px solid #ddd"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:3px">
                        <div style="height:8px;background:#fff;border-radius:2px;border:1px solid #ddd"></div>
                        <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                    </div>
                </div>
                <div class="option-card-label">Bordered</div>
            </div>
        </div>

        <hr class="customizer-divider">

        <!-- LAYOUT -->
        <span class="customizer-section-label">Layout</span>

        <div class="customizer-group-label">Menu (Navigation)</div>
        <div class="option-cards mb-3" id="menuOptions">
            <div class="option-card active" data-menu="expanded" style="width:120px">
                <div style="height:60px;background:#f5f5f9;display:flex;gap:4px;padding:6px">
                    <div style="width:32px;background:#696cff;border-radius:2px;opacity:.7"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:3px">
                        <div style="height:8px;background:#fff;border-radius:2px"></div>
                        <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                    </div>
                </div>
                <div class="option-card-label">Expanded</div>
            </div>
            <div class="option-card" data-menu="collapsed" style="width:120px">
                <div style="height:60px;background:#f5f5f9;display:flex;gap:4px;padding:6px">
                    <div style="width:12px;background:#696cff;border-radius:2px;opacity:.7"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:3px">
                        <div style="height:8px;background:#fff;border-radius:2px"></div>
                        <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                    </div>
                </div>
                <div class="option-card-label">Collapsed</div>
            </div>
        </div>

        <div class="customizer-group-label">Navbar Type</div>
        <div class="option-cards mb-3" id="navbarOptions">
            <div class="option-card active" data-navbar="sticky" style="width:86px">
                <div style="height:55px;background:#f5f5f9;display:flex;flex-direction:column;gap:3px;padding:4px 5px">
                    <div style="height:8px;background:#696cff;border-radius:2px;opacity:.7"></div>
                    <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                </div>
                <div class="option-card-label">Sticky</div>
            </div>
            <div class="option-card" data-navbar="static" style="width:86px">
                <div style="height:55px;background:#f5f5f9;display:flex;flex-direction:column;gap:3px;padding:4px 5px">
                    <div style="height:8px;background:#fff;border-radius:2px;border:1px solid #ddd"></div>
                    <div style="flex:1;background:#ebebeb;border-radius:2px"></div>
                </div>
                <div class="option-card-label">Static</div>
            </div>
            <div class="option-card" data-navbar="hidden" style="width:86px">
                <div style="height:55px;background:#f5f5f9;display:flex;flex-direction:column;gap:3px;padding:4px 5px">
                    <div style="flex:1;background:#ebebeb;border-radius:2px;margin-top:11px"></div>
                </div>
                <div class="option-card-label">Hidden</div>
            </div>
        </div>

        <div class="customizer-group-label">Content</div>
        <div class="option-cards mb-3" id="contentOptions">
            <div class="option-card" data-content="compact" style="width:120px">
                <div style="height:60px;background:#f5f5f9;padding:5px;display:flex;align-items:center;justify-content:center">
                    <div style="width:60%;height:100%;background:#fff;border-radius:2px;display:flex;flex-direction:column;gap:3px;padding:4px">
                        <div style="height:6px;background:#ebebeb;border-radius:1px"></div>
                        <div style="height:6px;background:#ebebeb;border-radius:1px"></div>
                    </div>
                </div>
                <div class="option-card-label">Compact</div>
            </div>
            <div class="option-card active" data-content="wide" style="width:120px">
                <div style="height:60px;background:#f5f5f9;padding:5px">
                    <div style="width:100%;height:100%;background:#fff;border-radius:2px;display:flex;flex-direction:column;gap:3px;padding:4px">
                        <div style="height:6px;background:#ebebeb;border-radius:1px"></div>
                        <div style="height:6px;background:#ebebeb;border-radius:1px"></div>
                    </div>
                </div>
                <div class="option-card-label">Wide</div>
            </div>
        </div>

        <div class="customizer-group-label">Direction</div>
        <div class="option-cards mb-3" id="directionOptions">
            <div class="option-card active" data-dir="ltr" style="width:120px">
                <div style="height:55px;background:#f5f5f9;padding:5px;display:flex;flex-direction:column;gap:3px">
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;padding:0 4px"><div style="width:60%;height:3px;background:#ddd;border-radius:1px"></div></div>
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;padding:0 4px"><div style="width:40%;height:3px;background:#ddd;border-radius:1px"></div></div>
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;padding:0 4px"><div style="width:80%;height:3px;background:#ddd;border-radius:1px"></div></div>
                </div>
                <div class="option-card-label">Left to Right (En)</div>
            </div>
            <div class="option-card" data-dir="rtl" style="width:120px">
                <div style="height:55px;background:#f5f5f9;padding:5px;display:flex;flex-direction:column;gap:3px">
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;justify-content:flex-end;padding:0 4px"><div style="width:60%;height:3px;background:#ddd;border-radius:1px"></div></div>
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;justify-content:flex-end;padding:0 4px"><div style="width:40%;height:3px;background:#ddd;border-radius:1px"></div></div>
                    <div style="height:8px;background:#fff;border-radius:2px;display:flex;align-items:center;justify-content:flex-end;padding:0 4px"><div style="width:80%;height:3px;background:#ddd;border-radius:1px"></div></div>
                </div>
                <div class="option-card-label">Right to Left (Ar)</div>
            </div>
        </div>

    </div>
</div>

<!-- Customizer Backdrop -->
<div id="customizerBackdrop" style="display:none;position:fixed;inset:0;z-index:1094;background:rgba(34,48,74,.5)"></div>

<script>
(function () {
    var STORAGE_KEY = 'ieams_customizer';
    var html = document.documentElement;

    var defaults = {
        color:   'purple',
        customHex: '#696cff',
        theme:   'light',
        skin:    'default',
        menu:    'expanded',
        navbar:  'sticky',
        content: 'wide',
        dir:     'ltr',
    };

    var colorMap = {
        purple: '#696cff',
        teal:   '#03c3ec',
        amber:  '#ffab00',
        rose:   '#ff3e1d',
        blue:   '#0d6efd',
    };

    function load() {
        try { return Object.assign({}, defaults, JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')); }
        catch(e) { return Object.assign({}, defaults); }
    }
    function save(cfg) { localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg)); }

    function hexToRgb(hex) {
        hex = hex.replace('#','');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var n = parseInt(hex, 16);
        return ((n>>16)&255)+','+(((n>>8)&255))+','+(n&255);
    }

    function darken(hex, pct) {
        hex = hex.replace('#','');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var n = parseInt(hex,16), r = Math.max(0,((n>>16)&255)-pct), g = Math.max(0,((n>>8)&255)-pct), b = Math.max(0,(n&255)-pct);
        return '#'+[r,g,b].map(function(v){return v.toString(16).padStart(2,'0');}).join('');
    }

    // ── Color ────────────────────────────────────────────────────────────
    function applyColor(color, customHex) {
        var hex = (color === 'custom' && customHex) ? customHex : (colorMap[color] || '#696cff');
        var rgb = hexToRgb(hex);
        var dark = darken(hex, 20);

        // CSS variable override
        var el = document.getElementById('customizer-color-style');
        if (!el) { el = document.createElement('style'); el.id = 'customizer-color-style'; document.head.appendChild(el); }
        el.textContent = [
            ':root {',
            '  --bs-primary: '+hex+';',
            '  --bs-primary-rgb: '+rgb+';',
            '  --bs-link-color: '+hex+';',
            '  --bs-link-hover-color: '+dark+';',
            '}',
            '.btn-primary { background-color:'+hex+' !important; border-color:'+hex+' !important; }',
            '.btn-primary:hover,.btn-primary:focus,.btn-primary:active { background-color:'+dark+' !important; border-color:'+dark+' !important; }',
            '.btn-outline-primary { color:'+hex+' !important; border-color:'+hex+' !important; }',
            '.btn-outline-primary:hover { background-color:'+hex+' !important; color:#fff !important; }',
            '.text-primary { color:'+hex+' !important; }',
            '.bg-primary { background-color:'+hex+' !important; }',
            '.badge.bg-primary { background-color:'+hex+' !important; }',
            'a:not(.btn):not(.nav-link):not(.menu-link):not(.dropdown-item) { color:'+hex+'; }',
            '.menu-item.active > .menu-link, .menu-link.active { color:'+hex+' !important; background-color: rgba('+rgb+',.1) !important; }',
            '.menu-item.active > .menu-link i, .menu-link.active i { color:'+hex+' !important; }',
        ].join('\n');

        // FAB color
        var fab = document.getElementById('customizerFab');
        if (fab) { fab.style.background = hex; fab.style.boxShadow = '-3px 0 12px '+hex+'88'; }
    }

    // ── Theme ────────────────────────────────────────────────────────────
    var DARK_CSS = [
        /* root overrides */
        ':root {',
        '  --bs-body-bg: #232333;',
        '  --bs-body-bg-rgb: 35,35,51;',
        '  --bs-body-color: #a3a4cc;',
        '  --bs-body-color-rgb: 163,164,204;',
        '  --bs-border-color: rgba(255,255,255,0.1);',
        '  --bs-secondary-bg: #2b2c40;',
        '  --bs-tertiary-bg: #323348;',
        '  --bs-heading-color: #cfd3ec;',
        '  --bs-secondary-color: rgba(163,164,204,0.6);',
        '}',
        /* body */
        'body { background-color:#232333 !important; color:#a3a4cc !important; }',
        /* headings */
        'h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6 { color:#cfd3ec !important; }',
        /* cards */
        '.card { background-color:#2b2c40 !important; border-color:rgba(255,255,255,0.08) !important; }',
        '.card-header,.card-footer { background-color:#2b2c40 !important; border-color:rgba(255,255,255,0.08) !important; }',
        /* navbar */
        '#layout-navbar,.layout-navbar { background-color:#2b2c40 !important; border-bottom-color:rgba(255,255,255,0.08) !important; }',
        '.navbar-nav .nav-link { color:#a3a4cc !important; }',
        /* sidebar */
        '.layout-menu,.bg-menu-theme { background-color:#2b2c40 !important; }',
        '.bg-menu-theme .menu-inner-shadow { background:linear-gradient(#2b2c40 41%,rgba(43,44,64,0.11) 95%,rgba(43,44,64,0)) !important; }',
        '.bg-menu-theme .menu-link,.bg-menu-theme .menu-header { color:#a3a4cc !important; }',
        '.bg-menu-theme .menu-link:hover { color:#cfd3ec !important; background:rgba(255,255,255,0.06) !important; }',
        '.bg-menu-theme .menu-item.active > .menu-link { background:rgba(105,108,255,0.15) !important; }',
        /* tables */
        '.table { --bs-table-bg:#2b2c40; --bs-table-color:#a3a4cc; --bs-table-border-color:rgba(255,255,255,0.08); color:#a3a4cc; }',
        '.table th { color:#cfd3ec !important; }',
        '.table-striped>tbody>tr:nth-of-type(odd)>* { --bs-table-accent-bg:rgba(255,255,255,0.03); }',
        '.table td,.table th { border-color:rgba(255,255,255,0.08) !important; }',
        /* inputs */
        '.form-label,.col-form-label { color:#a3a4cc !important; }',
        '.form-control,.form-select { background-color:#323348 !important; border-color:rgba(255,255,255,0.15) !important; color:#a3a4cc !important; }',
        '.form-control::placeholder { color:rgba(163,164,204,0.4) !important; }',
        '.form-control:focus,.form-select:focus { background-color:#3a3b52 !important; border-color:rgba(105,108,255,0.5) !important; box-shadow:0 0 0 0.2rem rgba(105,108,255,0.15) !important; }',
        '.input-group-text { background-color:#323348 !important; border-color:rgba(255,255,255,0.15) !important; color:#a3a4cc !important; }',
        'input:-webkit-autofill,input:-webkit-autofill:hover,input:-webkit-autofill:focus,textarea:-webkit-autofill,select:-webkit-autofill { -webkit-text-fill-color:#a3a4cc !important; -webkit-box-shadow:0 0 0px 1000px #323348 inset !important; transition: background-color 5000s ease-in-out 0s; }',
        'select option { background-color:#323348; color:#a3a4cc; }',
        /* dropdowns */
        '.dropdown-menu { background-color:#2b2c40 !important; border-color:rgba(255,255,255,0.1) !important; }',
        '.dropdown-item { color:#a3a4cc !important; }',
        '.dropdown-item:hover,.dropdown-item:focus { background-color:rgba(255,255,255,0.06) !important; color:#cfd3ec !important; }',
        '.dropdown-divider { border-color:rgba(255,255,255,0.1) !important; }',
        '.dropdown-header { color:rgba(163,164,204,0.5) !important; }',
        /* modals */
        '.modal-content { background-color:#2b2c40 !important; border-color:rgba(255,255,255,0.08) !important; }',
        '.modal-header,.modal-footer { border-color:rgba(255,255,255,0.08) !important; }',
        /* list groups */
        '.list-group-item { background-color:#2b2c40 !important; border-color:rgba(255,255,255,0.08) !important; color:#a3a4cc !important; }',
        /* badges / alerts */
        '.alert { border-color:rgba(255,255,255,0.08) !important; }',
        /* text utilities */
        '.text-muted { color:rgba(163,164,204,0.5) !important; }',
        '.text-dark { color:#cfd3ec !important; }',
        '.text-body { color:#a3a4cc !important; }',
        /* borders */
        '.border,.border-top,.border-end,.border-bottom,.border-start { border-color:rgba(255,255,255,0.1) !important; }',
        /* hr */
        'hr { border-color:rgba(255,255,255,0.1) !important; opacity:1; }',
        /* breadcrumb */
        '.breadcrumb-item,.breadcrumb-item a { color:#a3a4cc !important; }',
        '.breadcrumb-item.active { color:rgba(163,164,204,0.6) !important; }',
        /* DataTables */
        '.dataTables_wrapper .dataTables_length select,.dataTables_wrapper .dataTables_filter input { background-color:#323348 !important; color:#a3a4cc !important; border-color:rgba(255,255,255,0.15) !important; }',
        '.dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_paginate .paginate_button { color:#a3a4cc !important; }',
        '.dataTables_wrapper .dataTables_paginate .page-item.active .page-link,.dataTables_wrapper .dataTables_paginate .page-item.active .page-link:hover { background-color:var(--bs-primary) !important; border-color:var(--bs-primary) !important; color:#fff !important; }',
        '.dataTables_wrapper .dataTables_paginate .paginate_button.current,.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background:var(--bs-primary) !important; border-color:var(--bs-primary) !important; color:#fff !important; }',
        /* page wrapper */
        '.layout-page,.content-wrapper { background-color:#232333 !important; }',
        /* scrollbar */
        '::-webkit-scrollbar-track { background:#232333; }',
        '::-webkit-scrollbar-thumb { background:#3a3b52; }',
        '::-webkit-scrollbar-thumb:hover { background:#4a4b62; }',
    ].join('\n');

    function applyTheme(theme) {
        var resolved = theme === 'system'
            ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : theme;

        var darkEl = document.getElementById('customizer-dark-style');

        if (resolved === 'dark') {
            html.classList.remove('light-style');
            html.classList.add('dark-style');
            html.setAttribute('data-bs-theme', 'dark');
            if (!darkEl) {
                darkEl = document.createElement('style');
                darkEl.id = 'customizer-dark-style';
                document.head.appendChild(darkEl);
            }
            darkEl.textContent = DARK_CSS;
        } else {
            html.classList.remove('dark-style');
            html.classList.add('light-style');
            html.setAttribute('data-bs-theme', 'light');
            if (darkEl) darkEl.textContent = '';
        }
    }

    // ── Skin ─────────────────────────────────────────────────────────────
    // Sneat Free only ships theme-default.css; bordered is a visual-only overlay.
    function applySkin(skin) {
        var isBordered = skin === 'bordered';
        html.setAttribute('data-theme', 'theme-default');
        // Apply bordered look via injected style (no separate CSS file needed)
        var skinEl = document.getElementById('customizer-skin-style');
        if (!skinEl) { skinEl = document.createElement('style'); skinEl.id = 'customizer-skin-style'; document.head.appendChild(skinEl); }
        if (isBordered) {
            skinEl.textContent = [
                '.card { box-shadow:none !important; border:1px solid var(--bs-border-color) !important; }',
                '.layout-menu { border-right:1px solid var(--bs-border-color) !important; box-shadow:none !important; }',
                '#layout-navbar { border-bottom:1px solid var(--bs-border-color) !important; box-shadow:none !important; }',
            ].join('\n');
        } else {
            skinEl.textContent = '';
        }
    }

    // ── Menu ─────────────────────────────────────────────────────────────
    function applyMenu(menu) {
        if (menu === 'collapsed') {
            html.classList.add('layout-menu-collapsed');
            localStorage.setItem('ieams_sidebar_collapsed', '1');
        } else {
            html.classList.remove('layout-menu-collapsed');
            html.classList.remove('layout-menu-hover');
            localStorage.removeItem('ieams_sidebar_collapsed');
        }
        // Fix content padding after menu state change
        var layoutPage = document.querySelector('.layout-page');
        if (layoutPage) layoutPage.style.transition = 'padding-left 0.35s ease';
    }

    // ── Navbar ───────────────────────────────────────────────────────────
    function applyNavbar(navbar) {
        var nav = document.getElementById('layout-navbar');
        if (!nav) return;
        if (navbar === 'hidden') {
            nav.style.display = 'none';
        } else {
            nav.style.display = '';
            if (navbar === 'sticky') {
                nav.style.position = 'sticky';
                nav.style.top = '0';
                nav.style.zIndex = '1020';
            } else {
                // static — scrolls with page
                nav.style.position = 'relative';
                nav.style.top = '';
                nav.style.zIndex = '';
            }
        }
    }

    // ── Content ──────────────────────────────────────────────────────────
    function applyContent(content) {
        // Target the main content container (has flex-grow-1), not the navbar container
        var container = document.querySelector('.content-wrapper .container-xxl, div.container-xxl.flex-grow-1');
        if (!container) container = document.querySelector('.container-xxl.flex-grow-1');
        if (!container) return;
        if (content === 'compact') {
            container.style.maxWidth = '1140px';
            container.style.marginLeft = 'auto';
            container.style.marginRight = 'auto';
        } else {
            container.style.maxWidth = '';
            container.style.marginLeft = '';
            container.style.marginRight = '';
        }
    }

    // ── Direction ────────────────────────────────────────────────────────
    function applyDir(dir) {
        html.setAttribute('dir', dir);
        document.body && document.body.setAttribute('dir', dir);
    }

    function applyAll(cfg) {
        applyColor(cfg.color, cfg.customHex);
        applyTheme(cfg.theme);
        applySkin(cfg.skin);
        applyMenu(cfg.menu);
        applyNavbar(cfg.navbar);
        applyContent(cfg.content);
        applyDir(cfg.dir);
    }

    // ── UI sync ───────────────────────────────────────────────────────────
    function syncUI(cfg) {
        document.querySelectorAll('.color-swatch').forEach(function(el) {
            el.classList.toggle('active', el.dataset.color === cfg.color);
        });
        var picker = document.getElementById('customColorPicker');
        if (picker && cfg.customHex) picker.value = cfg.customHex;

        var groups = [
            ['themeOptions','theme'],['skinOptions','skin'],['menuOptions','menu'],
            ['navbarOptions','navbar'],['contentOptions','content'],['directionOptions','dir']
        ];
        groups.forEach(function(pair) {
            var wrap = document.getElementById(pair[0]);
            if (!wrap) return;
            wrap.querySelectorAll('.option-card').forEach(function(card) {
                var val = card.dataset.theme || card.dataset.skin || card.dataset.menu ||
                          card.dataset.navbar || card.dataset.content || card.dataset.dir;
                card.classList.toggle('active', val === cfg[pair[1]]);
            });
        });
    }

    // ── Boot (pre-DOMContentLoaded: apply silently) ───────────────────────
    var cfg = load();
    // Apply non-visual-flash things immediately
    applyTheme(cfg.theme);
    applyDir(cfg.dir);
    applyMenu(cfg.menu);

    document.addEventListener('DOMContentLoaded', function () {
        // Apply rest after DOM ready
        applyColor(cfg.color, cfg.customHex);
        applySkin(cfg.skin);
        applyNavbar(cfg.navbar);
        applyContent(cfg.content);
        syncUI(cfg);

        var panel    = document.getElementById('customizerPanel');
        var fab      = document.getElementById('customizerFab');
        var closeBtn = document.getElementById('customizerClose');
        var resetBtn = document.getElementById('customizerReset');
        var backdrop = document.getElementById('customizerBackdrop');

        function openPanel()  { panel.classList.add('open'); backdrop.style.display = 'block'; }
        function closePanel() { panel.classList.remove('open'); backdrop.style.display = 'none'; }

        fab.addEventListener('click', openPanel);
        closeBtn.addEventListener('click', closePanel);
        backdrop.addEventListener('click', closePanel);

        resetBtn.addEventListener('click', function () {
            cfg = Object.assign({}, defaults);
            save(cfg); applyAll(cfg); syncUI(cfg);
        });

        // Color swatches
        document.querySelectorAll('.color-swatch:not(.custom-swatch)').forEach(function(el) {
            el.addEventListener('click', function() {
                cfg.color = el.dataset.color;
                save(cfg); applyColor(cfg.color, cfg.customHex); syncUI(cfg);
            });
        });

        // Custom color picker
        var picker = document.getElementById('customColorPicker');
        if (picker) {
            picker.addEventListener('input', function() {
                cfg.color = 'custom'; cfg.customHex = picker.value;
                save(cfg); applyColor('custom', picker.value); syncUI(cfg);
            });
        }

        // Option cards
        function bindOptions(containerId, cfgKey, applyFn) {
            var wrap = document.getElementById(containerId);
            if (!wrap) return;
            wrap.querySelectorAll('.option-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    var val = card.dataset.theme || card.dataset.skin || card.dataset.menu ||
                              card.dataset.navbar || card.dataset.content || card.dataset.dir;
                    cfg[cfgKey] = val;
                    save(cfg); applyFn(val); syncUI(cfg);
                });
            });
        }

        bindOptions('themeOptions',     'theme',   applyTheme);
        bindOptions('skinOptions',      'skin',    applySkin);
        bindOptions('menuOptions',      'menu',    applyMenu);
        bindOptions('navbarOptions',    'navbar',  applyNavbar);
        bindOptions('contentOptions',   'content', applyContent);
        bindOptions('directionOptions', 'dir',     applyDir);

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                if (cfg.theme === 'system') applyTheme('system');
            });
        }
    });
}());
</script>
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    <!-- Sidebar toggle fix + auto-hide alerts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var htmlEl = document.documentElement;
            var layoutMenu = document.getElementById('layout-menu');
            var STORAGE_KEY = 'ieams_sidebar_collapsed';
            var BREAKPOINT = 1200;

            // Restore collapsed state
            if (localStorage.getItem(STORAGE_KEY) === 'true') {
                htmlEl.classList.add('layout-menu-collapsed');
            }

            // Desktop sidebar toggle (navbar button)
            var sidebarBtn = document.getElementById('sidebarToggleBtn');
            if (sidebarBtn) {
                sidebarBtn.addEventListener('click', function() {
                    htmlEl.classList.remove('layout-menu-hover');
                    var isCollapsed = htmlEl.classList.toggle('layout-menu-collapsed');
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? 'true' : 'false');
                });
            }

            // Mobile hamburger — let Sneat handle it via .layout-menu-toggle
            document.querySelectorAll('.layout-menu-toggle').forEach(function(toggler) {
                toggler.addEventListener('click', function(e) {
                    if (window.innerWidth < BREAKPOINT) return;
                    e.preventDefault();
                    e.stopImmediatePropagation();
                });
            });

            // Hover-expand: when collapsed, hovering the sidebar peeks the full menu
            if (layoutMenu) {
                layoutMenu.addEventListener('mouseenter', function() {
                    if (window.innerWidth >= BREAKPOINT && htmlEl.classList.contains('layout-menu-collapsed')) {
                        htmlEl.classList.add('layout-menu-hover');
                    }
                });
                layoutMenu.addEventListener('mouseleave', function() {
                    htmlEl.classList.remove('layout-menu-hover');
                });
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                document.querySelectorAll('.alert:not([data-no-autodismiss])').forEach(function(alert) {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
        });
    </script>

    @stack('scripts')

    {{-- Notification polling (every 5s) --}}
    @auth
    <script>
    (function pollNotifications() {
        const POLL_URL  = '{{ route('notifications.poll') }}';
        const CSRF      = '{{ csrf_token() }}';
        const INTERVAL  = {{ config('notifications.poll_interval', 5000) }};
        let lastCount   = {{ auth()->user()->unreadNotifications()->count() }};

        function updateBadge(count) {
            const badge = document.querySelector('.badge-notifications');
            if (count > 0) {
                if (!badge) {
                    const bell = document.querySelector('.bx-bell').closest('a');
                    const b = document.createElement('span');
                    b.className = 'badge bg-danger rounded-pill badge-notifications';
                    b.textContent = count > 9 ? '9+' : count;
                    bell.appendChild(b);
                } else {
                    badge.textContent = count > 9 ? '9+' : count;
                    badge.style.display = '';
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        }

        function buildItem(n) {
            return `
            <li class="list-group-item list-group-item-action dropdown-notifications-item px-3 py-2" data-notif-id="${n.id}">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded-circle bg-label-${n.color}"><i class="bx ${n.icon}"></i></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-0 text-truncate">${n.title}</h6>
                            <button type="button" class="btn-close btn-close-sm ms-2 flex-shrink-0 mark-read-btn"
                                title="Mark as read" data-id="${n.id}"
                                data-url="/notifications/${n.id}/read"
                                style="font-size:0.6rem"></button>
                        </div>
                        <p class="mb-0 small text-muted text-truncate">${n.message}</p>
                        <small class="text-muted">${n.time}</small>
                    </div>
                </div>
            </li>`;
        }

        function showEmpty() {
            return `<li class="list-group-item" id="notif-empty">
                <div class="text-center py-3">
                    <i class="bx bx-bell-off d-block mb-1 text-muted" style="font-size:1.5rem"></i>
                    <span class="text-muted small">You're all caught up!</span>
                </div>
            </li>`;
        }

        function updateDropdown(items) {
            const list = document.querySelector('#notif-list');
            if (!list) return;
            list.innerHTML = items.length ? items.map(buildItem).join('') : showEmpty();
        }

        function poll() {
            fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (data.unread !== lastCount) {
                        lastCount = data.unread;
                        updateBadge(data.unread);
                        updateDropdown(data.items);
                    }
                })
                .catch(() => {});
        }

        // Mark single notification as read
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.mark-read-btn');
            if (!btn) return;
            e.stopPropagation();
            const id  = btn.dataset.id;
            const url = btn.dataset.url;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            }).then(() => {
                const item = document.querySelector(`[data-notif-id="${id}"]`);
                if (item) item.remove();
                lastCount = Math.max(0, lastCount - 1);
                updateBadge(lastCount);
                if (!document.querySelector('#notif-list .dropdown-notifications-item')) {
                    document.querySelector('#notif-list').innerHTML = showEmpty();
                }
            }).catch(() => {});
        });

        setInterval(poll, INTERVAL);
    })();
    </script>
    @endauth
</body>
</html>
@endif
