<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Command;

class CheckAttendanceAlerts extends Command
{
    protected $signature   = 'alerts:check';
    protected $description = 'Check attendance and send alerts for threshold violations';

    public function handle(): int
    {
        $threshold = (int) SystemSetting::get('alert_absence_threshold', 3);
        $start     = today()->subDays(7);

        // Find employees with >= threshold absences in the last 7 days
        $results = AttendanceRecord::where('date', '>=', $start)
            ->where('status', 'absent')
            ->selectRaw('employee_id, COUNT(*) as absence_count')
            ->groupBy('employee_id')
            ->having('absence_count', '>=', $threshold)
            ->with('employee')
            ->get();

        foreach ($results as $result) {
            $employee = $result->employee;
            if (!$employee) continue;

            // Notify HR users
            $hrUsers = User::role(['hr', 'admin', 'superadmin'])->get();
            foreach ($hrUsers as $hrUser) {
                $hrUser->notify(new \App\Notifications\ExcessiveAbsenceAlert($employee, $result->absence_count));
            }

            $this->info("Alert sent for {$employee->full_name} ({$result->absence_count} absences).");
        }

        $this->info('Alert check complete.');
        return self::SUCCESS;
    }
}
