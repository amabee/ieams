@extends('layouts.app')
@section('title','Branches')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-building me-1"></i> Branches</h5>
    @can('create branches')
    <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Branch
    </a>
    @endcan
</div>
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Branch Name</th><th>Address</th><th>Contact</th><th>Manager</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                <tr>
                    <td class="fw-semibold">{{ $branch->name }}</td>
                    <td>{{ $branch->address ?? '—' }}</td>
                    <td>{{ $branch->contact_no ?? '—' }}</td>
                    <td>{{ $branch->manager->name ?? '—' }}</td>
                    <td>
                        @if($branch->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @can('delete branches')
                        <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this branch?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No branches found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $branches->links() }}
@endsection
