<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\ErrorCorrection;

use Fahad\QrCode\ErrorCorrection\GaloisField;
use Fahad\QrCode\ErrorCorrection\ReedSolomon;
use PHPUnit\Framework\TestCase;

final class ReedSolomonTest extends TestCase
{
    private ReedSolomon $reedSolomon;

    private GaloisField $field;

    protected function setUp(): void
    {
        $this->field = new GaloisField;
        $this->reedSolomon = new ReedSolomon($this->field);
    }

    public function test_generator_polynomial_degree_10_matches_reference(): void
    {
        $generator = $this->reedSolomon->generatorPolynomial(10);

        $this->assertSame(
            [0x01, 0xD8, 0xC2, 0x9F, 0x6F, 0xC7, 0x5E, 0x5F, 0x71, 0x9D, 0xC1],
            $generator
        );
    }

    public function test_generator_polynomial_degree_7_matches_reference(): void
    {
        // Thonky "Generator Polynomial Tool", 7 ECC codewords.
        $this->assertSame(
            [0x01, 127, 122, 154, 164, 11, 68, 117],
            $this->reedSolomon->generatorPolynomial(7)
        );
    }

    public function test_generator_polynomial_degree_13_matches_reference(): void
    {
        // Thonky "How to Create a Generator Polynomial", 13 ECC codewords.
        $this->assertSame(
            [0x01, 137, 73, 227, 17, 177, 17, 52, 13, 46, 43, 83, 132, 120],
            $this->reedSolomon->generatorPolynomial(13)
        );
    }

    public function test_generator_polynomial_degree_17_matches_reference(): void
    {
        // Thonky "Generator Polynomial Tool", 17 ECC codewords.
        $this->assertSame(
            [0x01, 119, 66, 83, 120, 119, 22, 197, 83, 249, 41, 143, 134, 85, 53, 125, 99, 79],
            $this->reedSolomon->generatorPolynomial(17)
        );
    }

    public function test_generator_polynomial_alpha_exponents_match_reference(): void
    {
        $exponents = fn (array $generator): array => array_map(
            fn (int $coefficient): int => $this->field->log($coefficient),
            $generator
        );

        // Thonky's alpha-exponent form of each Version 1 generator polynomial.
        $this->assertSame([0, 87, 229, 146, 149, 238, 102, 21], $exponents($this->reedSolomon->generatorPolynomial(7)));
        $this->assertSame([0, 251, 67, 46, 61, 118, 70, 64, 94, 32, 45], $exponents($this->reedSolomon->generatorPolynomial(10)));
        $this->assertSame([0, 74, 152, 176, 100, 86, 100, 106, 104, 130, 218, 206, 140, 78], $exponents($this->reedSolomon->generatorPolynomial(13)));
        $this->assertSame([0, 43, 139, 206, 78, 43, 239, 123, 206, 214, 147, 24, 99, 150, 39, 243, 163, 136], $exponents($this->reedSolomon->generatorPolynomial(17)));
    }

    public function test_generator_polynomial_has_consecutive_roots(): void
    {
        foreach ([7, 10, 13, 17] as $degree) {
            $roots = $this->field->evaluatePolynomial(
                $this->reedSolomon->generatorPolynomial($degree),
                array_map(fn (int $i): int => $this->field->exponent($i), range(0, $degree - 1))
            );

            $this->assertSame(array_fill(0, $degree, 0), $roots, "degree-{$degree} generator must vanish at a^0..a^(n-1)");
        }
    }

    public function test_multiply_polynomials_is_correct(): void
    {
        // (x + 1)(x + 1) = x^2 + 1 in characteristic 2 (1 ^ 1 = 0).
        $this->assertSame([1, 0, 1], $this->reedSolomon->multiplyPolynomials([1, 1], [1, 1]));

        // (2x + 3)(x) distributes GF multiplication over the shift.
        $this->assertSame(
            [$this->field->multiply(2, 1), $this->field->multiply(3, 1), 0],
            $this->reedSolomon->multiplyPolynomials([2, 3], [1, 0])
        );
    }

    public function test_ec_codewords_for_reference_block(): void
    {
        // Thonky worked error-correction example: 16 data codewords
        // (HELLO WORLD, level 1-M) followed by 10 zero ECC slots.
        $data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];

        $ecc = $this->reedSolomon->encodeBlock($data, 10);

        $this->assertSame(
            [196, 35, 39, 119, 235, 215, 231, 226, 93, 23],
            $ecc,
            'Must reproduce Thonky\'s worked-example ECC remainder exactly'
        );

        $this->assertZeroSyndromes(array_merge($data, $ecc), 10);
    }

    public function test_ec_codewords_for_all_version_1_levels(): void
    {
        // Fixed data blocks per Version 1 level (spec codeword counts:
        // L = 19/7, M = 16/10, Q = 13/13, H = 9/17).
        $vectors = [
            'L' => [[32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17, 236, 17, 236], 7],
            'M' => [[32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17], 10],
            'Q' => [[32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236], 13],
            'H' => [[32, 91, 11, 120, 209, 114, 220, 77, 67], 17],
        ];

        foreach ($vectors as $level => [$data, $eccCount]) {
            $ecc = $this->reedSolomon->encodeBlock($data, $eccCount);

            $this->assertCount($eccCount, $ecc, "Level {$level} must emit {$eccCount} ECC codewords");
            $this->assertZeroSyndromes(array_merge($data, $ecc), $eccCount);
        }
    }

    public function test_ecc_remainder_passes_through_division_states(): void
    {
        // Thonky's intermediate division states for the 1-M worked example
        // (before step 11 and after steps 11/12), proving the division
        // proceeds through the published states before producing the ECC.
        $data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];
        $generator = $this->reedSolomon->generatorPolynomial(10);
        $work = array_merge($data, array_fill(0, 10, 0));

        for ($i = 0; $i < 12; $i++) {
            $factor = $work[$i];

            if ($factor !== 0) {
                foreach ($generator as $j => $coefficient) {
                    $work[$i + $j] ^= $this->field->multiply($coefficient, $factor);
                }
            }

            if ($i === 9) {
                $this->assertSame([51, 95, 101, 114, 198, 226, 246, 225, 87, 219, 0], array_slice($work, 10, 11));
            }

            if ($i === 10) {
                $this->assertSame([41, 231, 193, 168, 159, 146, 182, 1, 14, 215], array_slice($work, 11, 10));
            }

            if ($i === 11) {
                $this->assertSame([179, 56, 177, 206, 230, 45, 179, 86, 156, 130], array_slice($work, 12, 10));
            }
        }

        $this->assertSame([196, 35, 39, 119, 235, 215, 231, 226, 93, 23], $this->reedSolomon->encodeBlock($data, 10));
    }

    public function test_zero_ecc_count_yields_no_codewords(): void
    {
        $this->assertSame([], $this->reedSolomon->encodeBlock([32, 91], 0));
        $this->assertSame([], $this->reedSolomon->encodeBlock([32, 91], -3));
    }

    public function test_empty_data_yields_zero_ecc(): void
    {
        $this->assertSame([], $this->reedSolomon->encodeBlock([], 1));
    }

    public function test_single_block_interleave_concatenates(): void
    {
        $result = $this->reedSolomon->interleave([[1, 2]], [[9, 8]]);

        $this->assertSame([1, 2, 9, 8], $result);
    }

    public function test_multi_block_interleave_round_robins(): void
    {
        $result = $this->reedSolomon->interleave(
            [[1, 2], [3]],
            [[7, 8], [9]]
        );

        $this->assertSame([1, 3, 2, 7, 9, 8], $result);
    }

    public function test_codeword_validation_rejects_out_of_range(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->reedSolomon->encodeBlock([256], 1);
    }

    /**
     * Assert the systematic codeword is divisible by the generator: the full
     * block evaluated at the generator roots a^0..a^(n-1) must be all zero.
     *
     * @param  int[]  $codewords
     */
    private function assertZeroSyndromes(array $codewords, int $eccCount): void
    {
        $syndromes = $this->field->evaluatePolynomial(
            $codewords,
            array_map(fn (int $i): int => $this->field->exponent($i), range(0, $eccCount - 1))
        );

        $this->assertSame(array_fill(0, $eccCount, 0), $syndromes);
    }
}
