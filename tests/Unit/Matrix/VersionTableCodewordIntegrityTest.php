<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Matrix;

use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Matrix\AlignmentPattern;
use Fahad\QrCode\Matrix\FinderPattern;
use Fahad\QrCode\Matrix\FormatInformation;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\QrMatrix;
use Fahad\QrCode\Matrix\TimingPattern;
use Fahad\QrCode\Matrix\VersionInformation;
use Fahad\QrCode\Matrix\VersionTable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Cross-checks the {@see VersionTable} codeword tables against two invariants
 * that are independent of the table itself, guarding against transcription
 * errors in any of the 160 version/level entries (ISO/IEC 18004 Table 9).
 *
 * This is a regression guard for two real defects found by an independent
 * audit: Version 7-H once carried block counts (4x11 + 1x12 = 56) that did not
 * sum to its 66 data codewords, and Version 8-Q once declared 122 data
 * codewords while its blocks (4x18 + 2x19) summed to 110 — both silently
 * corrupt the padded stream / block split at encode time.
 */
final class VersionTableCodewordIntegrityTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function versionLevelProvider(): iterable
    {
        foreach (range(1, 40) as $version) {
            foreach (['L', 'M', 'Q', 'H'] as $level) {
                yield "v{$version}-{$level}" => [$version, $level];
            }
        }
    }

    #[DataProvider('versionLevelProvider')]
    public function test_data_codewords_equal_the_sum_of_the_per_block_counts(int $version, string $level): void
    {
        $spec = VersionTable::get($version)->eccSpec(ErrorCorrectionLevel::fromName($level));

        $this->assertSame(
            array_sum($spec->blockDataCounts()),
            $spec->dataCodewords,
            "v{$version}-{$level}: dataCodewords must equal the sum of every block's data count."
        );
    }

    #[DataProvider('versionLevelProvider')]
    public function test_group_two_blocks_carry_exactly_one_more_data_codeword(int $version, string $level): void
    {
        $spec = VersionTable::get($version)->eccSpec(ErrorCorrectionLevel::fromName($level));

        if ($spec->g2Blocks === 0) {
            $this->assertSame(0, $spec->g2DataPerBlock, "v{$version}-{$level}: no group-2 blocks means no group-2 data.");

            return;
        }

        $this->assertSame(
            $spec->g1DataPerBlock + 1,
            $spec->g2DataPerBlock,
            "v{$version}-{$level}: group-2 blocks must hold exactly one more data codeword than group-1."
        );
    }

    /**
     * The strongest guard: total codewords (data + ECC) must equal the symbol's
     * real data-region capacity, derived here purely from module geometry by
     * laying down every function pattern and counting what is left — never from
     * the codeword table under test.
     */
    #[DataProvider('versionLevelProvider')]
    public function test_total_codewords_match_the_geometric_capacity(int $version, string $level): void
    {
        $spec = VersionTable::get($version)->eccSpec(ErrorCorrectionLevel::fromName($level));

        $totalCodewords = $spec->dataCodewords + $spec->totalBlocks() * $spec->eccPerBlock;

        $this->assertSame(
            $this->geometricCodewordCapacity($version),
            $totalCodewords,
            "v{$version}-{$level}: data + ECC codewords must fill the data region exactly."
        );
    }

    public function test_version_7_h_block_layout_is_the_iso_table_9_value(): void
    {
        // Direct pin for the corrected defect: 66 = 4x13 + 1x14, 26 ECC/block.
        $spec = VersionTable::get(7)->eccSpec(ErrorCorrectionLevel::H);

        $this->assertSame(66, $spec->dataCodewords);
        $this->assertSame(26, $spec->eccPerBlock);
        $this->assertSame([13, 13, 13, 13, 14], $spec->blockDataCounts());
    }

    public function test_version_8_q_block_layout_is_the_iso_table_9_value(): void
    {
        // Direct pin for the corrected defect: 110 = 4x18 + 2x19, 22 ECC/block.
        $spec = VersionTable::get(8)->eccSpec(ErrorCorrectionLevel::Q);

        $this->assertSame(110, $spec->dataCodewords);
        $this->assertSame(22, $spec->eccPerBlock);
        $this->assertSame([18, 18, 18, 18, 19, 19], $spec->blockDataCounts());
    }

    /**
     * Number of 8-bit codewords the data region holds for a version, computed
     * from the placed function patterns alone (independent of VersionTable's
     * codeword counts).
     */
    private function geometricCodewordCapacity(int $version): int
    {
        $spec = VersionTable::get($version);
        $matrix = new QrMatrix($spec->size);

        (new FinderPattern($matrix))->place();
        (new AlignmentPattern($matrix, $spec))->place();
        (new TimingPattern($matrix))->place();
        (new VersionInformation($matrix, $spec))->place();
        (new FormatInformation($matrix))->place(ErrorCorrectionLevel::L, MaskPattern::Pattern0);

        $dataModules = 0;
        for ($y = 0; $y < $spec->size; $y++) {
            for ($x = 0; $x < $spec->size; $x++) {
                if (! $matrix->isFunctionModule($x, $y)) {
                    $dataModules++;
                }
            }
        }

        return intdiv($dataModules, 8);
    }
}
