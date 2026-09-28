<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\VersionInformation;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for version-information PLACEMENT (ISO/IEC 18004 §8.10).
 * The BCH bit values are covered elsewhere; this pins the two placement copies
 * and the version-7 threshold.
 */
final class VersionInformationTest extends TestCase
{
    public function test_versions_below_7_place_nothing(): void
    {
        $spec = VersionTable::get(6);
        $matrix = new QrMatrix($spec->size);

        (new VersionInformation($matrix, $spec))->place();

        $marked = 0;
        for ($y = 0; $y < $matrix->size; $y++) {
            for ($x = 0; $x < $matrix->size; $x++) {
                if ($matrix->isFunctionModule($x, $y)) {
                    $marked++;
                }
            }
        }

        $this->assertSame(0, $marked, 'Version information must not be placed below version 7.');
    }

    public function test_version_7_places_both_copies_matching_the_bch_bits(): void
    {
        $spec = VersionTable::get(7);
        $matrix = new QrMatrix($spec->size);

        (new VersionInformation($matrix, $spec))->place();

        $bits = VersionInformation::bits(7);
        $size = $spec->size;

        for ($i = 0; $i < 18; $i++) {
            $expectedDark = (($bits >> $i) & 1) === 1;

            // Copy 1: bottom-left block.
            $x1 = intdiv($i, 3);
            $y1 = $size - 11 + ($i % 3);
            $this->assertSame($expectedDark, $matrix->get($x1, $y1), "Copy 1 bit {$i}");
            $this->assertTrue($matrix->isFunctionModule($x1, $y1), "Copy 1 bit {$i} function flag");

            // Copy 2: top-right block (transpose of copy 1).
            $x2 = $size - 11 + ($i % 3);
            $y2 = intdiv($i, 3);
            $this->assertSame($expectedDark, $matrix->get($x2, $y2), "Copy 2 bit {$i}");
            $this->assertTrue($matrix->isFunctionModule($x2, $y2), "Copy 2 bit {$i} function flag");
        }
    }
}
