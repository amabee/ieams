<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        return view('positions.index');
    }

    public function data(Request $request)
    {
        $query = Position::withCount('employees');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $total    = Position::count();
        $filtered = $query->count();

        $columns  = ['title', 'department', 'employees_count', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'title';
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderCol, $orderDir);

        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $positions = $query->skip($start)->take($length)->get();

        $data = $positions->map(function ($p) {
            $statusBadge = $p->is_active
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-secondary">Inactive</span>';

            $actions = '<a href="' . route('positions.edit', $p) . '" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
            if (auth()->user()->can('delete positions')) {
                $actions .= '<form action="' . route('positions.destroy', $p) . '" method="POST" class="d-inline swal-delete-form">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="button" class="btn btn-sm btn-icon btn-outline-danger swal-delete-btn" data-name="' . e($p->title) . '"><i class="bi bi-trash"></i></button></form>';
            }

            return [
                'title'           => e($p->title),
                'department'      => e($p->department ?? '—'),
                'employees_count' => $p->employees_count,
                'status'          => $statusBadge,
                'actions'         => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function create()
    {
        $this->authorize('create positions');
        return view('positions.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create positions');
        $validated = $request->validate([
            'title'      => 'required|string|max:100|unique:positions',
            'department' => 'nullable|string|max:100',
        ]);
        Position::create($validated);
        return redirect()->route('positions.index')->with('success', 'Position created successfully.');
    }

    public function edit(Position $position)
    {
        $this->authorize('edit positions');
        return view('positions.edit', compact('position'));
    }

    public function update(Request $request, Position $position)
    {
        $this->authorize('edit positions');
        $validated = $request->validate([
            'title'      => 'required|string|max:100|unique:positions,title,' . $position->id,
            'department' => 'nullable|string|max:100',
            'is_active'  => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $position->update($validated);
        return redirect()->route('positions.index')->with('success', 'Position updated successfully.');
    }

    public function destroy(Position $position)
    {
        $this->authorize('delete positions');
        if ($position->employees()->exists()) {
            return back()->with('error', 'Cannot delete a position that has employees assigned to it.');
        }
        $position->delete();
        return redirect()->route('positions.index')->with('success', 'Position deleted.');
    }
}
