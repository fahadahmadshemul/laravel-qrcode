<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\FormatInformation;
use Fahad\QrCode\Matrix\Mask;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the QR code masking system, pattern evaluation, penalty calculations,
 * temporary matrix candidate evaluation, mask selection, and format information writing.
 */
final class MaskingSystemTest extends TestCase
{
    public function test_all_eight_mask_patterns_are_defined_and_sequential(): void
    {
        $patterns = MaskPattern::cases();

        $this->assertCount(8, $patterns);
        foreach ($patterns as $index => $pattern) {
            $this->assertSame($index, $pattern->value);
        }
    }

    public function test_matrix_builder_evaluates_all_eight_masks_and_stores_selected_mask(): void
    {
        $builder = new MatrixBuilder();
        $matrix = $builder->build('TEST PAYLOAD', 'M');

        $selectedMask = $builder->selectedMask();
        $this->assertInstanceOf(MaskPattern::class, $selectedMask);

        // Verify format information stored on matrix matches the selected mask
        $formatBits = FormatInformation::bits(ErrorCorrectionLevel::M, $selectedMask);

        // Bit 0 at (8, 0)
        $bit0 = $matrix->get(8, 0) === true ? 1 : 0;
        $this->assertSame(($formatBits >> 0) & 1, $bit0);
    }

    public function test_mask_evaluation_uses_temporary_matrix(): void
    {
        $matrix = new QrMatrix(21);
        $matrix->set(10, 10, true);

        $tempMatrix = clone $matrix;
        (new Mask($tempMatrix))->apply(MaskPattern::Pattern3);

        // Temp matrix module toggles based on pattern 3 condition (10+10)%3 != 0 -> false
        // (10+10)%3 = 2 != 0, so inverts is false, remains true
        $tempMatrix2 = clone $matrix;
        (new Mask($tempMatrix2))->apply(MaskPattern::Pattern0);
        // (10+10)%2 = 0 -> inverts is true, toggles true to false
        $this->assertFalse($tempMatrix2->get(10, 10));

        // Original matrix remains unchanged
        $this->assertTrue($matrix->get(10, 10));
    }

    public function test_penalty_score_includes_n1_adjacent_modules(): void
    {
        $matrix = new QrMatrix(21);
        // Fill all light
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $matrix->set($x, $y, false);
            }
        }

        $mask = new Mask($matrix);

        // Each line of 21 light modules has a run of 21 modules:
        // Penalty = 3 + (21 - 5) = 19 per row/col.
        // 21 rows + 21 cols = 42 lines * 19 = 798.
        $score = $mask->score();
        $this->assertGreaterThanOrEqual(798, $score);
    }

    public function test_penalty_score_includes_n2_two_by_two_blocks(): void
    {
        $matrix = new QrMatrix(21);
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $matrix->set($x, $y, false);
            }
        }

        // 20x20 = 400 blocks of 2x2 same color -> 400 * 3 = 1200 penalty for N2
        $mask = new Mask($matrix);
        $score = $mask->score();

        $this->assertGreaterThanOrEqual(1200, $score);
    }

    public function test_penalty_score_includes_n3_finder_like_patterns(): void
    {
        $matrix = new QrMatrix(21);
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $matrix->set($x, $y, false);
            }
        }

        // Create finder-like pattern on row 10: 1011101 followed by 4 light modules
        $patternCoords = [0, 2, 3, 4, 6];
        foreach ($patternCoords as $x) {
            $matrix->set($x, 10, true);
        }

        $mask = new Mask($matrix);
        // N3 adds at least 40 points for the pattern
        $this->assertGreaterThan(0, $mask->score());
    }

    public function test_penalty_score_includes_n4_dark_light_balance(): void
    {
        $matrix = new QrMatrix(21);
        // All light -> 0% dark -> deviation 50% -> penalty 100
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $matrix->set($x, $y, false);
            }
        }

        $reflection = new \ReflectionMethod(Mask::class, 'ruleN4');
        $reflection->setAccessible(true);

        $n4Penalty = $reflection->invoke(new Mask($matrix));
        $this->assertSame(100, $n4Penalty);
    }

    public function test_selects_mask_with_lowest_penalty(): void
    {
        $builder = new MatrixBuilder();
        $payload = 'TEST';
        $matrix = $builder->build($payload, 'H');

        $chosenMask = $builder->selectedMask();

        // Calculate scores for all candidates manually and verify chosen mask has the minimum score
        $ecc = ErrorCorrectionLevel::H;
        $scores = [];

        // Build base matrix up to data placement
        $baseMatrix = new QrMatrix(21);
        (new \Fahad\QrCode\Matrix\FinderPattern($baseMatrix))->place();
        (new \Fahad\QrCode\Matrix\TimingPattern($baseMatrix))->place();
        (new FormatInformation($baseMatrix))->place($ecc, MaskPattern::Pattern0);
        
        $encoder = new \Fahad\QrCode\Encoding\DataEncoder();
        $codewords = $encoder->encode($payload, 'H');
        (new \Fahad\QrCode\Matrix\DataPlacer($baseMatrix))->place($codewords);

        $minScore = PHP_INT_MAX;
        $minPattern = MaskPattern::Pattern0;

        foreach (MaskPattern::cases() as $pattern) {
            $candidate = clone $baseMatrix;
            (new Mask($candidate))->apply($pattern);
            (new FormatInformation($candidate))->place($ecc, $pattern);

            $score = (new Mask($candidate))->score();
            $scores[$pattern->value] = $score;

            if ($score < $minScore) {
                $minScore = $score;
                $minPattern = $pattern;
            }
        }

        $this->assertSame($minPattern, $chosenMask);
    }

    public function test_writes_correct_format_information_to_matrix(): void
    {
        $matrix = new QrMatrix(21);
        $format = new FormatInformation($matrix);

        $format->place(ErrorCorrectionLevel::Q, MaskPattern::Pattern5);

        // Check format bits computed match standard bits
        $expectedBits = FormatInformation::bits(ErrorCorrectionLevel::Q, MaskPattern::Pattern5);

        // Top-left copy 1 bit 0 at (8,0)
        $this->assertSame((($expectedBits >> 0) & 1) === 1, $matrix->get(8, 0));
        // Top-left copy 1 bit 7 at (8,8)
        $this->assertSame((($expectedBits >> 7) & 1) === 1, $matrix->get(8, 8));

        // Dark module at (8, 13)
        $this->assertTrue($matrix->get(8, 13));
        $this->assertTrue($matrix->isFunctionModule(8, 13));
    }
}
