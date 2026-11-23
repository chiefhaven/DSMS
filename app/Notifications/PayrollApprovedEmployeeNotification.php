<?php

namespace App\Notifications;

use App\Models\Payroll;
use App\Models\PayrollGroup;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollApprovedEmployeeNotification extends Notification
{
    public function __construct(
        private ?PayrollGroup $payrollGroup = null,
        private ?Payroll $payroll = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        [$name, $month, $countLine] = $this->context();

        $message = (new MailMessage)
            ->subject('Payroll approved: ' . $name)
            ->line('Your payroll has been approved and finalized.')
            ->line('Payroll: ' . $name)
            ->line('Month: ' . $month);

        if ($countLine) {
            $message->line($countLine);
        }

        return $message
            ->action('View My HRM', route('instructor.my-hrm'))
            ->line('Your payslip will be available once payment has been processed.');
    }

    public function toDatabase($notifiable): array
    {
        [$name, $month, $countLine] = $this->context();

        $body = $name . ' (' . $month . ') has been approved and finalized.';
        if ($countLine) {
            $body .= ' ' . $countLine;
        }

        return [
            'title' => 'Payroll approved',
            'body' => $body,
            'url' => route('instructor.my-hrm'),
            'created_at' => now(),
        ];
    }

    private function context(): array
    {
        if ($this->payrollGroup) {
            $month = $this->payrollGroup->payment_month ?? '-';
            $name = $this->payrollGroup->name ?: ('Payroll ' . $month);
            $count = $this->payrollGroup->payrolls_count
                ?? ($this->payrollGroup->relationLoaded('payrolls')
                    ? $this->payrollGroup->payrolls->count()
                    : $this->payrollGroup->payrolls()->count());

            return [$name, $month, 'Records: ' . $count . '.'];
        }

        $payroll = $this->payroll;
        $month = $payroll?->payment_month ?? '-';
        $name = 'Payroll ' . $month;

        return [$name, $month, null];
    }
}