@extends('layouts.app')
@section('title','My Attendance')
@section('breadcrumb')<li class="breadcrumb-item active">Time In / Out</li>@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <h5 class="fw-bold mb-1">{{ now()->format('l, F d Y') }}</h5>
                <h2 class="text-primary" id="liveClock"></h2>

                @if(!$employee)
                <div class="alert alert-warning">No employee profile is linked to your account. Please contact HR.</div>
                @else
                <div class="mb-3">
                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                    <small class="text-muted">{{ $employee->branch->name ?? '' }} • {{ $employee->position }}</small>
                </div>

                @if($todayRecord)
                    <div class="alert alert-{{ $todayRecord->status === 'present' ? 'success' : ($todayRecord->status === 'late' ? 'warning' : 'info') }}">
                        <i class="bi bi-info-circle me-1"></i>
                        Status: <strong>{{ ucfirst(str_replace('_',' ',$todayRecord->status)) }}</strong>
                        @if($todayRecord->time_in) · Time In: <strong>{{ $todayRecord->time_in }}</strong> @endif
                        @if($todayRecord->time_out) · Time Out: <strong>{{ $todayRecord->time_out }}</strong> @endif
                    </div>
                @endif

                <div class="d-flex justify-content-center gap-3 mt-3">
                    @if(!$todayRecord || !$todayRecord->time_in)
                    <form method="POST" action="{{ route('attendance.time-in') }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg px-5" onclick="return confirm('Record TIME IN now?')">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Time In
                        </button>
                    </form>
                    @endif

                    @if($todayRecord && $todayRecord->time_in && !$todayRecord->time_out)
                    <form method="POST" action="{{ route('attendance.time-out') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-lg px-5" onclick="return confirm('Record TIME OUT now?')">
                            <i class="bi bi-box-arrow-right me-1"></i> Time Out
                        </button>
                    </form>
                    @endif

                    @if($todayRecord && $todayRecord->time_out)
                    <div class="text-muted">
                        <i class="bi bi-check-circle text-success me-1"></i> Attendance complete for today.
                        @if($todayRecord->hours_worked)
                        <div class="mt-1">Hours worked: <strong>{{ $todayRecord->hours_worked }} hrs</strong></div>
                        @endif
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        @if($recentRecords->count())
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Recent Attendance</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($recentRecords as $r)
                        <tr>
                            <td>{{ $r->date->format('M d') }}</td>
                            <td>{{ $r->time_in ?? '—' }}</td>
                            <td>{{ $r->time_out ?? '—' }}</td>
                            <td>{{ $r->hours_worked ?? '—' }}</td>
                            <td><span class="badge badge-{{ $r->status }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
@push('scripts')
<script>
function tick() {
    const now = new Date();
    document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-PH', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
tick(); setInterval(tick, 1000);
</script>
@endpush
