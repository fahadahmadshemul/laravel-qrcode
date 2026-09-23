<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\QrCode;
use PHPUnit\Framework\TestCase;

final class Base64Test extends TestCase
{
    public function test_svg_base64_output_with_data_uri(): void
    {
        $base64 = QrCode::make('http://a.co')
            ->svg()
            ->base64();

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $base64);

        $payload = substr($base64, strlen('data:image/svg+xml;base64,'));
        $decoded = base64_decode($payload, true);

        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith('<?xml version="1.0"', $decoded);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $decoded);
    }

    public function test_png_base64_output_with_data_uri(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        $base64 = QrCode::make('http://a.co')
            ->png()
            ->base64();

        $this->assertStringStartsWith('data:image/png;base64,', $base64);

        $payload = substr($base64, strlen('data:image/png;base64,'));
        $decoded = base64_decode($payload, true);

        $this->assertNotFalse($decoded);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($decoded, 0, 8));
    }

    public function test_raw_base64_output_without_data_uri_prefix(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        $rawBase64 = QrCode::make('http://a.co')
            ->png()
            ->base64(false);

        $this->assertStringStartsNotWith('data:', $rawBase64);

        $decoded = base64_decode($rawBase64, true);
        $this->assertNotFalse($decoded);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($decoded, 0, 8));
    }
}
