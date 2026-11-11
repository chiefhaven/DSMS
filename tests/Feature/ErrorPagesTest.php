<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_error_pages_preserve_status_and_show_safe_recovery_links(): void
    {
        config(['app.debug' => false]);
        foreach ([404 => 'Page not found', 500 => 'Something went wrong'] as $status => $title) {
            Route::get('/test-error-'.$status, fn () => abort($status, 'Private exception details'));
            $response = $this->get('/test-error-'.$status);
            $response->assertStatus($status)->assertSee($title)
                ->assertSee('MBIRA DSMS')->assertDontSee('Private exception details');
            $html = $response->getContent();
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML($html);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//h1[@id="error-title"]')->length);
            $this->assertSame(0, $xpath->query('//p[@class="error-code"]')->length);
            $this->assertSame(url('/'), $xpath->query('//nav/a[1]')->item(0)->getAttribute('href'));
            $this->assertSame(url('/login'), $xpath->query('//nav/a[2]')->item(0)->getAttribute('href'));
            $this->assertSame('noindex, nofollow', $xpath->query('//meta[@name="robots"]')->item(0)->getAttribute('content'));
            $this->assertSame(0, $xpath->query('//script | //link[@rel="stylesheet"]')->length);
            $artwork = $xpath->query('//svg[@class="error-artwork"]');
            $this->assertSame(1, $artwork->length);
            $this->assertSame($status === 404 ? 'lost-car' : 'car-repair', $artwork->item(0)->getAttribute('data-artwork'));
            $this->assertSame('true', $artwork->item(0)->getAttribute('aria-hidden'));
            $this->assertSame('0 0 360 210', $artwork->item(0)->getAttribute('viewbox'));
            $this->assertStringContainsString('border-radius: 50rem', $html);
            $this->assertStringNotContainsString('error-card', $html);
            $this->assertStringNotContainsString('box-shadow', $html);
            $this->assertSame(1, $xpath->query('//svg/g[@class="artwork-car"]')->length);
            $this->assertSame(1, $xpath->query($status === 404 ? '//svg/path[@class="artwork-route"]' : '//svg/path[@class="artwork-tool"]')->length);
            $this->assertStringContainsString('prefers-reduced-motion: reduce', $html);
        }
    }

    public function test_unhandled_server_errors_use_the_restyled_page_in_production(): void
    {
        config(['app.debug' => false]);
        Route::get('/test-unhandled-error', function () {
            throw new \RuntimeException('Private exception details');
        });
        $this->get('/test-unhandled-error')->assertStatus(500)
            ->assertSee('Something went wrong')->assertDontSee('Private exception details');
    }

    public function test_missing_url_uses_the_restyled_not_found_page(): void
    {
        $this->get('/missing-page-for-error-test')->assertNotFound()
            ->assertSee('Page not found')->assertSee('Go home');
    }
}
