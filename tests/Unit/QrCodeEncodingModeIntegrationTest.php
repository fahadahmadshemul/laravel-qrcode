<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\QrCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QrCodeEncodingModeIntegrationTest extends TestCase
{
    public function test_automatic_mode_selection_for_numeric_payload(): void
    {
        $qr = QrCode::make('1234567890');
        $svg = $qr->svg()->generate();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame('auto', $qr->toArray()['encoding_mode']);
    }

    public function test_automatic_mode_selection_for_alphanumeric_payload(): void
    {
        $qr = QrCode::make('HELLO WORLD');
        $svg = $qr->svg()->generate();

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_automatic_mode_selection_for_unicode_byte_payload(): void
    {
        $qr = QrCode::make('Hello বাংলা');
        $svg = $qr->svg()->generate();

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_explicit_encoding_mode_numeric(): void
    {
        $qr = QrCode::make('9876543210')->encodingMode('numeric');
        $svg = $qr->generate();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame('numeric', $qr->toArray()['encoding_mode']);
    }

    public function test_explicit_encoding_mode_alphanumeric(): void
    {
        $qr = QrCode::make('HELLO WORLD')->mode('alphanumeric');
        $svg = $qr->generate();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame('alphanumeric', $qr->toArray()['encoding_mode']);
    }

    public function test_explicit_encoding_mode_enum_instance(): void
    {
        $qr = QrCode::make('12345')->mode(EncodingMode::Numeric);
        $svg = $qr->generate();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame('numeric', $qr->toArray()['encoding_mode']);
    }

    public function test_explicit_encoding_mode_throws_when_payload_has_invalid_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Numeric encoding mode/');

        QrCode::make('HELLO')->mode('numeric')->generate();
    }

    public function test_blade_rendering_supports_encoding_mode_option(): void
    {
        $svg = QrCode::renderFromBlade('123456', ['mode' => 'numeric']);

        $this->assertStringContainsString('<svg', $svg);
    }
}
