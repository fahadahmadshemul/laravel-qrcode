<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\DataPlacer;
use Fahad\QrCode\Matrix\FinderPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

final class DataPlacerTest extends TestCase
{
    public function test_places_bits_in_available_modules_only(): void
    {
        $matrix = new QrMatrix(21);
        (new FinderPattern($matrix))->place();

        (new DataPlacer($matrix))->place([0xFF, 0x00, 0xAA]);

        // Exactly the number of codeword bits gets placed.
        $placed = 0;
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                if (! $matrix->isFunctionModule($x, $y) && $matrix->get($x, $y) !== null) {
                    $placed++;
                }
            }
        }

        $this->assertSame(24, $placed);
    }

    public function test_codewords_exceeding_capacity_throws(): void
    {
        $matrix = new QrMatrix(21);
        (new FinderPattern($matrix))->place();

        $this->expectException(\RuntimeException::class);
        (new DataPlacer($matrix))->place(array_fill(0, 100, 0xFF));
    }
}
