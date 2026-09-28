<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\VersionSpec;
use Fahad\QrCode\QrCode;

/**
 * Drives a {@see QrFixture} through the *production* generation pipeline for
 * the integration suite, and offers the in-repo matrix decode used to verify
 * symbols the third-party reader cannot reach.
 *
 * PNGs are rendered at a generous ~10 px per module with the standard 4-module
 * quiet zone so an image decoder has a fair chance; the encoding itself is
 * resolution-independent.
 */
final class QrFixtureRenderer
{
    private const PIXELS_PER_MODULE = 10;

    /**
     * Render the fixture as PNG bytes exactly as an application would, via the
     * public {@see QrCode} builder.
     */
    public static function png(QrFixture $fixture): string
    {
        return QrCode::make($fixture->data)
            ->errorCorrection($fixture->level)
            ->size(self::imageSize($fixture))
            ->margin(4)
            ->png()
            ->generate();
    }

    /**
     * The symbol version the engine selects for the fixture.
     */
    public static function selectedVersion(QrFixture $fixture): int
    {
        return self::build($fixture)['version']->number;
    }

    /**
     * Read the payload back out of the rendered matrix with the spec-faithful
     * in-repo decoder ({@see MatrixCodewordReader} + {@see QrStreamDecoder}).
     *
     * @return array{decoded: string, version: int, eci: int|null}
     */
    public static function matrixDecode(QrFixture $fixture): array
    {
        $built = self::build($fixture);
        $version = $built['version'];
        $spec = $version->eccSpec(ErrorCorrectionLevel::fromName($fixture->level));

        $codewords = MatrixCodewordReader::dataCodewords($built['matrix'], $built['mask'], $spec);
        $decoder = new QrStreamDecoder($codewords, $version);

        return [
            'decoded' => $decoder->decode(),
            'version' => $version->number,
            'eci' => $decoder->eciAssignment(),
        ];
    }

    private static function imageSize(QrFixture $fixture): int
    {
        $modules = self::build($fixture)['version']->size + 8; // + 4-module quiet zone each side

        return $modules * self::PIXELS_PER_MODULE;
    }

    /**
     * Build the matrix once and expose the pieces the decoders need.
     *
     * @return array{matrix: QrMatrix, version: VersionSpec, mask: MaskPattern}
     */
    private static function build(QrFixture $fixture): array
    {
        $builder = new MatrixBuilder;
        $matrix = $builder->build($fixture->data, $fixture->level);
        $version = $builder->version();

        if ($version === null) {
            throw new \RuntimeException('MatrixBuilder did not record a version.');
        }

        return ['matrix' => $matrix, 'version' => $version, 'mask' => $builder->selectedMask()];
    }
}
