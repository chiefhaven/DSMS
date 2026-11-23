<?php

namespace App\Notifications;

use App\Models\Payroll;
use App\Models\PayrollGroup;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollReadyForPaymentNotification extends Notification
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
        [$name, $month, $details] = $this->context();

        $message = (new MailMessage)
            ->subject('Payroll ready for payment: ' . $name)
            ->line('A payroll has been approved and finalized and is ready for payment.')
            ->line('Payroll: ' . $name)
            ->line('Month: ' . $month);

        foreach ($details as $detail) {
            $message->line($detail);
        }

        return $message
            ->action('Open payroll', url('/hrm/payroll'))
            ->line('Please process payment for the finalized payroll records.');
    }

    public function toDatabase($notifiable): array
    {
        [$name, $month, $details] = $this->context();

        $body = $name . ' (' . $month . ') has been approved and is ready for payment.';
        if (!empty($details)) {
            $body .= ' ' . implode(' ', $details);
        }

        return [
            'title' => 'Payroll ready for payment',
            'body' => $body,
            'url' => url('/hrm/payroll'),
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

            $details = ['Employees/records: ' . $count . '.'];
            if (isset($this->payrollGroup->payrolls_sum_net_pay)) {
                $details[] = 'Total net pay: K' . number_format((float) $this->payrollGroup->payrolls_sum_net_pay, 2) . '.';
            }

            return [$name, $month, $details];
        }

        $payroll = $this->payroll;
        $month = $payroll?->payment_month ?? '-';
        $name = 'Payroll ' . $month;
        $employee = $payroll?->employee_name ?? 'Employee';
        $details = ['Employee: ' . $employee . '.'];
        if ($payroll && $payroll->net_pay !== null) {
            $details[] = 'Net pay: K' . number_format((float) $payroll->net_pay, 2) . '.';
        }

        return [$name, $month, $details];
    }
}