<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PositionSeeder::class,
            DemoSeeder::class,        // system settings + admin/hr/manager accounts
            FakeDataSeeder::class,    // 5 branches, 125 employees, multi-year attendance + leaves
            QuickForecastDataSeeder::class, // recent 30-day attendance for forecasting
        ]);
    }
}
