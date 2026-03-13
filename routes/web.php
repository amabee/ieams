<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceMonitorController;
use App\Http\Controllers\AttendanceManagementController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {

    // UI Version Switcher
    Route::post('/ui/version', function (\Illuminate\Http\Request $request) {
        $version = in_array($request->input('version'), ['v1', 'v2']) ? $request->input('version') : 'v2';
        session(['ui_version' => $version]);
        return back();
    })->name('ui.version');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications (all authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::get('/notifications/list', [NotificationController::class, 'list'])->name('notifications.list');

    // ── Attendance Recording (all who can record) ──────────────────────────
    Route::middleware('can:record attendance')->group(function () {
        Route::get('/attendance/record', [AttendanceController::class, 'index'])->name('attendance.record');
        Route::post('/attendance/time-in', [AttendanceController::class, 'timeIn'])->name('attendance.time-in');
        Route::post('/attendance/time-out', [AttendanceController::class, 'timeOut'])->name('attendance.time-out');
    });

    // ── Attendance Monitor (HR / Manager / Admin) ──────────────────────────
    Route::middleware('can:view attendance')->group(function () {
        Route::get('/attendance/monitor', [AttendanceMonitorController::class, 'index'])->name('attendance.monitor');
        Route::get('/attendance/monitor/data', [AttendanceMonitorController::class, 'data'])->name('attendance.monitor.data');
    });

    // ── Attendance Management (HR / Admin) ─────────────────────────────────
    Route::middleware('can:edit attendance')->group(function () {
        Route::get('/attendance/manage', [AttendanceManagementController::class, 'index'])->name('attendance.manage');
        Route::get('/attendance/manage/data', [AttendanceManagementController::class, 'data'])->name('attendance.manage.data');
        Route::get('/attendance/manage/{record}/edit', [AttendanceManagementController::class, 'edit'])->name('attendance.manage.edit');
        Route::put('/attendance/manage/{record}', [AttendanceManagementController::class, 'update'])->name('attendance.manage.update');
    });

    // ── Attendance Corrections (HR / Admin) ────────────────────────────────
    Route::middleware('can:approve attendance correction')->group(function () {
        Route::get('/attendance/corrections', [AttendanceManagementController::class, 'corrections'])->name('attendance.corrections');
        Route::post('/attendance/corrections/{correction}/approve', [AttendanceManagementController::class, 'approve'])->name('attendance.corrections.approve');
        Route::post('/attendance/corrections/{correction}/deny', [AttendanceManagementController::class, 'deny'])->name('attendance.corrections.deny');
    });

    // ── Branches ───────────────────────────────────────────────────────────
    Route::middleware('can:view branches')->group(function () {
        Route::get('branches/data', [BranchController::class, 'data'])->name('branches.data');
        Route::resource('branches', BranchController::class);
    });

    // ── Employees ──────────────────────────────────────────────────────────
    Route::middleware('can:view employees')->group(function () {
        Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
        Route::resource('employees', EmployeeController::class);
    });

    // ── Shifts / Schedules ─────────────────────────────────────────────────
    Route::middleware('can:view schedules')->group(function () {
        Route::get('shifts/data', [ShiftController::class, 'data'])->name('shifts.data');
        Route::resource('shifts', ShiftController::class);
    });

    // ── Leaves ─────────────────────────────────────────────────────────────
    Route::middleware('can:view leaves')->group(function () {
        Route::resource('leaves', LeaveController::class);
    });
    Route::middleware('can:approve leaves')->group(function () {
        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leave}/deny', [LeaveController::class, 'deny'])->name('leaves.deny');
    });

    // ── Reports ────────────────────────────────────────────────────────────
    Route::middleware('can:view reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::match(['GET', 'POST'], '/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    });
    Route::get('/reports/download/{type}', [ReportController::class, 'download'])
        ->name('reports.download')
        ->middleware('can:export reports');

    // ── Analytics ──────────────────────────────────────────────────────────
    Route::middleware('can:view analytics')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data');
    });

    // ── Forecasting ────────────────────────────────────────────────────────
    Route::middleware('can:view forecasting')->group(function () {
        Route::get('/forecasting', [ForecastController::class, 'index'])->name('forecasting.index');
        Route::get('/forecasting/data', [ForecastController::class, 'data'])->name('forecasting.data');
    });
    Route::middleware('can:run forecast')->group(function () {
        Route::post('/forecasting/run', [ForecastController::class, 'run'])->name('forecasting.run');
        Route::post('/forecasting/interpret', [ForecastController::class, 'interpret'])->name('forecasting.interpret');
    });

    // ── Audit Logs ─────────────────────────────────────────────────────────
    Route::middleware('can:view audit logs')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
    });

    // ── Backups ────────────────────────────────────────────────────────────
    Route::middleware('can:manage backups')->group(function () {
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/run', [BackupController::class, 'run'])->name('backups.run');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });

    // ── Admin ──────────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:view users')->group(function () {
            Route::get('users/data', [UserController::class, 'data'])->name('users.data');
            Route::resource('users', UserController::class);
        });
        Route::middleware('can:manage settings')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
});

require __DIR__ . '/auth.php';
