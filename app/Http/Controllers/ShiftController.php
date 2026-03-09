<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Branch;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('employees')->latest()->paginate(15);
        return view('shifts.index', compact('shifts'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'start_time'             => 'required',
            'end_time'               => 'required|after:start_time',
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'branch_id'              => 'nullable|exists:branches,id',
        ]);
        Shift::create($validated);
        return redirect()->route('shifts.index')->with('success', 'Shift created.');
    }

    public function show(Shift $shift)
    {
        $shift->loadCount('employees');
        return view('shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.edit', compact('shift', 'branches'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'start_time'             => 'required',
            'end_time'               => 'required',
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'branch_id'              => 'nullable|exists:branches,id',
        ]);
        $shift->update($validated);
        return redirect()->route('shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }
}