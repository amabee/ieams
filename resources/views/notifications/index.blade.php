@extends('layouts.app')
@section('title','Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Notifications</h4>
    @if($notifications->where('read_at', null)->count())
    <form method="POST" action="{{ route('notifications.mark-all-read') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-check-all me-1"></i> Mark All as Read
        </button>
    </form>
    @endif
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @forelse($notifications as $notif)
        <div class="d-flex align-items-start p-3 border-bottom {{ $notif->read_at ? 'bg-white' : 'bg-light' }}">
            <div class="flex-shrink-0 me-3">
                @php
                $icon = match($notif->type ?? 'info') {
                    'leave_approved' => 'check-circle-fill text-success',
                    'leave_denied' => 'x-circle-fill text-danger',
                    'leave_pending' => 'clock-fill text-warning',
                    'attendance_alert' => 'exclamation-triangle-fill text-danger',
                    default => 'info-circle-fill text-primary'
                };
                @endphp
                <i class="bi bi-{{ $icon }} fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="mb-0 {{ $notif->read_at ? 'text-muted' : 'fw-bold' }}">
                        {{ $notif->data['title'] ?? 'Notification' }}
                    </h6>
                    <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
                </div>
                <p class="mb-1 small">{{ $notif->data['message'] ?? '' }}</p>
                @if(!$notif->read_at)
                <form method="POST" action="{{ route('notifications.mark-read', $notif) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">
                        Mark as read
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash fs-1 opacity-25"></i>
            <p class="mt-2 mb-0">No notifications</p>
        </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div class="card-footer bg-white">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
