<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Renderer;

use DOMDocument;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\QrCode;
use Fahad\QrCode\Renderer\SvgRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SvgRendererTest extends TestCase
{
    public function test_renders_valid_xml_svg(): void
    {
        $matrix = (new MatrixBuilder)->build('http://a.co', 'M');
        $svg = (new SvgRenderer)->render($matrix, 300, 4);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $svg);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('width="300"', $svg);
        $this->assertStringContainsString('height="300"', $svg);
        $this->assertStringContainsString('viewBox="0 0 29 29"', $svg);
        $this->assertStringContainsString('shape-rendering="crispEdges"', $svg);

        // Prove valid XML parsing
        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($svg), 'SVG must be valid XML');
        
        $xml = simplexml_load_string($svg);
        $this->assertNotFalse($xml, 'SimpleXML must parse SVG without errors');
        $this->assertSame('svg', $xml->getName());
    }

    public function test_example_fluent_builder_usage(): void
    {
        $svg = QrCode::make('http://a.co')
            ->format('svg')
            ->size(300)
            ->margin(4)
            ->generate();

        $this->assertIsString($svg);
        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
        $this->assertStringContainsString('width="300"', $svg);
        $this->assertStringContainsString('height="300"', $svg);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($svg));
    }

    public function test_configurable_size_and_margin(): void
    {
        $matrix = (new MatrixBuilder)->build('HELLO', 'L');
        
        // Custom size 500, margin 2 -> Version 1 (21) + margin 2*2 = 25 total modules
        $svg = (new SvgRenderer)->render($matrix, 500, 2);

        $this->assertStringContainsString('width="500"', $svg);
        $this->assertStringContainsString('height="500"', $svg);
        $this->assertStringContainsString('viewBox="0 0 25 25"', $svg);
    }

    public function test_configurable_foreground_and_background_colors(): void
    {
        $matrix = (new MatrixBuilder)->build('COLOR TEST', 'M');

        $svg = (new SvgRenderer)->render(
            $matrix,
            300,
            4,
            '#FF0000',
            '#000000'
        );

        $this->assertStringContainsString('fill="#000000"', $svg);
        $this->assertStringContainsString('fill="#FF0000"', $svg);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($svg));
    }

    public function test_fluent_builder_color_methods(): void
    {
        $svg = QrCode::make('COLOR TEST')
            ->format('svg')
            ->size(400)
            ->margin(4)
            ->foregroundColor('#123456')
            ->backgroundColor('#654321')
            ->generate();

        $this->assertStringContainsString('fill="#123456"', $svg);
        $this->assertStringContainsString('fill="#654321"', $svg);

        $svg2 = QrCode::make('SHORTHAND')
            ->format('svg')
            ->color('#00FF00', '#0000FF')
            ->generate();

        $this->assertStringContainsString('fill="#00FF00"', $svg2);
        $this->assertStringContainsString('fill="#0000FF"', $svg2);
    }

    public function test_transparent_background_omits_background_rect(): void
    {
        $matrix = (new MatrixBuilder)->build('TRANSPARENT', 'L');

        $svgTransparent = (new SvgRenderer)->render(
            $matrix,
            300,
            4,
            '#000000',
            'transparent'
        );

        $this->assertStringNotContainsString('<rect', $svgTransparent);
        $this->assertStringContainsString('<path fill="#000000"', $svgTransparent);

        $svgNone = (new SvgRenderer)->render(
            $matrix,
            300,
            4,
            '#000000',
            'none'
        );

        $this->assertStringNotContainsString('<rect', $svgNone);
    }

    public function test_no_raster_images_used(): void
    {
        $svg = QrCode::make('NO RASTER')
            ->format('svg')
            ->generate();

        $this->assertStringNotContainsString('<image', $svg);
        $this->assertStringNotContainsString('data:image', $svg);
        $this->assertStringNotContainsString('.png', $svg);
        $this->assertStringNotContainsString('.jpg', $svg);
    }

    public function test_svg_output_is_compact(): void
    {
        $matrix = (new MatrixBuilder)->build('COMPACT SVG', 'M');
        $svg = (new SvgRenderer)->render($matrix, 300, 4);

        // Compact path rendering uses a single <path> tag for all modules
        $this->assertSame(1, substr_count($svg, '<path'));
        $this->assertStringContainsString('d="M', $svg);
    }

    public function test_invalid_size_throws_exception(): void
    {
        $matrix = (new MatrixBuilder)->build('TEST', 'L');

        $this->expectException(RuntimeException::class);
        (new SvgRenderer)->render($matrix, 0, 4);
    }
}
