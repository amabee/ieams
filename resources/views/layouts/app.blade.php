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

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    @stack('styles')
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

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
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

                    @canany(['branch_manager', 'admin', 'superadmin'])
                    <!-- Branches -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                        <a href="{{ route('branches.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-buildings"></i>
                            <div data-i18n="Branches">Branches</div>
                        </a>
                    </li>
                    @endcanany

                    @canany(['hr', 'admin', 'superadmin'])
                    <!-- Employees -->
                    <li class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Employees">Employees</div>
                        </a>
                    </li>
                    @endcanany

                    <!-- Attendance -->
                    <li class="menu-header small text-uppercase"><span class="menu-header-text">Attendance</span></li>
                    
                    <li class="menu-item {{ request()->routeIs('attendance.record') ? 'active open' : '' }}">
                        <a href="{{ route('attendance.record') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Time In/Out">Time In/Out</div>
                        </a>
                    </li>

                    @canany(['branch_manager', 'hr', 'admin', 'superadmin'])
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
                    @endcanany

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

                    @canany(['hr', 'admin', 'superadmin'])
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
                    @endcanany

                    @canany(['admin', 'superadmin'])
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
                    @endcanany

                    @can('superadmin')
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
                    @endcan
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-search fs-4 lh-0"></i>
                                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..." aria-label="Search..." />
                            </div>
                        </div>
                        <!-- /Search -->

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
                                            <span class="badge badge-sm bg-label-primary">{{ $unread }} New</span>
                                            @endif
                                        </div>
                                    </li>
                                    <li class="dropdown-notifications-list scrollable-container">
                                        <ul class="list-group list-group-flush">
                                            @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar">
                                                            <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-bell"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                                        <p class="mb-0">{{ $notification->data['message'] ?? '' }}</p>
                                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </li>
                                            @empty
                                            <li class="list-group-item">
                                                <div class="text-center py-3">
                                                    <span class="text-muted">No new notifications</span>
                                                </div>
                                            </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="dropdown-menu-footer border-top">
                                        <a href="{{ route('notifications.index') }}" class="dropdown-item d-flex justify-content-center p-3">
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
                                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    @can('superadmin')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    @endcan
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
                        <!-- Breadcrumb -->
                        @hasSection('breadcrumb')
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb breadcrumb-style1">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}">Home</a>
                                </li>
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                        @endif

                        <!-- Page Title -->
                        @hasSection('title')
                        <h4 class="fw-bold py-3 mb-4">
                            @yield('title')
                        </h4>
                        @endif

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
                    <footer class="content-footer footer bg-footer-theme">
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
                    </footer>
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

    <!-- Core JS -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    <!-- Auto-hide alerts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>

    @stack('scripts')
</body>
</html>
