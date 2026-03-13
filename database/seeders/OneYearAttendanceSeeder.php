<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\Position;
use App\Models\Employee;
use App\Models\User;

/**
 * Generates 1 year of realistic attendance data (plus employees, branches, shifts, leaves).
 *
 * Scope:
 *  - 5 branches, 3 shifts
 *  - 25 employees per branch (125 total)
 *  - Attendance from (today - 365 days) through yesterday
 *  - Leave records + balances for the current year
 *
 * Run with:
 *   php artisan migrate:fresh --seed --seeder=OneYearAttendanceSeeder
 *   php artisan db:seed --class=OneYearAttendanceSeeder
 */
class OneYearAttendanceSeeder extends Seeder
{
    const EMPLOYEES_PER_BRANCH = 25;
    const ABSENT_RATE          = 0.06;  // 6%
    const LATE_RATE            = 0.12;  // 12%
    const HALF_DAY_RATE        = 0.03;  // 3%
    const LEAVE_PER_EMPLOYEE   = 6;     // avg leave requests per employee

    private array $firstNames = [
        'Maria','Jose','Juan','Ana','Miguel','Rosa','Carlo','Liza','Mark','Grace',
        'Paolo','Janine','Rico','Carla','Nico','Bella','Dino','Lea','Renz','Pia',
        'Alvin','Claire','Bryan','Ivy','Kevin','Sheila','Aaron','Jessa','Ryan','Trish',
        'Nathan','Angel','Ben','Cathy','Dan','Erika','Francis','Gina','Harry','Irene',
        'John','Karen','Leo','Mia','Neil','Olive','Percy','Quinn','Ralph','Sara',
        'Tony','Uma','Vince','Wanda','Rex','Stella','Troy','Aileen','Bert','Coleen',
    ];

    private array $lastNames = [
        'Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza','Torres','Flores','Ramos',
        'Dela Cruz','Villanueva','Gonzales','Aquino','Diaz','Castillo','Morales','Lim','Tan','Go',
        'Aguilar','Corpuz','Domingo','Fernandez','Guevarra','Hernandez','Javier','Lacson',
        'Navarro','Pascual','Serrano','Tamayo','Valencia','Yap','Zamora','Estrada',
    ];

    public function run(): void
    {
        $this->command->info('🌱 OneYearAttendanceSeeder starting...');

        // Bootstrap roles & positions if running standalone
        $this->call([RoleSeeder::class, PositionSeeder::class]);

        DB::disableQueryLog();

        $startDate = today()->subDays(365);
        $today     = today();

        // ── 1. Branches ──────────────────────────────────────────
        $this->command->info('Creating branches...');
        $branches = $this->seedBranches();

        // ── 2. Shifts ────────────────────────────────────────────
        $this->command->info('Creating shifts...');
        $shifts = $this->seedShifts();

        // ── 3. Employees + Users ─────────────────────────────────
        $this->command->info('Creating 125 employees...');
        $positions = Position::where('is_active', true)->pluck('id')->toArray();
        $employees = $this->seedEmployees($branches, $shifts, $positions, $startDate);

        // ── 4. Attendance ─────────────────────────────────────────
        $this->command->info('Generating attendance records for 1 year...');
        $this->seedAttendance($employees, $startDate, $today);

        // ── 5. Leaves ─────────────────────────────────────────────
        $this->command->info('Generating leave records...');
        $this->seedLeaves($employees, $startDate);

        $this->command->info('✅ Done!');
        $this->command->table(
            ['Table', 'Count'],
            [
                ['branches',           Branch::count()],
                ['employees',          Employee::count()],
                ['attendance_records', DB::table('attendance_records')->count()],
                ['leaves',             DB::table('leaves')->count()],
                ['leave_balances',     DB::table('leave_balances')->count()],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────

    private function seedBranches(): array
    {
        $data = [
            ['name' => 'Main Branch',  'address' => '123 Rizal St., Cagayan de Oro City',  'contact_no' => '09171234567', 'email' => 'main@petpal.com'],
            ['name' => 'North Branch', 'address' => '45 Limketkai Dr., CDO City',           'contact_no' => '09181234568', 'email' => 'north@petpal.com'],
            ['name' => 'South Branch', 'address' => '78 Corrales Ave., CDO City',           'contact_no' => '09191234569', 'email' => 'south@petpal.com'],
            ['name' => 'East Branch',  'address' => '200 Pabayo-Chavez St., CDO City',      'contact_no' => '09201234570', 'email' => 'east@petpal.com'],
            ['name' => 'West Branch',  'address' => '15 Velez-Tiano St., CDO City',         'contact_no' => '09211234571', 'email' => 'west@petpal.com'],
        ];
        $result = [];
        foreach ($data as $b) {
            $result[] = Branch::firstOrCreate(['name' => $b['name']], array_merge($b, ['is_active' => true]));
        }
        return $result;
    }

    private function seedShifts(): array
    {
        $data = [
            ['name' => 'Morning Shift',   'start_time' => '08:00:00', 'end_time' => '17:00:00', 'late_threshold_minutes' => 15],
            ['name' => 'Afternoon Shift', 'start_time' => '13:00:00', 'end_time' => '22:00:00', 'late_threshold_minutes' => 15],
            ['name' => 'Mid Shift',       'start_time' => '10:00:00', 'end_time' => '19:00:00', 'late_threshold_minutes' => 15],
        ];
        $result = [];
        foreach ($data as $s) {
            $result[] = Shift::firstOrCreate(['name' => $s['name']], $s);
        }
        return $result;
    }

    private function seedEmployees(array $branches, array $shifts, array $positions, Carbon $startDate): array
    {
        $employees  = [];
        $counter    = Employee::max('id') ?? 0;
        $empTypes   = ['full_time', 'full_time', 'full_time', 'part_time', 'contractual'];
        $genders    = ['male', 'female', 'female', 'male', 'other'];
        $civStatus  = ['single', 'single', 'married', 'married', 'separated'];

        foreach ($branches as $branch) {
            for ($i = 0; $i < self::EMPLOYEES_PER_BRANCH; $i++) {
                $counter++;
                $empNo = 'EMP-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

                if (Employee::where('employee_no', $empNo)->exists()) {
                    $employees[] = Employee::where('employee_no', $empNo)->first();
                    continue;
                }

                $first    = $this->firstNames[array_rand($this->firstNames)];
                $last     = $this->lastNames[array_rand($this->lastNames)];
                $email    = strtolower($first . '.' . $last . $counter) . '@ieams.test';
                $shift    = $shifts[array_rand($shifts)];
                $hireDate = $startDate->copy()->subDays(rand(0, 60));   // hired up to 2 months before coverage starts

                $emp = Employee::create([
                    'employee_no'     => $empNo,
                    'first_name'      => $first,
                    'last_name'       => $last,
                    'position_id'     => $positions ? $positions[array_rand($positions)] : null,
                    'employment_type' => $empTypes[array_rand($empTypes)],
                    'branch_id'       => $branch->id,
                    'shift_id'        => $shift->id,
                    'hire_date'       => $hireDate->format('Y-m-d'),
                    'status'          => rand(0, 9) > 0 ? 'active' : 'inactive',
                    'contact_no'      => '09' . rand(100000000, 999999999),
                    'address'         => rand(1, 99) . ' Sample St., Cagayan de Oro City',
                    'birthdate'       => Carbon::now()->subYears(rand(20, 50))->format('Y-m-d'),
                    'gender'          => $genders[array_rand($genders)],
                    'civil_status'    => $civStatus[array_rand($civStatus)],
                    'basic_salary'    => rand(15000, 60000),
                    'sss_no'          => rand(10, 99) . '-' . rand(1000000, 9999999) . '-' . rand(0, 9),
                    'philhealth_no'   => rand(10, 99) . '-' . rand(100000000, 999999999) . '-' . rand(0, 9),
                    'pagibig_no'      => rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'tin_no'          => rand(100, 999) . '-' . rand(100, 999) . '-' . rand(100, 999),
                ]);

                if (!User::where('email', $email)->exists()) {
                    $user = User::create([
                        'name'        => $first . ' ' . $last,
                        'email'       => $email,
                        'password'    => Hash::make('password'),
                        'employee_id' => $emp->id,
                        'branch_id'   => $branch->id,
                        'is_active'   => $emp->status === 'active',
                    ]);
                    $user->assignRole('employee');
                }

                $employees[] = $emp;
            }
        }

        return $employees;
    }

    private function seedAttendance(array $employees, Carbon $startDate, Carbon $today): void
    {
        $shiftMap = Shift::all()->keyBy('id');

        // Philippine holiday high-absence windows within the covered year range
        $holidayPeriods = [];
        $years = array_unique([$startDate->year, $today->year]);
        foreach ($years as $yr) {
            $holidayPeriods[] = [Carbon::create($yr, 12, 18), Carbon::create($yr, 12, 31)];
            $holidayPeriods[] = [Carbon::create($yr,  3, 28), Carbon::create($yr,  4,  8)];
            $holidayPeriods[] = [Carbon::create($yr,  6, 10), Carbon::create($yr,  6, 14)];
            $holidayPeriods[] = [Carbon::create($yr, 10, 30), Carbon::create($yr, 11,  3)];
        }

        $batch     = [];
        $batchSize = 500;

        $bar = $this->command->getOutput()->createProgressBar(count($employees));
        $bar->start();

        foreach ($employees as $emp) {
            $shift = $shiftMap[$emp->shift_id] ?? null;
            [$shStartH, $shStartM] = $shift ? array_map('intval', explode(':', $shift->start_time)) : [8, 0];
            [$shEndH,   $shEndM]   = $shift ? array_map('intval', explode(':', $shift->end_time))   : [17, 0];
            $lateThresh = $shift?->late_threshold_minutes ?? 15;
            $shStartMin = $shStartH * 60 + $shStartM;
            $shEndMin   = $shEndH   * 60 + $shEndM;

            $empStart = Carbon::parse($emp->hire_date)->gt($startDate) ? Carbon::parse($emp->hire_date) : $startDate->copy();
            $current  = $empStart->copy();

            while ($current->lte($today)) {
                $dow = $current->dayOfWeek;

                // Skip weekends
                if ($dow === 0 || $dow === 6) {
                    $current->addDay();
                    continue;
                }

                $nearHoliday = false;
                foreach ($holidayPeriods as [$hStart, $hEnd]) {
                    if ($current->between($hStart, $hEnd)) { $nearHoliday = true; break; }
                }

                $isFriday   = $dow === 5;
                $isMonday   = $dow === 1;
                $absentRate = $nearHoliday ? self::ABSENT_RATE * 2.5 : self::ABSENT_RATE;
                $lateRate   = $isMonday    ? self::LATE_RATE   * 1.4 : self::LATE_RATE;
                $halfRate   = $isFriday    ? self::HALF_DAY_RATE * 1.5 : self::HALF_DAY_RATE;

                $rand = mt_rand(0, 9999) / 10000;

                if ($rand < $absentRate) {
                    $status    = 'absent';
                    $timeIn    = null;
                    $timeOut   = null;
                    $hoursWork = null;

                } elseif ($rand < $absentRate + $halfRate) {
                    $status    = 'half_day';
                    $arrMin    = $shStartMin + rand(-10, 10);
                    $depMin    = $arrMin + rand(240, 310);
                    $timeIn    = $this->minsToTime($arrMin);
                    $timeOut   = $this->minsToTime($depMin);
                    $hoursWork = round(($depMin - $arrMin) / 60, 1);

                } elseif ($rand < $absentRate + $halfRate + $lateRate) {
                    $status    = 'late';
                    $arrMin    = $shStartMin + rand($lateThresh + 1, 100);
                    $depMin    = $shEndMin   + rand(-10, 45);
                    $timeIn    = $this->minsToTime($arrMin);
                    $timeOut   = $this->minsToTime($depMin);
                    $hoursWork = round(($depMin - $arrMin) / 60, 1);

                } else {
                    $status    = 'present';
                    $offset    = rand(0, 1) ? -rand(1, 15) : rand(0, $lateThresh - 1);
                    $arrMin    = $shStartMin + $offset;
                    $depMin    = $shEndMin   + ($isFriday ? rand(-20, 10) : rand(0, 60));
                    $timeIn    = $this->minsToTime($arrMin);
                    $timeOut   = $this->minsToTime($depMin);
                    $hoursWork = round(($depMin - $arrMin) / 60, 1);
                }

                $batch[] = [
                    'employee_id'     => $emp->id,
                    'branch_id'       => $emp->branch_id,
                    'date'            => $current->format('Y-m-d'),
                    'time_in'         => $timeIn,
                    'time_out'        => $timeOut,
                    'hours_worked'    => $hoursWork,
                    'status'          => $status,
                    'is_manual_entry' => 0,
                    'created_at'      => $current->format('Y-m-d') . ' 23:59:00',
                    'updated_at'      => $current->format('Y-m-d') . ' 23:59:00',
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('attendance_records')->insertOrIgnore($batch);
                    $batch = [];
                }

                $current->addDay();
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            DB::table('attendance_records')->insertOrIgnore($batch);
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function minsToTime(int $mins): string
    {
        $mins = max(0, min($mins, 1439));
        return sprintf('%02d:%02d:00', intdiv($mins, 60), $mins % 60);
    }

    private function seedLeaves(array $employees, Carbon $startDate): void
    {
        $leaveTypes = ['sick', 'vacation', 'emergency', 'other'];
        $statuses   = ['approved', 'approved', 'approved', 'pending', 'denied'];
        $reasons    = [
            'sick'      => ['Fever and flu', 'Doctor\'s appointment', 'Hospitalization', 'Medical check-up'],
            'vacation'  => ['Family vacation', 'Rest and recuperation', 'Out-of-town trip', 'Personal time off'],
            'emergency' => ['Family emergency', 'Death in the family', 'Home emergency', 'Child sick'],
            'other'     => ['Personal reasons', 'School event', 'Government errand', 'Community event'],
        ];

        $leaveBatch = [];
        $balBatch   = [];
        $years      = array_unique([$startDate->year, now()->year]);

        foreach ($employees as $emp) {
            // Leave balances
            foreach ($leaveTypes as $lt) {
                foreach ($years as $yr) {
                    $balBatch[] = [
                        'employee_id' => $emp->id,
                        'leave_type'  => $lt,
                        'year'        => $yr,
                        'total_days'  => 15,
                        'used_days'   => 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }

            // Leave requests
            $count = rand(max(1, self::LEAVE_PER_EMPLOYEE - 2), self::LEAVE_PER_EMPLOYEE + 2);
            for ($i = 0; $i < $count; $i++) {
                $type      = $leaveTypes[array_rand($leaveTypes)];
                $daysBack  = rand(7, 350);
                $leaveDate = now()->subDays($daysBack);

                // Skip weekends
                while ($leaveDate->isWeekend()) {
                    $leaveDate->addDay();
                }

                $duration = rand(1, 3);
                $status   = $statuses[array_rand($statuses)];

                $leaveBatch[] = [
                    'employee_id'  => $emp->id,
                    'leave_type'   => $type,
                    'start_date'   => $leaveDate->format('Y-m-d'),
                    'end_date'     => $leaveDate->copy()->addWeekdays($duration - 1)->format('Y-m-d'),
                    'total_days'   => $duration,
                    'reason'       => $reasons[$type][array_rand($reasons[$type])],
                    'status'       => $status,
                    'reviewed_by'  => null,
                    'review_comment' => null,
                    'created_at'   => $leaveDate->copy()->subDays(rand(1, 5))->format('Y-m-d H:i:s'),
                    'updated_at'   => now(),
                ];
            }
        }

        if (!empty($balBatch)) {
            foreach (array_chunk($balBatch, 500) as $chunk) {
                DB::table('leave_balances')->insertOrIgnore($chunk);
            }
        }
        if (!empty($leaveBatch)) {
            foreach (array_chunk($leaveBatch, 500) as $chunk) {
                DB::table('leaves')->insertOrIgnore($chunk);
            }
        }
    }
}
