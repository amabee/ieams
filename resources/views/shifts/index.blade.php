@extends('layouts.app')
@section('title','Shifts & Schedules')
@section('breadcrumb')<li class="breadcrumb-item active">Shifts</li>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-1"></i> Shifts & Schedules</h5>
    @can('create schedules')
    <a href="{{ route('shifts.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Shift</a>
    @endcan
</div>
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Shift Name</th><th>Start Time</th><th>End Time</th><th>Late Threshold</th><th>Branch</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                <tr>
                    <td class="fw-semibold">{{ $shift->name }}</td>
                    <td>{{ $shift->start_time }}</td>
                    <td>{{ $shift->end_time }}</td>
                    <td>{{ $shift->late_threshold_minutes }} min</td>
                    <td>{{ $shift->branch->name ?? 'All Branches' }}</td>
                    <td>
                        @can('edit schedules')
                        <a href="{{ route('shifts.edit', $shift) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('shifts.destroy', $shift) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this shift?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No shifts defined yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
