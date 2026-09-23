<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Computes and places the 18-bit Version Information pattern for Versions 7–10
 * (BCH(18, 6) code, ISO/IEC 18004 Section 8.10).
 */
final class VersionInformation
{
    private const GENERATOR = 0b1111100100101; // G(x) = x^12 + x^11 + x^10 + x^9 + x^8 + x^5 + x^2 + 1

    public function __construct(
        private readonly QrMatrix $matrix,
        private readonly VersionSpec $version,
    ) {}

    /**
     * Compute 18-bit version information for version number.
     */
    public static function bits(int $versionNumber): int
    {
        $remainder = $versionNumber << 12;

        for ($i = 17; $i >= 12; $i--) {
            if (($remainder >> $i) & 1) {
                $remainder ^= self::GENERATOR << ($i - 12);
            }
        }

        return ($versionNumber << 12) | $remainder;
    }

    public function place(): void
    {
        if ($this->version->number < 7) {
            return;
        }

        $bits = self::bits($this->version->number);
        $size = $this->matrix->size;

        // Copy 1: Bottom-left (3 columns x 6 rows, above bottom-left finder)
        for ($i = 0; $i < 18; $i++) {
            $x = intdiv($i, 3);
            $y = $size - 11 + ($i % 3);
            $dark = (($bits >> $i) & 1) === 1;

            $this->matrix->set($x, $y, $dark);
            $this->matrix->markFunction($x, $y);
        }

        // Copy 2: Top-right (6 columns x 3 rows, left of top-right finder)
        for ($i = 0; $i < 18; $i++) {
            $x = $size - 11 + ($i % 3);
            $y = intdiv($i, 3);
            $dark = (($bits >> $i) & 1) === 1;

            $this->matrix->set($x, $y, $dark);
            $this->matrix->markFunction($x, $y);
        }
    }
}
