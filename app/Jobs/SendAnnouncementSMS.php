<?php

namespace App\Jobs;

use App\Models\Student;
use App\Http\Controllers\NotificationController;
use App\Models\SmsReport;
use App\Notifications\SmsFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendAnnouncementSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student;
    public $body;

    public $tries = 3;        // retry 3 times
    public $timeout = 30;     // seconds

    public $reportId;

    public function __construct(Student $student, string $body, int $reportId)
    {
        $this->student = $student;
        $this->body = $body;
        $this->reportId = $reportId;
    }

    public function handle(): void
    {
        $report = SmsReport::find($this->reportId);

        if (!$report) return;

        $variables = [
            'FIRST_NAME' => $this->student->fname ?? '',
            'SIR_NAME'   => $this->student->sname ?? '',
            'BALANCE'    => optional($this->student->invoice)->invoice_balance ?? '',
        ];

        $sms = $this->body;

        foreach ($variables as $key => $value) {
            $sms = str_replace('{' . $key . '}', $value, $sms);
        }

        $sender = new NotificationController;
        $sender->sendSMS($sms, $this->student->phone);

        $report->update([
            'message'  => $sms,
            'status'   => 'sent',
            'attempts' => $report->attempts + 1
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        SmsReport::where('id', $this->reportId)->update([
            'status'   => 'failed',
            'attempts' => \DB::raw('attempts + 1'),
            'error'    => $exception->getMessage()
        ]);

        // Trigger alert
        Notification::route('mail', config('mail.admin_email'))
            ->notify(new SmsFailedNotification($this->reportId));
    }
}
