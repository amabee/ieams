<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Forecast;
use App\Models\SystemSetting;
use App\Services\ForecastingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ForecastController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchId = $request->branch_id ? (int) $request->branch_id : null;

        return view('forecasting.index', compact('branches', 'branchId'));
    }

    public function data(Request $request, ForecastingService $forecasting)
    {
        $branchId = $request->branch_id ? (int) $request->branch_id : null;
        $horizon  = min((int) ($request->horizon ?? 30), 90);

        // --- Historical: last 60 days of present+late counts ---
        $histStart = today()->subDays(59);
        $histRaw   = AttendanceRecord::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
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

        // --- Forecast: stored forecasts for the selected horizon ---
        $from = today()->format('Y-m-d');
        $to   = today()->addDays($horizon)->format('Y-m-d');

        if ($branchId) {
            $forecasts    = $forecasting->getForecast($branchId, $from, $to);
            $usedFallback = $forecasts->contains('model_used', 'moving_average');
        } else {
            // Aggregate across all branches per date
            $forecasts = Forecast::whereBetween('forecast_date', [$from, $to])
                ->select('forecast_date')
                ->selectRaw('SUM(predicted_absent_count) as predicted_absent_count')
                ->selectRaw('AVG(predicted_absenteeism_rate) as predicted_absenteeism_rate')
                ->selectRaw('SUM(CASE WHEN model_used = "moving_average" THEN 1 ELSE 0 END) as fallback_count')
                ->selectRaw('COUNT(*) as row_count')
                ->selectRaw('AVG(confidence_level) as confidence_level')
                ->selectRaw('MAX(generated_at) as generated_at')
                ->groupBy('forecast_date')
                ->orderBy('forecast_date')
                ->get();

            // Only trigger the fallback warning if the MAJORITY of branch-days used moving_average
            $totalRows     = $forecasts->sum('row_count');
            $totalFallback = $forecasts->sum('fallback_count');
            $usedFallback  = $totalRows > 0 && ($totalFallback / $totalRows) > 0.5;
        }
        $confidence   = $forecasts->avg('confidence_level') ?? 75;
        // CI half-width: ~15% for Holt-Winters (75% CI), ~25% for moving average (50% CI)
        $ciWidth = $usedFallback ? 0.25 : 0.15;

        $forecastData = $forecasts->map(fn ($f) => [
            'date'      => Carbon::parse($f->forecast_date)->format('Y-m-d'),
            'predicted' => $f->predicted_absent_count,
            'upper'     => (int) round($f->predicted_absent_count * (1 + $ciWidth)),
            'lower'     => (int) max(0, round($f->predicted_absent_count * (1 - $ciWidth))),
        ]);

        $avgForecast  = $forecastData->count() ? round($forecastData->avg('predicted'), 1) : 0;
        $lastUpdate   = $forecasts->max('generated_at')?->format('M d, Y H:i') ?? null;

        // --- Trend insight ---
        $trend = 'stable';
        if ($forecastData->count() >= 4) {
            $half  = (int) ($forecastData->count() / 2);
            $first = $forecastData->take($half)->avg('predicted');
            $last  = $forecastData->skip($half)->avg('predicted');
            if ($last > $first * 1.05)     $trend = 'increasing';
            elseif ($last < $first * 0.95) $trend = 'decreasing';
        }

        // --- Peak/low day of week from historical ---
        $byDay = array_fill(0, 7, ['sum' => 0, 'cnt' => 0]);
        foreach ($histRaw as $row) {
            $dow = Carbon::parse($row['date'])->dayOfWeek;
            $byDay[$dow]['sum'] += $row['count'];
            $byDay[$dow]['cnt']++;
        }
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayAvgs  = array_map(fn ($d) => $d['cnt'] > 0 ? $d['sum'] / $d['cnt'] : null, $byDay);
        $withData = array_filter($dayAvgs, fn ($v) => $v !== null);
        $peakDay  = $withData ? $dayNames[array_keys($withData, max($withData))[0]] : null;
        $lowDay   = $withData ? $dayNames[array_keys($withData, min($withData))[0]] : null;

        // --- Avg % change: last 7 historical vs first 7 forecast ---
        $histSlice     = $histRaw->reverse()->take(7)->avg('count');
        $forecastSlice = $forecastData->take(7)->avg('predicted');
        $avgChange     = ($histSlice > 0 && $forecastSlice !== null)
            ? round((($forecastSlice - $histSlice) / $histSlice) * 100, 1)
            : null;

        // --- Dynamic smoothing params ---
        $params = [
            'alpha'  => (float) SystemSetting::get('forecast_alpha', 0.3),
            'beta'   => (float) SystemSetting::get('forecast_beta',  0.1),
            'gamma'  => (float) SystemSetting::get('forecast_gamma', 0.2),
        ];

        return response()->json([
            'historical'   => $histRaw->values(),
            'forecast'     => $forecastData->values(),
            'avgForecast'  => $avgForecast,
            'lastUpdate'   => $lastUpdate,
            'usedFallback' => $usedFallback,
            'confidence'   => $confidence,
            'params'       => $params,
            'insights'     => compact('trend', 'peakDay', 'lowDay', 'avgChange'),
        ]);
    }

    public function run(Request $request, ForecastingService $forecasting)
    {
        $branchId = $request->input('branch_id');

        $branches = $branchId
            ? Branch::where('id', $branchId)->where('is_active', true)->get()
            : Branch::where('is_active', true)->get();

        foreach ($branches as $branch) {
            $forecasting->runForBranch($branch, 30);
        }

        $label = $branchId ? ($branches->first()?->name ?? 'selected branch') : 'all branches';

        if ($request->expectsJson()) {
            return response()->json(['message' => "Forecast generated for {$label}."]);
        }

        return back()->with('success', "Forecast generated for {$label}.");
    }

    public function interpret(Request $request)
    {
        $request->validate([
            'branch_name'  => 'required|string',
            'horizon'      => 'required|integer',
            'trend'        => 'required|string',
            'avg_forecast' => 'required|numeric',
            'avg_change'   => 'nullable|numeric',
            'peak_day'     => 'nullable|string',
            'low_day'      => 'nullable|string',
            'used_fallback'=> 'boolean',
            'confidence'   => 'required|numeric',
        ]);

        $apiKey = env('OPEN_API_KEY');

        if (!$apiKey) {
            return response()->json(['interpretation' => $this->ruleBasedInterpretation($request->all())]);
        }

        $prompt = $this->buildPrompt($request->all());

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders(['HTTP-Referer' => config('app.url'), 'X-Title' => config('app.name')])
                ->timeout(20)
                ->post('https://openrouter.ai/api/v1/embeddings', [
                    'model'       => 'nvidia/llama-nemotron-embed-vl-1b-v2:free',
                    'temperature' => 0.5,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are an HR analytics assistant. Write concise, plain-English attendance forecast summaries (3–5 sentences) for HR managers. Be specific, actionable, and professional. Do not use bullet points or markdown.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                return response()->json(['interpretation' => trim($text)]);
            }
        } catch (\Throwable) {
            // fall through to rule-based
        }

        return response()->json(['interpretation' => $this->ruleBasedInterpretation($request->all())]);
    }

    private function buildPrompt(array $d): string
    {
        $change = isset($d['avg_change'])
            ? ($d['avg_change'] > 0 ? "+{$d['avg_change']}%" : "{$d['avg_change']}%")
            : 'unknown';

        $fallback = $d['used_fallback']
            ? 'Note: the forecast used a simple moving average due to limited historical data, so accuracy may be lower.'
            : "The forecast used Holt-Winters triple exponential smoothing with {$d['confidence']}% confidence.";

        return "Branch: {$d['branch_name']}. Forecast horizon: {$d['horizon']} days. "
            . "Attendance trend: {$d['trend']}. Average predicted daily attendance: {$d['avg_forecast']} employees. "
            . "Change vs last period: {$change}. "
            . ($d['peak_day']  ? "Highest attendance day: {$d['peak_day']}. " : '')
            . ($d['low_day']   ? "Lowest attendance day: {$d['low_day']}. "  : '')
            . $fallback
            . ' Summarize this for an HR manager in 3–5 plain sentences with any recommended actions.';
    }

    private function ruleBasedInterpretation(array $d): string
    {
        $branch  = $d['branch_name'];
        $horizon = $d['horizon'];
        $avg     = $d['avg_forecast'];
        $trend   = $d['trend'];
        $change  = $d['avg_change'] ?? null;
        $peak    = $d['peak_day']   ?? null;
        $low     = $d['low_day']    ?? null;
        $fallback = $d['used_fallback'] ?? false;

        $sentences = [];

        // Opening
        if ($trend === 'increasing') {
            $sentences[] = "Attendance at {$branch} is projected to rise over the next {$horizon} days, with an average of {$avg} employees expected per day.";
        } elseif ($trend === 'decreasing') {
            $sentences[] = "Attendance at {$branch} is forecasted to decline over the next {$horizon} days, averaging {$avg} employees per day — HR should investigate potential underlying causes.";
        } else {
            $sentences[] = "Attendance at {$branch} is expected to remain stable over the next {$horizon} days, with approximately {$avg} employees present per day on average.";
        }

        // Change vs last period
        if ($change !== null) {
            if ($change > 5) {
                $sentences[] = "This represents a +{$change}% improvement compared to the recent historical period, suggesting positive workforce engagement.";
            } elseif ($change < -5) {
                $sentences[] = "This is a {$change}% decrease from the recent historical period; consider reaching out to team leads to understand the drivers.";
            } else {
                $sentences[] = "This is broadly consistent with recent attendance patterns (change: " . ($change >= 0 ? "+{$change}%" : "{$change}%") . ").";
            }
        }

        // Day-of-week pattern
        if ($peak && $low && $peak !== $low) {
            $sentences[] = "{$peak} tends to have the highest attendance while {$low} is typically the lowest — scheduling important meetings or activities on {$peak} would maximize participation.";
        } elseif ($peak) {
            $sentences[] = "{$peak} historically sees the highest employee attendance at this branch.";
        }

        // Model quality note
        if ($fallback) {
            $sentences[] = "Note: the system used a simplified forecast model due to limited historical data; accuracy will improve as more attendance records accumulate.";
        } else {
            $sentences[] = "This forecast was generated using Holt-Winters exponential smoothing with a {$d['confidence']}% confidence level, accounting for weekly attendance patterns.";
        }

        return implode(' ', $sentences);
    }
}

