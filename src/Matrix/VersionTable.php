<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use InvalidArgumentException;

/**
 * Reusable configuration table for QR Code Versions 1 through 40
 * (ISO/IEC 18004 specification).
 */
final class VersionTable
{
    /**
     * Maximum supported QR version.
     */
    private const MAX_VERSION = 40;

    /**
     * @var array<int, VersionSpec>|null
     */
    private static ?array $versions = null;

    public static function get(int $versionNumber): VersionSpec
    {
        if (self::$versions === null) {
            self::$versions = self::buildTable();
        }

        if (! isset(self::$versions[$versionNumber])) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported QR version %d. Supported versions are 1 to %d.',
                $versionNumber,
                self::MAX_VERSION
            ));
        }

        return self::$versions[$versionNumber];
    }

    public static function forPayload(string|int $payload, ErrorCorrectionLevel $level, ?EncodingMode $mode = null): VersionSpec
    {
        $data = is_string($payload) ? $payload : str_repeat('A', $payload);
        $encodingMode = $mode ?? (is_string($payload) ? EncodingMode::detect($data) : EncodingMode::Byte);

        for ($v = 1; $v <= self::MAX_VERSION; $v++) {
            $spec = self::get($v);
            if ($spec->canFit($data, $level, $encodingMode)) {
                return $spec;
            }
        }

        $maxCapacity = self::get(self::MAX_VERSION)->byteCapacity($level);

        throw QrCodeOverflowException::payloadTooLarge(strlen($data), self::MAX_VERSION, $level->name, $maxCapacity);
    }

    /**
     * @return array<int, VersionSpec>
     */
    private static function buildTable(): array
    {
        return [
            // ----------------------------------------------------------------
            // Versions 1–10
            // ----------------------------------------------------------------
            1 => new VersionSpec(
                number: 1,
                alignmentPatternCenters: [],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 19, eccPerBlock: 7, g1Blocks: 1, g1DataPerBlock: 19),
                    'M' => new EccBlockSpec(dataCodewords: 16, eccPerBlock: 10, g1Blocks: 1, g1DataPerBlock: 16),
                    'Q' => new EccBlockSpec(dataCodewords: 13, eccPerBlock: 13, g1Blocks: 1, g1DataPerBlock: 13),
                    'H' => new EccBlockSpec(dataCodewords: 9, eccPerBlock: 17, g1Blocks: 1, g1DataPerBlock: 9),
                ]
            ),
            2 => new VersionSpec(
                number: 2,
                alignmentPatternCenters: [6, 18],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 34, eccPerBlock: 10, g1Blocks: 1, g1DataPerBlock: 34),
                    'M' => new EccBlockSpec(dataCodewords: 28, eccPerBlock: 16, g1Blocks: 1, g1DataPerBlock: 28),
                    'Q' => new EccBlockSpec(dataCodewords: 22, eccPerBlock: 22, g1Blocks: 1, g1DataPerBlock: 22),
                    'H' => new EccBlockSpec(dataCodewords: 16, eccPerBlock: 28, g1Blocks: 1, g1DataPerBlock: 16),
                ]
            ),
            3 => new VersionSpec(
                number: 3,
                alignmentPatternCenters: [6, 22],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 55, eccPerBlock: 15, g1Blocks: 1, g1DataPerBlock: 55),
                    'M' => new EccBlockSpec(dataCodewords: 44, eccPerBlock: 26, g1Blocks: 1, g1DataPerBlock: 44),
                    'Q' => new EccBlockSpec(dataCodewords: 34, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 17),
                    'H' => new EccBlockSpec(dataCodewords: 26, eccPerBlock: 22, g1Blocks: 2, g1DataPerBlock: 13),
                ]
            ),
            4 => new VersionSpec(
                number: 4,
                alignmentPatternCenters: [6, 26],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 80, eccPerBlock: 20, g1Blocks: 1, g1DataPerBlock: 80),
                    'M' => new EccBlockSpec(dataCodewords: 64, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 32),
                    'Q' => new EccBlockSpec(dataCodewords: 48, eccPerBlock: 26, g1Blocks: 2, g1DataPerBlock: 24),
                    'H' => new EccBlockSpec(dataCodewords: 36, eccPerBlock: 16, g1Blocks: 4, g1DataPerBlock: 9),
                ]
            ),
            5 => new VersionSpec(
                number: 5,
                alignmentPatternCenters: [6, 30],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 108, eccPerBlock: 26, g1Blocks: 1, g1DataPerBlock: 108),
                    'M' => new EccBlockSpec(dataCodewords: 86, eccPerBlock: 24, g1Blocks: 2, g1DataPerBlock: 43),
                    'Q' => new EccBlockSpec(dataCodewords: 62, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 15, g2Blocks: 2, g2DataPerBlock: 16),
                    'H' => new EccBlockSpec(dataCodewords: 46, eccPerBlock: 22, g1Blocks: 2, g1DataPerBlock: 11, g2Blocks: 2, g2DataPerBlock: 12),
                ]
            ),
            6 => new VersionSpec(
                number: 6,
                alignmentPatternCenters: [6, 34],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 136, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 68),
                    'M' => new EccBlockSpec(dataCodewords: 108, eccPerBlock: 16, g1Blocks: 4, g1DataPerBlock: 27),
                    'Q' => new EccBlockSpec(dataCodewords: 76, eccPerBlock: 24, g1Blocks: 4, g1DataPerBlock: 19),
                    'H' => new EccBlockSpec(dataCodewords: 60, eccPerBlock: 28, g1Blocks: 4, g1DataPerBlock: 15),
                ]
            ),
            7 => new VersionSpec(
                number: 7,
                alignmentPatternCenters: [6, 22, 38],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 156, eccPerBlock: 20, g1Blocks: 2, g1DataPerBlock: 78),
                    'M' => new EccBlockSpec(dataCodewords: 124, eccPerBlock: 18, g1Blocks: 4, g1DataPerBlock: 31),
                    'Q' => new EccBlockSpec(dataCodewords: 88, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 14, g2Blocks: 4, g2DataPerBlock: 15),
                    'H' => new EccBlockSpec(dataCodewords: 66, eccPerBlock: 26, g1Blocks: 4, g1DataPerBlock: 11, g2Blocks: 1, g2DataPerBlock: 12),
                ]
            ),
            8 => new VersionSpec(
                number: 8,
                alignmentPatternCenters: [6, 24, 42],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 194, eccPerBlock: 24, g1Blocks: 2, g1DataPerBlock: 97),
                    'M' => new EccBlockSpec(dataCodewords: 154, eccPerBlock: 22, g1Blocks: 2, g1DataPerBlock: 38, g2Blocks: 2, g2DataPerBlock: 39),
                    'Q' => new EccBlockSpec(dataCodewords: 122, eccPerBlock: 22, g1Blocks: 4, g1DataPerBlock: 18, g2Blocks: 2, g2DataPerBlock: 19),
                    'H' => new EccBlockSpec(dataCodewords: 86, eccPerBlock: 26, g1Blocks: 4, g1DataPerBlock: 14, g2Blocks: 2, g2DataPerBlock: 15),
                ]
            ),
            9 => new VersionSpec(
                number: 9,
                alignmentPatternCenters: [6, 26, 46],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 232, eccPerBlock: 30, g1Blocks: 2, g1DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 182, eccPerBlock: 22, g1Blocks: 3, g1DataPerBlock: 36, g2Blocks: 2, g2DataPerBlock: 37),
                    'Q' => new EccBlockSpec(dataCodewords: 132, eccPerBlock: 20, g1Blocks: 4, g1DataPerBlock: 16, g2Blocks: 4, g2DataPerBlock: 17),
                    'H' => new EccBlockSpec(dataCodewords: 108, eccPerBlock: 23, g1Blocks: 4, g1DataPerBlock: 13, g2Blocks: 4, g2DataPerBlock: 14),
                ]
            ),
            10 => new VersionSpec(
                number: 10,
                alignmentPatternCenters: [6, 28, 50],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 274, eccPerBlock: 18, g1Blocks: 2, g1DataPerBlock: 68, g2Blocks: 2, g2DataPerBlock: 69),
                    'M' => new EccBlockSpec(dataCodewords: 216, eccPerBlock: 26, g1Blocks: 4, g1DataPerBlock: 43, g2Blocks: 1, g2DataPerBlock: 44),
                    'Q' => new EccBlockSpec(dataCodewords: 154, eccPerBlock: 24, g1Blocks: 6, g1DataPerBlock: 19, g2Blocks: 2, g2DataPerBlock: 20),
                    'H' => new EccBlockSpec(dataCodewords: 122, eccPerBlock: 28, g1Blocks: 6, g1DataPerBlock: 15, g2Blocks: 2, g2DataPerBlock: 16),
                ]
            ),
            // ----------------------------------------------------------------
            // Versions 11–20
            // ----------------------------------------------------------------
            11 => new VersionSpec(
                number: 11,
                alignmentPatternCenters: [6, 30, 54],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 324, eccPerBlock: 20, g1Blocks: 4, g1DataPerBlock: 81),
                    'M' => new EccBlockSpec(dataCodewords: 254, eccPerBlock: 30, g1Blocks: 1, g1DataPerBlock: 50, g2Blocks: 4, g2DataPerBlock: 51),
                    'Q' => new EccBlockSpec(dataCodewords: 180, eccPerBlock: 28, g1Blocks: 4, g1DataPerBlock: 22, g2Blocks: 4, g2DataPerBlock: 23),
                    'H' => new EccBlockSpec(dataCodewords: 140, eccPerBlock: 24, g1Blocks: 3, g1DataPerBlock: 12, g2Blocks: 8, g2DataPerBlock: 13),
                ]
            ),
            12 => new VersionSpec(
                number: 12,
                alignmentPatternCenters: [6, 32, 58],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 370, eccPerBlock: 24, g1Blocks: 2, g1DataPerBlock: 92, g2Blocks: 2, g2DataPerBlock: 93),
                    'M' => new EccBlockSpec(dataCodewords: 290, eccPerBlock: 22, g1Blocks: 6, g1DataPerBlock: 36, g2Blocks: 2, g2DataPerBlock: 37),
                    'Q' => new EccBlockSpec(dataCodewords: 206, eccPerBlock: 26, g1Blocks: 4, g1DataPerBlock: 20, g2Blocks: 6, g2DataPerBlock: 21),
                    'H' => new EccBlockSpec(dataCodewords: 158, eccPerBlock: 28, g1Blocks: 7, g1DataPerBlock: 14, g2Blocks: 4, g2DataPerBlock: 15),
                ]
            ),
            13 => new VersionSpec(
                number: 13,
                alignmentPatternCenters: [6, 34, 62],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 428, eccPerBlock: 26, g1Blocks: 4, g1DataPerBlock: 107),
                    'M' => new EccBlockSpec(dataCodewords: 334, eccPerBlock: 22, g1Blocks: 8, g1DataPerBlock: 37, g2Blocks: 1, g2DataPerBlock: 38),
                    'Q' => new EccBlockSpec(dataCodewords: 244, eccPerBlock: 24, g1Blocks: 8, g1DataPerBlock: 20, g2Blocks: 4, g2DataPerBlock: 21),
                    'H' => new EccBlockSpec(dataCodewords: 180, eccPerBlock: 22, g1Blocks: 12, g1DataPerBlock: 11, g2Blocks: 4, g2DataPerBlock: 12),
                ]
            ),
            14 => new VersionSpec(
                number: 14,
                alignmentPatternCenters: [6, 26, 46, 66],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 461, eccPerBlock: 30, g1Blocks: 3, g1DataPerBlock: 115, g2Blocks: 1, g2DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 365, eccPerBlock: 24, g1Blocks: 4, g1DataPerBlock: 40, g2Blocks: 5, g2DataPerBlock: 41),
                    'Q' => new EccBlockSpec(dataCodewords: 261, eccPerBlock: 20, g1Blocks: 11, g1DataPerBlock: 16, g2Blocks: 5, g2DataPerBlock: 17),
                    'H' => new EccBlockSpec(dataCodewords: 197, eccPerBlock: 24, g1Blocks: 11, g1DataPerBlock: 12, g2Blocks: 5, g2DataPerBlock: 13),
                ]
            ),
            15 => new VersionSpec(
                number: 15,
                alignmentPatternCenters: [6, 26, 48, 70],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 523, eccPerBlock: 22, g1Blocks: 5, g1DataPerBlock: 87, g2Blocks: 1, g2DataPerBlock: 88),
                    'M' => new EccBlockSpec(dataCodewords: 415, eccPerBlock: 24, g1Blocks: 5, g1DataPerBlock: 41, g2Blocks: 5, g2DataPerBlock: 42),
                    'Q' => new EccBlockSpec(dataCodewords: 295, eccPerBlock: 30, g1Blocks: 5, g1DataPerBlock: 24, g2Blocks: 7, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 223, eccPerBlock: 24, g1Blocks: 11, g1DataPerBlock: 12, g2Blocks: 7, g2DataPerBlock: 13),
                ]
            ),
            16 => new VersionSpec(
                number: 16,
                alignmentPatternCenters: [6, 26, 50, 74],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 589, eccPerBlock: 24, g1Blocks: 5, g1DataPerBlock: 98, g2Blocks: 1, g2DataPerBlock: 99),
                    'M' => new EccBlockSpec(dataCodewords: 453, eccPerBlock: 28, g1Blocks: 7, g1DataPerBlock: 45, g2Blocks: 3, g2DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 325, eccPerBlock: 24, g1Blocks: 15, g1DataPerBlock: 19, g2Blocks: 2, g2DataPerBlock: 20),
                    'H' => new EccBlockSpec(dataCodewords: 253, eccPerBlock: 30, g1Blocks: 3, g1DataPerBlock: 15, g2Blocks: 13, g2DataPerBlock: 16),
                ]
            ),
            17 => new VersionSpec(
                number: 17,
                alignmentPatternCenters: [6, 30, 54, 78],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 647, eccPerBlock: 28, g1Blocks: 1, g1DataPerBlock: 107, g2Blocks: 5, g2DataPerBlock: 108),
                    'M' => new EccBlockSpec(dataCodewords: 507, eccPerBlock: 28, g1Blocks: 10, g1DataPerBlock: 46, g2Blocks: 1, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 367, eccPerBlock: 28, g1Blocks: 1, g1DataPerBlock: 22, g2Blocks: 15, g2DataPerBlock: 23),
                    'H' => new EccBlockSpec(dataCodewords: 283, eccPerBlock: 28, g1Blocks: 2, g1DataPerBlock: 14, g2Blocks: 17, g2DataPerBlock: 15),
                ]
            ),
            18 => new VersionSpec(
                number: 18,
                alignmentPatternCenters: [6, 30, 56, 82],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 721, eccPerBlock: 30, g1Blocks: 5, g1DataPerBlock: 120, g2Blocks: 1, g2DataPerBlock: 121),
                    'M' => new EccBlockSpec(dataCodewords: 563, eccPerBlock: 26, g1Blocks: 9, g1DataPerBlock: 43, g2Blocks: 4, g2DataPerBlock: 44),
                    'Q' => new EccBlockSpec(dataCodewords: 397, eccPerBlock: 28, g1Blocks: 17, g1DataPerBlock: 22, g2Blocks: 1, g2DataPerBlock: 23),
                    'H' => new EccBlockSpec(dataCodewords: 313, eccPerBlock: 28, g1Blocks: 2, g1DataPerBlock: 14, g2Blocks: 19, g2DataPerBlock: 15),
                ]
            ),
            19 => new VersionSpec(
                number: 19,
                alignmentPatternCenters: [6, 30, 58, 86],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 795, eccPerBlock: 28, g1Blocks: 3, g1DataPerBlock: 113, g2Blocks: 4, g2DataPerBlock: 114),
                    'M' => new EccBlockSpec(dataCodewords: 627, eccPerBlock: 26, g1Blocks: 3, g1DataPerBlock: 44, g2Blocks: 11, g2DataPerBlock: 45),
                    'Q' => new EccBlockSpec(dataCodewords: 445, eccPerBlock: 26, g1Blocks: 17, g1DataPerBlock: 21, g2Blocks: 4, g2DataPerBlock: 22),
                    'H' => new EccBlockSpec(dataCodewords: 341, eccPerBlock: 26, g1Blocks: 9, g1DataPerBlock: 13, g2Blocks: 16, g2DataPerBlock: 14),
                ]
            ),
            20 => new VersionSpec(
                number: 20,
                alignmentPatternCenters: [6, 34, 62, 90],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 861, eccPerBlock: 28, g1Blocks: 3, g1DataPerBlock: 107, g2Blocks: 5, g2DataPerBlock: 108),
                    'M' => new EccBlockSpec(dataCodewords: 669, eccPerBlock: 26, g1Blocks: 3, g1DataPerBlock: 41, g2Blocks: 13, g2DataPerBlock: 42),
                    'Q' => new EccBlockSpec(dataCodewords: 485, eccPerBlock: 30, g1Blocks: 15, g1DataPerBlock: 24, g2Blocks: 5, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 385, eccPerBlock: 28, g1Blocks: 15, g1DataPerBlock: 15, g2Blocks: 10, g2DataPerBlock: 16),
                ]
            ),
            // ----------------------------------------------------------------
            // Versions 21–40
            // ----------------------------------------------------------------
            21 => new VersionSpec(
                number: 21,
                alignmentPatternCenters: [6, 28, 50, 72, 94],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 932, eccPerBlock: 28, g1Blocks: 4, g1DataPerBlock: 116, g2Blocks: 4, g2DataPerBlock: 117),
                    'M' => new EccBlockSpec(dataCodewords: 714, eccPerBlock: 26, g1Blocks: 17, g1DataPerBlock: 42),
                    'Q' => new EccBlockSpec(dataCodewords: 512, eccPerBlock: 28, g1Blocks: 17, g1DataPerBlock: 22, g2Blocks: 6, g2DataPerBlock: 23),
                    'H' => new EccBlockSpec(dataCodewords: 406, eccPerBlock: 30, g1Blocks: 19, g1DataPerBlock: 16, g2Blocks: 6, g2DataPerBlock: 17),
                ]
            ),
            22 => new VersionSpec(
                number: 22,
                alignmentPatternCenters: [6, 26, 50, 74, 98],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1006, eccPerBlock: 28, g1Blocks: 2, g1DataPerBlock: 111, g2Blocks: 7, g2DataPerBlock: 112),
                    'M' => new EccBlockSpec(dataCodewords: 782, eccPerBlock: 28, g1Blocks: 17, g1DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 568, eccPerBlock: 30, g1Blocks: 7, g1DataPerBlock: 24, g2Blocks: 16, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 442, eccPerBlock: 24, g1Blocks: 34, g1DataPerBlock: 13),
                ]
            ),
            23 => new VersionSpec(
                number: 23,
                alignmentPatternCenters: [6, 30, 54, 78, 102],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1094, eccPerBlock: 30, g1Blocks: 4, g1DataPerBlock: 121, g2Blocks: 5, g2DataPerBlock: 122),
                    'M' => new EccBlockSpec(dataCodewords: 860, eccPerBlock: 28, g1Blocks: 4, g1DataPerBlock: 47, g2Blocks: 14, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 614, eccPerBlock: 30, g1Blocks: 11, g1DataPerBlock: 24, g2Blocks: 14, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 464, eccPerBlock: 30, g1Blocks: 16, g1DataPerBlock: 15, g2Blocks: 14, g2DataPerBlock: 16),
                ]
            ),
            24 => new VersionSpec(
                number: 24,
                alignmentPatternCenters: [6, 28, 54, 80, 106],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1174, eccPerBlock: 30, g1Blocks: 6, g1DataPerBlock: 117, g2Blocks: 4, g2DataPerBlock: 118),
                    'M' => new EccBlockSpec(dataCodewords: 914, eccPerBlock: 28, g1Blocks: 6, g1DataPerBlock: 45, g2Blocks: 14, g2DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 664, eccPerBlock: 30, g1Blocks: 11, g1DataPerBlock: 24, g2Blocks: 16, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 514, eccPerBlock: 30, g1Blocks: 30, g1DataPerBlock: 16, g2Blocks: 2, g2DataPerBlock: 17),
                ]
            ),
            25 => new VersionSpec(
                number: 25,
                alignmentPatternCenters: [6, 32, 58, 84, 110],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1276, eccPerBlock: 26, g1Blocks: 8, g1DataPerBlock: 106, g2Blocks: 4, g2DataPerBlock: 107),
                    'M' => new EccBlockSpec(dataCodewords: 1000, eccPerBlock: 28, g1Blocks: 8, g1DataPerBlock: 47, g2Blocks: 13, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 718, eccPerBlock: 30, g1Blocks: 7, g1DataPerBlock: 24, g2Blocks: 22, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 538, eccPerBlock: 30, g1Blocks: 22, g1DataPerBlock: 15, g2Blocks: 13, g2DataPerBlock: 16),
                ]
            ),
            26 => new VersionSpec(
                number: 26,
                alignmentPatternCenters: [6, 30, 58, 86, 114],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1370, eccPerBlock: 28, g1Blocks: 10, g1DataPerBlock: 114, g2Blocks: 2, g2DataPerBlock: 115),
                    'M' => new EccBlockSpec(dataCodewords: 1062, eccPerBlock: 28, g1Blocks: 19, g1DataPerBlock: 46, g2Blocks: 4, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 754, eccPerBlock: 28, g1Blocks: 28, g1DataPerBlock: 22, g2Blocks: 6, g2DataPerBlock: 23),
                    'H' => new EccBlockSpec(dataCodewords: 596, eccPerBlock: 30, g1Blocks: 33, g1DataPerBlock: 16, g2Blocks: 4, g2DataPerBlock: 17),
                ]
            ),
            27 => new VersionSpec(
                number: 27,
                alignmentPatternCenters: [6, 34, 62, 90, 118],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1468, eccPerBlock: 30, g1Blocks: 8, g1DataPerBlock: 122, g2Blocks: 4, g2DataPerBlock: 123),
                    'M' => new EccBlockSpec(dataCodewords: 1128, eccPerBlock: 28, g1Blocks: 22, g1DataPerBlock: 45, g2Blocks: 3, g2DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 808, eccPerBlock: 30, g1Blocks: 8, g1DataPerBlock: 23, g2Blocks: 26, g2DataPerBlock: 24),
                    'H' => new EccBlockSpec(dataCodewords: 628, eccPerBlock: 30, g1Blocks: 12, g1DataPerBlock: 15, g2Blocks: 28, g2DataPerBlock: 16),
                ]
            ),
            28 => new VersionSpec(
                number: 28,
                alignmentPatternCenters: [6, 26, 50, 74, 98, 122],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1531, eccPerBlock: 30, g1Blocks: 3, g1DataPerBlock: 117, g2Blocks: 10, g2DataPerBlock: 118),
                    'M' => new EccBlockSpec(dataCodewords: 1193, eccPerBlock: 28, g1Blocks: 3, g1DataPerBlock: 45, g2Blocks: 23, g2DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 871, eccPerBlock: 30, g1Blocks: 4, g1DataPerBlock: 24, g2Blocks: 31, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 661, eccPerBlock: 30, g1Blocks: 11, g1DataPerBlock: 15, g2Blocks: 31, g2DataPerBlock: 16),
                ]
            ),
            29 => new VersionSpec(
                number: 29,
                alignmentPatternCenters: [6, 30, 54, 78, 102, 126],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1631, eccPerBlock: 30, g1Blocks: 7, g1DataPerBlock: 116, g2Blocks: 7, g2DataPerBlock: 117),
                    'M' => new EccBlockSpec(dataCodewords: 1267, eccPerBlock: 28, g1Blocks: 21, g1DataPerBlock: 45, g2Blocks: 7, g2DataPerBlock: 46),
                    'Q' => new EccBlockSpec(dataCodewords: 911, eccPerBlock: 30, g1Blocks: 1, g1DataPerBlock: 23, g2Blocks: 37, g2DataPerBlock: 24),
                    'H' => new EccBlockSpec(dataCodewords: 701, eccPerBlock: 30, g1Blocks: 19, g1DataPerBlock: 15, g2Blocks: 26, g2DataPerBlock: 16),
                ]
            ),
            30 => new VersionSpec(
                number: 30,
                alignmentPatternCenters: [6, 26, 52, 78, 104, 130],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1735, eccPerBlock: 30, g1Blocks: 5, g1DataPerBlock: 115, g2Blocks: 10, g2DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 1373, eccPerBlock: 28, g1Blocks: 19, g1DataPerBlock: 47, g2Blocks: 10, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 985, eccPerBlock: 30, g1Blocks: 15, g1DataPerBlock: 24, g2Blocks: 25, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 745, eccPerBlock: 30, g1Blocks: 23, g1DataPerBlock: 15, g2Blocks: 25, g2DataPerBlock: 16),
                ]
            ),
            31 => new VersionSpec(
                number: 31,
                alignmentPatternCenters: [6, 30, 56, 82, 108, 134],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1843, eccPerBlock: 30, g1Blocks: 13, g1DataPerBlock: 115, g2Blocks: 3, g2DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 1455, eccPerBlock: 28, g1Blocks: 2, g1DataPerBlock: 46, g2Blocks: 29, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1033, eccPerBlock: 30, g1Blocks: 42, g1DataPerBlock: 24, g2Blocks: 1, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 793, eccPerBlock: 30, g1Blocks: 23, g1DataPerBlock: 15, g2Blocks: 28, g2DataPerBlock: 16),
                ]
            ),
            32 => new VersionSpec(
                number: 32,
                alignmentPatternCenters: [6, 34, 60, 86, 112, 138],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 1955, eccPerBlock: 30, g1Blocks: 17, g1DataPerBlock: 115),
                    'M' => new EccBlockSpec(dataCodewords: 1541, eccPerBlock: 28, g1Blocks: 10, g1DataPerBlock: 46, g2Blocks: 23, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1115, eccPerBlock: 30, g1Blocks: 10, g1DataPerBlock: 24, g2Blocks: 35, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 845, eccPerBlock: 30, g1Blocks: 19, g1DataPerBlock: 15, g2Blocks: 35, g2DataPerBlock: 16),
                ]
            ),
            33 => new VersionSpec(
                number: 33,
                alignmentPatternCenters: [6, 30, 58, 86, 114, 142],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2071, eccPerBlock: 30, g1Blocks: 17, g1DataPerBlock: 115, g2Blocks: 1, g2DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 1631, eccPerBlock: 28, g1Blocks: 14, g1DataPerBlock: 46, g2Blocks: 21, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1171, eccPerBlock: 30, g1Blocks: 29, g1DataPerBlock: 24, g2Blocks: 19, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 901, eccPerBlock: 30, g1Blocks: 11, g1DataPerBlock: 15, g2Blocks: 46, g2DataPerBlock: 16),
                ]
            ),
            34 => new VersionSpec(
                number: 34,
                alignmentPatternCenters: [6, 34, 62, 90, 118, 146],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2191, eccPerBlock: 30, g1Blocks: 13, g1DataPerBlock: 115, g2Blocks: 6, g2DataPerBlock: 116),
                    'M' => new EccBlockSpec(dataCodewords: 1725, eccPerBlock: 28, g1Blocks: 14, g1DataPerBlock: 46, g2Blocks: 23, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1231, eccPerBlock: 30, g1Blocks: 44, g1DataPerBlock: 24, g2Blocks: 7, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 961, eccPerBlock: 30, g1Blocks: 59, g1DataPerBlock: 16, g2Blocks: 1, g2DataPerBlock: 17),
                ]
            ),
            35 => new VersionSpec(
                number: 35,
                alignmentPatternCenters: [6, 30, 54, 78, 102, 126, 150],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2306, eccPerBlock: 30, g1Blocks: 12, g1DataPerBlock: 121, g2Blocks: 7, g2DataPerBlock: 122),
                    'M' => new EccBlockSpec(dataCodewords: 1812, eccPerBlock: 28, g1Blocks: 12, g1DataPerBlock: 47, g2Blocks: 26, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 1286, eccPerBlock: 30, g1Blocks: 39, g1DataPerBlock: 24, g2Blocks: 14, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 986, eccPerBlock: 30, g1Blocks: 22, g1DataPerBlock: 15, g2Blocks: 41, g2DataPerBlock: 16),
                ]
            ),
            36 => new VersionSpec(
                number: 36,
                alignmentPatternCenters: [6, 24, 50, 76, 102, 128, 154],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2434, eccPerBlock: 30, g1Blocks: 6, g1DataPerBlock: 121, g2Blocks: 14, g2DataPerBlock: 122),
                    'M' => new EccBlockSpec(dataCodewords: 1914, eccPerBlock: 28, g1Blocks: 6, g1DataPerBlock: 47, g2Blocks: 34, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 1354, eccPerBlock: 30, g1Blocks: 46, g1DataPerBlock: 24, g2Blocks: 10, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 1054, eccPerBlock: 30, g1Blocks: 2, g1DataPerBlock: 15, g2Blocks: 64, g2DataPerBlock: 16),
                ]
            ),
            37 => new VersionSpec(
                number: 37,
                alignmentPatternCenters: [6, 28, 54, 80, 106, 132, 158],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2566, eccPerBlock: 30, g1Blocks: 17, g1DataPerBlock: 122, g2Blocks: 4, g2DataPerBlock: 123),
                    'M' => new EccBlockSpec(dataCodewords: 1992, eccPerBlock: 28, g1Blocks: 29, g1DataPerBlock: 46, g2Blocks: 14, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1426, eccPerBlock: 30, g1Blocks: 49, g1DataPerBlock: 24, g2Blocks: 10, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 1096, eccPerBlock: 30, g1Blocks: 24, g1DataPerBlock: 15, g2Blocks: 46, g2DataPerBlock: 16),
                ]
            ),
            38 => new VersionSpec(
                number: 38,
                alignmentPatternCenters: [6, 32, 58, 84, 110, 136, 162],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2702, eccPerBlock: 30, g1Blocks: 4, g1DataPerBlock: 122, g2Blocks: 18, g2DataPerBlock: 123),
                    'M' => new EccBlockSpec(dataCodewords: 2102, eccPerBlock: 28, g1Blocks: 13, g1DataPerBlock: 46, g2Blocks: 32, g2DataPerBlock: 47),
                    'Q' => new EccBlockSpec(dataCodewords: 1502, eccPerBlock: 30, g1Blocks: 48, g1DataPerBlock: 24, g2Blocks: 14, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 1142, eccPerBlock: 30, g1Blocks: 42, g1DataPerBlock: 15, g2Blocks: 32, g2DataPerBlock: 16),
                ]
            ),
            39 => new VersionSpec(
                number: 39,
                alignmentPatternCenters: [6, 26, 54, 82, 110, 138, 166],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2812, eccPerBlock: 30, g1Blocks: 20, g1DataPerBlock: 117, g2Blocks: 4, g2DataPerBlock: 118),
                    'M' => new EccBlockSpec(dataCodewords: 2216, eccPerBlock: 28, g1Blocks: 40, g1DataPerBlock: 47, g2Blocks: 7, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 1582, eccPerBlock: 30, g1Blocks: 43, g1DataPerBlock: 24, g2Blocks: 22, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 1222, eccPerBlock: 30, g1Blocks: 10, g1DataPerBlock: 15, g2Blocks: 67, g2DataPerBlock: 16),
                ]
            ),
            40 => new VersionSpec(
                number: 40,
                alignmentPatternCenters: [6, 30, 58, 86, 114, 142, 170],
                eccSpecs: [
                    'L' => new EccBlockSpec(dataCodewords: 2956, eccPerBlock: 30, g1Blocks: 19, g1DataPerBlock: 118, g2Blocks: 6, g2DataPerBlock: 119),
                    'M' => new EccBlockSpec(dataCodewords: 2334, eccPerBlock: 28, g1Blocks: 18, g1DataPerBlock: 47, g2Blocks: 31, g2DataPerBlock: 48),
                    'Q' => new EccBlockSpec(dataCodewords: 1666, eccPerBlock: 30, g1Blocks: 34, g1DataPerBlock: 24, g2Blocks: 34, g2DataPerBlock: 25),
                    'H' => new EccBlockSpec(dataCodewords: 1276, eccPerBlock: 30, g1Blocks: 20, g1DataPerBlock: 15, g2Blocks: 61, g2DataPerBlock: 16),
                ]
            ),
        ];
    }
}
