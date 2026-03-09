<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(["branch","shift"])->latest();
        if ($request->filled("branch_id")) $query->where("branch_id", $request->branch_id);
        if ($request->filled("status"))    $query->where("status", $request->status);
        if ($request->filled("search"))    $query->where(function($q) use ($request) {
            $q->where("first_name","like","%{$request->search}%")
              ->orWhere("last_name","like","%{$request->search}%")
              ->orWhere("employee_no","like","%{$request->search}%");
        });
        $employees = $query->paginate(15)->withQueryString();
        $branches  = Branch::where("is_active",true)->get();
        return view("employees.index", compact("employees","branches"));
    }

    public function create()
    {
        $branches = Branch::where("is_active",true)->get();
        $shifts   = Shift::all();
        return view("employees.create", compact("branches","shifts"));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "employee_no"     => "required|string|max:20|unique:employees",
            "first_name"      => "required|string|max:80",
            "last_name"       => "required|string|max:80",
            "middle_name"     => "nullable|string|max:80",
            "position"        => "required|string|max:100",
            "employment_type" => "required|in:full_time,part_time,contractual",
            "branch_id"       => "required|exists:branches,id",
            "shift_id"        => "nullable|exists:shifts,id",
            "hire_date"       => "required|date",
            "contact_no"      => "nullable|string|max:20",
            "address"         => "nullable|string|max:255",
            "photo"           => "nullable|image|max:2048",
        ]);
        if ($request->hasFile("photo")) {
            $validated["photo_path"] = $request->file("photo")->store("employees","public");
        }
        Employee::create($validated);
        return redirect()->route("employees.index")->with("success","Employee created successfully.");
    }

    public function show(Employee $employee)
    {
        $employee->load(["branch","shift","attendanceRecords"=>"branch","leaves"]);
        return view("employees.show", compact("employee"));
    }

    public function edit(Employee $employee)
    {
        $branches = Branch::where("is_active",true)->get();
        $shifts   = Shift::all();
        return view("employees.edit", compact("employee","branches","shifts"));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            "employee_no"     => "required|string|max:20|unique:employees,employee_no,".$employee->id,
            "first_name"      => "required|string|max:80",
            "last_name"       => "required|string|max:80",
            "middle_name"     => "nullable|string|max:80",
            "position"        => "required|string|max:100",
            "employment_type" => "required|in:full_time,part_time,contractual",
            "branch_id"       => "required|exists:branches,id",
            "shift_id"        => "nullable|exists:shifts,id",
            "hire_date"       => "required|date",
            "status"          => "required|in:active,inactive",
            "contact_no"      => "nullable|string|max:20",
            "address"         => "nullable|string|max:255",
            "photo"           => "nullable|image|max:2048",
        ]);
        if ($request->hasFile("photo")) {
            if ($employee->photo_path) Storage::disk("public")->delete($employee->photo_path);
            $validated["photo_path"] = $request->file("photo")->store("employees","public");
        }
        $employee->update($validated);
        return redirect()->route("employees.index")->with("success","Employee updated.");
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route("employees.index")->with("success","Employee removed.");
    }
}
