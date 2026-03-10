@extends('layouts.app')
@section('title', $employee->full_name)
@section('content')
<div class="row g-4">

    {{-- Profile card --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold">Employee Profile</h6>
                @can('edit employees')
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                @endcan
            </div>
            <div class="card-body text-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:90px;height:90px;overflow:hidden;background:#8592a3">
                    @if($employee->photo_path)
                    <img src="{{ Storage::url($employee->photo_path) }}" style="width:100%;height:100%;object-fit:cover" alt="">
                    @else
                    <span class="text-white fw-bold" style="font-size:2rem">
                        {{ strtoupper(substr($employee->first_name,0,1).substr($employee->last_name,0,1)) }}
                    </span>
                    @endif
                </div>
                <h5 class="mb-0 fw-semibold">{{ $employee->full_name }}</h5>
                <div class="text-muted small mb-2">{{ $employee->position?->title ?? '—' }}</div>
                <span class="badge {{ $employee->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                    {{ ucfirst($employee->status) }}
                </span>
            </div>
            <div class="card-body border-top pt-3">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-muted"></i>
                        <span class="text-muted">Employee No</span>
                        <code class="ms-auto text-primary fw-semibold">{{ $employee->employee_no }}</code>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-building text-muted"></i>
                        <span class="text-muted">Branch</span>
                        <span class="ms-auto fw-semibold">{{ $employee->branch->name ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-briefcase text-muted"></i>
                        <span class="text-muted">Type</span>
                        <span class="ms-auto fw-semibold">{{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar text-muted"></i>
                        <span class="text-muted">Hired</span>
                        <span class="ms-auto fw-semibold">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </li>
                    @if($employee->shift)
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-muted"></i>
                        <span class="text-muted">Shift</span>
                        <span class="ms-auto fw-semibold">{{ $employee->shift->name }}</span>
                    </li>
                    @endif
                    @if($employee->contact_no)
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-telephone text-muted"></i>
                        <span class="text-muted">Contact</span>
                        <span class="ms-auto fw-semibold">{{ $employee->contact_no }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Attendance card --}}
<div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-1"></i> Recent Attendance <span class="text-muted fw-normal small">(last 14 days)</span></h6>
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Date</th>
                            <th style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Time In</th>
                            <th style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Time Out</th>
                            <th style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Hours</th>
                            <th style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->attendanceRecords->sortByDesc('date')->take(14) as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->date->format('M d, Y') }}</td>
                            <td>{{ $r->time_in  ? \Carbon\Carbon::parse($r->time_in)->format('h:i A')  : '<span class="text-muted">&mdash;</span>' }}</td>
                            <td>{{ $r->time_out ? \Carbon\Carbon::parse($r->time_out)->format('h:i A') : '<span class="text-muted">&mdash;</span>' }}</td>
                            <td>{{ $r->hours_worked ?? '<span class="text-muted">&mdash;</span>' }}</td>
                            <td>
                                @php
                                    $badgeClass = match($r->status ?? '') {
                                        'present'   => 'bg-label-success',
                                        'absent'    => 'bg-label-danger',
                                        'late'      => 'bg-label-warning',
                                        'half_day'  => 'bg-label-info',
                                        'on_leave'  => 'bg-label-secondary',
                                        default     => 'bg-label-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-calendar-x d-block mb-1" style="font-size:1.5rem"></i>
                                No attendance records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
