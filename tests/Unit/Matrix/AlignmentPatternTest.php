<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\AlignmentPattern;
use Fahad\QrCode\Matrix\FinderPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for alignment-pattern placement (ISO/IEC 18004 §8.5):
 * the 5x5 concentric shape, the finder-overlap skip, and the no-op for
 * versions that define no alignment patterns.
 */
final class AlignmentPatternTest extends TestCase
{
    public function test_version_1_places_no_alignment_patterns(): void
    {
        $spec = VersionTable::get(1);
        $this->assertSame([], $spec->alignmentPatternCenters);

        $matrix = new QrMatrix($spec->size);
        (new AlignmentPattern($matrix, $spec))->place();

        // No alignment centers → nothing placed, no function modules marked.
        $marked = 0;
        for ($y = 0; $y < $matrix->size; $y++) {
            for ($x = 0; $x < $matrix->size; $x++) {
                if ($matrix->isFunctionModule($x, $y)) {
                    $marked++;
                }
            }
        }

        $this->assertSame(0, $marked);
    }

    public function test_interior_alignment_pattern_is_a_concentric_5x5(): void
    {
        $spec = VersionTable::get(7);
        // ISO Table E.1: Version 7 alignment centers.
        $this->assertSame([6, 22, 38], $spec->alignmentPatternCenters);

        $matrix = new QrMatrix($spec->size);
        (new FinderPattern($matrix))->place();
        (new AlignmentPattern($matrix, $spec))->place();

        // The center at (22, 22) is well away from finders and is placed in full.
        [$cx, $cy] = [22, 22];

        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $chebyshev = max(abs($dx), abs($dy));
                $expectedDark = $chebyshev === 0 || $chebyshev === 2;

                $this->assertSame(
                    $expectedDark,
                    $matrix->get($cx + $dx, $cy + $dy),
                    "Module at offset ({$dx}, {$dy}) has the wrong colour."
                );
                $this->assertTrue(
                    $matrix->isFunctionModule($cx + $dx, $cy + $dy),
                    "Module at offset ({$dx}, {$dy}) must be marked as a function module."
                );
            }
        }
    }

    public function test_center_overlapping_the_top_left_finder_is_skipped(): void
    {
        $spec = VersionTable::get(7);

        $matrix = new QrMatrix($spec->size);
        (new FinderPattern($matrix))->place();
        (new AlignmentPattern($matrix, $spec))->place();

        // (6, 6) coincides with the top-left finder, so the alignment loop must
        // skip it and leave the finder's dark module intact.
        $this->assertTrue($matrix->isFunctionModule(6, 6));
        $this->assertTrue($matrix->get(0, 0), 'Top-left finder corner must remain dark.');
        $this->assertTrue($matrix->get(6, 6), 'Finder module at (6, 6) must remain dark.');
    }
}
