<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;

/**
 * Computes and places the 15-bit format information (ECC level + mask
 * pattern), BCH(15,5) protected and XORed with 0x5412 per ISO/IEC 18004.
 */
final class FormatInformation
{
    private const FORMAT_MASK = 0x5412;

    private const GENERATOR = 0b10100110111;

    public function __construct(
        private readonly QrMatrix $matrix,
    ) {}

    /**
     * Compute the 15 format bits for an ECC level and mask pattern.
     */
    public static function bits(ErrorCorrectionLevel $level, MaskPattern $mask): int
    {
        $data = $level->formatBits() << 3 | $mask->value;
        $remainder = $data << 10;

        for ($i = 14; $i >= 10; $i--) {
            if (($remainder >> $i) & 1) {
                $remainder ^= self::GENERATOR << ($i - 10);
            }
        }

        return (($data << 10) | $remainder) ^ self::FORMAT_MASK;
    }

    /**
     * Place both copies of the format information and the dark module,
     * per ISO/IEC 18004 Figure 25.
     */
    public function place(ErrorCorrectionLevel $level, MaskPattern $mask): void
    {
        $bits = self::bits($level, $mask);
        $size = $this->matrix->size;

        // Copy 1, around the top-left finder.
        for ($i = 0; $i <= 5; $i++) {
            $this->placeBit(8, $i, $bits, $i);
        }
        $this->placeBit(8, 7, $bits, 6);
        $this->placeBit(8, 8, $bits, 7);
        $this->placeBit(7, 8, $bits, 8);
        for ($i = 9; $i <= 14; $i++) {
            $this->placeBit(14 - $i, 8, $bits, $i);
        }

        // Copy 2, beside the top-right and bottom-left finders.
        for ($i = 0; $i <= 7; $i++) {
            $this->placeBit($size - 1 - $i, 8, $bits, $i);
        }
        for ($i = 8; $i <= 14; $i++) {
            $this->placeBit(8, $size - 15 + $i, $bits, $i);
        }

        // Dark module (always dark, never masked): column 8, row 4*version+9.
        $this->matrix->set(8, $size - 8, true);
        $this->matrix->markFunction(8, $size - 8);
    }

    private function placeBit(int $x, int $y, int $bits, int $bitIndex): void
    {
        $this->matrix->set($x, $y, (($bits >> $bitIndex) & 1) === 1);
        $this->matrix->markFunction($x, $y);
    }
}
