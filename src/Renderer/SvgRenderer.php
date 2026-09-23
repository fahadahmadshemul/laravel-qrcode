<?php

declare(strict_types=1);

namespace Fahad\QrCode\Renderer;

use Fahad\QrCode\Matrix\QrMatrix;
use RuntimeException;

/**
 * Renders a QR matrix as a compact, scalable, valid SVG XML document.
 */
final class SvgRenderer implements Renderer
{
    public function render(
        QrMatrix $matrix,
        int $size,
        int $margin,
        string $foregroundColor = '#000000',
        string $backgroundColor = '#ffffff'
    ): string {
        $moduleCount = $matrix->size;
        $totalModules = $moduleCount + $margin * 2;

        if ($size < 1) {
            throw new RuntimeException(sprintf('Target size %dpx must be positive.', $size));
        }

        $fgColor = htmlspecialchars($foregroundColor, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $bgColor = htmlspecialchars($backgroundColor, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $viewBox = sprintf('0 0 %d %d', $totalModules, $totalModules);

        $pathData = [];

        for ($y = 0; $y < $moduleCount; $y++) {
            $x = 0;
            while ($x < $moduleCount) {
                if ($matrix->get($x, $y) === true) {
                    $startX = $x;
                    while ($x < $moduleCount && $matrix->get($x, $y) === true) {
                        $x++;
                    }
                    $length = $x - $startX;

                    $px = $margin + $startX;
                    $py = $margin + $y;

                    $pathData[] = sprintf('M%d %dh%dv1H%dz', $px, $py, $length, $px);
                } else {
                    $x++;
                }
            }
        }

        $bgRect = '';
        if ($bgColor !== '' && strtolower($bgColor) !== 'transparent' && strtolower($bgColor) !== 'none') {
            $bgRect = sprintf('<rect width="100%%" height="100%%" fill="%s"/>', $bgColor);
        }

        $pathTag = '';
        if (count($pathData) > 0) {
            $pathTag = sprintf('<path fill="%s" d="%s"/>', $fgColor, implode('', $pathData));
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d"'
            .' viewBox="%s" shape-rendering="crispEdges">%s%s</svg>',
            $size,
            $size,
            $viewBox,
            $bgRect,
            $pathTag
        );
    }

    public function format(): string
    {
        return 'svg';
    }
}
