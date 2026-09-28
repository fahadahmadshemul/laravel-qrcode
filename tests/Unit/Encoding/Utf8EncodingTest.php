<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\Eci;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\VersionSpec;
use Fahad\QrCode\Matrix\VersionTable;
use Fahad\QrCode\Tests\Support\MatrixCodewordReader;
use Fahad\QrCode\Tests\Support\QrStreamDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Audits UTF-8 / Unicode handling end to end: every sample payload must be
 * carried byte-for-byte and decode back to the exact original input, and
 * multibyte text must be declared as UTF-8 via an ECI header.
 */
final class Utf8EncodingTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}> [payload, isMultibyte]
     */
    public static function unicodePayloads(): array
    {
        return [
            'English (ASCII)' => ['Hello World', false],
            'Bangla' => ['হ্যালো বাংলাদেশ', true],
            'Arabic' => ['مرحبا بالعالم', true],
            'Chinese' => ['你好世界', true],
            'Japanese' => ['こんにちは世界', true],
            'Emoji' => ['Hello 👋', true],
        ];
    }

    #[DataProvider('unicodePayloads')]
    public function test_payload_round_trips_through_the_data_stream(string $payload, bool $multibyte): void
    {
        [$codewords, $version] = $this->dataCodewordsFor($payload, 'M', null);

        $decoder = new QrStreamDecoder($codewords, $version);
        $decoded = $decoder->decode();

        $this->assertSame($payload, $decoded, 'decoded payload must equal the original input');
        $this->assertSame(
            $multibyte ? Eci::UTF8 : null,
            $decoder->eciAssignment(),
            'multibyte payloads must declare UTF-8 via ECI; ASCII must not'
        );
    }

    #[DataProvider('unicodePayloads')]
    public function test_payload_round_trips_through_the_full_matrix(string $payload, bool $multibyte): void
    {
        $builder = new MatrixBuilder;
        $matrix = $builder->build($payload, 'M');

        $version = $builder->version();
        $this->assertNotNull($version);

        $spec = $version->eccSpec(ErrorCorrectionLevel::M);
        $codewords = MatrixCodewordReader::dataCodewords($matrix, $builder->selectedMask(), $spec);

        $decoded = (new QrStreamDecoder($codewords, $version))->decode();

        $this->assertSame($payload, $decoded, 'rendered matrix must decode back to the original input');
    }

    #[DataProvider('unicodePayloads')]
    public function test_multibyte_characters_are_not_corrupted(string $payload): void
    {
        // Decode at a second ECC level and assert the byte sequence is
        // reproduced exactly — same length and same bytes, nothing dropped,
        // substituted or truncated.
        [$codewords, $version] = $this->dataCodewordsFor($payload, 'Q', null);
        $decoded = (new QrStreamDecoder($codewords, $version))->decode();

        $this->assertSame(strlen($payload), strlen($decoded), 'byte length must be preserved');
        $this->assertSame($payload, $decoded, 'every byte must round-trip unchanged');
    }

    public function test_eci_can_be_disabled_and_still_round_trips_as_raw_bytes(): void
    {
        $payload = '你好世界';

        [$codewords, $version] = $this->dataCodewordsFor($payload, 'M', false);
        $decoder = new QrStreamDecoder($codewords, $version);

        $this->assertSame($payload, $decoder->decode(), 'raw byte mode must still reproduce the bytes');
        $this->assertNull($decoder->eciAssignment(), 'no ECI header must be present when disabled');
    }

    public function test_forcing_eci_emits_utf8_declaration_even_for_ascii(): void
    {
        [$codewords, $version] = $this->dataCodewordsFor('Hello World', 'M', true);
        $decoder = new QrStreamDecoder($codewords, $version);

        $this->assertSame('Hello World', $decoder->decode());
        $this->assertSame(Eci::UTF8, $decoder->eciAssignment());
    }

    public function test_eci_overhead_is_reserved_during_version_selection(): void
    {
        // A payload that fits a version without ECI must not overflow it once
        // the 12-bit ECI header is added — version selection accounts for it.
        $payload = str_repeat('あ', 50); // 150 UTF-8 bytes, multibyte -> ECI on.

        [$codewords, $version] = $this->dataCodewordsFor($payload, 'M', null);
        $requiredBits = $version->requiredDataBits($payload, EncodingMode::Byte, true);

        $this->assertLessThanOrEqual(
            $version->eccSpec(ErrorCorrectionLevel::M)->dataCodewords * 8,
            $requiredBits,
            'selected version must have room for payload plus ECI header'
        );
        $this->assertSame($payload, (new QrStreamDecoder($codewords, $version))->decode());
    }

    /**
     * Resolve mode + ECI + version the way the engine does, then return the
     * padded data codewords together with the chosen version.
     *
     * @return array{int[], VersionSpec}
     */
    private function dataCodewordsFor(string $data, string $level, ?bool $eci): array
    {
        $mode = EncodingMode::detect($data);
        $useEci = Eci::resolve($eci, $mode, $data);
        $ecc = ErrorCorrectionLevel::fromName($level);
        $version = VersionTable::forPayload($data, $ecc, $mode, $useEci);

        $codewords = (new DataEncoder)->dataCodewords(
            $data,
            $version,
            $mode,
            $version->eccSpec($ecc)->dataCodewords,
            $useEci
        );

        return [$codewords, $version];
    }
}
