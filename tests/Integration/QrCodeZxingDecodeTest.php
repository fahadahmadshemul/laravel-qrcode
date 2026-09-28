<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Integration;

use Fahad\QrCode\QrCode;
use Fahad\QrCode\Tests\Support\QrFixture;
use Fahad\QrCode\Tests\Support\QrFixtureRenderer;
use Fahad\QrCode\Tests\Support\QrFixtures;
use Fahad\QrCode\Tests\Support\ZxingDecoder;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * Independent decode verification.
 *
 * Each fixture is rendered to PNG through the production {@see QrCode}
 * builder and handed to a *third-party* ZXing reader ({@see ZxingDecoder}).
 * A valid-looking PNG is not enough: the assertion is that a foreign decoder
 * recovers the original bytes exactly. This is the strongest evidence, short
 * of a hardware scanner, that the generator produces genuinely conformant
 * codes — and it independently confirms that multibyte UTF-8 payloads carry a
 * correct ECI declaration, since the reader honours it to return proper UTF-8.
 *
 * The fixtures here are the subset the pure-PHP ZXing port reads reliably;
 * denser / higher-version symbols are covered by {@see QrCodeMatrixDecodeTest}.
 * See tests/Integration/README.md.
 */
final class QrCodeZxingDecodeTest extends TestCase
{
    protected function setUp(): void
    {
        if (! ZxingDecoder::isAvailable()) {
            $this->markTestSkipped(
                'Independent decode requires the khanamiryan/qrcode-detector-decoder '
                .'dev dependency and the GD (or Imagick) extension.'
            );
        }
    }

    #[DataProviderExternal(QrFixtures::class, 'independentlyDecodable')]
    public function test_generated_png_decodes_with_an_independent_reader(QrFixture $fixture): void
    {
        $png = QrFixtureRenderer::png($fixture);

        $decoded = ZxingDecoder::decodePng($png);

        $this->assertNotNull(
            $decoded,
            'the independent ZXing reader could not decode the generated image'
        );
        $this->assertSame(
            $fixture->data,
            $decoded,
            'the independently decoded payload must equal the original input'
        );
    }
}
