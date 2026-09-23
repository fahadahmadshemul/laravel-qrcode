<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Renderer;

use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\SvgRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RendererTest extends TestCase
{
    public function test_svg_output_structure(): void
    {
        $matrix = (new MatrixBuilder)->build('HELLO WORLD', 'M');
        $svg = (new SvgRenderer)->render($matrix, 210, 4);

        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('viewBox="0 0 29 29"', $svg);
        $this->assertStringContainsString('<rect', $svg);
    }

    public function test_svg_size_too_small_throws(): void
    {
        $matrix = (new MatrixBuilder)->build('A', 'L');

        $this->expectException(RuntimeException::class);
        (new SvgRenderer)->render($matrix, 0, 4);
    }

    public function test_png_produces_png_signature(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $matrix = (new MatrixBuilder)->build('HELLO WORLD', 'M');
        $png = (new PngRenderer)->render($matrix, 290, 4);

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
    }

    public function test_format_identifiers(): void
    {
        $this->assertSame('svg', (new SvgRenderer)->format());
        $this->assertSame('png', (new PngRenderer)->format());
    }
}
