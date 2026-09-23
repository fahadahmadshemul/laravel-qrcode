<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

/**
 * Contract for all data encoders.
 *
 * An encoder turns raw input into QR bit-stream segments and knows which
 * mode it implements. Choosing the optimal mix of encoders for a payload
 * is the job of the segment analyser (later milestone).
 */
interface Encoder
{
    /**
     * The QR data mode this encoder implements (e.g. byte mode = 0b0100).
     */
    public function mode(): int;

    /**
     * Whether this encoder can encode the given payload.
     */
    public function canEncode(string $data): bool;

    /**
     * The bit length of the character-count indicator for the given version.
     */
    public function countBits(int $version): int;

    /**
     * Append the payload's data bits to the buffer (without mode/count header).
     */
    public function encodeData(string $data, BitBuffer $buffer): void;

    /**
     * Build a complete segment (mode + count + data) for the given payload.
     */
    public function makeSegment(string $data, int $version): Segment;
}
