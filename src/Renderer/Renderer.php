<?php

declare(strict_types=1);

namespace Fahad\QrCode\Renderer;

use Fahad\QrCode\Matrix\QrMatrix;

/**
 * Contract for turning a QR matrix into a binary payload (SVG markup,
 * PNG bytes, …).
 */
interface Renderer
{
    /**
     * Render the matrix into its output representation.
     */
    public function render(
        QrMatrix $matrix,
        int $size,
        int $margin,
        string $foregroundColor = '#000000',
        string $backgroundColor = '#ffffff'
    ): string;

    /**
     * The output format identifier, e.g. "svg" or "png".
     */
    public function format(): string;
}
