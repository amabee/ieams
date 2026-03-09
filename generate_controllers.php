<?php
/**
 * Bootstrap script to write all IEAMS controller implementations.
 * Run: php generate_controllers.php
 */

$files = [];

// ── AttendanceController ──────────────────────────────────────────────────────
$files['app/Http/Controllers/AttendanceController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;

        $todayRecord = null;
        if ($employee) {
            $todayRecord = AttendanceRecord::where('employee_id', $employee->id)
                ->where('date', today())
                ->first();
        }

        $recentRecords = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->orderByDesc('date')->limit(14)->get()
            : collect();

        return view('attendance.record', compact('employee', 'todayRecord', 'recentRecords'));
    }

    public function timeIn(Request $request, AttendanceService $attendance)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }

        try {
            $attendance->recordTimeIn($employee, $user->id);
            return back()->with('success', 'Time-in recorded successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function timeOut(Request $request, AttendanceService $attendance)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }

        try {
            $attendance->recordTimeOut($employee, $user->id);
            return back()->with('success', 'Time-out recorded successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
PHP;

// ── AttendanceMonitorController ───────────────────────────────────────────────
$files['app/Http/Controllers/AttendanceMonitorController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceMonitorController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $date     = $request->date ?? today()->format('Y-m-d');
        $branches = Branch::where('is_active', true)->get();

        $summary = AttendanceRecord::where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("
                SUM(status = 'present') as present,
                SUM(status = 'late')    as late,
                SUM(status = 'absent')  as absent,
                SUM(status = 'on_leave') as on_leave
            ")->first();

        $records = AttendanceRecord::with('employee', 'branch')
            ->where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('time_in')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.monitor', compact('records', 'summary', 'branches', 'branchId', 'date'));
    }

    public function data(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $date     = $request->date ?? today()->format('Y-m-d');

        $records = AttendanceRecord::with('employee:id,first_name,last_name,position', 'branch:id,name')
            ->where('date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(fn ($r) => [
                'employee'     => $r->employee->full_name ?? '—',
                'position'     => $r->employee->position ?? '—',
                'branch'       => $r->branch->name ?? '—',
                'time_in'      => $r->time_in ?? '—',
                'time_out'     => $r->time_out ?? '—',
                'hours_worked' => $r->hours_worked ?? '—',
                'status'       => $r->status,
            ]);

        return response()->json($records);
    }
}
PHP;

// ── AttendanceManagementController ───────────────────────────────────────────
$files['app/Http/Controllers/AttendanceManagementController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceManagementController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->hasRole('branch_manager') ? $user->branch_id : $request->branch_id;
        $branches = Branch::where('is_active', true)->get();

        $records = AttendanceRecord::with('employee', 'branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->date_from, fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('date', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        return view('attendance.manage', compact('records', 'branches', 'branchId', 'employees'));
    }

    public function edit(AttendanceRecord $record)
    {
        $record->load('employee', 'branch');
        return view('attendance.edit', compact('record'));
    }

    public function update(Request $request, AttendanceRecord $record)
    {
        $validated = $request->validate([
            'time_in'  => 'required',
            'time_out' => 'nullable|after:time_in',
            'status'   => 'required|in:present,late,absent,on_leave,half_day',
            'notes'    => 'nullable|string|max:500',
            'reason'   => 'required|string|max:500',
        ]);

        // Log the correction
        AttendanceCorrection::create([
            'attendance_record_id' => $record->id,
            'corrected_by'         => auth()->id(),
            'old_time_in'          => $record->time_in,
            'old_time_out'         => $record->time_out,
            'old_status'           => $record->status,
            'new_time_in'          => $validated['time_in'],
            'new_time_out'         => $validated['time_out'] ?? null,
            'new_status'           => $validated['status'],
            'reason'               => $validated['reason'],
            'status'               => 'approved',
            'approved_by'          => auth()->id(),
        ]);

        $hours = null;
        if ($validated['time_in'] && $validated['time_out']) {
            $hours = app(\App\Services\AttendanceService::class)
                ->calculateHours($validated['time_in'], $validated['time_out']);
        }

        $record->update([
            'time_in'         => $validated['time_in'],
            'time_out'        => $validated['time_out'] ?? null,
            'status'          => $validated['status'],
            'notes'           => $validated['notes'] ?? null,
            'hours_worked'    => $hours,
            'is_manual_entry' => true,
        ]);

        return redirect()->route('attendance.manage')->with('success', 'Attendance record updated.');
    }

    public function corrections(Request $request)
    {
        $corrections = AttendanceCorrection::with(['attendanceRecord.employee', 'corrector'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('attendance.corrections', compact('corrections'));
    }

    public function approve(AttendanceCorrection $correction)
    {
        $correction->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        $correction->attendanceRecord->update([
            'time_in'      => $correction->new_time_in,
            'time_out'     => $correction->new_time_out,
            'status'       => $correction->new_status,
            'is_manual_entry' => true,
        ]);
        return back()->with('success', 'Correction approved.');
    }

    public function deny(AttendanceCorrection $correction)
    {
        $correction->update(['status' => 'denied', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Correction denied.');
    }
}
PHP;

// ── LeaveController ───────────────────────────────────────────────────────────
$files['app/Http/Controllers/LeaveController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        // Employees only see their own leaves
        $query = Leave::with('employee.branch')->latest();
        if ($user->hasRole('employee') && $employee) {
            $query->where('employee_id', $employee->id);
        } elseif ($user->hasRole('branch_manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $user->branch_id));
        }

        $leaves = $query->paginate(15);
        return view('leaves.index', compact('leaves'));
    }

    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No employee profile found for your account.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $totalDays = Carbon::parse($validated['start_date'])
            ->diffInWeekdays(Carbon::parse($validated['end_date'])) + 1;

        Leave::create(array_merge($validated, [
            'employee_id' => $employee->id,
            'total_days'  => $totalDays,
            'status'      => 'pending',
        ]));

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted.');
    }

    public function show(Leave $leave)
    {
        $leave->load('employee.branch', 'reviewer');
        return view('leaves.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        return view('leaves.edit', compact('leave'));
    }

    public function update(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Cannot edit a processed leave request.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,other',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $leave->update($validated);
        return redirect()->route('leaves.index')->with('success', 'Leave request updated.');
    }

    public function destroy(Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Cannot delete a processed leave request.');
        }
        $leave->delete();
        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled.');
    }

    public function approve(Leave $leave)
    {
        $leave->update(['status' => 'approved', 'reviewed_by' => auth()->id()]);

        // Mark attendance records as on_leave for approved dates
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                AttendanceRecord::updateOrCreate(
                    ['employee_id' => $leave->employee_id, 'date' => $date->format('Y-m-d')],
                    [
                        'branch_id'      => $leave->employee->branch_id,
                        'status'         => 'on_leave',
                        'is_manual_entry' => true,
                    ]
                );
            }
        }

        // Update leave balance
        LeaveBalance::updateOrCreate(
            ['employee_id' => $leave->employee_id, 'leave_type' => $leave->leave_type, 'year' => now()->year],
            ['total_days' => 15]
        );
        LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type', $leave->leave_type)
            ->where('year', now()->year)
            ->increment('used_days', $leave->total_days);

        return back()->with('success', 'Leave approved.');
    }

    public function deny(Request $request, Leave $leave)
    {
        $leave->update([
            'status'         => 'denied',
            'reviewed_by'    => auth()->id(),
            'review_comment' => $request->comment,
        ]);
        return back()->with('success', 'Leave denied.');
    }
}
PHP;

// ── AnalyticsController ───────────────────────────────────────────────────────
$files['app/Http/Controllers/AnalyticsController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchId = $request->branch_id;
        $period   = $request->period ?? '30';

        return view('analytics.index', compact('branches', 'branchId', 'period'));
    }

    public function data(Request $request)
    {
        $branchId = $request->branch_id;
        $days     = (int) ($request->period ?? 30);
        $start    = today()->subDays($days - 1);

        // Daily trend
        $trend = AttendanceRecord::where('date', '>=', $start)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('date, 
                SUM(status="present") as present,
                SUM(status="late")    as late,
                SUM(status="absent")  as absent,
                SUM(status="on_leave") as on_leave')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Branch comparison
        $branchStats = AttendanceRecord::where('date', '>=', $start)
            ->selectRaw('branch_id,
                COUNT(*) as total,
                SUM(status="absent") as absent,
                SUM(status="late")   as late,
                ROUND(SUM(status="absent")/COUNT(*)*100,1) as absence_rate')
            ->groupBy('branch_id')
            ->with('branch:id,name')
            ->get();

        // Top absentees
        $topAbsentees = AttendanceRecord::where('date', '>=', $start)
            ->where('status', 'absent')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('employee_id, COUNT(*) as absent_count')
            ->groupBy('employee_id')
            ->orderByDesc('absent_count')
            ->limit(10)
            ->with('employee:id,first_name,last_name,position')
            ->get()
            ->map(fn ($r) => [
                'name'         => $r->employee->full_name ?? '—',
                'position'     => $r->employee->position ?? '—',
                'absent_count' => $r->absent_count,
            ]);

        // Day-of-week absenteeism heatmap
        $heatmap = AttendanceRecord::where('date', '>=', $start)
            ->where('status', 'absent')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('DAYOFWEEK(date) as dow, COUNT(*) as count')
            ->groupBy('dow')
            ->pluck('count', 'dow');

        return response()->json([
            'trend'       => $trend,
            'branchStats' => $branchStats,
            'topAbsentees' => $topAbsentees,
            'heatmap'     => $heatmap,
        ]);
    }
}
PHP;

// ── ForecastController ────────────────────────────────────────────────────────
$files['app/Http/Controllers/ForecastController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Forecast;
use App\Services\ForecastingService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchId = $request->branch_id ?? $branches->first()?->id;

        return view('forecasting.index', compact('branches', 'branchId'));
    }

    public function data(Request $request, ForecastingService $forecasting)
    {
        $branchId = $request->branch_id;
        $from     = today()->format('Y-m-d');
        $to       = today()->addDays(30)->format('Y-m-d');

        $forecasts = $forecasting->getForecast($branchId, $from, $to);

        return response()->json($forecasts->map(fn ($f) => [
            'date'            => $f->forecast_date->format('Y-m-d'),
            'absent_count'    => $f->predicted_absent_count,
            'absenteeism_rate'=> $f->predicted_absenteeism_rate,
            'generated_at'    => $f->generated_at?->format('Y-m-d H:i'),
        ]));
    }

    public function run(Request $request, ForecastingService $forecasting)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::find($validated['branch_id']);
        $forecasting->runForBranch($branch, 30);

        return back()->with('success', "Forecast generated for {$branch->name}.");
    }
}
PHP;

// ── ReportController ──────────────────────────────────────────────────────────
$files['app/Http/Controllers/ReportController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('reports.index', compact('branches'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:daily,weekly,monthly,annual',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
            'branch_id'   => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'format'      => 'required|in:pdf,excel',
        ]);

        $records = AttendanceRecord::with('employee', 'branch')
            ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['branch_id'] ?? null, fn ($q) => $q->where('branch_id', $validated['branch_id']))
            ->when($validated['employee_id'] ?? null, fn ($q) => $q->where('employee_id', $validated['employee_id']))
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $summary = [
            'total'    => $records->count(),
            'present'  => $records->where('status', 'present')->count(),
            'late'     => $records->where('status', 'late')->count(),
            'absent'   => $records->where('status', 'absent')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
            'date_from' => $validated['date_from'],
            'date_to'   => $validated['date_to'],
        ];

        if ($validated['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.attendance-pdf', compact('records', 'summary', 'validated'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('attendance-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // Excel via Maatwebsite
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($records, $summary),
            'attendance-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function download(string $type)
    {
        return redirect()->route('reports.index');
    }
}
PHP;

// ── NotificationController ────────────────────────────────────────────────────
$files['app/Http/Controllers/NotificationController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}
PHP;

// ── AuditLogController ────────────────────────────────────────────────────────
$files['app/Http/Controllers/AuditLogController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = Activity::with('causer')
            ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->date_from, fn ($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('created_at', '<=', $request->date_to . ' 23:59:59'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}
PHP;

// ── BackupController ──────────────────────────────────────────────────────────
$files['app/Http/Controllers/BackupController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::latest()->paginate(15);
        return view('backups.index', compact('backups'));
    }

    public function run()
    {
        Artisan::call('backup:run');
        return back()->with('success', 'Backup created successfully.');
    }

    public function download(Backup $backup)
    {
        $path = storage_path('app/backups/' . $backup->filename);
        if (!file_exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }
        return response()->download($path);
    }
}
PHP;

// ── Admin\UserController ──────────────────────────────────────────────────────
$files['app/Http/Controllers/Admin/UserController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles', 'branch')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles    = Role::all();
        $branches = Branch::where('is_active', true)->get();
        return view('admin.users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:8|confirmed',
            'role'      => 'required|exists:roles,name',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => true,
        ]);
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'branch', 'employee');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles    = Role::all();
        $branches = Branch::where('is_active', true)->get();
        return view('admin.users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|min:8|confirmed',
            'role'      => 'required|exists:roles,name',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            ...(isset($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
PHP;

// ── Admin\SettingsController ──────────────────────────────────────────────────
$files['app/Http/Controllers/Admin/SettingsController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings'   => 'required|array',
            'settings.*' => 'nullable|string|max:500',
        ]);

        foreach ($data['settings'] as $key => $value) {
            SystemSetting::set($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
PHP;

// ── ShiftController ───────────────────────────────────────────────────────────
$files['app/Http/Controllers/ShiftController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Branch;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('employees')->latest()->paginate(15);
        return view('shifts.index', compact('shifts'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'start_time'             => 'required',
            'end_time'               => 'required|after:start_time',
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'branch_id'              => 'nullable|exists:branches,id',
        ]);
        Shift::create($validated);
        return redirect()->route('shifts.index')->with('success', 'Shift created.');
    }

    public function show(Shift $shift)
    {
        $shift->loadCount('employees');
        return view('shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.edit', compact('shift', 'branches'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'start_time'             => 'required',
            'end_time'               => 'required',
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'branch_id'              => 'nullable|exists:branches,id',
        ]);
        $shift->update($validated);
        return redirect()->route('shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }
}
PHP;

// Write all files
$base = dirname(__FILE__);
foreach ($files as $relativePath => $content) {
    $fullPath = $base . '/' . $relativePath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($fullPath, $content);
    echo "Written: $relativePath\n";
}

echo "\nAll controllers written successfully.\n";
