<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\Matrix\FinderPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

final class FinderPatternTest extends TestCase
{
    public function test_centres_for_version_1(): void
    {
        $this->assertSame(
            [
                ['x' => 3, 'y' => 3],
                ['x' => 17, 'y' => 3],
                ['x' => 3, 'y' => 17],
            ],
            FinderPattern::centres(21)
        );
    }

    public function test_patterns_are_placed_and_marked_function(): void
    {
        $matrix = new QrMatrix(21);
        (new FinderPattern($matrix))->place();

        // Corners of each 7x7 pattern are dark.
        $this->assertTrue($matrix->get(0, 0));
        $this->assertTrue($matrix->get(20, 0));
        $this->assertTrue($matrix->get(0, 20));

        // Inner ring light, centre dark (3x3 core of top-left finder).
        $this->assertFalse($matrix->get(1, 1));
        $this->assertTrue($matrix->get(3, 3));

        // Separators are light: top-left separator ring outside the pattern.
        $this->assertFalse($matrix->get(7, 0));
        $this->assertFalse($matrix->get(0, 7));

        // Everything placed is marked as function module.
        $this->assertTrue($matrix->isFunctionModule(0, 0));
        $this->assertTrue($matrix->isFunctionModule(7, 0));
        $this->assertFalse($matrix->isFunctionModule(10, 10));
    }
}
