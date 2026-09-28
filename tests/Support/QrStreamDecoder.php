<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\Matrix\VersionSpec;

/**
 * A minimal, spec-faithful QR *data segment* decoder used by the test suite.
 *
 * It reverses {@see DataEncoder}: given the padded data
 * codewords (before Reed-Solomon / interleaving) and the version they were
 * built for, it walks the bit stream — honouring an optional UTF-8 ECI header
 * — and reconstructs the original payload bytes. This lets tests assert that a
 * generated code round-trips back to the exact input, including multibyte
 * Unicode text.
 */
final class QrStreamDecoder
{
    private const MODE_TERMINATOR = 0b0000;

    private const MODE_NUMERIC = 0b0001;

    private const MODE_ALPHANUMERIC = 0b0010;

    private const MODE_BYTE = 0b0100;

    private const MODE_ECI = 0b0111;

    private const ALPHANUMERIC = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    /** @var int[] */
    private array $bits;

    private int $position = 0;

    private ?int $eciAssignment = null;

    /**
     * @param  int[]  $dataCodewords  Padded data codewords (no ECC / interleave).
     */
    public function __construct(array $dataCodewords, private readonly VersionSpec $version)
    {
        $this->bits = [];
        foreach ($dataCodewords as $byte) {
            for ($shift = 7; $shift >= 0; $shift--) {
                $this->bits[] = ($byte >> $shift) & 1;
            }
        }
    }

    /**
     * Decode the stream to the original payload string.
     */
    public function decode(): string
    {
        $output = '';

        while ($this->remaining() >= 4) {
            $mode = $this->read(4);

            if ($mode === self::MODE_TERMINATOR) {
                break;
            }

            match ($mode) {
                self::MODE_ECI => $this->readEciAssignment(),
                self::MODE_NUMERIC => $output .= $this->readNumeric(EncodingMode::Numeric),
                self::MODE_ALPHANUMERIC => $output .= $this->readAlphanumeric(),
                self::MODE_BYTE => $output .= $this->readBytes(),
                default => throw new \RuntimeException(sprintf('Unknown mode indicator 0b%04b.', $mode)),
            };
        }

        return $output;
    }

    /**
     * The ECI assignment number seen in the stream, or null when none was
     * present (26 = UTF-8).
     */
    public function eciAssignment(): ?int
    {
        return $this->eciAssignment;
    }

    private function readEciAssignment(): void
    {
        $first = $this->read(8);

        // ISO/IEC 18004 §7.4.2.2: 1-, 2- or 3-byte forms by leading bits.
        if (($first & 0b1000_0000) === 0) {
            $this->eciAssignment = $first & 0b0111_1111;
        } elseif (($first & 0b1100_0000) === 0b1000_0000) {
            $this->eciAssignment = (($first & 0b0011_1111) << 8) | $this->read(8);
        } else {
            $this->eciAssignment = (($first & 0b0001_1111) << 16) | $this->read(8) << 8 | $this->read(8);
        }
    }

    private function readBytes(): string
    {
        $count = $this->read($this->version->characterCountBits(EncodingMode::Byte));

        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $out .= chr($this->read(8));
        }

        return $out;
    }

    private function readNumeric(EncodingMode $mode): string
    {
        $count = $this->read($this->version->characterCountBits($mode));

        $out = '';
        $remaining = $count;
        while ($remaining >= 3) {
            $out .= str_pad((string) $this->read(10), 3, '0', STR_PAD_LEFT);
            $remaining -= 3;
        }
        if ($remaining === 2) {
            $out .= str_pad((string) $this->read(7), 2, '0', STR_PAD_LEFT);
        } elseif ($remaining === 1) {
            $out .= (string) $this->read(4);
        }

        return $out;
    }

    private function readAlphanumeric(): string
    {
        $count = $this->read($this->version->characterCountBits(EncodingMode::Alphanumeric));

        $out = '';
        $remaining = $count;
        while ($remaining >= 2) {
            $value = $this->read(11);
            $out .= self::ALPHANUMERIC[intdiv($value, 45)].self::ALPHANUMERIC[$value % 45];
            $remaining -= 2;
        }
        if ($remaining === 1) {
            $out .= self::ALPHANUMERIC[$this->read(6)];
        }

        return $out;
    }

    private function read(int $length): int
    {
        $value = 0;
        for ($i = 0; $i < $length; $i++) {
            $value = ($value << 1) | ($this->bits[$this->position++] ?? 0);
        }

        return $value;
    }

    private function remaining(): int
    {
        return count($this->bits) - $this->position;
    }
}
