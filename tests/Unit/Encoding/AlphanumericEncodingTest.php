<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

final class AlphanumericEncodingTest extends TestCase
{
    public function test_character_count_bits_for_alphanumeric_mode(): void
    {
        $v1  = VersionTable::get(1);
        $v10 = VersionTable::get(10);
        $v27 = VersionTable::get(27);

        $this->assertSame(9, $v1->characterCountBits(EncodingMode::Alphanumeric));
        $this->assertSame(11, $v10->characterCountBits(EncodingMode::Alphanumeric));
        $this->assertSame(13, $v27->characterCountBits(EncodingMode::Alphanumeric));
    }

    public function test_alphanumeric_mode_encodes_data_correctly(): void
    {
        $encoder = new DataEncoder();
        $codewords = $encoder->encode('HELLO WORLD', 'L', VersionTable::get(1), EncodingMode::Alphanumeric);

        $this->assertCount(26, $codewords);
    }

    public function test_alphanumeric_character_mapping_values(): void
    {
        $map = EncodingMode::ALPHANUMERIC_CHAR_MAP;

        $this->assertSame(0, $map['0']);
        $this->assertSame(9, $map['9']);
        $this->assertSame(10, $map['A']);
        $this->assertSame(35, $map['Z']);
        $this->assertSame(36, $map[' ']);
        $this->assertSame(37, $map['$']);
        $this->assertSame(38, $map['%']);
        $this->assertSame(39, $map['*']);
        $this->assertSame(40, $map['+']);
        $this->assertSame(41, $map['-']);
        $this->assertSame(42, $map['.']);
        $this->assertSame(43, $map['/']);
        $this->assertSame(44, $map[':']);
    }

    public function test_alphanumeric_mode_compactness(): void
    {
        $payload = 'ABCDEF123456';
        $vNumeric = VersionTable::forPayload($payload, ErrorCorrectionLevel::L, EncodingMode::Alphanumeric);

        $this->assertSame(1, $vNumeric->number);
    }
}
