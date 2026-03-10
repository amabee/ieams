<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private Leave $leave, private string $status, private ?string $comment = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $type      = ucfirst($this->leave->leave_type);
        $approved  = $this->status === 'approved';
        $title     = $approved ? "Leave Approved" : "Leave Denied";
        $message   = $approved
            ? "Your {$type} leave request ({$this->leave->start_date->format('M d')} – {$this->leave->end_date->format('M d, Y')}) has been approved."
            : "Your {$type} leave request ({$this->leave->start_date->format('M d')} – {$this->leave->end_date->format('M d, Y')}) was denied."
              . ($this->comment ? " Reason: {$this->comment}" : '');

        return [
            'title'   => $title,
            'message' => $message,
            'url'     => route('leaves.index'),
            'icon'    => $approved ? 'bx-check-circle' : 'bx-x-circle',
            'color'   => $approved ? 'success' : 'danger',
        ];
    }
}
