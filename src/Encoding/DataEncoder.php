<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\ErrorCorrection\ReedSolomon;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;

/**
 * The Version 1 data pipeline for byte mode.
 *
 * Builds the segment bit stream exactly per ISO/IEC 18004:
 *
 *   mode indicator (4) + character count (8) + data (8/char)
 *   + terminator (up to 4 zero bits) + pad to byte boundary
 *   + pad codewords (0xEC 0x11 alternating) + Reed–Solomon ECC
 *
 * Version 1 is a single block, so interleaving is a simple concatenation.
 *
 * This class is part of the framework-agnostic QR engine and must not
 * depend on Laravel.
 */
final class DataEncoder
{
    private const BYTE_MODE = 0b0100;

    public function __construct(
        private readonly ReedSolomon $reedSolomon = new ReedSolomon,
    ) {}

    /**
     * Build the final codeword sequence for the payload at the given
     * error correction level.
     *
     * @param  string  $data  Raw payload (UTF-8 bytes)
     * @param  string  $level  ECC level: L, M, Q or H
     * @return int[] Final codewords (data + ECC), each an unsigned byte
     */
    public function encode(string $data, string $level): array
    {
        $level = ErrorCorrectionLevel::fromName($level);
        $dataBits = $this->buildDataBits($data, $level);
        $dataCodewords = $this->toCodewords($dataBits, $level->dataCodewords());
        $eccCodewords = $this->reedSolomon->encodeBlock($dataCodewords, $level->eccCodewords());

        return $this->reedSolomon->interleave([$dataCodewords], [$eccCodewords]);
    }

    /**
     * The complete data bit stream (mode + count + data), with the payload
     * length validated against the Version 1 byte-mode capacity.
     */
    private function buildDataBits(string $data, ErrorCorrectionLevel $level): BitBuffer
    {
        $length = strlen($data);

        if ($length > $level->byteCapacity()) {
            throw QrCodeOverflowException::forVersion(1, $level->value, $length, $level->byteCapacity());
        }

        $buffer = new BitBuffer;
        $buffer->append(self::BYTE_MODE, 4);
        $buffer->append($length, 8);

        foreach (unpack('C*', $data) ?: [] as $byte) {
            $buffer->append($byte, 8);
        }

        return $buffer;
    }

    /**
     * Terminator, bit padding to the codeword boundary, then alternating
     * 0xEC / 0x11 pad codewords up to the exact data-codeword count.
     *
     * @return int[]
     */
    private function toCodewords(BitBuffer $buffer, int $totalDataCodewords): array
    {
        $capacityBits = $totalDataCodewords * 8;

        // Terminator: up to four zero bits, truncated if fewer remain.
        $terminatorBits = min(4, $capacityBits - $buffer->length());
        $buffer->append(0, $terminatorBits);

        // Pad to a byte boundary.
        while ($buffer->length() % 8 !== 0) {
            $buffer->append(0, 1);
        }

        $codewords = $buffer->toBytes();

        // Alternating pad codewords fill the remaining data codewords,
        // always starting with 0xEC regardless of position.
        $padStart = count($codewords);

        for ($i = $padStart; $i < $totalDataCodewords; $i++) {
            $codewords[] = ($i - $padStart) % 2 === 0 ? 0xEC : 0x11;
        }

        return $codewords;
    }
}
