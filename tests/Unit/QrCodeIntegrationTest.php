<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\QrCode;
use PHPUnit\Framework\TestCase;

final class QrCodeIntegrationTest extends TestCase
{
    public function test_generate_returns_svg_by_default(): void
    {
        $output = QrCode::make('HELLO WORLD')->generate();

        $this->assertStringStartsWith('<?xml version="1.0"', $output);
        $this->assertStringContainsString('<svg', $output);
    }

    public function test_generate_png(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $output = QrCode::make('HELLO WORLD')->format('png')->size(290)->generate();

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($output, 0, 8));
    }

    public function test_fluent_configuration_is_honored(): void
    {
        $small = QrCode::make('A')->size(290)->margin(4)->generate();
        $large = QrCode::make('A')->size(580)->margin(4)->generate();

        $this->assertStringContainsString('width="290"', $small);
        $this->assertStringContainsString('width="580"', $large);
    }

    public function test_all_error_correction_levels_generate(): void
    {
        foreach (['L', 'M', 'Q'] as $level) {
            $output = QrCode::make('HELLO WORLD')->errorCorrection($level)->generate();

            $this->assertStringContainsString('<svg', $output, "level $level");
        }

        // Level H only fits 7 bytes in Version 1.
        $output = QrCode::make('HELLO')->errorCorrection('H')->generate();
        $this->assertStringContainsString('<svg', $output);
    }

    public function test_utf8_payload_generates(): void
    {
        $output = QrCode::make('Hé wörld ✓')->generate();

        $this->assertStringContainsString('<svg', $output);
    }

    public function test_overflow_throws(): void
    {
        $this->expectException(QrCodeOverflowException::class);

        // 1300 bytes exceeds Version 40 max capacity at Level H in Byte mode (1273 bytes max)
        QrCode::make(str_repeat('a', 1300))->errorCorrection('H')->generate();
    }

    public function test_empty_data_throws(): void
    {
        $this->expectException(QrCodeException::class);

        QrCode::make('')->generate();
    }
}
