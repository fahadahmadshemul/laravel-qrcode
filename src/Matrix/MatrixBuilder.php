<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;

/**
 * Orchestrates the Version 1–40 assembly pipeline:
 *
 * version selection → finder patterns → alignment patterns → timing patterns
 * → version information → provisional format information → data placement
 * → best-mask selection → final format information.
 *
 * Pure PHP; no Laravel dependency.
 */
final class MatrixBuilder
{
    private ?MaskPattern $selectedMask = null;

    private ?VersionSpec $version = null;

    public function __construct(
        private readonly DataEncoder $dataEncoder = new DataEncoder,
    ) {}

    /**
     * Build the final masked QR matrix for the payload.
     */
    public function build(string $data, string $level, EncodingMode|string|null $mode = null): QrMatrix
    {
        $ecc = ErrorCorrectionLevel::fromName($level);
        $encodingMode = EncodingMode::resolve($mode ?? EncodingMode::Auto, $data);

        $versionSpec = VersionTable::forPayload($data, $ecc, $encodingMode);
        $this->version = $versionSpec;

        $codewords = $this->dataEncoder->encode($data, $level, $versionSpec, $encodingMode);

        $matrix = new QrMatrix($versionSpec->size);

        (new FinderPattern($matrix))->place();
        (new AlignmentPattern($matrix, $versionSpec))->place();
        (new TimingPattern($matrix))->place();
        (new VersionInformation($matrix, $versionSpec))->place();

        // Reserve the format information area (and dark module)
        $format = new FormatInformation($matrix);
        $format->place($ecc, MaskPattern::Pattern0);

        (new DataPlacer($matrix))->place($codewords);

        // Evaluate all 8 masks on temporary candidate matrices
        $bestScore = null;
        $bestPattern = MaskPattern::Pattern0;

        foreach (MaskPattern::cases() as $pattern) {
            $candidateMatrix = clone $matrix;
            (new Mask($candidateMatrix))->apply($pattern);
            (new FormatInformation($candidateMatrix))->place($ecc, $pattern);

            $score = (new Mask($candidateMatrix))->score();

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestPattern = $pattern;
            }
        }

        // Apply chosen mask and write final format information
        (new Mask($matrix))->apply($bestPattern);
        $format->place($ecc, $bestPattern);

        $this->selectedMask = $bestPattern;

        return $matrix;
    }

    /**
     * The mask pattern chosen by the most recent build (lowest penalty).
     */
    public function selectedMask(): MaskPattern
    {
        return $this->selectedMask ?? MaskPattern::Pattern0;
    }

    /**
     * The version specification chosen by the most recent build.
     */
    public function version(): ?VersionSpec
    {
        return $this->version;
    }
}
