<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

/**
 * Base class shared by the concrete data encoders.
 *
 * Holds the mode/count framing logic that is identical for every mode;
 * subclasses only provide the mode identifier, count-bit table and the
 * payload-to-bits conversion.
 */
abstract class AbstractEncoder implements Encoder
{
    /**
     * Character-count indicator bit lengths per version range,
     * indexed from version group 1 (versions 1-9), 2 (10-26), 3 (27-40).
     *
     * @var list<int>
     */
    protected const COUNT_BITS = [8, 16, 16];

    public function countBits(int $version): int
    {
        $group = match (true) {
            $version >= 27 => 2,
            $version >= 10 => 1,
            default => 0,
        };

        return self::COUNT_BITS[$group];
    }

    public function makeSegment(string $data, int $version): Segment
    {
        $buffer = new BitBuffer;
        $this->encodeData($data, $buffer);

        return new Segment(
            mode: $this->mode(),
            count: $this->characterCount($data),
            countBits: $this->countBits($version),
            data: array_values($buffer->toBytes()),
        );
    }

    /**
     * Number of characters the encoded payload counts towards the
     * character-count indicator.
     */
    abstract protected function characterCount(string $data): int;
}
