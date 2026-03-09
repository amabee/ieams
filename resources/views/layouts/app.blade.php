<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IEAMS') – {{ config('app.name', 'IEAMS') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        :root { 
            --sidebar-width: 260px; 
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
        }
        * { box-sizing: border-box; }
        body { 
            background: #f4f6f9; 
            min-height: 100vh; 
            overflow-x: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1e2a3a 0%, #253142 100%);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease, transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        #sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        #sidebar .sidebar-brand {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            min-height: var(--topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        #sidebar .brand-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow: hidden;
            flex: 1;
        }
        #sidebar .brand-icon {
            font-size: 1.5rem;
            color: #4da3ff;
            flex-shrink: 0;
        }
        #sidebar .brand-text {
            overflow: hidden;
            transition: opacity 0.2s ease;
        }
        #sidebar.collapsed .brand-text {
            opacity: 0;
            width: 0;
        }
        #sidebar .brand-text h5 {
            color: #fff;
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            white-space: nowrap;
        }
        #sidebar .brand-text small {
            color: #8fa3b1;
            font-size: 0.7rem;
            display: block;
            white-space: nowrap;
        }
        #sidebar .sidebar-toggle {
            background: rgba(255,255,255,0.08);
            border: none;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            flex-shrink: 0;
        }
        #sidebar .sidebar-toggle:hover {
            background: rgba(255,255,255,0.15);
        }
        #sidebar.collapsed .sidebar-toggle i::before {
            content: "\f285"; /* bi-chevron-right */
        }
        
        /* Scrollable nav area */
        #sidebar .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.5rem 0;
        }
        #sidebar .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }
        #sidebar .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }
        #sidebar .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        #sidebar .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }
        
        #sidebar .nav-section {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8fa3b1;
            padding: 0.75rem 1rem 0.3rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s ease;
        }
        #sidebar.collapsed .nav-section {
            opacity: 0;
            height: 2px;
            padding: 0;
        }
        
        #sidebar .nav-link {
            color: #c8d6df;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            border-radius: 0;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            position: relative;
            text-decoration: none;
        }
        #sidebar .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
            padding-left: 1.25rem;
        }
        #sidebar .nav-link.active {
            background: rgba(77,163,255,0.15);
            color: #4da3ff;
            border-left: 3px solid #4da3ff;
        }
        #sidebar .nav-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }
        #sidebar .nav-link span {
            transition: opacity 0.2s ease;
        }
        #sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.65rem 0;
        }
        #sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        
        /* Tooltip for collapsed state */
        #sidebar.collapsed .nav-link:hover::after {
            content: attr(data-title);
            position: absolute;
            left: 100%;
            margin-left: 10px;
            padding: 0.5rem 0.75rem;
            background: #2c3e50;
            color: #fff;
            border-radius: 0.375rem;
            white-space: nowrap;
            font-size: 0.8rem;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Main Content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
            width: 100%;
        }
        body.sidebar-collapsed #main-content {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Topbar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e3e6ea;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1040;
            min-height: var(--topbar-height);
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        
        /* Page Content */
        .page-content {
            padding: 1.5rem;
            flex: 1;
            max-width: 100%;
        }
        
        /* Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1045;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* Utility Classes */
        .stat-card { border: none; border-radius: 0.5rem; }
        .stat-card .stat-icon {
            width: 52px; height: 52px; border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .badge-present  { background: #198754 !important; }
        .badge-late     { background: #ffc107 !important; color: #212529 !important; }
        .badge-absent   { background: #dc3545 !important; }
        .badge-on_leave { background: #0dcaf0 !important; color: #212529 !important; }
        .badge-half_day { background: #6f42c1 !important; }
        
        /* Responsive Design */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            body.sidebar-collapsed #main-content {
                margin-left: 0;
            }
            #sidebar.collapsed {
                width: var(--sidebar-width);
            }
            #sidebar.collapsed .brand-text,
            #sidebar.collapsed .nav-link span,
            #sidebar.collapsed .nav-section {
                opacity: 1;
                width: auto;
            }
            #sidebar.collapsed .nav-link {
                justify-content: flex-start;
                padding: 0.65rem 1rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .page-content {
                padding: 1rem;
            }
            #topbar {
                padding: 0.5rem 1rem;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
        }
        
        /* Prevent horizontal scroll on small screens */
        .container-fluid, .row {
            max-width: 100%;
        }
        
        /* Make tables more mobile friendly */
        @media (max-width: 767.98px) {
            .table thead {
                display: none;
            }
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.375rem;
                padding: 0.5rem;
            }
            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none;
            }
            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.5rem;
                width: 45%;
                padding-right: 0.5rem;
                text-align: left;
                font-weight: 600;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-content">
            <i class="bi bi-activity brand-icon"></i>
            <div class="brand-text">
                <h5>IEAMS</h5>
                <small>Attendance System</small>
            </div>
        </div>
        <button class="sidebar-toggle d-none d-lg-flex" onclick="toggleSidebarCollapse()" title="Toggle Sidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-title="Dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        @canany(['view attendance','record attendance'])
        <div class="nav-section">Attendance</div>
        @can('record attendance')
        <a href="{{ route('attendance.record') }}" class="nav-link {{ request()->routeIs('attendance.record') ? 'active' : '' }}" data-title="Time In / Out">
            <i class="bi bi-clock-history"></i>
            <span>Time In / Out</span>
        </a>
        @endcan
        @can('view attendance')
        <a href="{{ route('attendance.monitor') }}" class="nav-link {{ request()->routeIs('attendance.monitor') ? 'active' : '' }}" data-title="Monitor">
            <i class="bi bi-display"></i>
            <span>Monitor</span>
        </a>
        <a href="{{ route('attendance.manage') }}" class="nav-link {{ request()->routeIs('attendance.manage') ? 'active' : '' }}" data-title="Manage Records">
            <i class="bi bi-pencil-square"></i>
            <span>Manage Records</span>
        </a>
        @endcan
        @endcanany

        @canany(['view leaves','create leaves'])
        <div class="nav-section">Leaves</div>
        <a href="{{ route('leaves.index') }}" class="nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}" data-title="Leave Requests">
            <i class="bi bi-calendar-x"></i>
            <span>Leave Requests</span>
        </a>
        @endcanany

        @canany(['view employees','view branches'])
        <div class="nav-section">People</div>
        @can('view employees')
        <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-title="Employees">
            <i class="bi bi-people"></i>
            <span>Employees</span>
        </a>
        @endcan
        @can('view branches')
        <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" data-title="Branches">
            <i class="bi bi-building"></i>
            <span>Branches</span>
        </a>
        @endcan
        @can('view schedules')
        <a href="{{ route('shifts.index') }}" class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}" data-title="Shifts / Schedules">
            <i class="bi bi-calendar3"></i>
            <span>Shifts / Schedules</span>
        </a>
        @endcan
        @endcanany

        @canany(['view reports','view analytics','view forecasting'])
        <div class="nav-section">Analytics</div>
        @can('view reports')
        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-title="Reports">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Reports</span>
        </a>
        @endcan
        @can('view analytics')
        <a href="{{ route('analytics.index') }}" class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" data-title="Analytics">
            <i class="bi bi-graph-up"></i>
            <span>Analytics</span>
        </a>
        @endcan
        @can('view forecasting')
        <a href="{{ route('forecasting.index') }}" class="nav-link {{ request()->routeIs('forecasting.*') ? 'active' : '' }}" data-title="Forecasting">
            <i class="bi bi-lightning-charge"></i>
            <span>Forecasting</span>
        </a>
        @endcan
        @endcanany

        @canany(['view audit logs','manage backups','manage settings'])
        <div class="nav-section">System</div>
        @can('view audit logs')
        <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" data-title="Audit Logs">
            <i class="bi bi-journal-text"></i>
            <span>Audit Logs</span>
        </a>
        @endcan
        @can('manage backups')
        <a href="{{ route('backups.index') }}" class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}" data-title="Backup & Recovery">
            <i class="bi bi-cloud-arrow-up"></i>
            <span>Backup & Recovery</span>
        </a>
        @endcan
        @can('manage settings')
        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" data-title="Settings">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" data-title="User Management">
            <i class="bi bi-shield-lock"></i>
            <span>User Management</span>
        </a>
        @endcan
        @endcanany
    </div>
</nav>

<div id="main-content">
    <div id="topbar">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
            <button class="btn btn-sm btn-light d-lg-none" onclick="toggleSidebar()" title="Menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <nav aria-label="breadcrumb" class="mb-0 d-none d-sm-block">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="position-relative">
                <a href="{{ route('notifications.index') }}" class="text-secondary text-decoration-none" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                    @if($unread > 0)
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size:.6rem">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.8rem">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <span class="d-none d-md-inline small fw-semibold">{{ Str::limit(auth()->user()->name, 15) }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-muted">{{ Str::limit(implode(', ', auth()->user()->getRoleNames()->toArray()), 30) }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle Functions
function toggleSidebarCollapse() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    
    sidebar.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');
    
    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    
    // Prevent body scroll when sidebar is open on mobile
    if (sidebar.classList.contains('show')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Close sidebar when clicking outside on mobile
document.querySelector('.sidebar-overlay')?.addEventListener('click', function() {
    toggleSidebar();
});

// Close sidebar when pressing Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
});

// Restore sidebar state from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    // Only apply collapsed state on desktop
    if (window.innerWidth >= 992) {
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            document.getElementById('sidebar').classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
    }
});

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (window.innerWidth >= 992) {
            // Desktop: close mobile menu and restore collapsed state
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            }
        } else {
            // Mobile: remove collapsed class
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
        }
    }, 250);
});

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert:not(.alert-info):not(.alert-warning)').forEach(function(alert) {
    if (!alert.querySelector('.alert-link')) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    }
});
</script>
@stack('scripts')
</body>
</html>
