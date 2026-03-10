<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }

    public function poll()
    {
        $user  = auth()->user();
        $unread = $user->unreadNotifications()->count();
        $items = $user->unreadNotifications()->latest()->take(5)->get()->map(fn ($n) => [
            'id'      => $n->id,
            'title'   => $n->data['title'] ?? 'Notification',
            'message' => $n->data['message'] ?? '',
            'icon'    => $n->data['icon'] ?? 'bx-bell',
            'color'   => $n->data['color'] ?? 'primary',
            'url'     => $n->data['url'] ?? '#',
            'time'    => $n->created_at->diffForHumans(),
        ]);

        return response()->json(['unread' => $unread, 'items' => $items]);
    }
}
