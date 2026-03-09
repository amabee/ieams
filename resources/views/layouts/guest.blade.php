<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'IEAMS') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e2a3a 0%, #2d4a6b 100%); min-height: 100vh; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="w-100" style="max-width: 420px; padding: 1rem;">
        <div class="text-center mb-4">
            <i class="bi bi-activity text-white" style="font-size: 2.5rem;"></i>
            <h4 class="text-white mt-2 mb-0">IEAMS</h4>
            <small class="text-white-50">Integrated Employee Attendance Monitoring System</small>
        </div>
        <div class="card shadow-lg border-0 rounded-3">
            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
