<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Forecast;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ForecastingService
{
    private float $alpha; // level smoothing
    private float $beta;  // trend smoothing
    private float $gamma; // seasonal smoothing
    private int   $period = 7; // weekly seasonality

    public function __construct()
    {
        $this->alpha = (float) SystemSetting::get('forecast_alpha', 0.3);
        $this->beta  = (float) SystemSetting::get('forecast_beta',  0.1);
        $this->gamma = (float) SystemSetting::get('forecast_gamma', 0.2);
    }

    /**
     * Collect historical daily absenteeism counts for a branch.
     */
    public function collectHistoricalData(int $branchId, int $days = 180): array
    {
        $start = today()->subDays($days);

        $records = AttendanceRecord::where('branch_id', $branchId)
            ->where('date', '>=', $start)
            ->selectRaw('date, COUNT(CASE WHEN status IN ("absent","on_leave") THEN 1 END) as absent_count, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill gaps with 0
        $data = [];
        $current = clone $start;
        $end = today()->subDay();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $row = $records->firstWhere('date', $dateStr);
            $data[] = $row ? (float) $row->absent_count : 0.0;
            $current->addDay();
        }

        return $data;
    }

    /**
     * Holt-Winters Triple Exponential Smoothing.
     *
     * @param float[] $data     Historical series
     * @param int     $horizon  Number of future steps to forecast
     * @return array{values: float[], usedFallback: bool}
     */
    public function holtwinters(array $data, int $horizon): array
    {
        $n = count($data);
        if ($n < $this->period * 2) {
            // Not enough data — fall back to simple moving average
            return ['values' => $this->movingAverage($data, $horizon), 'usedFallback' => true];
        }

        // Initialize seasonal components
        $seasonal = $this->initSeasonals($data);

        // Initialize level and trend
        $level = array_sum(array_slice($data, 0, $this->period)) / $this->period;
        $trend = 0.0;
        for ($i = 0; $i < $this->period; $i++) {
            $trend += ($data[$i + $this->period] - $data[$i]) / $this->period;
        }
        $trend /= $this->period;

        // Smoothing pass
        for ($i = 0; $i < $n; $i++) {
            $seasonIdx   = $i % $this->period;
            $prevLevel   = $level;
            $level       = $this->alpha * ($data[$i] - $seasonal[$seasonIdx]) + (1 - $this->alpha) * ($level + $trend);
            $trend       = $this->beta  * ($level - $prevLevel) + (1 - $this->beta) * $trend;
            $seasonal[$seasonIdx] = $this->gamma * ($data[$i] - $level) + (1 - $this->gamma) * $seasonal[$seasonIdx];
        }

        // Forecast future values
        $forecast = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $seasonIdx  = ($n + $h - 1) % $this->period;
            $predicted  = ($level + $h * $trend) + $seasonal[$seasonIdx];
            $forecast[] = max(0.0, round($predicted, 2));
        }

        return ['values' => $forecast, 'usedFallback' => false];
    }

    private function initSeasonals(array $data): array
    {
        $seasonal = array_fill(0, $this->period, 0.0);
        $nPeriods = (int) floor(count($data) / $this->period);

        // Seasonal averages
        for ($i = 0; $i < $this->period; $i++) {
            $sum = 0.0;
            for ($j = 0; $j < $nPeriods; $j++) {
                $sum += $data[$j * $this->period + $i];
            }
            $seasonal[$i] = $sum / $nPeriods;
        }

        return $seasonal;
    }

    private function movingAverage(array $data, int $horizon): array
    {
        $window = min(7, count($data));
        $slice  = array_slice($data, -$window);
        $avg    = array_sum($slice) / $window;
        return array_fill(0, $horizon, round($avg, 2));
    }

    /**
     * Run and persist forecasts for a branch.
     */
    public function runForBranch(Branch $branch, int $horizon = 30): void
    {
        $data = $this->collectHistoricalData($branch->id, 180);

        if (empty($data)) {
            return;
        }

        $result      = $this->holtwinters($data, $horizon);
        $predictions = $result['values'];
        $modelUsed   = $result['usedFallback'] ? 'moving_average' : 'holt_winters';

        // Get total employees for rate calculation
        $totalEmployees = $branch->employees()->where('status', 'active')->count();

        $generatedAt = now();
        foreach ($predictions as $offset => $predictedCount) {
            $forecastDate = today()->addDays($offset + 1);
            $rate = $totalEmployees > 0
                ? round(($predictedCount / $totalEmployees) * 100, 2)
                : 0.0;

            Forecast::updateOrCreate(
                ['branch_id' => $branch->id, 'forecast_date' => $forecastDate->format('Y-m-d')],
                [
                    'predicted_absent_count'      => (int) round($predictedCount),
                    'predicted_absenteeism_rate'  => $rate,
                    'model_used'                  => $modelUsed,
                    'confidence_level'            => $result['usedFallback'] ? 50.0 : 75.0,
                    'generated_at'                => $generatedAt,
                ]
            );
        }
    }

    /**
     * Get stored forecasts for a branch within a date range.
     */
    public function getForecast(?int $branchId, string $fromDate, string $toDate): Collection
    {
        return Forecast::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('forecast_date', [$fromDate, $toDate])
            ->orderBy('forecast_date')
            ->get();
    }
}
