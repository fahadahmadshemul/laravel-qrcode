<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Places the alternating dark/light timing patterns along row 6 and
 * column 6, between the finder patterns.
 */
final class TimingPattern
{
    public function __construct(
        private readonly QrMatrix $matrix,
    ) {}

    public function place(): void
    {
        $size = $this->matrix->size;

        for ($i = 8; $i < $size - 8; $i++) {
            $dark = $i % 2 === 0;

            $this->matrix->set($i, 6, $dark);
            $this->matrix->markFunction($i, 6);

            $this->matrix->set(6, $i, $dark);
            $this->matrix->markFunction(6, $i);
        }
    }
}
