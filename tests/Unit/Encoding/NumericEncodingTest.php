<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

final class NumericEncodingTest extends TestCase
{
    public function test_character_count_bits_for_numeric_mode(): void
    {
        $v1  = VersionTable::get(1);
        $v10 = VersionTable::get(10);
        $v27 = VersionTable::get(27);

        $this->assertSame(10, $v1->characterCountBits(EncodingMode::Numeric));
        $this->assertSame(12, $v10->characterCountBits(EncodingMode::Numeric));
        $this->assertSame(14, $v27->characterCountBits(EncodingMode::Numeric));
    }

    public function test_numeric_mode_encodes_smaller_version_for_digits(): void
    {
        // 100 digits: in Byte mode requires 100 bytes = V4-L (80 bytes capacity in V4-L, needs V5-L)
        // In Numeric mode: 4 + 10 + 33*10 + 4 = 348 bits = 44 bytes -> fits easily in V2-L (34 data codewords)!
        $data = str_repeat('9', 70);

        $encoder = new DataEncoder();
        $codewords = $encoder->encode($data, 'L', null, EncodingMode::Numeric);

        $this->assertNotEmpty($codewords);

        $versionSpec = VersionTable::forPayload($data, \Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel::L, EncodingMode::Numeric);
        $this->assertLessThanOrEqual(3, $versionSpec->number);
    }

    public function test_numeric_mode_encodes_data_correctly(): void
    {
        $encoder = new DataEncoder();
        // Encodes '1234567890' in V1-L
        $codewords = $encoder->encode('1234567890', 'L', VersionTable::get(1), EncodingMode::Numeric);

        $this->assertCount(26, $codewords); // V1-L has 19 data codewords
    }
}
