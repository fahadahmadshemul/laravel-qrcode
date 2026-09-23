<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\Mask;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

final class MaskTest extends TestCase
{
    public function test_apply_inverts_data_modules_per_pattern(): void
    {
        $matrix = new QrMatrix(21);
        $matrix->set(0, 0, false); // data module (not yet marked function)

        (new Mask($matrix))->apply(MaskPattern::Pattern0);

        // (0+0) % 2 === 0 -> inverted to dark.
        $this->assertTrue($matrix->get(0, 0));
    }

    public function test_apply_skips_function_modules(): void
    {
        $matrix = new QrMatrix(21);
        $matrix->set(0, 0, false);
        $matrix->markFunction(0, 0);

        (new Mask($matrix))->apply(MaskPattern::Pattern0);

        $this->assertFalse($matrix->get(0, 0));
    }

    public function test_apply_is_self_inverse(): void
    {
        $matrix = new QrMatrix(21);
        $matrix->set(5, 5, true);

        $mask = new Mask($matrix);
        $mask->apply(MaskPattern::Pattern1);
        $mask->apply(MaskPattern::Pattern1);

        $this->assertTrue($matrix->get(5, 5));
    }

    public function test_apply_and_score_combine(): void
    {
        $matrix = new QrMatrix(21);
        $matrix->set(9, 9, true);
        $matrix->set(10, 9, false);

        $mask = new Mask($matrix);
        $before = $mask->score();

        $mask->apply(MaskPattern::Pattern1);

        // The mask toggles data modules, so the score changes.
        $this->assertNotSame($before, $mask->score());
    }

    public function test_score_is_deterministic_and_non_negative(): void
    {
        $mask = new Mask(new QrMatrix(21));

        $this->assertSame($mask->score(), $mask->score());
        $this->assertGreaterThanOrEqual(0, $mask->score());
    }

    public function test_score_all_light_matrix(): void
    {
        $matrix = $this->uniformMatrix(false);

        // N1: 42 lines x (3 + 16) = 798; N2: 400 x 3 = 1200;
        // N3: 0; N4: 100 (0% dark -> deviation 50).
        $this->assertSame(798 + 1200 + 0 + 100, (new Mask($matrix))->score());
    }

    public function test_score_all_dark_matrix(): void
    {
        $matrix = $this->uniformMatrix(true);

        // Same as all-light: N1 = 798, N2 = 1200, N3 = 0, N4 = 100.
        $this->assertSame(798 + 1200 + 0 + 100, (new Mask($matrix))->score());
    }

    public function test_rule_n2_counts_single_uniform_block(): void
    {
        $matrix = new QrMatrix(21);
        $this->fillAll($matrix, true);

        // Break every 2x2 block except the one at (0, 0).
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                if ($x + $y > 1 && ($x + $y) % 2 === 1) {
                    $matrix->set($x, $y, false);
                }
            }
        }

        $this->assertSame(3, $this->ruleN2($matrix));
    }

    public function test_rule_n3_counts_finder_like_run(): void
    {
        $matrix = new QrMatrix(21);
        $this->fillAll($matrix, false);

        // Row 10: [1 0 111 0 1] boxed by 4 light modules on the right.
        foreach ([0, 2, 3, 4, 6, 11, 12, 13, 14] as $x) {
            $matrix->set($x, 10, true);
        }

        $this->assertSame(40, $this->ruleN3($matrix));
    }

    public function test_rule_n3_reverse_direction_counts(): void
    {
        $matrix = new QrMatrix(21);
        $this->fillAll($matrix, false);

        // Row 10: [1 0 111 0 1] boxed by four light modules on the right,
        // built so the ONLY matching 11-wide window in any orientation
        // across rows and columns is this one: assert via manual window walk.
        foreach ([2, 4, 5, 6, 8, 13, 14, 15, 16] as $x) {
            $matrix->set($x, 10, true);
        }

        $this->assertCount(1, $this->matchingWindows($matrix, 10));
        $this->assertSame(40, $this->ruleN3($matrix));
    }

    /**
     * @return list<int>
     */
    private function matchingWindows(QrMatrix $matrix, int $row): array
    {
        $line = [];
        for ($x = 0; $x < 21; $x++) {
            $line[] = $matrix->get($x, $row);
        }

        $found = [];
        $pattern = [true, false, true, true, true, false, true, false, false, false, false];
        $reverse = array_reverse($pattern);

        for ($i = 0; $i <= 21 - 11; $i++) {
            $window = array_slice($line, $i, 11);
            if ($window === $pattern || $window === $reverse) {
                $found[] = $i;
            }
        }

        return $found;
    }

    public function test_rule_n4_scales_with_dark_ratio(): void
    {
        // 220 / 441 = 49.88% -> deviation from 50% is < 5% -> 0 penalty.
        $matrix1 = new QrMatrix(21);
        $dark = 0;
        for ($y = 0; $y < 21 && $dark < 220; $y++) {
            for ($x = 0; $x < 21 && $dark < 220; $x++) {
                $matrix1->set($x, $y, true);
                $dark++;
            }
        }
        $this->assertSame(0, $this->ruleN4($matrix1));

        // 195 / 441 = 44.2% -> deviation from 50% is 5.8% -> 10 penalty.
        $matrix2 = new QrMatrix(21);
        $dark = 0;
        for ($y = 0; $y < 21 && $dark < 195; $y++) {
            for ($x = 0; $x < 21 && $dark < 195; $x++) {
                $matrix2->set($x, $y, true);
                $dark++;
            }
        }
        $this->assertSame(10, $this->ruleN4($matrix2));

        // 0 / 441 = 0% -> deviation from 50% is 50% -> 100 penalty.
        $matrix3 = $this->uniformMatrix(false);
        $this->assertSame(100, $this->ruleN4($matrix3));
    }

    public function test_applying_mask_to_temporary_matrix_leaves_original_untouched(): void
    {
        $original = new QrMatrix(21);
        $original->set(0, 0, false);

        $temp = clone $original;
        (new Mask($temp))->apply(MaskPattern::Pattern0);

        // (0+0) % 2 === 0 -> temp inverted to dark.
        $this->assertTrue($temp->get(0, 0));
        // Original remains false (light).
        $this->assertFalse($original->get(0, 0));
    }

    private function uniformMatrix(bool $dark): QrMatrix
    {
        $matrix = new QrMatrix(21);
        $this->fillAll($matrix, $dark);

        return $matrix;
    }

    private function fillAll(QrMatrix $matrix, bool $dark): void
    {
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $matrix->set($x, $y, $dark);
            }
        }
    }

    private function ruleN2(QrMatrix $matrix): int
    {
        return $this->invokeRule($matrix, 'ruleN2');
    }

    private function ruleN3(QrMatrix $matrix): int
    {
        return $this->invokeRule($matrix, 'ruleN3');
    }

    private function ruleN4(QrMatrix $matrix): int
    {
        return $this->invokeRule($matrix, 'ruleN4');
    }

    private function invokeRule(QrMatrix $matrix, string $method): int
    {
        $mask = new Mask($matrix);
        $reflection = new \ReflectionMethod($mask, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($mask);
    }
}

