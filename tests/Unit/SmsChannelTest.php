<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.smsApi.url' => 'https://sms.example.test',
            'services.smsApi.token' => 'test-token',
            'services.smsApi.from' => 'DSMS',
        ]);
    }

    public function test_local_numbers_receive_the_malawi_country_code(): void
    {
        $this->sendSmsTo('0999 123 456');

        Http::assertSent(fn (Request $request) => $request['recipient'] === '265999123456');
    }

    public function test_existing_international_country_codes_are_preserved(): void
    {
        $this->sendSmsTo('+265 (999) 123-456');

        Http::assertSent(fn (Request $request) => $request['recipient'] === '265999123456');
    }

    public function test_other_international_country_codes_are_not_replaced(): void
    {
        $this->sendSmsTo('+1 (212) 555-0100');

        Http::assertSent(fn (Request $request) => $request['recipient'] === '12125550100');
    }

    private function sendSmsTo(string $phoneNumber): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $notifiable = new User();
        $notifiable->setAttribute('id', 1);
        $notifiable->setAttribute('phone', $phoneNumber);

        $notification = new class extends Notification
        {
            public function toSms($notifiable): string
            {
                return 'Test SMS';
            }
        };

        (new SmsChannel())->send($notifiable, $notification);
    }
}
