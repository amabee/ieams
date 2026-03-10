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
 * Generates realistic fake data:
 *  - 5 branches
 *  - 3 shifts
 *  - ~120 employees spread across branches
 *  - 365 days of attendance records per employee (~43,800 rows)
 *  - Leave records (approved, pending, denied)
 *  - Leave balances
 *
 * Run with: php artisan db:seed --class=FakeDataSeeder
 */
class FakeDataSeeder extends Seeder
{
    // ─── Configuration ───────────────────────────────────────────
    const EMPLOYEES_PER_BRANCH = 25; // 5 branches × 25 = 125 employees
    const DAYS_BACK            = 365; // 1 year of attendance history
    const ABSENT_RATE          = 0.06;  // 6% chance of absent on any workday
    const LATE_RATE            = 0.12;  // 12% chance of late
    const HALF_DAY_RATE        = 0.03;  // 3% chance of half-day
    const LEAVE_PER_EMPLOYEE   = 4;    // avg leave requests per employee

    private array $firstNames = [
        'Maria','Jose','Juan','Ana','Miguel','Rosa','Carlo','Liza','Mark','Grace',
        'Paolo','Janine','Rico','Carla','Nico','Bella','Dino','Lea','Renz','Pia',
        'Alvin','Claire','Bryan','Ivy','Kevin','Sheila','Aaron','Jessa','Ryan','Trish',
        'Nathan','Angel','Ben','Cathy','Dan','Erika','Francis','Gina','Harry','Irene',
        'John','Karen','Leo','Mia','Neil','Olive','Percy','Queen','Ralph','Sara',
        'Tony','Uma','Vince','Wanda','Xandro','Ysa','Zach','Aileen','Bert','Coleen',
        'Dante','Elena','Fred','Gaby','Hank','Iris','Jake','Kira','Lando','Mavis',
        'Nina','Oscar','Prima','Quinn','Rex','Stella','Troy','Ursula','Victor','Wendy',
    ];

    private array $lastNames = [
        'Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza','Torres','Flores','Ramos',
        'Dela Cruz','Villanueva','Gonzales','Aquino','Diaz','Castillo','Morales','Lim','Tan','Go',
        'Aguilar','Buenaventura','Corpuz','Domingo','Evangelista','Fernandez','Guevarra','Hernandez',
        'Inguillo','Javier','Katipunan','Lacson','Macaraeg','Navarro','Ong','Pascual','Quizon',
        'Recto','Serrano','Tamayo','Umali','Valencia','Wijangco','Xavier','Yap','Zamora',
        'Abello','Balanag','Casinto','Delgado','Estrada',
    ];

    private array $streets = [
        'Rizal St.','Mabini Ave.','Bonifacio Blvd.','Quezon Ave.','Magallanes St.',
        'Burgos St.','Luna St.','Del Pilar St.','Osmena Blvd.','Roxas Blvd.',
    ];

    private array $cities = [
        'Cagayan de Oro City','Iligan City','Davao City','General Santos City','Butuan City',
    ];

    public function run(): void
    {
        $this->command->info('🌱 Starting FakeDataSeeder...');
        $this->command->info('This may take a minute — generating ~125 employees + 1 year of attendance.');

        DB::disableQueryLog();

        // ── 1. Branches ───────────────────────────────────────────
        $this->command->info('Creating branches...');
        $branches = $this->seedBranches();

        // ── 2. Shifts ─────────────────────────────────────────────
        $this->command->info('Creating shifts...');
        $shifts = $this->seedShifts();

        // ── 3. Positions ──────────────────────────────────────────
        $positions = Position::where('is_active', true)->pluck('id')->toArray();
        if (empty($positions)) {
            $this->command->warn('No positions found — run PositionSeeder first. Using null position_id.');
        }

        // ── 4. Employees + Users ──────────────────────────────────
        $this->command->info('Creating employees & user accounts...');
        $employees = $this->seedEmployees($branches, $shifts, $positions);

        // ── 5. Attendance records ─────────────────────────────────
        $this->command->info('Generating attendance records (this is the heavy part)...');
        $this->seedAttendance($employees, $branches);

        // ── 6. Leaves ─────────────────────────────────────────────
        $this->command->info('Generating leave records...');
        $this->seedLeaves($employees);

        $this->command->info('✅ FakeDataSeeder complete!');
        $this->command->table(
            ['Table', 'Rows Added'],
            [
                ['branches',            Branch::count()],
                ['employees',           Employee::count()],
                ['attendance_records',  DB::table('attendance_records')->count()],
                ['leaves',              DB::table('leaves')->count()],
                ['leave_balances',      DB::table('leave_balances')->count()],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    private function seedBranches(): array
    {
        $branchData = [
            ['name' => 'Main Branch',      'address' => '123 Rizal St., Cagayan de Oro City',    'contact_no' => '09171234567', 'email' => 'main@petpal.com'],
            ['name' => 'North Branch',     'address' => '45 Limketkai Dr., CDO City',             'contact_no' => '09181234568', 'email' => 'north@petpal.com'],
            ['name' => 'South Branch',     'address' => '78 Corrales Ave., CDO City',             'contact_no' => '09191234569', 'email' => 'south@petpal.com'],
            ['name' => 'East Branch',      'address' => '200 Pabayo-Chavez St., CDO City',        'contact_no' => '09201234570', 'email' => 'east@petpal.com'],
            ['name' => 'West Branch',      'address' => '15 Velez-Tiano St., CDO City',           'contact_no' => '09211234571', 'email' => 'west@petpal.com'],
        ];

        $result = [];
        foreach ($branchData as $b) {
            $result[] = Branch::firstOrCreate(['name' => $b['name']], array_merge($b, ['is_active' => true]));
        }
        return $result;
    }

    private function seedShifts(): array
    {
        $shiftData = [
            ['name' => 'Morning Shift',   'start_time' => '08:00:00', 'end_time' => '17:00:00', 'late_threshold_minutes' => 15],
            ['name' => 'Afternoon Shift', 'start_time' => '13:00:00', 'end_time' => '22:00:00', 'late_threshold_minutes' => 15],
            ['name' => 'Mid Shift',       'start_time' => '10:00:00', 'end_time' => '19:00:00', 'late_threshold_minutes' => 15],
        ];

        $result = [];
        foreach ($shiftData as $s) {
            $result[] = Shift::firstOrCreate(['name' => $s['name']], $s);
        }
        return $result;
    }

    private function seedEmployees(array $branches, array $shifts, array $positions): array
    {
        $employees   = [];
        $empCounter  = Employee::max('id') ?? 0;
        $employmentTypes = ['full_time', 'full_time', 'full_time', 'part_time', 'contractual'];
        $genders     = ['male', 'female', 'female', 'male', 'other'];
        $civilStatus = ['single', 'single', 'married', 'married', 'separated', 'widowed'];

        foreach ($branches as $branch) {
            for ($i = 0; $i < self::EMPLOYEES_PER_BRANCH; $i++) {
                $empCounter++;
                $empNo     = 'EMP-' . str_pad($empCounter, 4, '0', STR_PAD_LEFT);
                $firstName = $this->firstNames[array_rand($this->firstNames)];
                $lastName  = $this->lastNames[array_rand($this->lastNames)];
                $email     = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName))
                             . $empCounter . '@ieams.test';

                // Skip if already exists (idempotent)
                if (Employee::where('employee_no', $empNo)->exists()) {
                    $employees[] = Employee::where('employee_no', $empNo)->first();
                    continue;
                }

                $shift    = $shifts[array_rand($shifts)];
                $hireDate = Carbon::now()->subDays(rand(30, self::DAYS_BACK + 200));
                $gender   = $genders[array_rand($genders)];
                $salary   = rand(15000, 60000);

                $emp = Employee::create([
                    'employee_no'     => $empNo,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'middle_name'     => rand(0, 1) ? $this->lastNames[array_rand($this->lastNames)] : null,
                    'position_id'     => $positions ? $positions[array_rand($positions)] : null,
                    'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                    'branch_id'       => $branch->id,
                    'shift_id'        => $shift->id,
                    'hire_date'       => $hireDate->format('Y-m-d'),
                    'status'          => rand(0, 9) > 0 ? 'active' : 'inactive', // 90% active
                    'contact_no'      => '09' . rand(100000000, 999999999),
                    'address'         => rand(1, 99) . ' ' . $this->streets[array_rand($this->streets)] . ', ' . $this->cities[array_rand($this->cities)],
                    'birthdate'       => Carbon::now()->subYears(rand(20, 50))->subDays(rand(0, 365))->format('Y-m-d'),
                    'gender'          => $gender,
                    'civil_status'    => $civilStatus[array_rand($civilStatus)],
                    'basic_salary'    => $salary,
                    'sss_no'          => rand(10, 99) . '-' . rand(1000000, 9999999) . '-' . rand(0, 9),
                    'philhealth_no'   => rand(10, 99) . '-' . rand(100000000, 999999999) . '-' . rand(0, 9),
                    'pagibig_no'      => rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'tin_no'          => rand(100, 999) . '-' . rand(100, 999) . '-' . rand(100, 999) . '-' . rand(0, 999),
                ]);

                // Create linked user account
                if (!User::where('email', $email)->exists()) {
                    $user = User::create([
                        'name'        => $firstName . ' ' . $lastName,
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

    private function seedAttendance(array $employees, array $branches): void
    {
        $branchMap = collect($branches)->keyBy('id');
        $today     = today();
        $startDate = today()->subDays(self::DAYS_BACK - 1);

        // Holiday-like periods: higher absences around these windows
        $holidayPeriods = [
            [$today->copy()->subYear()->month(12)->startOfMonth()->day(20), $today->copy()->subYear()->month(12)->endOfMonth()],
            [$today->copy()->month(4)->startOfMonth()->day(1), $today->copy()->month(4)->startOfMonth()->day(7)],
        ];

        $batchSize = 500;
        $batch     = [];

        $bar = $this->command->getOutput()->createProgressBar(count($employees));
        $bar->start();

        foreach ($employees as $emp) {
            $empStart = max($startDate, Carbon::parse($emp->hire_date));

            $current = $empStart->copy();
            while ($current->lt($today)) {
                $dow = $current->dayOfWeek; // 0=Sun, 6=Sat

                // Skip weekends (Mon–Fri only: 1–5)
                if ($dow === 0 || $dow === 6) {
                    $current->addDay();
                    continue;
                }

                // Check if near a holiday period → higher absent rate
                $nearHoliday = false;
                foreach ($holidayPeriods as [$hStart, $hEnd]) {
                    if ($current->between($hStart, $hEnd)) {
                        $nearHoliday = true;
                        break;
                    }
                }

                $absentRate  = $nearHoliday ? self::ABSENT_RATE * 3 : self::ABSENT_RATE;
                $lateRate    = self::LATE_RATE;
                $halfDayRate = self::HALF_DAY_RATE;

                $rand = mt_rand(0, 9999) / 10000;

                if ($rand < $absentRate) {
                    $status    = 'absent';
                    $timeIn    = null;
                    $timeOut   = null;
                    $hoursWork = null;
                } elseif ($rand < $absentRate + $halfDayRate) {
                    $status    = 'half_day';
                    $timeIn    = sprintf('%02d:%02d:00', rand(8, 9), rand(0, 59));
                    $timeOut   = sprintf('%02d:%02d:00', rand(12, 14), rand(0, 59));
                    $hoursWork = rand(3, 5) + rand(0, 9) / 10;
                } elseif ($rand < $absentRate + $halfDayRate + $lateRate) {
                    $status    = 'late';
                    $minutesLate = rand(16, 90);
                    $timeIn    = sprintf('%02d:%02d:00', 8 + intdiv($minutesLate, 60), $minutesLate % 60);
                    $timeOut   = sprintf('%02d:%02d:00', rand(17, 18), rand(0, 59));
                    $hoursWork = rand(6, 8) + rand(0, 9) / 10;
                } else {
                    $status    = 'present';
                    $timeIn    = sprintf('%02d:%02d:00', 7 + rand(0, 1), rand(55, 59));
                    $timeOut   = sprintf('%02d:%02d:00', rand(17, 18), rand(0, 59));
                    $hoursWork = 8 + rand(0, 5) * 0.5;
                }

                $batch[] = [
                    'employee_id'    => $emp->id,
                    'branch_id'      => $emp->branch_id,
                    'date'           => $current->format('Y-m-d'),
                    'time_in'        => $timeIn,
                    'time_out'       => $timeOut,
                    'hours_worked'   => $hoursWork,
                    'status'         => $status,
                    'is_manual_entry'=> 0,
                    'created_at'     => $current->format('Y-m-d') . ' 23:59:00',
                    'updated_at'     => $current->format('Y-m-d') . ' 23:59:00',
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('attendance_records')->insertOrIgnore($batch);
                    $batch = [];
                }

                $current->addDay();
            }

            $bar->advance();
        }

        // Flush remaining
        if (!empty($batch)) {
            DB::table('attendance_records')->insertOrIgnore($batch);
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function seedLeaves(array $employees): void
    {
        $leaveTypes   = ['sick', 'vacation', 'emergency', 'other'];
        $leaveWeights = [40, 35, 15, 10]; // % distribution
        $statuses     = ['approved', 'approved', 'approved', 'pending', 'denied'];
        $reasons      = [
            'sick'      => ['Fever and flu', 'Doctor\'s appointment', 'Hospitalization', 'Medical check-up', 'Severe headache'],
            'vacation'  => ['Family vacation', 'Rest and recuperation', 'Out-of-town trip', 'Personal time off', 'Anniversary celebration'],
            'emergency' => ['Family emergency', 'Death in the family', 'Home emergency', 'Child sick', 'Accident'],
            'other'     => ['Personal reasons', 'School event', 'Government errand', 'Community event', 'Other personal matter'],
        ];

        $year       = now()->year;
        $prevYear   = $year - 1;
        $leaveBatch = [];
        $balBatch   = [];

        // Leave balances first
        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                // Previous year — fully consumed
                $balBatch[] = [
                    'employee_id' => $emp->id,
                    'leave_type'  => $lt,
                    'year'        => $prevYear,
                    'total_days'  => 15,
                    'used_days'   => rand(5, 15),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                // Current year — partially used
                $balBatch[] = [
                    'employee_id' => $emp->id,
                    'leave_type'  => $lt,
                    'year'        => $year,
                    'total_days'  => 15,
                    'used_days'   => rand(0, 8),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        // Batch-insert balances (ignore dupes)
        foreach (array_chunk($balBatch, 1000) as $chunk) {
            DB::table('leave_balances')->insertOrIgnore($chunk);
        }

        // Leave requests
        foreach ($employees as $emp) {
            $count = rand(2, self::LEAVE_PER_EMPLOYEE + 2);
            for ($i = 0; $i < $count; $i++) {
                $type = $this->weighted($leaveTypes, $leaveWeights);

                // Random date in last 12 months
                $startDays = rand(0, self::DAYS_BACK - 1);
                $start     = today()->subDays($startDays);

                // Skip if weekend
                while ($start->isWeekend()) {
                    $start->addDay();
                }

                $duration = rand(1, 3);
                $end      = $start->copy()->addWeekdays($duration - 1);

                $status = $statuses[array_rand($statuses)];

                $leaveBatch[] = [
                    'employee_id'    => $emp->id,
                    'leave_type'     => $type,
                    'start_date'     => $start->format('Y-m-d'),
                    'end_date'       => $end->format('Y-m-d'),
                    'total_days'     => $duration,
                    'reason'         => $reasons[$type][array_rand($reasons[$type])],
                    'status'         => $status,
                    'reviewed_by'    => $status !== 'pending' ? 1 : null,
                    'review_comment' => $status === 'denied' ? 'Insufficient leave balance / scheduling conflict.' : null,
                    'created_at'     => $start->copy()->subDays(rand(1, 7))->format('Y-m-d H:i:s'),
                    'updated_at'     => now()->format('Y-m-d H:i:s'),
                ];
            }
        }

        foreach (array_chunk($leaveBatch, 500) as $chunk) {
            DB::table('leaves')->insert($chunk);
        }
    }

    /** Weighted random picker */
    private function weighted(array $items, array $weights): string
    {
        $total  = array_sum($weights);
        $rand   = rand(1, $total);
        $cumulative = 0;
        foreach ($items as $i => $item) {
            $cumulative += $weights[$i];
            if ($rand <= $cumulative) {
                return $item;
            }
        }
        return $items[array_key_last($items)];
    }
}
