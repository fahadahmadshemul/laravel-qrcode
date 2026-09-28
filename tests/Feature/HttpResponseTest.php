<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\Facades\QrCode as QrCodeFacade;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * Verifies the package integrates with Laravel's HTTP layer: the builder
 * produces a proper Illuminate\Http\Response and, as a Responsable, can be
 * returned directly from a route and rendered by the HTTP kernel.
 */
final class HttpResponseTest extends PackageTestCase
{
    public function test_response_returns_an_illuminate_response_with_svg_content_type(): void
    {
        $response = QrCodeFacade::make('https://example.com')->svg()->response();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('<?xml version="1.0"', $this->responseBody($response));
    }

    public function test_response_honors_status_and_custom_headers(): void
    {
        $response = QrCodeFacade::make('https://example.com')
            ->svg()
            ->response(201, ['X-Custom' => 'yes']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('yes', $response->headers->get('X-Custom'));
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    }

    public function test_png_response_sets_png_content_type(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The gd extension is required for PNG rendering.');
        }

        $response = QrCodeFacade::make('https://example.com')->png()->response();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_builder_is_responsable_from_a_route(): void
    {
        Route::get('/__qrcode-test', fn () => QrCodeFacade::make('https://example.com')->svg());

        $response = $this->get('/__qrcode-test');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertSee('<?xml version="1.0"', false);
    }
}
