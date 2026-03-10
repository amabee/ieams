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

Route::middleware(['auth'])->group(function () {

  // Dashboard
  Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

  // Profile (Breeze)
  Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
  Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
  Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

  // Branches
  Route::get('branches/data', [BranchController::class, 'data'])->name('branches.data')->middleware('can:view branches');
  Route::resource('branches', BranchController::class)->middleware('can:view branches');

  // Employees
  Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data')->middleware('can:view employees');
  Route::resource('employees', EmployeeController::class)->middleware('can:view employees');

  // Shifts / Schedules
  Route::get('shifts/data', [ShiftController::class, 'data'])->name('shifts.data')->middleware('can:view schedules');
  Route::resource('shifts', ShiftController::class);

  // Attendance Recording (Employee-facing)
  Route::get('/attendance/record', [AttendanceController::class, 'index'])->name('attendance.record');
  Route::post('/attendance/time-in', [AttendanceController::class, 'timeIn'])->name('attendance.time-in');
  Route::post('/attendance/time-out', [AttendanceController::class, 'timeOut'])->name('attendance.time-out');

  // Attendance Monitor (HR/Manager)
  Route::get('/attendance/monitor', [AttendanceMonitorController::class, 'index'])->name('attendance.monitor');
  Route::get('/attendance/monitor/data', [AttendanceMonitorController::class, 'data'])->name('attendance.monitor.data');

  // Attendance Management (HR/Admin)
  Route::get('/attendance/manage', [AttendanceManagementController::class, 'index'])->name('attendance.manage')->middleware('can:view users');
  Route::get('/attendance/manage/data', [AttendanceManagementController::class, 'data'])->name('attendance.manage.data');
  Route::get('/attendance/manage/{record}/edit', [AttendanceManagementController::class, 'edit'])->name('attendance.manage.edit');
  Route::put('/attendance/manage/{record}', [AttendanceManagementController::class, 'update'])->name('attendance.manage.update');
  Route::get('/attendance/corrections', [AttendanceManagementController::class, 'corrections'])->name('attendance.corrections');
  Route::post('/attendance/corrections/{correction}/approve', [AttendanceManagementController::class, 'approve'])->name('attendance.corrections.approve');
  Route::post('/attendance/corrections/{correction}/deny', [AttendanceManagementController::class, 'deny'])->name('attendance.corrections.deny');

  // Leaves
  Route::resource('leaves', LeaveController::class);
  Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
  Route::post('/leaves/{leave}/deny', [LeaveController::class, 'deny'])->name('leaves.deny');

  // Reports
  Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
  Route::match(['GET', 'POST'], '/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
  Route::get('/reports/download/{type}', [ReportController::class, 'download'])->name('reports.download');

  // Analytics
  Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
  Route::get('/analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data');

  // Forecasting
  Route::get('/forecasting', [ForecastController::class, 'index'])->name('forecasting.index');
  Route::get('/forecasting/data', [ForecastController::class, 'data'])->name('forecasting.data');
  Route::post('/forecasting/run', [ForecastController::class, 'run'])->name('forecasting.run');
  Route::post('/forecasting/interpret', [ForecastController::class, 'interpret'])->name('forecasting.interpret');

  // Notifications
  Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
  Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
  Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
  Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
  Route::get('/notifications/list', [NotificationController::class, 'list'])->name('notifications.list');

  // Audit Logs
  Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->middleware('can:view audit logs');
  Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data')->middleware('can:view audit logs');

  // Backups
  Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
  Route::post('/backups/run', [BackupController::class, 'run'])->name('backups.run');
  Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
  Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy')->middleware('can:manage backups');

  // Admin
  Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::resource('users', UserController::class);
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
  });
});

require __DIR__ . '/auth.php';
