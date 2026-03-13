<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Branch;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return view('shifts.index');
    }

    public function data(Request $request)
    {
        $query = Shift::withCount('employees')->with('branch');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        $total    = Shift::count();
        $filtered = $query->count();

        $columns  = ['name', 'start_time', 'end_time', 'late_threshold_minutes', 'employees_count'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderCol, $orderDir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $shifts = $query->skip($start)->take($length)->get();

        $data = $shifts->map(function ($s) {
            $fmt = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i:s', $t)->format('h:i A') : '—';

            $actions = '';
            if (auth()->user()->can('edit schedules')) {
                $actions .= '<a href="' . route('shifts.edit', $s) . '" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                $actions .= '<form action="' . route('shifts.destroy', $s) . '" method="POST" class="d-inline swal-delete-form">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="button" class="btn btn-sm btn-icon btn-outline-danger swal-delete-btn" data-name="' . e($s->name) . '"><i class="bi bi-trash"></i></button></form>';
            }

            return [
                'name'                   => '<span class="fw-semibold">' . e($s->name) . '</span>',
                'start_time'             => $fmt($s->start_time),
                'end_time'               => $fmt($s->end_time),
                'late_threshold_minutes' => $s->late_threshold_minutes . ' min',
                'employees_count'        => $s->employees_count,
                'branch'                 => $s->branch ? e($s->branch->name) : '<span class="text-muted">All Branches</span>',
                'actions'                => $actions ?: '—',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->values(),
        ]);
    }

    public function create()
    {
        $this->authorize('create schedules');
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create schedules');
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
        $this->authorize('edit schedules');
        $branches = Branch::where('is_active', true)->get();
        return view('shifts.edit', compact('shift', 'branches'));
    }

    public function update(Request $request, Shift $shift)
    {
        $this->authorize('edit schedules');
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
        $this->authorize('edit schedules');
        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }
}
