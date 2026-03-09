<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ExcessiveAbsenceAlert extends Notification
{
    use Queueable;

    public function __construct(
        private Employee $employee,
        private int $absenceCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Attendance Alert: Excessive Absences')
            ->line("Employee {$this->employee->full_name} has been absent {$this->absenceCount} times in the past 7 days.")
            ->line('Please review their attendance records.')
            ->action('View Attendance', url('/attendance/manage'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'excessive_absence',
            'employee_id'   => $this->employee->id,
            'employee_name' => $this->employee->full_name,
            'absence_count' => $this->absenceCount,
            'message'       => "{$this->employee->full_name} has {$this->absenceCount} absences in the last 7 days.",
        ];
    }
}
