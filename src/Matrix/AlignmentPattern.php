<?php

declare(strict_types=1);

namespace Fahad\QrCode\Matrix;

/**
 * Places 5x5 alignment patterns at version-specific coordinates
 * (ISO/IEC 18004 Section 8.5, Figure 11).
 */
final class AlignmentPattern
{
    public function __construct(
        private readonly QrMatrix $matrix,
        private readonly VersionSpec $version,
    ) {}

    public function place(): void
    {
        $centers = $this->version->alignmentPatternCenters;

        if (count($centers) === 0) {
            return;
        }

        foreach ($centers as $cy) {
            foreach ($centers as $cx) {
                // Skip if center overlaps existing function modules (finders/timing)
                if ($this->matrix->isFunctionModule($cx, $cy)) {
                    continue;
                }

                $this->placePatternAt($cx, $cy);
            }
        }
    }

    private function placePatternAt(int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;

                $maxDist = max(abs($dx), abs($dy));
                $dark = ($maxDist === 2 || $maxDist === 0);

                $this->matrix->set($x, $y, $dark);
                $this->matrix->markFunction($x, $y);
            }
        }
    }
}
