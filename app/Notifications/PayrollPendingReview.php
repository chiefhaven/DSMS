<?php

namespace App\Notifications;

use App\Models\PayrollGroup;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollPendingReview extends Notification
{
    public function __construct(private PayrollGroup $payrollGroup)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $month = $this->payrollGroup->payment_month;
        $name = $this->payrollGroup->name ?: ('Payroll ' . $month);
        $count = $this->payrollGroup->payrolls_count ?? $this->payrollGroup->payrolls()->count();

        return (new MailMessage)
            ->subject('Payroll pending review: ' . $name)
            ->line('A payroll group has been created and is pending review.')
            ->line('Group: ' . $name)
            ->line('Month: ' . $month)
            ->line('Employees: ' . $count)
            ->action('Review payroll', url('/hrm/payroll'))
            ->line('Please review and approve/finalize the payroll so it can be paid.');
    }

    public function toDatabase($notifiable): array
    {
        $month = $this->payrollGroup->payment_month;
        $name = $this->payrollGroup->name ?: ('Payroll ' . $month);
        $count = $this->payrollGroup->payrolls_count ?? $this->payrollGroup->payrolls()->count();

        return [
            'title' => 'Payroll pending review',
            'body' => $name . ' (' . $month . ') with ' . $count . ' employee record(s) is pending review.',
            'url' => url('/hrm/payroll'),
            'created_at' => now(),
        ];
    }
}
