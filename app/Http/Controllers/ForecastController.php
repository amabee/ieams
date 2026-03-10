<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Forecast;
use App\Services\ForecastingService;
use Carbon\Carbon;
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

        if (!$branchId) {
            return response()->json(['error' => 'Branch required'], 422);
        }

        // --- Historical: last 60 days of present+late counts ---
        $histStart = today()->subDays(59);
        $histRaw   = AttendanceRecord::where('branch_id', $branchId)
            ->where('date', '>=', $histStart)
            ->where('date', '<', today())
            ->selectRaw('date, SUM(status IN ("present","late")) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => Carbon::parse($r->date)->format('Y-m-d'),
                'count' => (int) $r->count,
            ]);

        // --- Forecast: next 30 days from stored forecasts ---
        $from      = today()->format('Y-m-d');
        $to        = today()->addDays(30)->format('Y-m-d');
        $forecasts = $forecasting->getForecast($branchId, $from, $to);

        $forecastData = $forecasts->map(fn ($f) => [
            'date'      => $f->forecast_date->format('Y-m-d'),
            'predicted' => $f->predicted_absent_count,
        ]);

        $avgForecast = $forecastData->count()
            ? round($forecastData->avg('predicted'), 1)
            : 0;

        $lastUpdate = $forecasts->max('generated_at')?->format('M d, Y H:i') ?? 'Never';

        // --- Insights ---
        $trend = 'stable';
        if ($forecastData->count() >= 4) {
            $half  = (int) ($forecastData->count() / 2);
            $first = $forecastData->take($half)->avg('predicted');
            $last  = $forecastData->skip($half)->avg('predicted');
            if ($last > $first * 1.05)      $trend = 'increasing';
            elseif ($last < $first * 0.95)  $trend = 'decreasing';
        }

        // Peak/low day of week from historical
        $byDay = array_fill(0, 7, ['sum' => 0, 'cnt' => 0]);
        foreach ($histRaw as $row) {
            $dow = Carbon::parse($row['date'])->dayOfWeek;
            $byDay[$dow]['sum'] += $row['count'];
            $byDay[$dow]['cnt']++;
        }
        $dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $dayAvgs  = array_map(fn ($d) => $d['cnt'] > 0 ? $d['sum'] / $d['cnt'] : null, $byDay);
        $withData = array_filter($dayAvgs, fn ($v) => $v !== null);
        $peakDay  = $withData ? $dayNames[array_keys($withData, max($withData))[0]] : null;
        $lowDay   = $withData ? $dayNames[array_keys($withData, min($withData))[0]] : null;

        // Avg % change: last 7 historical days vs first 7 forecast days
        $histSlice    = $histRaw->reverse()->take(7)->avg('count');
        $forecastSlice = $forecastData->take(7)->avg('predicted');
        $avgChange = ($histSlice > 0 && $forecastSlice !== null)
            ? round((($forecastSlice - $histSlice) / $histSlice) * 100, 1)
            : null;

        return response()->json([
            'historical'  => $histRaw->values(),
            'forecast'    => $forecastData->values(),
            'avgForecast' => $avgForecast,
            'lastUpdate'  => $lastUpdate,
            'insights'    => compact('trend', 'peakDay', 'lowDay', 'avgChange'),
        ]);
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