<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\VersionInformation;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QR Code Versions 21 through 40 (ISO/IEC 18004).
 */
final class VersionsV21To40Test extends TestCase
{
    // ----------------------------------------------------------------
    // Table: correct matrix sizes
    // ----------------------------------------------------------------

    public function test_version_sizes_from_21_to_40(): void
    {
        for ($v = 21; $v <= 40; $v++) {
            $spec         = VersionTable::get($v);
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
     * ISO/IEC 18004 Table E.1 alignment pattern row/column coordinates for v21–40.
     *
     * @return array<string, array{int, list<int>}>
     */
    public static function alignmentCentersProvider(): array
    {
        return [
            'v21' => [21, [6, 28, 50, 72, 94]],
            'v22' => [22, [6, 26, 50, 74, 98]],
            'v23' => [23, [6, 30, 54, 78, 102]],
            'v24' => [24, [6, 28, 54, 80, 106]],
            'v25' => [25, [6, 32, 58, 84, 110]],
            'v26' => [26, [6, 30, 58, 86, 114]],
            'v27' => [27, [6, 34, 62, 90, 118]],
            'v28' => [28, [6, 26, 50, 74, 98, 122]],
            'v29' => [29, [6, 30, 54, 78, 102, 126]],
            'v30' => [30, [6, 26, 52, 78, 104, 130]],
            'v31' => [31, [6, 30, 56, 82, 108, 134]],
            'v32' => [32, [6, 34, 60, 86, 112, 138]],
            'v33' => [33, [6, 30, 58, 86, 114, 142]],
            'v34' => [34, [6, 34, 62, 90, 118, 146]],
            'v35' => [35, [6, 30, 54, 78, 102, 126, 150]],
            'v36' => [36, [6, 24, 50, 76, 102, 128, 154]],
            'v37' => [37, [6, 28, 54, 80, 106, 132, 158]],
            'v38' => [38, [6, 32, 58, 84, 110, 136, 162]],
            'v39' => [39, [6, 26, 54, 82, 110, 138, 166]],
            'v40' => [40, [6, 30, 58, 86, 114, 142, 170]],
        ];
    }

    // ----------------------------------------------------------------
    // Table: version information bits (BCH-computed from engine)
    // ----------------------------------------------------------------

    /**
     * @dataProvider versionBitsProvider
     */
    public function test_version_information_bits(int $version, int $expectedBits): void
    {
        $this->assertSame($expectedBits, VersionInformation::bits($version));
    }

    /**
     * BCH(18,6) version information codes computed from the same generator polynomial
     * as the engine (G(x) = x^12 + x^11 + x^10 + x^9 + x^8 + x^5 + x^2 + 1).
     *
     * @return array<string, array{int, int}>
     */
    public static function versionBitsProvider(): array
    {
        return [
            'v21' => [21, 0x15683],
            'v22' => [22, 0x168C9],
            'v23' => [23, 0x177EC],
            'v24' => [24, 0x18EC4],
            'v25' => [25, 0x191E1],
            'v26' => [26, 0x1AFAB],
            'v27' => [27, 0x1B08E],
            'v28' => [28, 0x1CC1A],
            'v29' => [29, 0x1D33F],
            'v30' => [30, 0x1ED75],
            'v31' => [31, 0x1F250],
            'v32' => [32, 0x209D5],
            'v33' => [33, 0x216F0],
            'v34' => [34, 0x228BA],
            'v35' => [35, 0x2379F],
            'v36' => [36, 0x24B0B],
            'v37' => [37, 0x2542E],
            'v38' => [38, 0x26A64],
            'v39' => [39, 0x27541],
            'v40' => [40, 0x28C69],
        ];
    }

    // ----------------------------------------------------------------
    // Version info bits: upper 6 bits encode the version number
    // ----------------------------------------------------------------

    public function test_version_information_bits_encode_correct_version_number(): void
    {
        foreach (range(21, 40) as $v) {
            $bits = VersionInformation::bits($v);
            $this->assertSame($v, $bits >> 12, "Version number in bits() for v{$v}");
        }
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
     * ISO/IEC 18004 Table 9 — data codewords for versions 21–40.
     *
     * @return array<string, array{int, string, int}>
     */
    public static function dataCodewordsProvider(): array
    {
        return [
            // v21
            'v21-L' => [21, 'L', 932],
            'v21-M' => [21, 'M', 714],
            'v21-Q' => [21, 'Q', 512],
            'v21-H' => [21, 'H', 406],
            // v22
            'v22-L' => [22, 'L', 1006],
            'v22-M' => [22, 'M', 782],
            'v22-Q' => [22, 'Q', 568],
            'v22-H' => [22, 'H', 442],
            // v23
            'v23-L' => [23, 'L', 1094],
            'v23-M' => [23, 'M', 860],
            'v23-Q' => [23, 'Q', 614],
            'v23-H' => [23, 'H', 464],
            // v24
            'v24-L' => [24, 'L', 1174],
            'v24-M' => [24, 'M', 914],
            'v24-Q' => [24, 'Q', 664],
            'v24-H' => [24, 'H', 514],
            // v25
            'v25-L' => [25, 'L', 1276],
            'v25-M' => [25, 'M', 1000],
            'v25-Q' => [25, 'Q', 718],
            'v25-H' => [25, 'H', 538],
            // v26
            'v26-L' => [26, 'L', 1370],
            'v26-M' => [26, 'M', 1062],
            'v26-Q' => [26, 'Q', 754],
            'v26-H' => [26, 'H', 596],
            // v27
            'v27-L' => [27, 'L', 1468],
            'v27-M' => [27, 'M', 1128],
            'v27-Q' => [27, 'Q', 808],
            'v27-H' => [27, 'H', 628],
            // v28
            'v28-L' => [28, 'L', 1531],
            'v28-M' => [28, 'M', 1193],
            'v28-Q' => [28, 'Q', 871],
            'v28-H' => [28, 'H', 661],
            // v29
            'v29-L' => [29, 'L', 1631],
            'v29-M' => [29, 'M', 1267],
            'v29-Q' => [29, 'Q', 911],
            'v29-H' => [29, 'H', 701],
            // v30
            'v30-L' => [30, 'L', 1735],
            'v30-M' => [30, 'M', 1373],
            'v30-Q' => [30, 'Q', 985],
            'v30-H' => [30, 'H', 745],
            // v31
            'v31-L' => [31, 'L', 1843],
            'v31-M' => [31, 'M', 1455],
            'v31-Q' => [31, 'Q', 1033],
            'v31-H' => [31, 'H', 793],
            // v32
            'v32-L' => [32, 'L', 1955],
            'v32-M' => [32, 'M', 1541],
            'v32-Q' => [32, 'Q', 1115],
            'v32-H' => [32, 'H', 845],
            // v33
            'v33-L' => [33, 'L', 2071],
            'v33-M' => [33, 'M', 1631],
            'v33-Q' => [33, 'Q', 1171],
            'v33-H' => [33, 'H', 901],
            // v34
            'v34-L' => [34, 'L', 2191],
            'v34-M' => [34, 'M', 1725],
            'v34-Q' => [34, 'Q', 1231],
            'v34-H' => [34, 'H', 961],
            // v35
            'v35-L' => [35, 'L', 2306],
            'v35-M' => [35, 'M', 1812],
            'v35-Q' => [35, 'Q', 1286],
            'v35-H' => [35, 'H', 986],
            // v36
            'v36-L' => [36, 'L', 2434],
            'v36-M' => [36, 'M', 1914],
            'v36-Q' => [36, 'Q', 1354],
            'v36-H' => [36, 'H', 1054],
            // v37
            'v37-L' => [37, 'L', 2566],
            'v37-M' => [37, 'M', 1992],
            'v37-Q' => [37, 'Q', 1426],
            'v37-H' => [37, 'H', 1096],
            // v38
            'v38-L' => [38, 'L', 2702],
            'v38-M' => [38, 'M', 2102],
            'v38-Q' => [38, 'Q', 1502],
            'v38-H' => [38, 'H', 1142],
            // v39
            'v39-L' => [39, 'L', 2812],
            'v39-M' => [39, 'M', 2216],
            'v39-Q' => [39, 'Q', 1582],
            'v39-H' => [39, 'H', 1222],
            // v40
            'v40-L' => [40, 'L', 2956],
            'v40-M' => [40, 'M', 2334],
            'v40-Q' => [40, 'Q', 1666],
            'v40-H' => [40, 'H', 1276],
        ];
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

    /**
     * @return array<string, array{int, string}>
     */
    public static function versionLevelProvider(): array
    {
        $cases = [];
        foreach (range(21, 40) as $v) {
            foreach (['L', 'M', 'Q', 'H'] as $level) {
                $cases["v{$v}-{$level}"] = [$v, $level];
            }
        }

        return $cases;
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

    // ----------------------------------------------------------------
    // Short payload stays in low version
    // ----------------------------------------------------------------

    public function test_short_payload_stays_in_low_version(): void
    {
        $builder = new MatrixBuilder;
        $matrix  = $builder->build('Hello', 'M');

        $this->assertLessThan(21, $builder->version()->number);
        $this->assertSame(17 + 4 * $builder->version()->number, $matrix->size);
    }

    // ----------------------------------------------------------------
    // Escalation: V20→V21 boundary
    // ----------------------------------------------------------------

    public function test_payload_one_byte_over_v20_escalates_to_v21_or_higher(): void
    {
        $v20Capacity = VersionTable::get(20)->byteCapacity(ErrorCorrectionLevel::fromName('L'));
        $payload     = str_repeat('x', $v20Capacity + 1);

        $builder = new MatrixBuilder;
        $builder->build($payload, 'L');

        $this->assertGreaterThanOrEqual(21, $builder->version()->number);
    }

    public function test_auto_escalation_from_v20_to_v21_at_level_m(): void
    {
        $v20Capacity = VersionTable::get(20)->byteCapacity(ErrorCorrectionLevel::fromName('M'));
        $payload     = str_repeat('x', $v20Capacity + 1);

        $builder = new MatrixBuilder;
        $builder->build($payload, 'M');

        $this->assertGreaterThanOrEqual(21, $builder->version()->number);
    }

    // ----------------------------------------------------------------
    // Escalation: V39→V40 boundary
    // ----------------------------------------------------------------

    public function test_auto_escalation_from_v39_to_v40_at_level_h(): void
    {
        $v39Capacity = VersionTable::get(39)->byteCapacity(ErrorCorrectionLevel::fromName('H'));
        $payload     = str_repeat('x', $v39Capacity + 1);

        $builder = new MatrixBuilder;
        $builder->build($payload, 'H');

        $this->assertSame(40, $builder->version()->number);
    }

    // ----------------------------------------------------------------
    // Max capacity: V40 at each level
    // ----------------------------------------------------------------

    /**
     * @dataProvider v40CapacityProvider
     */
    public function test_payload_at_v40_max_capacity(string $level, int $expectedDataCodewords): void
    {
        $ecc      = ErrorCorrectionLevel::fromName($level);
        $spec     = VersionTable::get(40);
        $capacity = $spec->byteCapacity($ecc);

        $this->assertGreaterThan(0, $capacity);

        $builder = new MatrixBuilder;
        $matrix  = $builder->build(str_repeat('z', $capacity), $level);

        $this->assertSame(40, $builder->version()->number);
        $this->assertSame(177, $matrix->size); // 17 + 4*40 = 177
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function v40CapacityProvider(): array
    {
        return [
            'L' => ['L', 2956],
            'M' => ['M', 2334],
            'Q' => ['Q', 1666],
            'H' => ['H', 1276],
        ];
    }

    // ----------------------------------------------------------------
    // Overflow: one byte over V40-L max capacity throws
    // ----------------------------------------------------------------

    public function test_payload_one_byte_over_v40_capacity_throws(): void
    {
        $spec     = VersionTable::get(40);
        $ecc      = ErrorCorrectionLevel::fromName('L');
        $capacity = $spec->byteCapacity($ecc);

        $this->expectException(QrCodeOverflowException::class);

        (new MatrixBuilder)->build(str_repeat('x', $capacity + 1), 'L');
    }

    public function test_payload_over_v40_h_capacity_throws(): void
    {
        $spec     = VersionTable::get(40);
        $ecc      = ErrorCorrectionLevel::fromName('H');
        $capacity = $spec->byteCapacity($ecc);

        $this->expectException(QrCodeOverflowException::class);

        (new MatrixBuilder)->build(str_repeat('x', $capacity + 1), 'H');
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

    public function test_get_version_minus_one_throws_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VersionTable::get(-1);
    }

    // ----------------------------------------------------------------
    // Medium payloads escalate correctly into V21–40 range
    // ----------------------------------------------------------------

    public function test_medium_payload_uses_v21_to_v40(): void
    {
        // 900 bytes fits in V21-L (byteCapacity ≈ 925)
        $builder = new MatrixBuilder;
        $builder->build(str_repeat('a', 900), 'L');

        $version = $builder->version()->number;
        $this->assertGreaterThanOrEqual(21, $version);
        $this->assertLessThanOrEqual(40, $version);
    }

    public function test_large_payload_escalates_to_high_version(): void
    {
        // 2500 bytes: well into V38–V40 range at level L
        $builder = new MatrixBuilder;
        $matrix  = $builder->build(str_repeat('b', 2500), 'L');

        $version = $builder->version()->number;
        $this->assertGreaterThanOrEqual(37, $version);
        $this->assertLessThanOrEqual(40, $version);
        $this->assertSame(17 + 4 * $version, $matrix->size);
    }

    // ----------------------------------------------------------------
    // V40 specific: matrix is 177×177
    // ----------------------------------------------------------------

    public function test_v40_matrix_is_177x177(): void
    {
        $spec = VersionTable::get(40);
        $this->assertSame(177, $spec->size);
    }

    // ----------------------------------------------------------------
    // V21 specific: matrix is 101×101
    // ----------------------------------------------------------------

    public function test_v21_matrix_is_101x101(): void
    {
        $spec = VersionTable::get(21);
        $this->assertSame(101, $spec->size); // 17 + 4*21 = 101
    }
}
