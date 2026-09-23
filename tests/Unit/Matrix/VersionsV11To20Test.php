<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\VersionInformation;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QR Code Versions 11 through 20 (ISO/IEC 18004).
 */
final class VersionsV11To20Test extends TestCase
{
    // ----------------------------------------------------------------
    // Table: correct sizes for versions 11–20
    // ----------------------------------------------------------------

    public function test_version_sizes_from_11_to_20(): void
    {
        for ($v = 11; $v <= 20; $v++) {
            $spec        = VersionTable::get($v);
            $expectedSize = 17 + 4 * $v;

            $this->assertSame($v, $spec->number, "Version number mismatch for v{$v}");
            $this->assertSame($expectedSize, $spec->size, "Matrix size mismatch for v{$v}");
        }
    }

    // ----------------------------------------------------------------
    // Table: alignment pattern centers
    // ----------------------------------------------------------------

    /**
     * @dataProvider alignmentCentersProvider
     * @param list<int> $expected
     */
    public function test_alignment_pattern_centers(int $version, array $expected): void
    {
        $this->assertSame($expected, VersionTable::get($version)->alignmentPatternCenters);
    }

    /**
     * @return array<string, array{int, list<int>}>
     */
    public static function alignmentCentersProvider(): array
    {
        return [
            'v11' => [11, [6, 30, 54]],
            'v12' => [12, [6, 32, 58]],
            'v13' => [13, [6, 34, 62]],
            'v14' => [14, [6, 26, 46, 66]],
            'v15' => [15, [6, 26, 48, 70]],
            'v16' => [16, [6, 26, 50, 74]],
            'v17' => [17, [6, 30, 54, 78]],
            'v18' => [18, [6, 30, 56, 82]],
            'v19' => [19, [6, 30, 58, 86]],
            'v20' => [20, [6, 34, 62, 90]],
        ];
    }

    // ----------------------------------------------------------------
    // Table: version information bits (ISO/IEC 18004 published values)
    // ----------------------------------------------------------------

    /**
     * @dataProvider versionBitsProvider
     */
    public function test_version_information_bits(int $version, int $expectedBits): void
    {
        $this->assertSame($expectedBits, VersionInformation::bits($version));
    }

    /**
     * ISO/IEC 18004 Annex D version information bit strings for versions 11–20.
     *
     * @return array<string, array{int, int}>
     */
    public static function versionBitsProvider(): array
    {
        return [
            'v11' => [11, 0x0BBF6],
            'v12' => [12, 0x0C762],
            'v13' => [13, 0x0D847],
            'v14' => [14, 0x0E60D],
            'v15' => [15, 0x0F928],
            'v16' => [16, 0x10B78],
            'v17' => [17, 0x1145D],
            'v18' => [18, 0x12A17],
            'v19' => [19, 0x13532],
            'v20' => [20, 0x149A6],
        ];
    }

    // ----------------------------------------------------------------
    // ECC capacity: dataCodewords per version and level
    // ----------------------------------------------------------------

    /**
     * @dataProvider dataCodewordsProvider
     */
    public function test_ecc_data_codewords(int $version, string $level, int $expectedDataCodewords): void
    {
        $ecc  = ErrorCorrectionLevel::fromName($level);
        $spec = VersionTable::get($version);

        $this->assertSame($expectedDataCodewords, $spec->eccSpec($ecc)->dataCodewords);
    }

    /**
     * ISO/IEC 18004 Table 9 — data codewords for versions 11–20.
     *
     * @return array<string, array{int, string, int}>
     */
    public static function dataCodewordsProvider(): array
    {
        return [
            // v11
            'v11-L' => [11, 'L', 324],
            'v11-M' => [11, 'M', 254],
            'v11-Q' => [11, 'Q', 180],
            'v11-H' => [11, 'H', 140],
            // v12
            'v12-L' => [12, 'L', 370],
            'v12-M' => [12, 'M', 290],
            'v12-Q' => [12, 'Q', 206],
            'v12-H' => [12, 'H', 158],
            // v13
            'v13-L' => [13, 'L', 428],
            'v13-M' => [13, 'M', 334],
            'v13-Q' => [13, 'Q', 244],
            'v13-H' => [13, 'H', 180],
            // v14
            'v14-L' => [14, 'L', 461],
            'v14-M' => [14, 'M', 365],
            'v14-Q' => [14, 'Q', 261],
            'v14-H' => [14, 'H', 197],
            // v15
            'v15-L' => [15, 'L', 523],
            'v15-M' => [15, 'M', 415],
            'v15-Q' => [15, 'Q', 295],
            'v15-H' => [15, 'H', 223],
            // v16
            'v16-L' => [16, 'L', 589],
            'v16-M' => [16, 'M', 453],
            'v16-Q' => [16, 'Q', 325],
            'v16-H' => [16, 'H', 253],
            // v17
            'v17-L' => [17, 'L', 647],
            'v17-M' => [17, 'M', 507],
            'v17-Q' => [17, 'Q', 367],
            'v17-H' => [17, 'H', 283],
            // v18
            'v18-L' => [18, 'L', 721],
            'v18-M' => [18, 'M', 563],
            'v18-Q' => [18, 'Q', 397],
            'v18-H' => [18, 'H', 313],
            // v19
            'v19-L' => [19, 'L', 795],
            'v19-M' => [19, 'M', 627],
            'v19-Q' => [19, 'Q', 445],
            'v19-H' => [19, 'H', 341],
            // v20
            'v20-L' => [20, 'L', 861],
            'v20-M' => [20, 'M', 669],
            'v20-Q' => [20, 'Q', 485],
            'v20-H' => [20, 'H', 385],
        ];
    }

    // ----------------------------------------------------------------
    // Matrix build: every version × every ECC level (max-capacity payload)
    // ----------------------------------------------------------------

    /**
     * @dataProvider versionLevelProvider
     */
    public function test_every_version_and_ecc_level_builds_valid_matrix(int $version, string $level): void
    {
        $ecc      = ErrorCorrectionLevel::fromName($level);
        $spec     = VersionTable::get($version);
        $capacity = $spec->byteCapacity($ecc);
        $payload  = str_repeat('A', max(1, $capacity));

        $builder = new MatrixBuilder;
        $matrix  = $builder->build($payload, $level);

        $chosenVersion = $builder->version();
        $this->assertNotNull($chosenVersion);
        $this->assertLessThanOrEqual($version, $chosenVersion->number);

        $expectedSize = 17 + 4 * $chosenVersion->number;
        $this->assertSame($expectedSize, $matrix->size);

        $this->assertInstanceOf(MaskPattern::class, $builder->selectedMask());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function versionLevelProvider(): array
    {
        $cases = [];
        foreach (range(11, 20) as $v) {
            foreach (['L', 'M', 'Q', 'H'] as $level) {
                $cases["v{$v}-{$level}"] = [$v, $level];
            }
        }

        return $cases;
    }

    // ----------------------------------------------------------------
    // Short payloads — engine escalates to the right version
    // ----------------------------------------------------------------

    public function test_short_payload_stays_at_low_version(): void
    {
        // 1 byte always fits in Version 1
        $builder = new MatrixBuilder;
        $matrix  = $builder->build('A', 'L');

        $this->assertSame(1, $builder->version()->number);
        $this->assertSame(21, $matrix->size);
    }

    public function test_medium_payload_escalates_into_v11_range(): void
    {
        // 275 bytes exceeds V10-L capacity (274) → needs at least V11
        $builder = new MatrixBuilder;
        $matrix  = $builder->build(str_repeat('x', 275), 'L');

        $version = $builder->version()->number;
        $this->assertGreaterThanOrEqual(11, $version);
        // 275 bytes fits comfortably within V11 (322 bytes at L)
        $this->assertLessThanOrEqual(11, $version);

        $expectedSize = 17 + 4 * $version;
        $this->assertSame($expectedSize, $matrix->size);
    }

    public function test_payload_at_v11_l_capacity_boundary(): void
    {
        $spec     = VersionTable::get(11);
        $ecc      = ErrorCorrectionLevel::fromName('L');
        $capacity = $spec->byteCapacity($ecc);

        // Exactly at V11 capacity → chosen version must be ≤ 11
        $builder = new MatrixBuilder;
        $builder->build(str_repeat('b', $capacity), 'L');

        $this->assertLessThanOrEqual(11, $builder->version()->number);
    }

    public function test_payload_at_v20_l_max_capacity(): void
    {
        $spec     = VersionTable::get(20);
        $ecc      = ErrorCorrectionLevel::fromName('L');
        $capacity = $spec->byteCapacity($ecc);

        $builder = new MatrixBuilder;
        $matrix  = $builder->build(str_repeat('z', $capacity), 'L');

        $this->assertSame(20, $builder->version()->number);
        $this->assertSame(97, $matrix->size); // 17 + 4*20 = 97
    }

    public function test_payload_one_byte_over_v20_capacity_escalates_to_v21(): void
    {
        $spec     = VersionTable::get(20);
        $ecc      = ErrorCorrectionLevel::fromName('L');
        $capacity = $spec->byteCapacity($ecc);

        // One byte over V20 → escalates to V21+, does NOT throw
        $builder = new MatrixBuilder;
        $builder->build(str_repeat('x', $capacity + 1), 'L');

        $this->assertGreaterThanOrEqual(21, $builder->version()->number);
    }

    // ----------------------------------------------------------------
    // Version-specific: version info bits carry the correct version number
    // ----------------------------------------------------------------

    public function test_version_information_bits_encode_correct_version_number(): void
    {
        foreach (range(11, 20) as $v) {
            $bits = VersionInformation::bits($v);
            // Upper 6 bits (bits 17..12) are the version number
            $this->assertSame($v, $bits >> 12, "Version number in bits() for v{$v}");
        }
    }

    // ----------------------------------------------------------------
    // Auto-escalation boundary: v10→v11 and v19→v20
    // ----------------------------------------------------------------

    public function test_auto_escalation_from_v10_to_v11_at_level_m(): void
    {
        $v10Capacity = VersionTable::get(10)->byteCapacity(ErrorCorrectionLevel::fromName('M'));
        $payload     = str_repeat('x', $v10Capacity + 1);

        $builder = new MatrixBuilder;
        $builder->build($payload, 'M');

        $this->assertGreaterThanOrEqual(11, $builder->version()->number);
    }

    public function test_auto_escalation_from_v19_to_v20_at_level_h(): void
    {
        $v19Capacity = VersionTable::get(19)->byteCapacity(ErrorCorrectionLevel::fromName('H'));
        $payload     = str_repeat('x', $v19Capacity + 1);

        $builder = new MatrixBuilder;
        $builder->build($payload, 'H');

        $this->assertGreaterThanOrEqual(20, $builder->version()->number);
    }

    // ----------------------------------------------------------------
    // ECC block integrity: sum of block data counts == dataCodewords
    // ----------------------------------------------------------------

    /**
     * @dataProvider versionLevelProvider
     */
    public function test_block_data_counts_sum_matches_total_data_codewords(int $version, string $level): void
    {
        $ecc     = ErrorCorrectionLevel::fromName($level);
        $spec    = VersionTable::get($version);
        $eccSpec = $spec->eccSpec($ecc);

        $this->assertSame(
            $eccSpec->dataCodewords,
            array_sum($eccSpec->blockDataCounts()),
            "Block data sum mismatch for v{$version}-{$level}"
        );
    }

    // ----------------------------------------------------------------
    // Invalid version numbers throw
    // ----------------------------------------------------------------

    public function test_get_version_41_throws_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported QR version 41/');

        VersionTable::get(41);
    }

    public function test_get_version_0_throws_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VersionTable::get(0);
    }
}
