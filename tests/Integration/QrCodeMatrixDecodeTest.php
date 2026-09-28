<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Integration;

use Fahad\QrCode\Encoding\Eci;
use Fahad\QrCode\Tests\Support\QrFixture;
use Fahad\QrCode\Tests\Support\QrFixtureRenderer;
use Fahad\QrCode\Tests\Support\QrFixtures;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * Full-matrix decode verification.
 *
 * Complements {@see QrCodeZxingDecodeTest}: it reads the payload back out of
 * the rendered module matrix with the spec-faithful in-repo decoder, which —
 * unlike the bundled third-party reader — copes with the full version range
 * (up to Version 40) and the densest symbols. This covers the "multiple QR
 * versions" and high-ECC fixtures end to end without depending on an external
 * package, and confirms the exact byte sequence (and UTF-8 ECI header) that
 * was placed in the matrix.
 *
 * This is not a stand-in for the independent check — it is the deterministic,
 * complete tier. Together the two tests bracket the generator: an external
 * reader proves real-world readability on the codes it can read, and this
 * reader proves module-level correctness on every fixture.
 */
final class QrCodeMatrixDecodeTest extends TestCase
{
    #[DataProviderExternal(QrFixtures::class, 'fullMatrix')]
    public function test_rendered_matrix_decodes_back_to_the_original_payload(QrFixture $fixture): void
    {
        $result = QrFixtureRenderer::matrixDecode($fixture);

        $this->assertSame(
            $fixture->data,
            $result['decoded'],
            'the rendered matrix must decode back to the exact original input'
        );
        $this->assertSame(
            strlen($fixture->data),
            strlen($result['decoded']),
            'byte length must be preserved'
        );
    }

    #[DataProviderExternal(QrFixtures::class, 'fullMatrix')]
    public function test_engine_selects_the_expected_version(QrFixture $fixture): void
    {
        if ($fixture->expectedVersion === null) {
            $this->markTestSkipped('fixture does not pin a version');
        }

        $this->assertSame($fixture->expectedVersion, QrFixtureRenderer::selectedVersion($fixture));
    }

    #[DataProviderExternal(QrFixtures::class, 'withEciExpectation')]
    public function test_utf8_eci_declaration_matches_expectation(QrFixture $fixture): void
    {
        $eci = QrFixtureRenderer::matrixDecode($fixture)['eci'];

        $this->assertSame(
            $fixture->expectedUtf8Eci ? Eci::UTF8 : null,
            $eci,
            'multibyte UTF-8 payloads must declare UTF-8 via ECI; others must not'
        );
    }
}
