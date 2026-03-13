<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Mail\AccountCreatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        return view('employees.index');
    }

    public function create()
    {
        $this->authorize('create employees');
        $branches  = Branch::where('is_active', true)->get();
        $shifts    = Shift::all();
        $roles     = Role::orderBy('name')->get();
        $positions = Position::where('is_active', true)->orderBy('department')->orderBy('title')->get()->groupBy('department');
        return view('employees.create', compact('branches', 'shifts', 'roles', 'positions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create employees');
        $rules = [
            'employee_no'     => 'required|string|max:20|unique:employees',
            'first_name'      => 'required|string|max:80',
            'last_name'       => 'required|string|max:80',
            'middle_name'     => 'nullable|string|max:80',
            'position_id'     => 'required|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contractual',
            'branch_id'       => 'required|exists:branches,id',
            'shift_id'        => 'nullable|exists:shifts,id',
            'hire_date'       => 'required|date',
            'contact_no'      => 'nullable|string|max:20',
            'address'         => 'nullable|string|max:255',
            'birthdate'       => 'nullable|date|before:today',
            'gender'          => 'nullable|in:male,female,other',
            'civil_status'    => 'nullable|in:single,married,widowed,divorced,separated',
            'basic_salary'    => 'nullable|numeric|min:0',
            'sss_no'          => 'nullable|string|max:50',
            'philhealth_no'   => 'nullable|string|max:50',
            'pagibig_no'      => 'nullable|string|max:50',
            'tin_no'          => 'nullable|string|max:50',
            'photo'           => 'nullable|image|max:2048',
        ];
        if ($request->boolean('create_account')) {
            $rules['account_email'] = 'required|email|unique:users,email';
            $rules['account_role']  = 'required|exists:roles,name';
        }
        $validated = $request->validate($rules);
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $photo    = $request->file('photo');
            $filename = Str::random(40) . '.' . ($photo->extension() ?: $photo->getClientOriginalExtension() ?: 'jpg');
            $photo->move(storage_path('app/public/employees'), $filename);
            $validated['photo_path'] = 'employees/' . $filename;
        }
        $employee = Employee::create($validated);
        if ($request->boolean('create_account')) {
            $plainPassword = Str::random(12);
            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $request->account_email,
                'password'    => Hash::make($plainPassword),
                'employee_id' => $employee->id,
                'branch_id'   => $employee->branch_id,
                'is_active'   => true,
            ]);
            $user->assignRole($request->account_role);
            Mail::to($request->account_email)->queue(new AccountCreatedMail(
                $employee->full_name,
                $request->account_email,
                $plainPassword,
                route('login'),
            ));
        }
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['branch', 'shift', 'attendanceRecords', 'leaves']);
        return view("employees.show", compact("employee"));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('edit employees');
        $branches  = Branch::where('is_active', true)->get();
        $shifts    = Shift::all();
        $roles     = Role::orderBy('name')->get();
        $positions = Position::where('is_active', true)->orderBy('department')->orderBy('title')->get()->groupBy('department');
        $employee->loadMissing('user');
        return view('employees.edit', compact('employee', 'branches', 'shifts', 'roles', 'positions'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('edit employees');
        $employee->loadMissing('user');
        $rules = [
            'employee_no'     => 'required|string|max:20|unique:employees,employee_no,' . $employee->id,
            'first_name'      => 'required|string|max:80',
            'last_name'       => 'required|string|max:80',
            'middle_name'     => 'nullable|string|max:80',
            'position_id'     => 'required|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contractual',
            'branch_id'       => 'required|exists:branches,id',
            'shift_id'        => 'nullable|exists:shifts,id',
            'hire_date'       => 'required|date',
            'status'          => 'required|in:active,inactive',
            'contact_no'      => 'nullable|string|max:20',
            'address'         => 'nullable|string|max:255',
            'birthdate'       => 'nullable|date|before:today',
            'gender'          => 'nullable|in:male,female,other',
            'civil_status'    => 'nullable|in:single,married,widowed,divorced,separated',
            'basic_salary'    => 'nullable|numeric|min:0',
            'sss_no'          => 'nullable|string|max:50',
            'philhealth_no'   => 'nullable|string|max:50',
            'pagibig_no'      => 'nullable|string|max:50',
            'tin_no'          => 'nullable|string|max:50',
            'photo'           => 'nullable|image|max:2048',
        ];
        if ($request->boolean('create_account') && !$employee->user) {
            $rules['account_email'] = 'required|email|unique:users,email';
            $rules['account_role']  = 'required|exists:roles,name';
        }
        $validated = $request->validate($rules);
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            if ($employee->photo_path) Storage::disk('public')->delete($employee->photo_path);
            $photo    = $request->file('photo');
            $filename = Str::random(40) . '.' . ($photo->extension() ?: $photo->getClientOriginalExtension() ?: 'jpg');
            $photo->move(storage_path('app/public/employees'), $filename);
            $validated['photo_path'] = 'employees/' . $filename;
        }
        $employee->update($validated);
        if ($request->boolean('create_account') && !$employee->fresh()->user) {
            $plainPassword = Str::random(12);
            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $request->account_email,
                'password'    => Hash::make($plainPassword),
                'employee_id' => $employee->id,
                'branch_id'   => $employee->branch_id,
                'is_active'   => true,
            ]);
            $user->assignRole($request->account_role);
            Mail::to($request->account_email)->queue(new AccountCreatedMail(
                $employee->full_name,
                $request->account_email,
                $plainPassword,
                route('login'),
            ));
        }
        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete employees');
        $employee->delete();
        return redirect()->route("employees.index")->with("success","Employee removed.");
    }

    public function data(Request $request)
    {
        $user  = auth()->user();
        $query = Employee::with(['branch', 'position']);

        // Scope branch_manager to their own branch only
        if ($user->hasRole('branch_manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        // Custom filters
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Global search
        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhereHas('position', fn($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        $total    = $user->hasRole('branch_manager') && $user->branch_id
            ? Employee::where('branch_id', $user->branch_id)->count()
            : Employee::count();
        $filtered = $query->count();

        // Ordering
        $columns  = ['employee_no', null, null, null, 'employment_type', 'status', null];
        $orderIdx = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        if (isset($columns[$orderIdx]) && $columns[$orderIdx]) {
            $query->orderBy($columns[$orderIdx], $orderDir);
        } else {
            $query->latest();
        }

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $employees = $query->skip($start)->take($length)->get();

        $data = $employees->map(function ($emp) {
            // Avatar
            if ($emp->photo_path) {
                $avatar = '<img src="'.\Storage::url($emp->photo_path).'" class="rounded-circle" style="width:32px;height:32px;object-fit:cover" alt="">';
            } else {
                $initials = strtoupper(substr($emp->first_name, 0, 1).substr($emp->last_name, 0, 1));
                $avatar   = '<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">'.$initials.'</div>';
            }

            $type = match($emp->employment_type) {
                'full_time'   => '<span class="badge bg-label-primary">Full Time</span>',
                'part_time'   => '<span class="badge bg-label-warning">Part Time</span>',
                'contractual' => '<span class="badge bg-label-info">Contractual</span>',
                default       => '<span class="badge bg-label-secondary">'.ucfirst($emp->employment_type).'</span>',
            };

            $status = $emp->status === 'active'
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-secondary">Inactive</span>';

            $actions = '<a href="'.route('employees.show', $emp).'" class="btn btn-sm btn-icon btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>';
            if (auth()->user()->can('edit employees')) {
                $actions .= ' <a href="'.route('employees.edit', $emp).'" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>';
            }
            if (auth()->user()->can('delete employees')) {
                $name = e($emp->full_name);
                $actions .= ' <form action="'.route('employees.destroy', $emp).'" method="POST" class="d-inline swal-delete-form">'.
                    csrf_field().method_field('DELETE').
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger swal-delete-btn" title="Delete" data-name="'.$name.'"><i class="bi bi-trash"></i></button></form>';
            }

            return [
                'employee_no' => '<code class="text-primary fw-semibold">'.$emp->employee_no.'</code>',
                'name'        => '<div class="d-flex align-items-center gap-2">'.$avatar.'<span class="fw-semibold">'.$emp->full_name.'</span></div>',
                'position'    => $emp->position?->title ?? '—',
                'branch'      => $emp->branch->name ?? '—',
                'type'        => $type,
                'status'      => $status,
                'actions'     => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }
}
