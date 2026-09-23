<?php

declare(strict_types=1);

namespace Fahad\QrCode\Renderer;

use Fahad\QrCode\Matrix\QrMatrix;
use RuntimeException;

/**
 * Renders a QR matrix as PNG image data (GD-based).
 */
final class PngRenderer implements Renderer
{
    public function render(QrMatrix $matrix, int $size, int $margin): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required for PNG rendering.');
        }

        $moduleCount = $matrix->size;
        $totalModules = $moduleCount + $margin * 2;
        $scale = intdiv($size, $totalModules);

        if ($scale < 1) {
            throw new RuntimeException(sprintf(
                'Target size %dpx is too small for %d modules; minimum is %dpx.',
                $size,
                $totalModules,
                $totalModules
            ));
        }

        $width = $scale * $totalModules;

        if ($width < 1) {
            throw new RuntimeException('PNG dimensions must be positive.');
        }

        $image = imagecreatetruecolor($width, $width);

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        if ($white === false || $black === false) {
            throw new RuntimeException('Unable to allocate PNG colours.');
        }

        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === true) {
                    imagefilledrectangle(
                        $image,
                        ($margin + $x) * $scale,
                        ($margin + $y) * $scale,
                        ($margin + $x) * $scale + $scale - 1,
                        ($margin + $y) * $scale + $scale - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    public function format(): string
    {
        return 'png';
    }
}
