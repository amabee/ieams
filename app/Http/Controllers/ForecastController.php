<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Forecast;
use App\Services\ForecastingService;
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
        $from     = today()->format('Y-m-d');
        $to       = today()->addDays(30)->format('Y-m-d');

        $forecasts = $forecasting->getForecast($branchId, $from, $to);

        return response()->json($forecasts->map(fn ($f) => [
            'date'            => $f->forecast_date->format('Y-m-d'),
            'absent_count'    => $f->predicted_absent_count,
            'absenteeism_rate'=> $f->predicted_absenteeism_rate,
            'generated_at'    => $f->generated_at?->format('Y-m-d H:i'),
        ]));
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