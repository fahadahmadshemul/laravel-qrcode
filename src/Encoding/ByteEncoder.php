<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

use RuntimeException;

/**
 * Byte-mode encoder (mode 0b0100).
 *
 * Encodes ISO-8859-1 bytes; UTF-8 input is stored as-is (byte per octet),
 * which is the customary fallback when the alphanumeric mode cannot apply.
 *
 * Algorithm note: the 8-bit-per-character data-bit conversion still needs
 * to be wired to the final bit-packing rules; encoding currently reports
 * itself unavailable until the engine milestone completes.
 */
final class ByteEncoder extends AbstractEncoder
{
    public const MODE = 0b0100;

    public function mode(): int
    {
        return self::MODE;
    }

    public function canEncode(string $data): bool
    {
        // Byte mode can carry arbitrary bytes.
        return true;
    }

    public function encodeData(string $data, BitBuffer $buffer): void
    {
        foreach (unpack('C*', $data) ?: [] as $byte) {
            $buffer->append($byte, 8);
        }
    }

    protected function characterCount(string $data): int
    {
        return strlen($data);
    }

    /**
     * Number of data codewords the given payload will occupy for the given
     * version — used by the segment analyser to pick the smallest version.
     * Full capacity tables land with the version-selection milestone.
     */
    public function dataCodewords(string $data, int $version): int
    {
        throw new RuntimeException('Version/capacity tables are not implemented yet.');
    }
}
