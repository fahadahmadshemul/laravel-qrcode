<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * The module grid of a QR symbol.
 *
 * Module values: true = dark, false = light, null = not yet decided.
 * Function modules (finder, timing, format) are tracked separately from
 * data modules so masking only ever touches the data region.
 *
 * Placement classes (finder, timing, format, data) write into this grid;
 * the renderer only reads it. Part of the framework-agnostic QR engine.
 */
final class QrMatrix
{
    /** @var array<int, array<int, bool|null>> */
    private array $modules = [];

    /** @var array<int, array<int, bool>> */
    private array $isFunction = [];

    public function __construct(
        public readonly int $size,
    ) {
        if ($size < 21) {
            throw new InvalidArgumentException('QR matrices must be at least 21 modules (version 1).');
        }

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $this->modules[$y][$x] = null;
                $this->isFunction[$y][$x] = false;
            }
        }
    }

    public function set(int $x, int $y, bool $dark): void
    {
        $this->assertBounds($x, $y);

        $this->modules[$y][$x] = $dark;
    }

    public function get(int $x, int $y): ?bool
    {
        $this->assertBounds($x, $y);

        return $this->modules[$y][$x];
    }

    /**
     * Mark a module as a function module (excluded from data/mask regions).
     */
    public function markFunction(int $x, int $y): void
    {
        $this->assertBounds($x, $y);

        $this->isFunction[$y][$x] = true;
    }

    /**
     * Whether the module is part of a function pattern.
     */
    public function isFunctionModule(int $x, int $y): bool
    {
        $this->assertBounds($x, $y);

        return $this->isFunction[$y][$x];
    }

    public function isInBounds(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $x < $this->size && $y < $this->size;
    }

    /**
     * @return list<list<bool|null>>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (range(0, $this->size - 1) as $y) {
            $result[] = array_values($this->modules[$y]);
        }

        return $result;
    }

    private function assertBounds(int $x, int $y): void
    {
        if (! $this->isInBounds($x, $y)) {
            throw new OutOfBoundsException(sprintf('Module (%d, %d) is outside the %dx%d matrix.', $x, $y, $this->size, $this->size));
        }
    }
}
