<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\SystemSetting;
use Carbon\Carbon;

class AttendanceService
{
    public function recordTimeIn(Employee $employee, ?int $recordedBy = null): AttendanceRecord
    {
        $today = today();
        $now   = now()->format('H:i:s');

        $record = AttendanceRecord::firstOrNew([
            'employee_id' => $employee->id,
            'date'        => $today,
        ]);

        if ($record->time_in) {
            throw new \RuntimeException('Employee has already timed in today.');
        }

        $shift     = $employee->shift;
        $status    = $this->determineStatus($now, $shift, $record->status);
        $record->fill([
            'branch_id'   => $employee->branch_id,
            'time_in'     => $now,
            'status'      => $status,
            'recorded_by' => $recordedBy,
        ]);
        $record->save();

        return $record;
    }

    public function recordTimeOut(Employee $employee, ?int $recordedBy = null): AttendanceRecord
    {
        $today  = today();
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->firstOrFail();

        if ($record->time_out) {
            throw new \RuntimeException('Employee has already timed out today.');
        }

        $timeIn      = Carbon::parse($record->time_in);
        $timeOut     = Carbon::now();
        $hoursWorked = round($timeOut->diffInMinutes($timeIn) / 60, 2);

        $record->update([
            'time_out'    => $timeOut->format('H:i:s'),
            'hours_worked' => $hoursWorked,
            'recorded_by'  => $recordedBy,
        ]);

        return $record;
    }

    private function determineStatus(string $timeIn, ?Shift $shift, ?string $currentStatus): string
    {
        if ($currentStatus === 'on_leave') {
            return 'on_leave';
        }

        if (!$shift) {
            return 'present';
        }

        $threshold = (int) SystemSetting::get('late_threshold_minutes', 15);
        $shiftStart = Carbon::parse($shift->start_time)->addMinutes($threshold);
        $clockIn    = Carbon::parse($timeIn);

        return $clockIn->greaterThan($shiftStart) ? 'late' : 'present';
    }

    public function calculateHours(string $timeIn, string $timeOut): float
    {
        $start = Carbon::parse($timeIn);
        $end   = Carbon::parse($timeOut);
        return round($end->diffInMinutes($start) / 60, 2);
    }
}
