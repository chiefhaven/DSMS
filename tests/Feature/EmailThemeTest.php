<?php

namespace Tests\Feature;

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class EmailThemeTest extends TestCase
{
    public function test_markdown_emails_use_the_school_brand_and_keep_message_content(): void
    {
        config(['app.school_name' => 'Daron Driving School']);

        $html = (string) (new MailMessage())
            ->subject('Account setup')
            ->line('Set up your student portal account.')
            ->action('Set password', 'https://school.example.test/reset')
            ->render();

        $this->assertStringContainsString('Daron Driving School', $html);
        $this->assertStringContainsString('Set up your student portal account.', $html);
        $this->assertStringContainsString('Set password', $html);
        $this->assertStringContainsString('https://school.example.test/reset', $html);
        $this->assertStringContainsString('#0665d0', $html);
        $this->assertStringContainsString('HavenPlus Technologies', $html);
    }
}
