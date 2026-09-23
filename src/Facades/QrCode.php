<?php

declare(strict_types=1);

namespace Fahad\QrCode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fahad\QrCode\QrCode make(?string $data = null)
 * @method static \Fahad\QrCode\QrCode data(string $data)
 * @method static \Fahad\QrCode\QrCode size(int $size)
 * @method static \Fahad\QrCode\QrCode margin(int $margin)
 * @method static \Fahad\QrCode\QrCode format(string $format)
 * @method static \Fahad\QrCode\QrCode errorCorrection(string $level)
 * @method static \Fahad\QrCode\QrCode foregroundColor(string $color)
 * @method static \Fahad\QrCode\QrCode foreground(string $color)
 * @method static \Fahad\QrCode\QrCode backgroundColor(string $color)
 * @method static \Fahad\QrCode\QrCode background(string $color)
 * @method static \Fahad\QrCode\QrCode color(string $foregroundColor, ?string $backgroundColor = null)
 * @method static \Fahad\QrCode\QrCode svg(?string $data = null)
 * @method static \Fahad\QrCode\QrCode png(?string $data = null)
 * @method static string generate(?string $data = null)
 * @method static string base64(bool $includeDataUri = true)
 * @method static \Fahad\QrCode\QrCode save(string $path)
 * @method static string renderFromBlade(string $data, array $options = [])
 * @method static \Illuminate\Http\Response response(int $status = 200, array $headers = [])
 * @method static string contentType()
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
