<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use RuntimeException;

/**
 * Places codeword bits into the data region in the standard zigzag order
 * (right to left, two-module-wide columns, skipping the vertical timing
 * pattern column), per ISO/IEC 18004.
 */
final class DataPlacer
{
    public function __construct(
        private readonly QrMatrix $matrix,
    ) {}

    /**
     * Place the codeword bits; remaining data modules stay light.
     *
     * @param  int[]  $codewords  Data+ECC codewords, unsigned bytes
     */
    public function place(array $codewords): void
    {
        $bits = [];

        foreach ($codewords as $codeword) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = (($codeword >> $i) & 1) === 1;
            }
        }

        $bitIndex = 0;
        $size = $this->matrix->size;
        $totalBits = count($bits);

        // Data modules are exactly the non-function modules at this stage.
        $available = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (! $this->matrix->isFunctionModule($x, $y)) {
                    $available++;
                }
            }
        }

        if ($totalBits > $available) {
            throw new RuntimeException(sprintf(
                'Codeword bits (%d) exceed the available data modules (%d).',
                $totalBits,
                $available
            ));
        }

        // Two-column strips, right to left, skipping the vertical timing
        // column (column 6) by shifting the strip one column left.
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            // First strip runs upward, alternating thereafter.
            $upward = (($right + 1) & 2) === 0;

            for ($vertical = 0; $vertical < $size; $vertical++) {
                $y = $upward ? $size - 1 - $vertical : $vertical;

                // Right column of the pair first, then the left one.
                $bitIndex = $this->placeBit($right, $y, $bits, $bitIndex);
                $bitIndex = $this->placeBit($right - 1, $y, $bits, $bitIndex);
            }
        }

        if ($bitIndex < $totalBits) {
            throw new RuntimeException(sprintf(
                'Codeword bits (%d) exceed the available data modules (placed %d).',
                $totalBits,
                $bitIndex
            ));
        }
    }

    /**
     * Place one bit if the module is available for data; returns the
     * (possibly unchanged) bit index.
     *
     * @param  list<bool>  $bits
     */
    private function placeBit(int $x, int $y, array $bits, int $bitIndex): int
    {
        if ($bitIndex >= count($bits)) {
            return $bitIndex;
        }

        if ($this->matrix->isFunctionModule($x, $y) || $this->matrix->get($x, $y) !== null) {
            return $bitIndex;
        }

        $this->matrix->set($x, $y, $bits[$bitIndex]);

        return $bitIndex + 1;
    }
}
