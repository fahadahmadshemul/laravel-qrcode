<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\TimingPattern;
use PHPUnit\Framework\TestCase;

final class TimingPatternTest extends TestCase
{
    public function test_timing_row_and_column_alternate_starting_dark(): void
    {
        $matrix = new QrMatrix(21);
        (new TimingPattern($matrix))->place();

        for ($i = 8; $i <= 12; $i++) {
            $expected = $i % 2 === 0;

            $this->assertSame($expected, $matrix->get($i, 6), "row timing at x=$i");
            $this->assertSame($expected, $matrix->get(6, $i), "column timing at y=$i");
        }
    }

    public function test_timing_modules_marked_function(): void
    {
        $matrix = new QrMatrix(21);
        (new TimingPattern($matrix))->place();

        $this->assertTrue($matrix->isFunctionModule(8, 6));
        $this->assertTrue($matrix->isFunctionModule(6, 8));
        $this->assertFalse($matrix->isFunctionModule(10, 10));
    }
}
