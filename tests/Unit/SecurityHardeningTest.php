<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use DOMDocument;
use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\QrCode;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\SvgRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Regression tests for the security audit hardening.
 *
 * Each test pins a defence against a specific abuse vector: resource
 * exhaustion (memory/CPU), invalid dimensions, markup injection, and
 * oversized payloads. The "legitimate still works" tests guard against the
 * hardening becoming an over-restriction that blocks real QR generation.
 */
final class SecurityHardeningTest extends TestCase
{
    // ── Resource exhaustion: PNG image dimensions ────────────────────────

    public function test_oversized_png_size_is_rejected_before_allocation(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        // A 50 000px request would drive imagecreatetruecolor() to ~10 GB.
        // The renderer must refuse it instead of attempting the allocation.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/exceeds the maximum/');

        QrCode::make('DoS attempt')->png()->size(50000)->margin(4)->generate();
    }

    public function test_oversized_png_via_huge_margin_is_rejected(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        // A modest size with a huge margin can still blow past the ceiling.
        $this->expectException(RuntimeException::class);

        (new PngRenderer)->render((new MatrixBuilder)->build('x', 'L'), 200000, 400);
    }

    public function test_legitimate_large_png_still_renders(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        // 2000px is comfortably within the cap and must keep working.
        $png = QrCode::make('legitimate high-resolution code')
            ->png()->size(2000)->margin(4)->generate();

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertLessThanOrEqual(2000, $info[0]);
    }

    // ── Invalid dimensions ───────────────────────────────────────────────

    public function test_zero_size_is_rejected(): void
    {
        $this->expectException(QrCodeException::class);
        QrCode::make('x')->size(0)->generate();
    }

    public function test_negative_size_is_rejected(): void
    {
        $this->expectException(QrCodeException::class);
        QrCode::make('x')->size(-100)->generate();
    }

    public function test_negative_margin_is_rejected(): void
    {
        $this->expectException(QrCodeException::class);
        QrCode::make('x')->margin(-1)->generate();
    }

    public function test_renderers_reject_negative_margin_directly(): void
    {
        $matrix = (new MatrixBuilder)->build('x', 'L');

        try {
            (new SvgRenderer)->render($matrix, 300, -5);
            $this->fail('SvgRenderer accepted a negative margin.');
        } catch (RuntimeException) {
            $this->addToAssertionCount(1);
        }

        if (extension_loaded('gd')) {
            try {
                (new PngRenderer)->render($matrix, 300, -5);
                $this->fail('PngRenderer accepted a negative margin.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // ── Markup injection / XML escaping ──────────────────────────────────

    public function test_svg_neutralizes_color_attribute_injection(): void
    {
        $payload = '#000"/><script>alert(document.cookie)</script><rect fill="#000';

        $svg = QrCode::make('injection')
            ->svg()
            ->foregroundColor($payload)
            ->backgroundColor($payload)
            ->generate();

        // The raw markup must never appear unescaped, and the attribute must
        // not be broken out of (the `"/><script>` breakout sequence).
        $this->assertStringNotContainsString('<script>', $svg);
        $this->assertStringNotContainsString('"/><script', $svg);

        // The dangerous characters must be entity-encoded instead.
        $this->assertStringContainsString('&lt;script&gt;', $svg);
        $this->assertStringContainsString('&quot;', $svg);

        // And the document must remain well-formed XML with no injected node.
        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($svg), 'SVG must stay valid XML after escaping a hostile colour.');
        $this->assertSame(0, $dom->getElementsByTagName('script')->length);
    }

    public function test_svg_payload_is_never_emitted_as_markup(): void
    {
        // The data itself is only ever encoded into modules, never written
        // into the SVG text, so a script-like payload cannot inject markup.
        $svg = QrCode::make('<script>alert(1)</script>')->svg()->generate();

        $this->assertStringNotContainsString('<script>', $svg);

        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($svg));
    }

    public function test_png_handles_malicious_color_without_crashing(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        // An unparseable colour must fall back to a default, not error out.
        $png = QrCode::make('color fallback')
            ->png()
            ->foregroundColor('"><script>alert(1)</script>')
            ->generate();

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
    }

    // ── Oversized payloads ───────────────────────────────────────────────

    public function test_payload_beyond_version_40_capacity_is_rejected(): void
    {
        // Exceeds the largest byte-mode capacity (Version 40-L ≈ 2953 bytes).
        $this->expectException(QrCodeOverflowException::class);

        QrCode::make(str_repeat('A', 5000))
            ->errorCorrection('H')
            ->svg()
            ->generate();
    }

    public function test_oversized_payload_is_rejected_quickly(): void
    {
        // A multi-megabyte payload must be refused promptly (bounded work),
        // not chew through CPU or memory trying to place it.
        $start = microtime(true);

        try {
            QrCode::make(str_repeat('x', 5_000_000))->svg()->generate();
            $this->fail('Expected an overflow exception for a 5 MB payload.');
        } catch (QrCodeOverflowException) {
            $this->addToAssertionCount(1);
        }

        $this->assertLessThan(2.0, microtime(true) - $start, 'Oversized payload rejection must be fast.');
    }
}
