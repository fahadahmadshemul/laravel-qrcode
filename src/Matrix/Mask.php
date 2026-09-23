<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Applies a mask pattern to the data region and selects the best mask via
 * the ISO/IEC 18004 penalty rules N1–N4.
 *
 * Only data modules are ever masked or scored; function modules (finder,
 * timing, format) are excluded.
 */
final class Mask
{
    public function __construct(
        private readonly QrMatrix $matrix,
    ) {}

    /**
     * XOR the given mask pattern over the data region.
     */
    public function apply(MaskPattern $pattern): void
    {
        for ($y = 0; $y < $this->matrix->size; $y++) {
            for ($x = 0; $x < $this->matrix->size; $x++) {
                if ($this->matrix->isFunctionModule($x, $y)) {
                    continue;
                }

                if ($pattern->inverts($x, $y)) {
                    $this->matrix->set($x, $y, ! $this->matrix->get($x, $y));
                }
            }
        }
    }

    /**
     * Penalty score for the current matrix state (rules N1, N3, N4; rule
     * N2 concerns 2×2 blocks and is included as well).
     */
    public function score(): int
    {
        return $this->ruleN1() + $this->ruleN2() + $this->ruleN3() + $this->ruleN4();
    }

    /**
     * N1: runs of five or more same-coloured modules in rows and columns.
     */
    private function ruleN1(): int
    {
        $penalty = 0;
        $size = $this->matrix->size;

        foreach ($this->matrix->toArray() as $row) {
            $this->scoreRuns($row, $penalty);
        }

        for ($x = 0; $x < $size; $x++) {
            $column = [];

            for ($y = 0; $y < $size; $y++) {
                $column[] = $this->matrix->get($x, $y);
            }

            $this->scoreRuns($column, $penalty);
        }

        return $penalty;
    }

    /**
     * N2: 2×2 blocks of the same colour.
     */
    private function ruleN2(): int
    {
        $penalty = 0;
        $size = $this->matrix->size;

        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $a = $this->matrix->get($x, $y);
                $b = $this->matrix->get($x + 1, $y);
                $c = $this->matrix->get($x, $y + 1);
                $d = $this->matrix->get($x + 1, $y + 1);

                if ($a === $b && $a === $c && $a === $d) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }

    /**
     * N3: finder-like patterns (1:1:3:1:1 with light on either side).
     */
    private function ruleN3(): int
    {
        $penalty = 0;
        $size = $this->matrix->size;

        $pattern = [true, false, true, true, true, false, true, false, false, false, false];
        $reverse = array_reverse($pattern);

        foreach ($this->matrix->toArray() as $row) {
            $penalty += $this->countPattern($row, $pattern) * 40;
            $penalty += $this->countPattern($row, $reverse) * 40;
        }

        for ($x = 0; $x < $size; $x++) {
            $column = [];

            for ($y = 0; $y < $size; $y++) {
                $column[] = $this->matrix->get($x, $y);
            }

            $penalty += $this->countPattern($column, $pattern) * 40;
            $penalty += $this->countPattern($column, $reverse) * 40;
        }

        return $penalty;
    }

    /**
     * N4: proportion of dark modules deviating from 50%.
     */
    private function ruleN4(): int
    {
        $size = $this->matrix->size;
        $dark = 0;
        $total = 0;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($this->matrix->get($x, $y) === true) {
                    $dark++;
                }
                $total++;
            }
        }

        $percent = intdiv($dark * 100, $total);
        $deviation = abs($percent - 50);

        return intdiv($deviation + 4, 5) * 10;
    }

    /**
     * @param  list<bool|null>  $line
     */
    private function scoreRuns(array $line, int &$penalty): void
    {
        $runLength = 0;
        $runValue = null;

        foreach ($line as $module) {
            if ($module === $runValue) {
                $runLength++;
            } else {
                if ($runLength >= 5) {
                    $penalty += 3 + ($runLength - 5);
                }
                $runValue = $module;
                $runLength = 1;
            }
        }

        if ($runLength >= 5) {
            $penalty += 3 + ($runLength - 5);
        }
    }

    /**
     * @param  list<bool|null>  $line
     * @param  list<bool>  $pattern
     */
    private function countPattern(array $line, array $pattern): int
    {
        $count = 0;
        $length = count($line);
        $patternLength = count($pattern);

        for ($i = 0; $i <= $length - $patternLength; $i++) {
            $match = true;

            for ($j = 0; $j < $patternLength; $j++) {
                if ($line[$i + $j] !== $pattern[$j]) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $count++;
            }
        }

        return $count;
    }
}
