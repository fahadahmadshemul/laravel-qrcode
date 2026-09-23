<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

final class UnsupportedFormatException extends QrCodeException
{
    public static function forFormat(string $format): static
    {
        return new self(sprintf(
            'Unsupported QR code format "%s". Supported formats: svg, png.',
            $format
        ));
    }
}
