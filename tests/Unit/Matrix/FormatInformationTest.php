<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\FormatInformation;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use PHPUnit\Framework\TestCase;

final class FormatInformationTest extends TestCase
{
    /**
     * The 32 format strings of ISO/IEC 18004 Table C.1 — the four entries
     * for mask 0 cover every ECC level.
     */
    public function test_bits_match_published_table(): void
    {
        $this->assertSame(0b111011111000100, FormatInformation::bits(ErrorCorrectionLevel::L, MaskPattern::Pattern0));
        $this->assertSame(0b101010000010010, FormatInformation::bits(ErrorCorrectionLevel::M, MaskPattern::Pattern0));
        $this->assertSame(0b011010101011111, FormatInformation::bits(ErrorCorrectionLevel::Q, MaskPattern::Pattern0));
        $this->assertSame(0b001011010001001, FormatInformation::bits(ErrorCorrectionLevel::H, MaskPattern::Pattern0));
    }

    public function test_bits_for_every_mask_and_level_are_15bit(): void
    {
        foreach (ErrorCorrectionLevel::cases() as $level) {
            foreach (MaskPattern::cases() as $mask) {
                $bits = FormatInformation::bits($level, $mask);

                $this->assertGreaterThanOrEqual(0, $bits);
                $this->assertLessThan(1 << 15, $bits);
            }
        }
    }

    public function test_place_marks_format_modules_and_dark_module(): void
    {
        $matrix = new QrMatrix(21);
        (new FormatInformation($matrix))->place(ErrorCorrectionLevel::M, MaskPattern::Pattern0);

        // All format positions plus the dark module are function modules.
        for ($i = 0; $i <= 5; $i++) {
            $this->assertTrue($matrix->isFunctionModule(8, $i));
        }

        $this->assertTrue($matrix->isFunctionModule(8, 8));
        $this->assertTrue($matrix->isFunctionModule(8, 13)); // dark module position
        $this->assertTrue($matrix->get(8, 13)); // dark module is dark
    }
}
