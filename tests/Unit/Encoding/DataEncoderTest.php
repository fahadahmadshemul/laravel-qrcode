<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use PHPUnit\Framework\TestCase;

final class DataEncoderTest extends TestCase
{
    private DataEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new DataEncoder;
    }

    public function test_hello_world_level_m_reference_codewords(): void
    {
        // Hand-verified bit stream: mode 0100 + count 00001011 + "HELLO WORLD"
        // bytes + terminator + EC/11 pads, ECC verified via zero syndromes
        // at α^0..α^9 (see ReedSolomonTest).
        $codewords = $this->encoder->encode('HELLO WORLD', 'M');

        $this->assertSame(
            [
                0x40, 0xB4, 0x84, 0x54, 0xC4, 0xC4, 0xF2, 0x05, 0x74, 0xF5, 0x24,
                0xC4, 0x40, 0xEC, 0x11, 0xEC, // data (16)
                0x0C, 0x4B, 0xCF, 0x9A, 0x89, 0x4F, 0x65, 0x09, 0x97, 0xCC, // ECC (10)
            ],
            $codewords
        );
    }

    public function test_total_codeword_count_matches_level(): void
    {
        $this->assertCount(19 + 7, $this->encoder->encode('A', 'L'));
        $this->assertCount(16 + 10, $this->encoder->encode('A', 'M'));
        $this->assertCount(13 + 13, $this->encoder->encode('A', 'Q'));
        $this->assertCount(9 + 17, $this->encoder->encode('A', 'H'));
    }

    public function test_pad_codewords_alternate_ec_11(): void
    {
        // Level H, 9 data codewords, 1 byte payload ('A').
        // Bits: mode 0100 + count 00000001 + data 01000001 + terminator 0000
        // -> 0x40, 0x14, 0x10; then EC/11 pad codewords.
        $codewords = $this->encoder->encode('A', 'H');
        $dataPart = array_slice($codewords, 0, 9);

        $this->assertSame([0x40, 0x14, 0x10, 0xEC, 0x11, 0xEC, 0x11, 0xEC, 0x11], $dataPart);
    }

    public function test_payload_exceeding_capacity_throws(): void
    {
        $this->expectException(QrCodeOverflowException::class);

        // V1-M fits 14 bytes; 15 must overflow.
        $this->encoder->encode(str_repeat('A', 15), 'M');
    }

    public function test_capacity_limits_per_level(): void
    {
        // 14 bytes at L/M/Q and 7 at H encode fine.
        $this->assertCount(26, $this->encoder->encode(str_repeat('A', 14), 'L'));
        $this->assertCount(26, $this->encoder->encode(str_repeat('A', 7), 'H'));
    }

    public function test_utf8_payload_is_byte_encoded(): void
    {
        // 'é' is two UTF-8 bytes (0xC3 0xA9).
        $codewords = $this->encoder->encode('é', 'H');
        $dataPart = array_slice($codewords, 0, 9);

        // Header: mode 0100 + count 00000010 -> 0x40, 0x2C; data 0xC3 0xA9
        // -> 0x3A, 0x90 (with terminator/pad bits); then EC/11 pads.
        $this->assertSame([0x40, 0x2C, 0x3A, 0x90, 0xEC, 0x11, 0xEC, 0x11, 0xEC], $dataPart);
    }
}
