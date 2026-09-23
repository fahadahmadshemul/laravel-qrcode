<?php

declare(strict_types=1);

namespace Fahad\QrCode\ErrorCorrection;

use RuntimeException;

/**
 * Reed–Solomon error correction over GF(2^8).
 *
 * Computes the ECC codewords for a data block (systematic encoding via
 * polynomial division by the generator polynomial), and handles the
 * QR-specific interleaving of data/ECC codewords across multiple blocks.
 *
 * This class is part of the framework-agnostic QR engine and must not
 * depend on Laravel.
 */
final class ReedSolomon
{
    public function __construct(
        private readonly GaloisField $field = new GaloisField,
    ) {}

    /**
     * Compute the ECC codewords for one block.
     *
     * @param  int[]  $data  Data codewords (unsigned bytes)
     * @param  int  $eccCodewordCount  Number of ECC codewords to produce
     * @return int[] ECC codewords
     */
    public function encodeBlock(array $data, int $eccCodewordCount): array
    {
        $this->assertCodewords($data);

        if ($eccCodewordCount < 1 || $data === []) {
            return [];
        }

        $generator = $this->generatorPolynomial($eccCodewordCount);
        $dataCount = count($data);

        // Work on the message polynomial followed by $eccCodewordCount
        // zero coefficients; synthetic division leaves the remainder,
        // which is exactly the ECC codeword sequence.
        $work = array_merge($data, array_fill(0, $eccCodewordCount, 0));

        for ($i = 0; $i < $dataCount; $i++) {
            $factor = $work[$i];

            if ($factor === 0) {
                continue;
            }

            foreach ($generator as $j => $coefficient) {
                $work[$i + $j] ^= $this->field->multiply($coefficient, $factor);
            }
        }

        return array_slice($work, $dataCount, $eccCodewordCount);
    }

    /**
     * Interleave data and ECC codewords across blocks, per the QR spec.
     * With a single block (all versions up to and including 1) this simply
     * concatenates data followed by ECC.
     *
     * @param  list<int[]>  $dataBlocks
     * @param  list<int[]>  $eccBlocks
     * @return int[]
     */
    public function interleave(array $dataBlocks, array $eccBlocks): array
    {
        if (count($dataBlocks) !== count($eccBlocks)) {
            throw new RuntimeException('Data and ECC block counts do not match.');
        }

        $interleaved = [];

        $maxDataLength = count($dataBlocks) === 0
            ? 0
            : max(array_map('count', $dataBlocks));
        $maxEccLength = count($eccBlocks) === 0
            ? 0
            : max(array_map('count', $eccBlocks));

        for ($column = 0; $column < $maxDataLength; $column++) {
            foreach ($dataBlocks as $block) {
                if ($column < count($block)) {
                    $interleaved[] = $block[$column];
                }
            }
        }

        for ($column = 0; $column < $maxEccLength; $column++) {
            foreach ($eccBlocks as $block) {
                if ($column < count($block)) {
                    $interleaved[] = $block[$column];
                }
            }
        }

        return $interleaved;
    }

    /**
     * The generator polynomial ∏(x - α^i) for i in [0, count),
     * coefficients highest degree first, monic.
     *
     * Exposed for testing and for callers that need the QR-spec generator
     * polynomial directly (e.g. degrees 7, 10, 13, 17 for Version 1).
     *
     * @return int[]
     */
    public function generatorPolynomial(int $count): array
    {
        $polynomial = [1];

        for ($i = 0; $i < $count; $i++) {
            $polynomial = $this->multiplyPolynomials(
                $polynomial,
                [1, $this->field->exponent($i)],
            );
        }

        return $polynomial;
    }

    /**
     * Multiply two polynomials over GF(256).
     *
     * @param  int[]  $a
     * @param  int[]  $b
     * @return int[]
     */
    public function multiplyPolynomials(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0);

        foreach ($a as $i => $aCoefficient) {
            if ($aCoefficient === 0) {
                continue;
            }

            foreach ($b as $j => $bCoefficient) {
                $result[$i + $j] ^= $this->field->multiply($aCoefficient, $bCoefficient);
            }
        }

        return $result;
    }

    /**
     * @param  int[]  $codewords
     */
    private function assertCodewords(array $codewords): void
    {
        foreach ($codewords as $codeword) {
            if ($codeword < 0 || $codeword > 255) {
                throw new RuntimeException('Codewords must be unsigned bytes (0-255).');
            }
        }
    }
}
