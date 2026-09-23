<?php

declare(strict_types=1);

namespace Fahad\QrCode\Renderer;

use Fahad\QrCode\Matrix\QrMatrix;
use RuntimeException;

/**
 * Renders a QR matrix as a scalable SVG document.
 */
final class SvgRenderer implements Renderer
{
    public function render(QrMatrix $matrix, int $size, int $margin): string
    {
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
        $viewBox = sprintf('0 0 %d %d', $totalModules, $totalModules);
        $rects = [];

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === true) {
                    $rects[] = sprintf(
                        '<rect x="%d" y="%d" width="%d" height="%d"/>',
                        $margin + $x,
                        $margin + $y,
                        1,
                        1
                    );
                }
            }
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d"'
            .' viewBox="%s" shape-rendering="crispEdges">'
            .'<rect width="100%%" height="100%%" fill="#ffffff"/>'
            .'<g fill="#000000">%s</g></svg>',
            $width,
            $width,
            $viewBox,
            implode('', $rects)
        );
    }

    public function format(): string
    {
        return 'svg';
    }
}
