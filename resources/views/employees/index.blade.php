@extends('layouts.app')
@section('title','Employees')
@section('breadcrumb')<li class="breadcrumb-item active">Employees</li>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-people me-1"></i> Employees</h5>
    @can('create employees')
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Employee
    </a>
    @endcan
</div>
{{-- Filters --}}
<form method="GET" class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name or employee no…">
            </div>
            <div class="col-md-3">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Employee No</th><th>Name</th><th>Position</th>
                    <th>Branch</th><th>Type</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td><code>{{ $emp->employee_no }}</code></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($emp->photo_path)
                            <img src="{{ Storage::url($emp->photo_path) }}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover">
                            @else
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">
                                {{ strtoupper(substr($emp->first_name,0,1).substr($emp->last_name,0,1)) }}
                            </div>
                            @endif
                            <span class="fw-semibold">{{ $emp->full_name }}</span>
                        </div>
                    </td>
                    <td>{{ $emp->position }}</td>
                    <td>{{ $emp->branch->name ?? '—' }}</td>
                    <td><span class="badge bg-info text-dark">{{ ucfirst(str_replace('_',' ',$emp->employment_type)) }}</span></td>
                    <td>
                        <span class="badge {{ $emp->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($emp->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        @can('edit employees')
                        <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        @endcan
                        @can('delete employees')
                        <form action="{{ route('employees.destroy', $emp) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete {{ $emp->full_name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">{{ $employees->links() }}</div>
@endsection
