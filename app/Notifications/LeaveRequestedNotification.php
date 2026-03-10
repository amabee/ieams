<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private Leave $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $employee = $this->leave->employee;
        $type     = ucfirst($this->leave->leave_type);
        $days     = $this->leave->total_days;

        return [
            'title'   => 'Leave Request: ' . $employee->full_name,
            'message' => "{$employee->full_name} requested {$days}-day {$type} leave ({$this->leave->start_date->format('M d')} – {$this->leave->end_date->format('M d, Y')}).",
            'url'     => route('leaves.index'),
            'icon'    => 'bx-calendar-event',
            'color'   => 'warning',
        ];
    }
}
