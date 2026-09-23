<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Growable bit-level buffer used by the encoders.
 *
 * Segments append their bits here; the buffer guarantees bits are written
 * MSB-first, which is what the QR specification requires.
 *
 * This class is part of the framework-agnostic QR engine and must not
 * depend on Laravel.
 */
final class BitBuffer
{
    /** @var int[] */
    private array $bits = [];

    private int $length = 0;

    /**
     * Append a value using exactly $bits bits (MSB first).
     *
     * @throws InvalidArgumentException when the value does not fit
     */
    public function append(int $value, int $bits): self
    {
        if ($bits < 1 || $bits > 32) {
            throw new InvalidArgumentException(sprintf('Bit count must be between 1 and 32, %d given.', $bits));
        }

        if ($value < 0 || $value >= (1 << $bits)) {
            throw new InvalidArgumentException(sprintf('Value %d does not fit into %d bits.', $value, $bits));
        }

        for ($shift = $bits - 1; $shift >= 0; $shift--) {
            $this->bits[$this->length++] = ($value >> $shift) & 1;
        }

        return $this;
    }

    /**
     * Append another buffer's contents.
     */
    public function appendBuffer(self $other): self
    {
        for ($i = 0; $i < $other->length; $i++) {
            $this->bits[$this->length++] = $other->bits[$i];
        }

        return $this;
    }

    /**
     * The bit at the given index.
     *
     * @throws OutOfBoundsException
     */
    public function bit(int $index): int
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(sprintf('Bit index %d is out of bounds.', $index));
        }

        return $this->bits[$index];
    }

    /**
     * Total number of bits currently stored.
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * The bits right-padded with zeros to fill the last byte, as bytes.
     *
     * @return int[]
     */
    public function toBytes(): array
    {
        $bytes = [];
        $accumulator = 0;
        $accumulated = 0;

        for ($i = 0; $i < $this->length; $i++) {
            $accumulator = ($accumulator << 1) | $this->bits[$i];
            if (++$accumulated === 8) {
                $bytes[] = $accumulator;
                $accumulator = 0;
                $accumulated = 0;
            }
        }

        if ($accumulated > 0) {
            $bytes[] = $accumulator << (8 - $accumulated);
        }

        return $bytes;
    }

    /**
     * Reset the buffer for reuse.
     */
    public function clear(): self
    {
        $this->bits = [];
        $this->length = 0;

        return $this;
    }
}
