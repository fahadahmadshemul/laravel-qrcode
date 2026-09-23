<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\BitBuffer;
use PHPUnit\Framework\TestCase;

final class BitBufferTest extends TestCase
{
    public function test_append_packs_bits_msb_first(): void
    {
        $buffer = (new BitBuffer)->append(0b0100, 4)->append(0b00001111, 8);

        $this->assertSame(12, $buffer->length());
        $this->assertSame([0x40, 0xF0], $buffer->toBytes());
    }

    public function test_to_bytes_pads_final_partial_byte(): void
    {
        $buffer = (new BitBuffer)->append(0b101, 3);

        $this->assertSame([0b10100000], $buffer->toBytes());
    }

    public function test_bit_access(): void
    {
        $buffer = (new BitBuffer)->append(0b101, 3);

        $this->assertSame(1, $buffer->bit(0));
        $this->assertSame(0, $buffer->bit(1));
        $this->assertSame(1, $buffer->bit(2));
    }

    public function test_bit_out_of_bounds_throws(): void
    {
        $buffer = new BitBuffer;

        $this->expectException(\OutOfBoundsException::class);
        $buffer->bit(0);
    }

    public function test_append_value_too_large_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BitBuffer)->append(16, 4);
    }

    public function test_append_invalid_bit_count_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BitBuffer)->append(1, 0);
    }

    public function test_clear_resets(): void
    {
        $buffer = (new BitBuffer)->append(1, 8)->clear();

        $this->assertSame(0, $buffer->length());
        $this->assertSame([], $buffer->toBytes());
    }

    public function test_append_buffer_concatenates(): void
    {
        $a = (new BitBuffer)->append(0b10, 2);
        $b = (new BitBuffer)->append(0b11, 2);
        $a->appendBuffer($b);

        $this->assertSame(4, $a->length());
        $this->assertSame([0b10110000], $a->toBytes());
    }
}
