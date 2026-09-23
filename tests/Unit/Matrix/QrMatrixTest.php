<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\QrMatrix;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class QrMatrixTest extends TestCase
{
    public function test_size_21_for_version_1(): void
    {
        $matrix = new QrMatrix(21);

        $this->assertSame(21, $matrix->size);
        $this->assertCount(21, $matrix->toArray());
        $this->assertCount(21, $matrix->toArray()[0]);
    }

    public function test_size_below_21_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QrMatrix(20);
    }

    public function test_set_and_get(): void
    {
        $matrix = new QrMatrix(21);

        $matrix->set(0, 0, true);
        $matrix->set(20, 20, false);

        $this->assertTrue($matrix->get(0, 0));
        $this->assertFalse($matrix->get(20, 20));
        $this->assertNull($matrix->get(10, 10));
    }

    public function test_out_of_bounds_throws(): void
    {
        $matrix = new QrMatrix(21);

        $this->expectException(OutOfBoundsException::class);
        $matrix->get(21, 0);
    }

    public function test_function_module_tracking(): void
    {
        $matrix = new QrMatrix(21);

        $this->assertFalse($matrix->isFunctionModule(10, 10));

        $matrix->markFunction(10, 10);

        $this->assertTrue($matrix->isFunctionModule(10, 10));
    }

    public function test_is_in_bounds(): void
    {
        $matrix = new QrMatrix(21);

        $this->assertTrue($matrix->isInBounds(0, 0));
        $this->assertTrue($matrix->isInBounds(20, 20));
        $this->assertFalse($matrix->isInBounds(21, 0));
        $this->assertFalse($matrix->isInBounds(-1, 5));
    }
}
