<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\ErrorCorrection\ReedSolomon;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\Matrix\VersionSpec;
use Fahad\QrCode\Matrix\VersionTable;

final class DataEncoder
{
    /**
     * Encode payload data into interleaved data and ECC codewords for a given version, ECC level, and encoding mode.
     *
     * @return list<int>
     */
    public function encode(
        string $data,
        string $level,
        ?VersionSpec $version = null,
        EncodingMode|string|null $mode = null
    ): array {
        $eccLevel = ErrorCorrectionLevel::fromName($level);
        $encodingMode = EncodingMode::resolve($mode ?? EncodingMode::Auto, $data);
        $encodingMode->validatePayload($data);

        $versionSpec = $version ?? VersionTable::forPayload($data, $eccLevel, $encodingMode);
        $blockSpec = $versionSpec->eccSpec($eccLevel);

        if (! $versionSpec->canFit($data, $eccLevel, $encodingMode)) {
            throw QrCodeOverflowException::payloadTooLarge(
                strlen($data),
                $versionSpec->number,
                $eccLevel->name,
                $versionSpec->byteCapacity($eccLevel)
            );
        }

        // 1. Bitstream assembly
        $bits = [];

        // Mode indicator
        $this->pushBits($bits, $encodingMode->modeIndicator(), 4);

        // Character count indicator
        $charCount = strlen($data);
        $countBits = $versionSpec->characterCountBits($encodingMode);
        $this->pushBits($bits, $charCount, $countBits);

        // Payload bits
        match ($encodingMode) {
            EncodingMode::Numeric => $this->encodeNumericPayload($bits, $data),
            EncodingMode::Alphanumeric => $this->encodeAlphanumericPayload($bits, $data),
            EncodingMode::Byte, EncodingMode::Auto => $this->encodeBytePayload($bits, $data),
        };

        // Terminator (up to 4 zero bits)
        $totalDataBits = $blockSpec->dataCodewords * 8;
        $neededTerminator = min(4, $totalDataBits - count($bits));
        if ($neededTerminator > 0) {
            $this->pushBits($bits, 0, $neededTerminator);
        }

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Convert bits to data codewords
        $dataCodewords = [];
        foreach (array_chunk($bits, 8) as $byteBits) {
            $value = 0;
            foreach ($byteBits as $bit) {
                $value = ($value << 1) | $bit;
            }
            $dataCodewords[] = $value;
        }

        // Pad with alternating 236 (0xEC) and 17 (0x11) up to total data codewords
        $padBytes = [0xEC, 0x11];
        $padIndex = 0;
        while (count($dataCodewords) < $blockSpec->dataCodewords) {
            $dataCodewords[] = $padBytes[$padIndex % 2];
            $padIndex++;
        }

        // 2. Split data codewords into blocks and compute Reed-Solomon ECC for each block
        $rs = new ReedSolomon();
        $blockDataCounts = $blockSpec->blockDataCounts();
        $blocksData = [];
        $blocksEcc = [];

        $offset = 0;
        foreach ($blockDataCounts as $count) {
            $blockData = array_slice($dataCodewords, $offset, $count);
            $offset += $count;

            $ecc = $rs->encodeBlock($blockData, $blockSpec->eccPerBlock);

            $blocksData[] = $blockData;
            $blocksEcc[] = $ecc;
        }

        // 3. Interleave data and ECC codewords across blocks
        return $rs->interleave($blocksData, $blocksEcc);
    }

    private function encodeNumericPayload(array &$bits, string $data): void
    {
        $length = strlen($data);
        for ($i = 0; $i < $length; $i += 3) {
            $chunk = substr($data, $i, 3);
            $chunkLen = strlen($chunk);
            $val = (int) $chunk;
            $bitLen = match ($chunkLen) {
                3 => 10,
                2 => 7,
                default => 4,
            };
            $this->pushBits($bits, $val, $bitLen);
        }
    }

    private function encodeAlphanumericPayload(array &$bits, string $data): void
    {
        $length = strlen($data);
        for ($i = 0; $i < $length; $i += 2) {
            $chunk = substr($data, $i, 2);
            if (strlen($chunk) === 2) {
                $v1 = EncodingMode::ALPHANUMERIC_CHAR_MAP[$chunk[0]];
                $v2 = EncodingMode::ALPHANUMERIC_CHAR_MAP[$chunk[1]];
                $val = $v1 * 45 + $v2;
                $this->pushBits($bits, $val, 11);
            } else {
                $v1 = EncodingMode::ALPHANUMERIC_CHAR_MAP[$chunk[0]];
                $this->pushBits($bits, $v1, 6);
            }
        }
    }

    private function encodeBytePayload(array &$bits, string $data): void
    {
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $this->pushBits($bits, ord($data[$i]), 8);
        }
    }

    private function pushBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }
}
