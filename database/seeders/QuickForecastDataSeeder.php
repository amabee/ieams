<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;

/**
 * Seeds attendance from 30 days ago through the end of the current month for all
 * existing active branches/employees.
 * Safe to run on an existing database — uses insertOrIgnore, touches nothing else.
 *
 * Run with:
 *   php artisan db:seed --class=QuickForecastDataSeeder
 *
 * Covers past + rest-of-current-month so the full month is always populated for demos.
 */
class QuickForecastDataSeeder extends Seeder
{
    public function run(): void
    {
        $endOfMonth = today()->endOfMonth();
        $daysAhead  = (int) today()->diffInDays($endOfMonth, false); // days left in month
        $this->command->info("⚡ QuickForecastDataSeeder: seeding 30 days back + {$daysAhead} days ahead (through end of month)...");

        $branches  = Branch::where('is_active', true)->get();
        $shiftMap  = Shift::all()->keyBy('id');
        $batch     = [];
        $batchSize = 500;

        foreach ($branches as $branch) {
            $employees = Employee::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn("  Branch [{$branch->name}] has no active employees — skipping.");
                continue;
            }

            $this->command->info("  Branch [{$branch->name}]: {$employees->count()} employees");

            foreach ($employees as $emp) {
                $shift = $shiftMap[$emp->shift_id] ?? null;
                [$shStartH, $shStartM] = $shift
                    ? array_map('intval', explode(':', $shift->start_time))
                    : [8, 0];
                [$shEndH, $shEndM] = $shift
                    ? array_map('intval', explode(':', $shift->end_time))
                    : [17, 0];
                $lateThresh = $shift?->late_threshold_minutes ?? 15;
                $shStartMin = $shStartH * 60 + $shStartM;
                $shEndMin   = $shEndH   * 60 + $shEndM;

                // Generate from 30 days ago through end of current month
                $rangeStart = today()->subDays(30);
                $rangeEnd   = today()->endOfMonth();
                $current    = $rangeStart->copy();
                while ($current->lte($rangeEnd)) {
                    $date = $current->copy();
                    $current->addDay();

                    // Skip weekends
                    if ($date->isWeekend()) {
                        continue;
                    }

                    $rand = mt_rand(0, 9999) / 10000;

                    if ($rand < 0.06) {
                        // absent
                        $status = 'absent';
                        $timeIn = $timeOut = $hoursWork = null;
                    } elseif ($rand < 0.09) {
                        // half_day
                        $status    = 'half_day';
                        $arrMin    = $shStartMin + rand(-10, 10);
                        $depMin    = $arrMin + rand(240, 310);
                        $timeIn    = $this->minsToTime($arrMin);
                        $timeOut   = $this->minsToTime($depMin);
                        $hoursWork = round(($depMin - $arrMin) / 60, 1);
                    } elseif ($rand < 0.21) {
                        // late
                        $status    = 'late';
                        $arrMin    = $shStartMin + rand($lateThresh + 1, 90);
                        $depMin    = $shEndMin   + rand(-10, 45);
                        $timeIn    = $this->minsToTime($arrMin);
                        $timeOut   = $this->minsToTime($depMin);
                        $hoursWork = round(($depMin - $arrMin) / 60, 1);
                    } else {
                        // present
                        $status    = 'present';
                        $arrMin    = $shStartMin + rand(-15, $lateThresh - 1);
                        $depMin    = $shEndMin   + rand(0, 60);
                        $timeIn    = $this->minsToTime($arrMin);
                        $timeOut   = $this->minsToTime($depMin);
                        $hoursWork = round(($depMin - $arrMin) / 60, 1);
                    }

                    $dateStr = $date->format('Y-m-d');
                    $batch[] = [
                        'employee_id'     => $emp->id,
                        'branch_id'       => $branch->id,
                        'date'            => $dateStr,
                        'time_in'         => $timeIn,
                        'time_out'        => $timeOut,
                        'hours_worked'    => $hoursWork,
                        'status'          => $status,
                        'is_manual_entry' => 0,
                        'created_at'      => $dateStr . ' 23:59:00',
                        'updated_at'      => $dateStr . ' 23:59:00',
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('attendance_records')->insertOrIgnore($batch);
                        $batch = [];
                    }
                }
            }
        }

        if (!empty($batch)) {
            DB::table('attendance_records')->insertOrIgnore($batch);
        }

        $this->command->info('✅ Done! Now run forecasting to use Holt-Winters.');
        $this->command->table(
            ['Table', 'Count'],
            [
                ['attendance_records', DB::table('attendance_records')->count()],
            ]
        );
    }

    private function minsToTime(int $minutes): string
    {
        $minutes = max(0, min($minutes, 1439));
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
