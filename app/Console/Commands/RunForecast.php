<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\ForecastingService;
use Illuminate\Console\Command;

class RunForecast extends Command
{
    protected $signature   = 'forecast:run {--branch= : Branch ID to run for a specific branch only}';
    protected $description = 'Generate attendance forecasts using Holt-Winters exponential smoothing';

    public function handle(ForecastingService $forecasting): int
    {
        $branchId = $this->option('branch');

        $branches = $branchId
            ? Branch::where('id', $branchId)->where('is_active', true)->get()
            : Branch::where('is_active', true)->get();

        if ($branches->isEmpty()) {
            $this->warn('No active branches found.');
            return self::FAILURE;
        }

        $this->info("Running forecast for {$branches->count()} branch(es)...");
        $bar = $this->output->createProgressBar($branches->count());
        $bar->start();

        foreach ($branches as $branch) {
            try {
                $forecasting->runForBranch($branch, 30);
                $bar->advance();
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Failed for branch [{$branch->name}]: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Forecast generation complete.');

        return self::SUCCESS;
    }
}
