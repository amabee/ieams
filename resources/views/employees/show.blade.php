@extends('layouts.app')
@section('title', $employee->full_name)
@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 text-center p-4">
            @if($employee->photo_path)
            <img src="{{ Storage::url($employee->photo_path) }}" class="rounded-circle mx-auto mb-3" style="width:100px;height:100px;object-fit:cover">
            @else
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:90px;height:90px;font-size:2rem">
                {{ strtoupper(substr($employee->first_name,0,1).substr($employee->last_name,0,1)) }}
            </div>
            @endif
            <h5 class="mb-0">{{ $employee->full_name }}</h5>
            <div class="text-muted small">{{ $employee->position }}</div>
            <span class="badge {{ $employee->status === 'active' ? 'bg-success' : 'bg-secondary' }} mt-2">{{ ucfirst($employee->status) }}</span>
            <hr>
            <div class="text-start small">
                <div class="mb-1"><i class="bi bi-building me-1 text-muted"></i> {{ $employee->branch->name ?? '—' }}</div>
                <div class="mb-1"><i class="bi bi-briefcase me-1 text-muted"></i> {{ ucwords(str_replace('_',' ',$employee->employment_type)) }}</div>
                <div class="mb-1"><i class="bi bi-calendar me-1 text-muted"></i> Hired {{ $employee->hire_date?->format('M d, Y') }}</div>
                @if($employee->contact_no)<div class="mb-1"><i class="bi bi-telephone me-1 text-muted"></i> {{ $employee->contact_no }}</div>@endif
                @if($employee->shift)<div class="mb-1"><i class="bi bi-clock me-1 text-muted"></i> {{ $employee->shift->name }}</div>@endif
            </div>
            @can('edit employees')
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary mt-3 w-100">Edit Profile</a>
            @endcan
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Recent Attendance (last 14 days)</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($employee->attendanceRecords->take(14) as $r)
                        <tr>
                            <td>{{ $r->date->format('M d, Y') }}</td>
                            <td>{{ $r->time_in ?? '—' }}</td>
                            <td>{{ $r->time_out ?? '—' }}</td>
                            <td>{{ $r->hours_worked ?? '—' }}</td>
                            <td><span class="badge badge-{{ $r->status }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
