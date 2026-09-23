<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;

/**
 * Specification for a single QR Code Version (1–40).
 */
final class VersionSpec
{
    public readonly int $size;

    /**
     * @param  list<int>  $alignmentPatternCenters
     * @param  array<string, EccBlockSpec>  $eccSpecs
     */
    public function __construct(
        public readonly int $number,
        public readonly array $alignmentPatternCenters,
        public readonly array $eccSpecs,
    ) {
        $this->size = 17 + 4 * $number;
    }

    public function eccSpec(ErrorCorrectionLevel $level): EccBlockSpec
    {
        return $this->eccSpecs[$level->name];
    }

    /**
     * Number of character count bits for the given mode and version.
     * (ISO/IEC 18004 Section 7.4.1, Table 3).
     */
    public function characterCountBits(?EncodingMode $mode = null): int
    {
        $encodingMode = $mode ?? EncodingMode::Byte;

        if ($this->number <= 9) {
            return match ($encodingMode) {
                EncodingMode::Numeric => 10,
                EncodingMode::Alphanumeric => 9,
                EncodingMode::Byte, EncodingMode::Auto => 8,
            };
        }

        if ($this->number <= 26) {
            return match ($encodingMode) {
                EncodingMode::Numeric => 12,
                EncodingMode::Alphanumeric => 11,
                EncodingMode::Byte, EncodingMode::Auto => 16,
            };
        }

        // Versions 27–40
        return match ($encodingMode) {
            EncodingMode::Numeric => 14,
            EncodingMode::Alphanumeric => 13,
            EncodingMode::Byte, EncodingMode::Auto => 16,
        };
    }

    /**
     * Total bit length required to encode the given payload data in the specified mode.
     */
    public function requiredDataBits(string $data, ?EncodingMode $mode = null): int
    {
        $encodingMode = $mode ?? EncodingMode::detect($data);
        $charCount = strlen($data);

        $headerBits = 4 + $this->characterCountBits($encodingMode);

        $payloadBits = match ($encodingMode) {
            EncodingMode::Numeric => (int) (intdiv($charCount, 3) * 10 + match ($charCount % 3) {
                2 => 7,
                1 => 4,
                default => 0,
            }),
            EncodingMode::Alphanumeric => (int) (intdiv($charCount, 2) * 11 + ($charCount % 2 === 1 ? 6 : 0)),
            EncodingMode::Byte, EncodingMode::Auto => $charCount * 8,
        };

        return $headerBits + $payloadBits;
    }

    /**
     * Check if the payload can fit into this version for the given ECC level and encoding mode.
     */
    public function canFit(string $data, ErrorCorrectionLevel $level, ?EncodingMode $mode = null): bool
    {
        $availableDataBits = $this->eccSpec($level)->dataCodewords * 8;

        return $this->requiredDataBits($data, $mode) <= $availableDataBits;
    }

    /**
     * Byte capacity for the given ECC level in byte mode (for backward compatibility).
     */
    public function byteCapacity(ErrorCorrectionLevel $level): int
    {
        $dataCodewords = $this->eccSpec($level)->dataCodewords;
        $headerBits = 4 + $this->characterCountBits(EncodingMode::Byte);

        return intdiv($dataCodewords * 8 - $headerBits, 8);
    }
}
