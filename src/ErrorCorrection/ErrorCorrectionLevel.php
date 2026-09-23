<?php

declare(strict_types=1);

namespace Fahad\QrCode\ErrorCorrection;

use Fahad\QrCode\Exceptions\InvalidErrorCorrectionLevelException;

/**
 * QR error correction levels with the Version 1 codeword tables
 * (ISO/IEC 18004, single block per level).
 */
enum ErrorCorrectionLevel: string
{
    case L = 'L';
    case M = 'M';
    case Q = 'Q';
    case H = 'H';

    /**
     * The two-bit format-information encoding of this level.
     */
    public function formatBits(): int
    {
        return match ($this) {
            self::L => 0b01,
            self::M => 0b00,
            self::Q => 0b11,
            self::H => 0b10,
        };
    }

    /**
     * Number of data codewords for Version 1.
     */
    public function dataCodewords(): int
    {
        return match ($this) {
            self::L => 19,
            self::M => 16,
            self::Q => 13,
            self::H => 9,
        };
    }

    /**
     * Number of ECC codewords for Version 1 (single block).
     */
    public function eccCodewords(): int
    {
        return match ($this) {
            self::L => 7,
            self::M => 10,
            self::Q => 13,
            self::H => 17,
        };
    }

    /**
     * Number of interleaver blocks for Version 1 (always one).
     */
    public function blockCount(): int
    {
        return 1;
    }

    /**
     * Maximum payload bytes in byte mode for Version 1:
     * (data codewords * 8 - 4 mode bits - 8 count bits) / 8.
     */
    public function byteCapacity(): int
    {
        return intdiv($this->dataCodewords() * 8 - 4 - 8, 8);
    }

    public static function fromName(string $name): self
    {
        $level = self::tryFrom(strtoupper($name));

        if ($level === null) {
            throw InvalidErrorCorrectionLevelException::forLevel($name);
        }

        return $level;
    }
}
