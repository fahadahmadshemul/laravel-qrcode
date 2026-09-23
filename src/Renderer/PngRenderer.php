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
    public function render(
        QrMatrix $matrix,
        int $size,
        int $margin,
        string $foregroundColor = '#000000',
        string $backgroundColor = '#ffffff'
    ): string {
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

        if ($image === false) {
            throw new RuntimeException('Unable to create GD image surface.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $bgRgb = $this->parseColor($backgroundColor, [255, 255, 255, 0]);
        $fgRgb = $this->parseColor($foregroundColor, [0, 0, 0, 0]);

        $bgColorAlloc = imagecolorallocatealpha($image, $bgRgb[0], $bgRgb[1], $bgRgb[2], $bgRgb[3]);
        $fgColorAlloc = imagecolorallocatealpha($image, $fgRgb[0], $fgRgb[1], $fgRgb[2], $fgRgb[3]);

        if ($bgColorAlloc === false || $fgColorAlloc === false) {
            imagedestroy($image);
            throw new RuntimeException('Unable to allocate PNG colours.');
        }

        imagefill($image, 0, 0, $bgColorAlloc);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === true) {
                    imagefilledrectangle(
                        $image,
                        ($margin + $x) * $scale,
                        ($margin + $y) * $scale,
                        ($margin + $x) * $scale + $scale - 1,
                        ($margin + $y) * $scale + $scale - 1,
                        $fgColorAlloc
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

    /**
     * @param  array{0: int, 1: int, 2: int, 3: int}  $default
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function parseColor(string $color, array $default): array
    {
        $clean = strtolower(trim($color));

        if ($clean === 'transparent' || $clean === 'none') {
            return [0, 0, 0, 127];
        }

        $namedColors = [
            'black' => [0, 0, 0, 0],
            'white' => [255, 255, 255, 0],
            'red' => [255, 0, 0, 0],
            'green' => [0, 128, 0, 0],
            'blue' => [0, 0, 255, 0],
            'yellow' => [255, 255, 0, 0],
            'cyan' => [0, 255, 255, 0],
            'magenta' => [255, 0, 255, 0],
            'gray' => [128, 128, 128, 0],
            'grey' => [128, 128, 128, 0],
        ];

        if (isset($namedColors[$clean])) {
            return $namedColors[$clean];
        }

        $hex = ltrim($clean, '#');

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));

            return [(int) $r, (int) $g, (int) $b, 0];
        }

        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));

            return [(int) $r, (int) $g, (int) $b, 0];
        }

        return $default;
    }
}
