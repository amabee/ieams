@extends('layouts.app')
@section('title','Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Notifications</h4>
    @if($notifications->where('read_at', null)->count())
    <div id="markAllWrap">
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-check-all me-1"></i> Mark All as Read
        </button>
    </form>
    </div>
    @endif
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0 notif-card-body">
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
                <button class="btn btn-sm btn-link p-0 text-decoration-none mark-read" data-id="{{ $notif->id }}">
                    Mark as read
                </button>
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

@push('scripts')
<script>
(function () {
    const LIST_URL  = '{{ route('notifications.list') }}';
    const READ_URL  = (id) => `/notifications/${id}/read`;
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const INTERVAL  = {{ config('notifications.poll_interval', 5000) }};
    let lastUnread  = {{ auth()->user()->unreadNotifications()->count() }};

    function iconClass(notifType) {
        const map = {
            leave_approved: 'check-circle-fill text-success',
            leave_denied:   'x-circle-fill text-danger',
            leave_pending:  'clock-fill text-warning',
            attendance_alert: 'exclamation-triangle-fill text-danger',
        };
        return 'bi bi-' + (map[notifType] ?? 'info-circle-fill text-primary') + ' fs-4';
    }

    function buildItem(n) {
        const readBtn = n.read ? '' : `
            <button class="btn btn-sm btn-link p-0 text-decoration-none mark-read" data-id="${n.id}">
                Mark as read
            </button>`;
        return `
        <div class="d-flex align-items-start p-3 border-bottom ${n.read ? 'bg-white' : 'bg-light'}" data-notif-id="${n.id}">
            <div class="flex-shrink-0 me-3"><i class="${iconClass(n.notifType)}"></i></div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="mb-0 ${n.read ? 'text-muted' : 'fw-bold'}">${n.title}</h6>
                    <small class="text-muted">${n.time}</small>
                </div>
                <p class="mb-1 small">${n.message}</p>
                ${readBtn}
            </div>
        </div>`;
    }

    function renderList(data) {
        const card = document.querySelector('.notif-card-body');
        if (!card) return;

        // Update mark-all button visibility
        const markAllWrap = document.getElementById('markAllWrap');
        if (markAllWrap) markAllWrap.style.display = data.unread > 0 ? '' : 'none';

        if (!data.items.length) {
            card.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 opacity-25"></i>
                <p class="mt-2 mb-0">No notifications</p>
            </div>`;
            return;
        }
        card.innerHTML = data.items.map(buildItem).join('');
        lastUnread = data.unread;
    }

    function fetchAndRender() {
        fetch(LIST_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(renderList)
            .catch(() => {});
    }

    // Mark single as read via Ajax
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.mark-read');
        if (!btn) return;
        e.preventDefault();
        const id = btn.dataset.id;
        fetch(READ_URL(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(() => fetchAndRender()).catch(() => {});
    });

    // Poll for updates
    function poll() {
        fetch(LIST_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.unread !== lastUnread) renderList(data);
            })
            .catch(() => {})
            .finally(() => setTimeout(poll, INTERVAL));
    }

    setTimeout(poll, INTERVAL);
})();
</script>
@endpush
