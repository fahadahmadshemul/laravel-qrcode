<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\ErrorCorrection;

use Fahad\QrCode\ErrorCorrection\GaloisField;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GaloisFieldTest extends TestCase
{
    private GaloisField $field;

    protected function setUp(): void
    {
        $this->field = new GaloisField;
    }

    public function test_alpha_powers_are_correct(): void
    {
        $this->assertSame(1, $this->field->exponent(0));
        $this->assertSame(2, $this->field->exponent(1)); // α = 0b10
        $this->assertSame(4, $this->field->exponent(2));
        $this->assertSame(0x1D, $this->field->exponent(8)); // wraps into poly 0x11D
    }

    public function test_exponent_255_wraps_to_one(): void
    {
        $this->assertSame(1, $this->field->exponent(255));
    }

    public function test_log_inverts_exponent(): void
    {
        $this->assertSame(0, $this->field->log(1));
        $this->assertSame(1, $this->field->log(2));
        $this->assertSame(206, $this->field->log(0x53));
    }

    public function test_multiply_by_zero_is_zero(): void
    {
        $this->assertSame(0, $this->field->multiply(0, 123));
        $this->assertSame(0, $this->field->multiply(123, 0));
    }

    public function test_multiply_is_commutative_and_identity_holds(): void
    {
        $this->assertSame(0x8F, $this->field->multiply(0x53, 0xCA));
        $this->assertSame(0x8F, $this->field->multiply(0xCA, 0x53));
        $this->assertSame(0x53, $this->field->multiply(0x53, 1));
    }

    public function test_multiply_results_stay_in_byte_range(): void
    {
        for ($i = 1; $i < 256; $i += 7) {
            $product = $this->field->multiply($i, 0xF0);

            $this->assertGreaterThanOrEqual(0, $product);
            $this->assertLessThanOrEqual(255, $product);
        }
    }

    public function test_out_of_range_exponent_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->field->exponent(256);
    }

    public function test_log_of_zero_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->field->log(0);
    }

    public function test_evaluate_polynomial_uses_horner(): void
    {
        // p(x) = x^2 + 1 evaluated at α^0 = 1 gives 1*1 ^ 0*1 ^ 1 = 0... in GF: (1)(1)^(0)(1)^1 = 0
        $results = $this->field->evaluatePolynomial([1, 0, 1], [1]);

        $this->assertSame([0], $results);
    }
}
