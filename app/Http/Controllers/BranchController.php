<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('branches.index');
    }

    public function data(Request $request)
    {
        $query = Branch::withCount('employees')->with('manager');

        // Global search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contact_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $total = Branch::withCount('employees')->count();
        $filtered = $query->count();

        // Ordering
        $columns = ['name', 'address', 'contact_no', 'email', 'employees_count', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderCol, $orderDir);

        // Pagination
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $branches = $query->skip($start)->take($length)->get();

        $data = $branches->map(function ($b) {
            $statusBadge = $b->is_active
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-secondary">Inactive</span>';

            $actions = '<a href="' . route('branches.edit', $b) . '" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
            if (auth()->user()->can('delete branches')) {
                $actions .= '<form action="' . route('branches.destroy', $b) . '" method="POST" class="d-inline swal-delete-form">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="button" class="btn btn-sm btn-icon btn-outline-danger swal-delete-btn" data-name="' . e($b->name) . '"><i class="bi bi-trash"></i></button></form>';
            }

            return [
                'name'            => e($b->name),
                'address'         => e($b->address ?? '—'),
                'contact_no'      => e($b->contact_no ?? '—'),
                'email'           => e($b->email ?? '—'),
                'employees_count' => $b->employees_count,
                'manager'         => e($b->manager->name ?? '—'),
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
        $this->authorize('create branches');
        $managers = User::role(['admin', 'branch_manager', 'hr'])->get();
        return view('branches.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create branches');
        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:branches',
            'address'    => 'nullable|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        Branch::create($validated);
        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        $branch->loadCount('employees');
        $branch->load('manager', 'employees');
        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $this->authorize('edit branches');
        $managers = User::role(['admin', 'branch_manager', 'hr'])->get();
        return view('branches.edit', compact('branch', 'managers'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('edit branches');
        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:branches,name,' . $branch->id,
            'address'    => 'nullable|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active'  => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $branch->update($validated);
        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete branches');
        if ($branch->employees()->exists()) {
            return back()->with('error', 'Cannot delete a branch with active employees.');
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }
}
