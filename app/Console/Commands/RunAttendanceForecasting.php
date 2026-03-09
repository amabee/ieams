<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\ForecastingService;
use Illuminate\Console\Command;

class RunAttendanceForecasting extends Command
{
    protected $signature   = 'forecast:run {--branch= : Run for a specific branch ID}';
    protected $description = 'Run Holt-Winters attendance forecasting for all active branches';

    public function handle(ForecastingService $forecasting): int
    {
        $query = Branch::where('is_active', true);

        if ($branchId = $this->option('branch')) {
            $query->where('id', $branchId);
        }

        $branches = $query->get();

        if ($branches->isEmpty()) {
            $this->warn('No active branches found.');
            return self::FAILURE;
        }

        foreach ($branches as $branch) {
            $this->info("Running forecast for: {$branch->name}");
            $forecasting->runForBranch($branch, 30);
            $this->info("  Done.");
        }

        $this->info('Forecasting complete.');
        return self::SUCCESS;
    }
}
