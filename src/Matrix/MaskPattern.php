<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * QR mask patterns 0-7, as XOR templates over the data region.
 */
enum MaskPattern: int
{
    case Pattern0 = 0;
    case Pattern1 = 1;
    case Pattern2 = 2;
    case Pattern3 = 3;
    case Pattern4 = 4;
    case Pattern5 = 5;
    case Pattern6 = 6;
    case Pattern7 = 7;

    /**
     * The mask condition for this pattern at the given coordinate.
     * Formulas per ISO/IEC 18004; returns true when the module is inverted.
     */
    public function inverts(int $x, int $y): bool
    {
        return match ($this) {
            self::Pattern0 => ($x + $y) % 2 === 0,
            self::Pattern1 => $y % 2 === 0,
            self::Pattern2 => $x % 3 === 0,
            self::Pattern3 => ($x + $y) % 3 === 0,
            self::Pattern4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
            self::Pattern5 => (($x * $y) % 2 + ($x * $y) % 3) === 0,
            self::Pattern6 => ((($x * $y) % 2 + ($x * $y) % 3) % 2) === 0,
            self::Pattern7 => ((($x + $y) % 2 + ($x * $y) % 3) % 2) === 0,
        };
    }
}
