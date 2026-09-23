<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Renderer;

use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\QrCode;
use Fahad\QrCode\Renderer\PngRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PngRendererTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }
    }

    public function test_renders_valid_png_binary(): void
    {
        $matrix = (new MatrixBuilder)->build('HELLO', 'M');
        $png = (new PngRenderer)->render($matrix, 290, 4);

        // Assert PNG binary header signature
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        // Get image dimensions using PHP image functions
        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertSame(290, $info[0]);
        $this->assertSame(290, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function test_fluent_builder_png_generation(): void
    {
        $png = QrCode::make('TEST PNG')
            ->format('png')
            ->size(290)
            ->margin(4)
            ->generate();

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertSame(290, $info[0]);
    }

    public function test_png_rendering_with_custom_colors(): void
    {
        $matrix = (new MatrixBuilder)->build('COLOR PNG', 'L');

        $pngHex = (new PngRenderer)->render($matrix, 290, 4, '#FF0000', '#000000');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($pngHex, 0, 8));

        $pngNamed = (new PngRenderer)->render($matrix, 290, 4, 'blue', 'white');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($pngNamed, 0, 8));

        $pngTransparent = (new PngRenderer)->render($matrix, 290, 4, '#000000', 'transparent');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($pngTransparent, 0, 8));
    }

    public function test_png_target_size_too_small_throws_exception(): void
    {
        $matrix = (new MatrixBuilder)->build('TEST', 'L');

        $this->expectException(RuntimeException::class);
        (new PngRenderer)->render($matrix, 5, 4);
    }
}
