<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

/**
 * A single encoded data segment (e.g. a byte run) plus its metadata.
 *
 * Responsibilities of the concrete encoders are limited to turning input
 * data into segments; assembling segments into a message belongs to the
 * message-planning step that lands in a later milestone.
 */
final class Segment
{
    /**
     * @param  int  $mode  QR data mode indicator (byte mode: 0b0100)
     * @param  int  $count  Number of characters covered by this segment
     * @param  int  $countBits  Bit length of the character-count indicator,
     *                          which depends on the selected QR version
     * @param  list<int>  $data  Raw data bytes
     */
    public function __construct(
        public readonly int $mode,
        public readonly int $count,
        public readonly int $countBits,
        public readonly array $data,
    ) {}
}
