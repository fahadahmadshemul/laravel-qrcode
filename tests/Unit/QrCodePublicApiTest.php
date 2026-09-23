<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\Exceptions\InvalidErrorCorrectionLevelException;
use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\UnsupportedFormatException;
use Fahad\QrCode\QrCode;
use Illuminate\Http\Response;

use PHPUnit\Framework\TestCase;

final class QrCodePublicApiTest extends TestCase
{
    public function test_fluent_builder_full_chain_generate(): void
    {
        $svg = QrCode::make('Hello World')
            ->size(300)
            ->margin(4)
            ->format('svg')
            ->errorCorrection('M')
            ->generate();

        $this->assertIsString($svg);
        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
        $this->assertStringContainsString('width="300"', $svg);
        $this->assertStringContainsString('height="300"', $svg);
        $this->assertStringContainsString('viewBox="0 0 29 29"', $svg);
    }

    public function test_svg_shortcut_method(): void
    {
        $svg1 = (string) QrCode::make('Hello World')->svg();
        $this->assertStringStartsWith('<?xml version="1.0"', $svg1);
        $this->assertStringContainsString('<path', $svg1);

        $svg2 = QrCode::make('Hello World')->svg()->generate();
        $this->assertStringStartsWith('<?xml version="1.0"', $svg2);
    }

    public function test_png_shortcut_method(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        $png1 = (string) QrCode::make('Hello World')->png();
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png1, 0, 8));

        $png2 = QrCode::make('Hello World')->png()->generate();
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png2, 0, 8));
    }

    public function test_svg_response_helper(): void
    {
        $response = QrCode::make('http://a.co')
            ->format('svg')
            ->response();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('<?xml version="1.0"', (string) $response->getContent());
    }

    public function test_png_response_helper(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        $response = QrCode::make('http://a.co')
            ->png()
            ->response();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame("\x89PNG\r\n\x1a\n", substr((string) $response->getContent(), 0, 8));
    }

    public function test_to_response_responsable_contract(): void
    {
        $qr = QrCode::make('http://a.co')->svg();
        $response = $qr->toResponse(null);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    }

    public function test_foreground_and_background_aliases(): void
    {
        $svg = (string) QrCode::make('Hello World')
            ->foreground('#000000')
            ->background('#ffffff')
            ->svg();

        $this->assertStringContainsString('fill="#ffffff"', $svg);
        $this->assertStringContainsString('fill="#000000"', $svg);
    }

    public function test_foreground_color_and_background_color_methods(): void
    {
        $svg = QrCode::make('Hello World')
            ->foregroundColor('#112233')
            ->backgroundColor('#445566')
            ->generate();

        $this->assertStringContainsString('fill="#112233"', $svg);
        $this->assertStringContainsString('fill="#445566"', $svg);
    }

    public function test_color_shorthand_method(): void
    {
        $svg = QrCode::make('Hello World')
            ->color('#AABBCC', '#DDEEFF')
            ->generate();

        $this->assertStringContainsString('fill="#AABBCC"', $svg);
        $this->assertStringContainsString('fill="#DDEEFF"', $svg);
    }

    public function test_dependency_injection_instance_make(): void
    {
        $injectedService = new QrCode(['size' => 400, 'margin' => 2]);

        $svg = (string) $injectedService
            ->make('Hello World')
            ->svg();

        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
        $this->assertStringContainsString('width="400"', $svg);
        $this->assertStringContainsString('height="400"', $svg);
        $this->assertStringContainsString('viewBox="0 0 25 25"', $svg);
    }

    public function test_generate_with_direct_payload_parameter(): void
    {
        $svg = QrCode::make()->generate('Hello World');
        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
    }

    public function test_to_array_returns_configured_options(): void
    {
        $qr = QrCode::make('Hello World')
            ->size(500)
            ->margin(6)
            ->format('png')
            ->errorCorrection('H')
            ->foreground('#FF0000')
            ->background('#000000');

        $options = $qr->toArray();

        $this->assertSame('Hello World', $options['data']);
        $this->assertSame(500, $options['size']);
        $this->assertSame(6, $options['margin']);
        $this->assertSame('png', $options['format']);
        $this->assertSame('H', $options['error_correction']);
        $this->assertSame('#FF0000', $options['foreground_color']);
        $this->assertSame('#000000', $options['background_color']);
    }

    public function test_empty_data_throws_exception(): void
    {
        $this->expectException(QrCodeException::class);
        QrCode::make()->generate();
    }

    public function test_invalid_format_throws_exception(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        QrCode::make('Hello World')->format('gif')->generate();
    }

    public function test_invalid_error_correction_level_throws_exception(): void
    {
        $this->expectException(InvalidErrorCorrectionLevelException::class);
        QrCode::make('Hello World')->errorCorrection('X');
    }
}
