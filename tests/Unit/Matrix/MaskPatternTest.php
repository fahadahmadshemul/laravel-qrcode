<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\MaskPattern;
use PHPUnit\Framework\TestCase;

/**
 * Hand-computed truth tables for the eight ISO/IEC 18004 mask formulas.
 * Coordinates use (x = column, y = row), matching DataPlacer and Mask.
 */
final class MaskPatternTest extends TestCase
{
    public function test_all_eight_patterns_exist_with_sequential_values(): void
    {
        $this->assertSame(range(0, 7), array_map(
            static fn (MaskPattern $pattern): int => $pattern->value,
            MaskPattern::cases()
        ));
    }

    public function test_pattern_0_inverts_on_even_parity(): void
    {
        $this->assertTrue(MaskPattern::Pattern0->inverts(0, 0));
        $this->assertTrue(MaskPattern::Pattern0->inverts(3, 1)); // sum 4
        $this->assertFalse(MaskPattern::Pattern0->inverts(3, 2)); // sum 5
        $this->assertFalse(MaskPattern::Pattern0->inverts(20, 19));
        $this->assertTrue(MaskPattern::Pattern0->inverts(20, 20));
    }

    public function test_pattern_1_inverts_on_even_rows(): void
    {
        $this->assertTrue(MaskPattern::Pattern1->inverts(0, 0));
        $this->assertTrue(MaskPattern::Pattern1->inverts(20, 4));
        $this->assertFalse(MaskPattern::Pattern1->inverts(0, 1));
        $this->assertFalse(MaskPattern::Pattern1->inverts(20, 19));
    }

    public function test_pattern_2_inverts_every_third_column(): void
    {
        $this->assertTrue(MaskPattern::Pattern2->inverts(0, 0));
        $this->assertTrue(MaskPattern::Pattern2->inverts(3, 20));
        $this->assertFalse(MaskPattern::Pattern2->inverts(1, 0));
        $this->assertFalse(MaskPattern::Pattern2->inverts(20, 0));
    }

    public function test_pattern_3_inverts_when_row_plus_column_mod_3_is_zero(): void
    {
        $this->assertTrue(MaskPattern::Pattern3->inverts(0, 0));
        $this->assertTrue(MaskPattern::Pattern3->inverts(1, 2)); // sum 3
        $this->assertFalse(MaskPattern::Pattern3->inverts(1, 1)); // sum 2
        $this->assertFalse(MaskPattern::Pattern3->inverts(20, 20)); // sum 40 mod 3 = 1
        $this->assertTrue(MaskPattern::Pattern3->inverts(20, 19)); // sum 39
    }

    public function test_pattern_4_inverts_on_half_block_parity(): void
    {
        $this->assertTrue(MaskPattern::Pattern4->inverts(0, 0)); // 0 + 0
        $this->assertTrue(MaskPattern::Pattern4->inverts(5, 3)); // 1 + 1 = 2
        $this->assertFalse(MaskPattern::Pattern4->inverts(3, 0)); // 1 + 0 = 1
        $this->assertFalse(MaskPattern::Pattern4->inverts(0, 2)); // 0 + 1 = 1
    }

    public function test_pattern_5_inverts_when_product_terms_cancel(): void
    {
        $this->assertTrue(MaskPattern::Pattern5->inverts(0, 0)); // 0 + 0
        $this->assertTrue(MaskPattern::Pattern5->inverts(2, 3)); // 0 + 0
        $this->assertFalse(MaskPattern::Pattern5->inverts(1, 1)); // 1 + 1
        $this->assertFalse(MaskPattern::Pattern5->inverts(1, 2)); // 0 + 2
    }

    public function test_pattern_6_inverts_when_parity_of_product_terms_is_even(): void
    {
        // (0 + 0) % 2 === 0.
        $this->assertTrue(MaskPattern::Pattern6->inverts(0, 0));

        // x*y = 6: (0 + 0) % 2 === 0.
        $this->assertTrue(MaskPattern::Pattern6->inverts(3, 2));

        // x*y = 3: (1 + 0) % 2 === 1.
        $this->assertFalse(MaskPattern::Pattern6->inverts(3, 1));
        $this->assertFalse(MaskPattern::Pattern6->inverts(1, 3));
    }

    public function test_pattern_7_inverts_when_combined_condition_is_even(): void
    {
        // (0 % 2) + (0 % 3) = 0.
        $this->assertTrue(MaskPattern::Pattern7->inverts(0, 0));

        // (2 % 2) + (0 % 3) = 0 and (6 % 2) + (9 % 3) = 0.
        $this->assertTrue(MaskPattern::Pattern7->inverts(2, 0));
        $this->assertTrue(MaskPattern::Pattern7->inverts(3, 3));

        // (1 % 2) + (0 % 3) = 1 and (5 % 2) + (6 % 3) = 1.
        $this->assertFalse(MaskPattern::Pattern7->inverts(1, 0));
        $this->assertFalse(MaskPattern::Pattern7->inverts(3, 2));
    }

    public function test_full_grid_counts_match_closed_forms(): void
    {
        $counts = [];

        foreach (MaskPattern::cases() as $pattern) {
            $counts[$pattern->value] = $this->countGrid($pattern);
        }

        // (x + y) even over 21x21 (odd side length -> one extra).
        $this->assertSame(221, $counts[0]);

        // Every second row fully inverted.
        $this->assertSame(231, $counts[1]);

        // Every third column inverted.
        $this->assertSame(147, $counts[2]);

        // Thirds of the grid, offset so all eight stay in a sane band.
        $this->assertSame(147, $counts[3]);

        foreach (array_slice($counts, 4) as $count) {
            $this->assertGreaterThan(100, $count);
            $this->assertLessThan(330, $count);
        }
    }

    private function countGrid(MaskPattern $pattern): int
    {
        $count = 0;

        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                if ($pattern->inverts($x, $y)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
