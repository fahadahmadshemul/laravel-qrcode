<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Places the three finder patterns (7×7) and their separators into the
 * corners of the matrix: top-left, top-right, bottom-left.
 */
final class FinderPattern
{
    private const PATTERN = [
        '1111111',
        '1000001',
        '1011101',
        '1011101',
        '1011101',
        '1000001',
        '1111111',
    ];

    public function __construct(
        private readonly QrMatrix $matrix,
    ) {}

    /**
     * Place all finder patterns and separators.
     */
    public function place(): void
    {
        foreach (self::centres($this->matrix->size) as $centre) {
            $this->placePattern($centre['x'], $centre['y']);
            $this->placeSeparator($centre['x'], $centre['y']);
        }
    }

    /**
     * The centre coordinates of the three finder patterns.
     *
     * @return list<array{x: int, y: int}>
     */
    public static function centres(int $size): array
    {
        return [
            ['x' => 3, 'y' => 3],
            ['x' => $size - 4, 'y' => 3],
            ['x' => 3, 'y' => $size - 4],
        ];
    }

    /**
     * Stamp the 7×7 finder pattern around the given centre.
     */
    private function placePattern(int $cx, int $cy): void
    {
        foreach (self::PATTERN as $dy => $row) {
            for ($dx = 0; $dx < 7; $dx++) {
                $x = $cx - 3 + $dx;
                $y = $cy - 3 + $dy;
                $this->matrix->set($x, $y, $row[$dx] === '1');
                $this->matrix->markFunction($x, $y);
            }
        }
    }

    /**
     * Stamp the one-module light separator ring around the finder pattern.
     */
    private function placeSeparator(int $cx, int $cy): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;

                if (! $this->matrix->isInBounds($x, $y) || $this->insidePattern($x, $y, $cx, $cy)) {
                    continue;
                }

                $this->matrix->set($x, $y, false);
                $this->matrix->markFunction($x, $y);
            }
        }
    }

    /**
     * Whether the coordinate falls inside the 7×7 finder pattern proper.
     */
    private function insidePattern(int $x, int $y, int $cx, int $cy): bool
    {
        return abs($x - $cx) <= 3 && abs($y - $cy) <= 3;
    }
}
