<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Applies a mask pattern to the data region and selects the best mask via
 * the ISO/IEC 18004 penalty rules N1–N4.
 *
 * Only data modules are ever masked or scored; function modules (finder,
 * timing, format) are excluded.
 *
 * Scoring reads the grid through a single plain-array snapshot (rows plus a
 * one-time column transpose) rather than per-module accessor calls: the
 * penalty rules touch every module several times over, so for large versions
 * this avoids millions of bounds-checked {@see QrMatrix::get()} calls.
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
        $rows = $this->matrix->toArray();
        $columns = $this->transpose($rows);

        return $this->ruleN1($rows, $columns)
            + $this->ruleN2($rows)
            + $this->ruleN3($rows, $columns)
            + $this->ruleN4($rows);
    }

    /**
     * N1: runs of five or more same-coloured modules in rows and columns.
     *
     * @param  list<list<bool|null>>|null  $rows
     * @param  list<list<bool|null>>|null  $columns
     */
    private function ruleN1(?array $rows = null, ?array $columns = null): int
    {
        $rows ??= $this->matrix->toArray();
        $columns ??= $this->transpose($rows);

        $penalty = 0;

        foreach ($rows as $row) {
            $this->scoreRuns($row, $penalty);
        }

        foreach ($columns as $column) {
            $this->scoreRuns($column, $penalty);
        }

        return $penalty;
    }

    /**
     * N2: 2×2 blocks of the same colour.
     *
     * @param  list<list<bool|null>>|null  $rows
     */
    private function ruleN2(?array $rows = null): int
    {
        $rows ??= $this->matrix->toArray();

        $penalty = 0;
        $size = count($rows);

        for ($y = 0; $y < $size - 1; $y++) {
            $rowA = $rows[$y];
            $rowB = $rows[$y + 1];

            for ($x = 0; $x < $size - 1; $x++) {
                $a = $rowA[$x];

                if ($a === $rowA[$x + 1] && $a === $rowB[$x] && $a === $rowB[$x + 1]) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }

    /**
     * N3: finder-like patterns (1:1:3:1:1 with light on either side).
     *
     * @param  list<list<bool|null>>|null  $rows
     * @param  list<list<bool|null>>|null  $columns
     */
    private function ruleN3(?array $rows = null, ?array $columns = null): int
    {
        $rows ??= $this->matrix->toArray();
        $columns ??= $this->transpose($rows);

        $penalty = 0;

        $pattern = [true, false, true, true, true, false, true, false, false, false, false];
        $reverse = array_reverse($pattern);

        foreach ($rows as $line) {
            $penalty += ($this->countPattern($line, $pattern) + $this->countPattern($line, $reverse)) * 40;
        }

        foreach ($columns as $line) {
            $penalty += ($this->countPattern($line, $pattern) + $this->countPattern($line, $reverse)) * 40;
        }

        return $penalty;
    }

    /**
     * N4: proportion of dark modules deviating from 50%.
     *
     * @param  list<list<bool|null>>|null  $rows
     */
    private function ruleN4(?array $rows = null): int
    {
        $rows ??= $this->matrix->toArray();

        $dark = 0;
        $total = 0;

        foreach ($rows as $row) {
            foreach ($row as $module) {
                if ($module === true) {
                    $dark++;
                }
                $total++;
            }
        }

        if ($total === 0) {
            return 0;
        }

        $percent = ($dark / $total) * 100;
        $rating = (int) (abs($percent - 50) / 5);

        return $rating * 10;
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

    /**
     * Transpose a row-major grid into a column-major one, so the rules that
     * scan columns can read plain arrays instead of per-module accessors.
     *
     * @param  list<list<bool|null>>  $rows
     * @return list<list<bool|null>>
     */
    private function transpose(array $rows): array
    {
        $columns = [];
        $size = count($rows);

        for ($x = 0; $x < $size; $x++) {
            $column = [];

            for ($y = 0; $y < $size; $y++) {
                $column[] = $rows[$y][$x];
            }

            $columns[] = $column;
        }

        return $columns;
    }
}
