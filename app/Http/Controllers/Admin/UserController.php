<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.users.index');
    }

    public function data(Request $request)
    {
        $query = User::with('roles', 'branch', 'employee');

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($r) => $r->where('name', $request->role));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(fn ($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"));
        }

        $total = (clone $query)->count();

        $columns  = ['name', 'email', null, null, null, 'is_active', 'updated_at'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        if ($orderCol) $query->orderBy($orderCol, $orderDir);

        $users = $query->skip($request->input('start', 0))
                       ->take($request->input('length', 10))
                       ->get();

        $rows = $users->map(function ($user) {
            $avatar = '<div class="rounded-circle bg-label-primary text-primary d-flex align-items-center justify-content-center"'
                    . ' style="width:32px;height:32px;font-size:.75rem;font-weight:700;flex-shrink:0">'
                    . strtoupper(substr($user->name, 0, 2))
                    . '</div>';

            $roles = $user->roles->map(fn ($r) =>
                '<span class="badge bg-label-secondary">' . ucfirst($r->name) . '</span>'
            )->join(' ');

            $status = $user->is_active
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $employee = $user->employee
                ? '<div>' . e($user->employee->full_name) . '</div><small class="text-muted">' . e($user->employee->employee_no) . '</small>'
                : '<span class="text-muted">&mdash;</span>';

            $actions = '';
            if (Gate::allows('edit-user')) {
                $actions .= '<a href="' . route('admin.users.edit', $user) . '" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
            }
            if (Gate::allows('delete-user') && $user->id !== auth()->id()) {
                $actions .= '<form method="POST" action="' . route('admin.users.destroy', $user) . '" class="d-inline">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" class="btn btn-sm btn-icon btn-outline-danger" onclick="return confirm(\'Delete this user?\')"><i class="bi bi-trash"></i></button>'
                    . '</form>';
            }

            return [
                'name'     => '<div class="d-flex align-items-center gap-2">' . $avatar
                            . '<div><div class="fw-semibold">' . e($user->name) . '</div>'
                            . '<small class="text-muted">#' . $user->id . '</small></div></div>',
                'email'    => e($user->email),
                'employee' => $employee,
                'branch'   => e($user->branch->name ?? '—'),
                'roles'    => $roles ?: '<span class="text-muted">&mdash;</span>',
                'status'   => $status,
                'updated'  => '<small class="text-muted">' . $user->updated_at->diffForHumans() . '</small>',
                'actions'  => $actions,
            ];
        });

        return response()->json([
            'draw'            => intval($request->draw),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows,
        ]);
    }

    public function create()
    {
        $roles     = Role::orderBy('name')->get();
        $branches  = Branch::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::whereDoesntHave('user')->orderBy('first_name')->get();
        return view('admin.users.create', compact('roles', 'branches', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|min:8|confirmed',
            'role'        => 'required|exists:roles,name',
            'branch_id'   => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'is_active'   => 'boolean',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'branch_id'   => $validated['branch_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'branch', 'employee');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles     = Role::orderBy('name')->get();
        $branches  = Branch::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::whereDoesntHave('user')->orWhere('id', $user->employee_id)->orderBy('first_name')->get();
        return view('admin.users.edit', compact('user', 'roles', 'branches', 'employees'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'password'    => 'nullable|min:8|confirmed',
            'role'        => 'required|exists:roles,name',
            'branch_id'   => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'is_active'   => 'boolean',
        ]);

        $user->update([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'branch_id'   => $validated['branch_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            ...(filled($validated['password'] ?? null) ? ['password' => Hash::make($validated['password'])] : []),
        ]);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
