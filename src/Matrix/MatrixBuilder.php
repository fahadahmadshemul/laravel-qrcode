<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;

/**
 * Orchestrates the Version 1 assembly pipeline:
 *
 * finder patterns → timing patterns → format information → data placement
 * → best-mask selection → re-placing format information with the chosen mask.
 *
 * Pure PHP; no Laravel dependency.
 */
final class MatrixBuilder
{
    private const VERSION_1_SIZE = 21;

    public function __construct(
        private readonly DataEncoder $dataEncoder = new DataEncoder,
    ) {}

    /**
     * Build the final masked QR matrix for the payload.
     */
    public function build(string $data, string $level): QrMatrix
    {
        $ecc = ErrorCorrectionLevel::fromName($level);
        $codewords = $this->dataEncoder->encode($data, $level);

        $matrix = new QrMatrix(self::VERSION_1_SIZE);

        (new FinderPattern($matrix))->place();
        (new TimingPattern($matrix))->place();

        // Reserve the format information area (and the dark module) before
        // data placement so no data module is ever overwritten by format
        // bits. The values are provisional; the final mask decides them.
        $format = new FormatInformation($matrix);
        $format->place($ecc, MaskPattern::Pattern0);

        (new DataPlacer($matrix))->place($codewords);

        // Evaluate every mask with the format information that mask implies,
        // then keep the candidate with the lowest penalty score.
        $mask = new Mask($matrix);
        $bestScore = null;
        $bestPattern = MaskPattern::Pattern0;

        foreach (MaskPattern::cases() as $pattern) {
            $mask->apply($pattern);
            $format->place($ecc, $pattern);

            $score = $mask->score();

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestPattern = $pattern;
            }

            $mask->apply($pattern); // XOR is self-inverse: revert the mask.
        }

        $mask->apply($bestPattern);
        $format->place($ecc, $bestPattern);

        return $matrix;
    }
}
