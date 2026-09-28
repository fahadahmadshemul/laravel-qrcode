<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Matrix\EccBlockSpec;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\QrMatrix;

/**
 * Reads the data codewords back out of a finished QR matrix.
 *
 * Given the mask the builder chose, it reverses the module masking and the
 * zig-zag placement to recover the interleaved codeword stream, then undoes
 * the block interleaving to return the padded data codewords — the same array
 * {@see DataEncoder::dataCodewords()} produced. Feeding
 * that to {@see QrStreamDecoder} closes the loop from a rendered code back to
 * the original payload.
 */
final class MatrixCodewordReader
{
    /**
     * Recover the padded data codewords from a built matrix.
     *
     * @return int[]
     */
    public static function dataCodewords(QrMatrix $matrix, MaskPattern $mask, EccBlockSpec $spec): array
    {
        $interleaved = self::extractInterleaved($matrix, $mask);

        return self::deinterleaveData($interleaved, $spec);
    }

    /**
     * Reverse the zig-zag walk and unmask to recover the full codeword stream
     * (data followed by ECC), exactly as it was placed.
     *
     * @return int[]
     */
    private static function extractInterleaved(QrMatrix $matrix, MaskPattern $mask): array
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

    /**
     * Undo the data-codeword interleaving and return the blocks concatenated
     * back in order (i.e. the original padded data-codeword sequence).
     *
     * @param  int[]  $interleaved
     * @return int[]
     */
    private static function deinterleaveData(array $interleaved, EccBlockSpec $spec): array
    {
        $blockCounts = $spec->blockDataCounts();
        $totalData = array_sum($blockCounts);
        $dataSection = array_slice($interleaved, 0, $totalData);

        $blocks = array_fill(0, count($blockCounts), []);
        $maxData = $blockCounts === [] ? 0 : max($blockCounts);

        $index = 0;
        for ($column = 0; $column < $maxData; $column++) {
            foreach ($blockCounts as $block => $count) {
                if ($column < $count) {
                    $blocks[$block][$column] = $dataSection[$index++];
                }
            }
        }

        $data = [];
        foreach ($blocks as $block) {
            foreach ($block as $codeword) {
                $data[] = $codeword;
            }
        }

        return $data;
    }
}
