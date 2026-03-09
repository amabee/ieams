<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('employees')->latest()->paginate(15);
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $managers = User::role(['admin', 'branch_manager', 'hr'])->get();
        return view('branches.create', compact('managers'));
    }

    public function store(Request $request)
    {
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
        $managers = User::role(['admin', 'branch_manager', 'hr'])->get();
        return view('branches.edit', compact('branch', 'managers'));
    }

    public function update(Request $request, Branch $branch)
    {
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
        if ($branch->employees()->exists()) {
            return back()->with('error', 'Cannot delete a branch with active employees.');
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }
}
