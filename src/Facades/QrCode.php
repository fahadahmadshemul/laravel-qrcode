<?php

declare(strict_types=1);

namespace Fahad\QrCode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fahad\QrCode\QrCode make(string $data)
 * @method static \Fahad\QrCode\QrCode data(string $data)
 * @method static \Fahad\QrCode\QrCode size(int $size)
 * @method static \Fahad\QrCode\QrCode margin(int $margin)
 * @method static \Fahad\QrCode\QrCode format(string $format)
 * @method static \Fahad\QrCode\QrCode errorCorrection(string $level)
 * @method static string generate()
 *
 * @see \Fahad\QrCode\QrCode
 */
class QrCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'qrcode';
    }
}
