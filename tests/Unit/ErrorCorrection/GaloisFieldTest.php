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

    public function test_log_antilog_table_matches_published_reference(): void
    {
        // Spot values from Thonky's "QR Code Log Antilog Table for Galois
        // Field 256": a^9 = 58, a^16 = 0b1001100 (76), log(83) = 206.
        $this->assertSame(58, $this->field->exponent(9));
        $this->assertSame(116, $this->field->exponent(10));
        $this->assertSame(0b1001100, $this->field->exponent(16));
        $this->assertSame(152, $this->field->exponent(17));
        $this->assertSame(206, $this->field->log(83));
        $this->assertSame(73, $this->field->log(202));
    }

    public function test_tables_form_a_bijection(): void
    {
        $seen = [];

        for ($i = 0; $i < 255; $i++) {
            $seen[$this->field->exponent($i)] = true;
        }

        // All 255 nonzero field elements appear exactly once.
        $this->assertCount(255, $seen);
        $this->assertArrayNotHasKey(0, $seen);
    }

    public function test_every_nonzero_element_has_a_multiplicative_inverse(): void
    {
        for ($a = 1; $a < 256; $a += 7) {
            $inverse = $this->field->exponent((255 - $this->field->log($a)) % 255);

            $this->assertSame(1, $this->field->multiply($a, $inverse), "element {$a} must have a unit inverse");
        }

        $this->assertSame(1, $this->field->multiply(1, 1));
    }

    public function test_multiply_matches_repeated_doubling(): void
    {
        // Multiplying by 2 (a^1) must advance the exponent by one.
        for ($i = 0; $i < 254; $i++) {
            $this->assertSame(
                $this->field->exponent($i + 1),
                $this->field->multiply($this->field->exponent($i), 2)
            );
        }
    }

    public function test_evaluate_polynomial_matches_manual_sum(): void
    {
        // p(x) = 2x + 3 at x = 5 must equal (2*5) ^ 3 in the field.
        $expected = $this->field->multiply(2, 5) ^ 3;

        $this->assertSame([$expected], $this->field->evaluatePolynomial([2, 3], [5]));
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
