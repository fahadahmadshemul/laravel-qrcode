<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\Matrix\AlignmentPattern;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\VersionInformation;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\TestCase;

final class VersionsTest extends TestCase
{
    public function test_version_sizes_from_1_to_10(): void
    {
        for ($v = 1; $v <= 10; $v++) {
            $spec = VersionTable::get($v);
            $expectedSize = 17 + 4 * $v;

            $this->assertSame($v, $spec->number);
            $this->assertSame($expectedSize, $spec->size);
        }
    }

    public function test_alignment_pattern_centers_table(): void
    {
        $this->assertSame([], VersionTable::get(1)->alignmentPatternCenters);
        $this->assertSame([6, 18], VersionTable::get(2)->alignmentPatternCenters);
        $this->assertSame([6, 22], VersionTable::get(3)->alignmentPatternCenters);
        $this->assertSame([6, 26], VersionTable::get(4)->alignmentPatternCenters);
        $this->assertSame([6, 30], VersionTable::get(5)->alignmentPatternCenters);
        $this->assertSame([6, 34], VersionTable::get(6)->alignmentPatternCenters);
        $this->assertSame([6, 22, 38], VersionTable::get(7)->alignmentPatternCenters);
        $this->assertSame([6, 24, 42], VersionTable::get(8)->alignmentPatternCenters);
        $this->assertSame([6, 26, 46], VersionTable::get(9)->alignmentPatternCenters);
        $this->assertSame([6, 28, 50], VersionTable::get(10)->alignmentPatternCenters);
    }

    public function test_version_information_bits_for_versions_7_to_10(): void
    {
        // Published ISO/IEC 18004 values for versions 7–10
        $this->assertSame(0x07C94, VersionInformation::bits(7));
        $this->assertSame(0x085BC, VersionInformation::bits(8));
        $this->assertSame(0x09A99, VersionInformation::bits(9));
        $this->assertSame(0x0A4D3, VersionInformation::bits(10));
    }

    public function test_every_version_and_ecc_level_builds_valid_matrix(): void
    {
        $levels = ['L', 'M', 'Q', 'H'];

        for ($v = 1; $v <= 10; $v++) {
            foreach ($levels as $level) {
                $ecc = ErrorCorrectionLevel::fromName($level);
                $spec = VersionTable::get($v);

                // Construct a payload string of length equal to capacity of version $v
                $capacity = $spec->byteCapacity($ecc);
                $payload = str_repeat('A', max(1, $capacity));

                $builder = new MatrixBuilder();
                $matrix = $builder->build($payload, $level);

                $chosenVersion = $builder->version();
                $this->assertNotNull($chosenVersion);
                $this->assertLessThanOrEqual($v, $chosenVersion->number);

                // Matrix size matches version size formula
                $expectedSize = 17 + 4 * $chosenVersion->number;
                $this->assertSame($expectedSize, $matrix->size);

                // Mask is selected
                $this->assertInstanceOf(MaskPattern::class, $builder->selectedMask());
            }
        }
    }

    public function test_automatic_version_escalation_based_on_payload_length(): void
    {
        $builder = new MatrixBuilder();

        // 10 bytes -> Version 1 (Level M max 14)
        $matrix1 = $builder->build(str_repeat('x', 10), 'M');
        $this->assertSame(1, $builder->version()->number);
        $this->assertSame(21, $matrix1->size);

        // 20 bytes -> Version 2 (Level M max 26)
        $matrix2 = $builder->build(str_repeat('x', 20), 'M');
        $this->assertSame(2, $builder->version()->number);
        $this->assertSame(25, $matrix2->size);

        // 50 bytes -> Version 4 (Level M max 62)
        $matrix4 = $builder->build(str_repeat('x', 50), 'M');
        $this->assertSame(4, $builder->version()->number);
        $this->assertSame(33, $matrix4->size);

        // 150 bytes -> Version 8 (Level M max 152)
        $matrix8 = $builder->build(str_repeat('x', 150), 'M');
        $this->assertSame(8, $builder->version()->number);
        $this->assertSame(49, $matrix8->size);

        // 200 bytes -> Version 10 (Level M max 213)
        $matrix10 = $builder->build(str_repeat('x', 200), 'M');
        $this->assertSame(10, $builder->version()->number);
        $this->assertSame(57, $matrix10->size);
    }

    public function test_alignment_pattern_placement_on_matrix(): void
    {
        $version = VersionTable::get(2); // Centers at (6, 18)
        $matrix = new QrMatrix($version->size);

        (new AlignmentPattern($matrix, $version))->place();

        // Center at (18, 18) is dark and marked function
        $this->assertTrue($matrix->get(18, 18));
        $this->assertTrue($matrix->isFunctionModule(18, 18));

        // Center at (6, 18) overlaps top-right finder area so it is skipped if finders placed first
        $matrixWithFinders = new QrMatrix($version->size);
        (new \Fahad\QrCode\Matrix\FinderPattern($matrixWithFinders))->place();
        (new AlignmentPattern($matrixWithFinders, $version))->place();

        // Only non-overlapping alignment pattern at (18, 18) placed
        $this->assertTrue($matrixWithFinders->isFunctionModule(18, 18));
    }

    public function test_version_information_placement_on_version_7_matrix(): void
    {
        $version = VersionTable::get(7);
        $matrix = new QrMatrix($version->size);

        (new VersionInformation($matrix, $version))->place();

        // Bottom-left copy at x=0..2, y=34..39 marked as function modules
        for ($i = 0; $i < 18; $i++) {
            $x = intdiv($i, 3);
            $y = $matrix->size - 11 + ($i % 3);
            $this->assertTrue($matrix->isFunctionModule($x, $y));
        }

        // Top-right copy at x=34..39, y=0..2 marked as function modules
        for ($i = 0; $i < 18; $i++) {
            $x = $matrix->size - 11 + ($i % 3);
            $y = intdiv($i, 3);
            $this->assertTrue($matrix->isFunctionModule($x, $y));
        }
    }

    public function test_exceeding_version_40_capacity_throws_overflow_exception(): void
    {
        $this->expectException(QrCodeOverflowException::class);

        // 3000 bytes exceeds Version 40 max byte capacity (2956 bytes at Level L)
        (new MatrixBuilder())->build(str_repeat('a', 3000), 'L');
    }
}
