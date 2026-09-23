<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Error correction block configuration for a specific version and ECC level.
 */
final class EccBlockSpec
{
    public function __construct(
        public readonly int $dataCodewords,
        public readonly int $eccPerBlock,
        public readonly int $g1Blocks,
        public readonly int $g1DataPerBlock,
        public readonly int $g2Blocks = 0,
        public readonly int $g2DataPerBlock = 0,
    ) {}

    public function totalBlocks(): int
    {
        return $this->g1Blocks + $this->g2Blocks;
    }

    /**
     * @return list<int> List of data codeword counts for each block.
     */
    public function blockDataCounts(): array
    {
        $counts = [];
        for ($i = 0; $i < $this->g1Blocks; $i++) {
            $counts[] = $this->g1DataPerBlock;
        }
        for ($i = 0; $i < $this->g2Blocks; $i++) {
            $counts[] = $this->g2DataPerBlock;
        }

        return $counts;
    }
}
