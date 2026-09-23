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
}
