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
        $method = new \ReflectionMethod($this->reedSolomon, 'generator');
        $method->setAccessible(true);

        $generator = $method->invoke($this->reedSolomon, 10);

        $this->assertSame(
            [0x01, 0xD8, 0xC2, 0x9F, 0x6F, 0xC7, 0x5E, 0x5F, 0x71, 0x9D, 0xC1],
            $generator
        );
    }

    public function test_ec_codewords_for_reference_block(): void
    {
        // 2-block-structure reference: data of a 16-codeword block, 10 ECC.
        $data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];

        $ecc = $this->reedSolomon->encodeBlock($data, 10);

        $this->assertCount(10, $ecc);
        // Verified independently: zero syndromes at α^0..α^9.
        $syndromes = $this->field->evaluatePolynomial(
            array_merge($data, $ecc),
            array_map(fn (int $i): int => $this->field->exponent($i), range(0, 9))
        );

        $this->assertSame(array_fill(0, 10, 0), $syndromes);
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
}
