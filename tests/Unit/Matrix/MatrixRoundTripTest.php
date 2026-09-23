<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\FormatInformation;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

/**
 * Reads a finished matrix back (format info, unmasking, reverse zigzag)
 * and proves the codewords, mask and ECC level can be recovered exactly.
 * This is an end-to-end proof that placement and masking follow the spec.
 */
final class MatrixRoundTripTest extends TestCase
{
    public function test_finished_matrix_round_trips_to_the_original_codewords(): void
    {
        $payload = 'HELLO WORLD';
        $matrix = (new MatrixBuilder)->build($payload, 'M');

        // --- 1. Read back the format information (copy 1). ---
        $bits = 0;
        for ($i = 0; $i <= 5; $i++) {
            $bits |= ($matrix->get(8, $i) === true ? 1 : 0) << $i;
        }
        $bits |= ($matrix->get(8, 7) === true ? 1 : 0) << 6;
        $bits |= ($matrix->get(8, 8) === true ? 1 : 0) << 7;
        $bits |= ($matrix->get(7, 8) === true ? 1 : 0) << 8;
        for ($i = 9; $i <= 14; $i++) {
            $bits |= ($matrix->get(14 - $i, 8) === true ? 1 : 0) << $i;
        }

        // The mask pattern lives in bits 10-12 of the *un-XORed* format code.
        $unmasked = $bits ^ 0x5412;
        $maskValue = ($unmasked >> 10) & 0b111;
        $mask = MaskPattern::from($maskValue);
        $levelBits = ($unmasked >> 13) & 0b11;

        $this->assertSame(
            FormatInformation::bits(ErrorCorrectionLevel::M, $mask),
            $bits,
            'format information read back must match a valid format code'
        );
        $this->assertSame(ErrorCorrectionLevel::M->formatBits(), $levelBits);

        // --- 2. Copy 2 must carry the same format information. ---
        $this->assertSame($this->readFormatCopyTwo($matrix), $bits);

        // --- 3. Version 1 has exactly 26 codewords = 208 data modules. ---
        $dataModules = 0;
        for ($y = 0; $y < $matrix->size; $y++) {
            for ($x = 0; $x < $matrix->size; $x++) {
                if (! $matrix->isFunctionModule($x, $y)) {
                    $dataModules++;
                }
            }
        }
        $this->assertSame(208, $dataModules);

        // --- 4. Unmask and reverse the zigzag to recover codewords. ---
        $recovered = $this->extractCodewords($matrix, $mask);

        $this->assertSame(
            (new DataEncoder)->encode($payload, 'M'),
            $recovered,
            'recovered codewords (data + ECC) must equal the encoded codewords'
        );
    }

    private function readFormatCopyTwo(QrMatrix $matrix): int
    {
        $size = $matrix->size;
        $bits = 0;

        for ($i = 0; $i <= 7; $i++) {
            $bits |= ($matrix->get($size - 1 - $i, 8) === true ? 1 : 0) << $i;
        }
        $bits |= ($matrix->get(8, $size - 8) === true ? 1 : 0) << 8;
        for ($i = 9; $i <= 14; $i++) {
            $bits |= ($matrix->get(8, $size - 15 + $i) === true ? 1 : 0) << $i;
        }

        return $bits;
    }

    /**
     * @return int[]
     */
    private function extractCodewords(QrMatrix $matrix, MaskPattern $mask): array
    {
        $size = $matrix->size;
        $bits = [];

        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            $upward = (($right + 1) & 2) === 0;

            for ($vertical = 0; $vertical < $size; $vertical++) {
                $y = $upward ? $size - 1 - $vertical : $vertical;

                foreach ([$right, $right - 1] as $x) {
                    if ($matrix->isFunctionModule($x, $y)) {
                        continue;
                    }

                    $dark = $matrix->get($x, $y) === true;

                    if ($mask->inverts($x, $y)) {
                        $dark = ! $dark;
                    }

                    $bits[] = $dark ? 1 : 0;
                }
            }
        }

        $codewords = [];
        foreach (array_chunk($bits, 8) as $byte) {
            if (count($byte) < 8) {
                continue;
            }
            $value = 0;
            foreach ($byte as $bit) {
                $value = ($value << 1) | $bit;
            }
            $codewords[] = $value;
        }

        return $codewords;
    }
}
