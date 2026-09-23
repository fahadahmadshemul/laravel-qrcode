<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

final class InvalidErrorCorrectionLevelException extends QrCodeException
{
    public static function forLevel(string $level): static
    {
        return new self(sprintf(
            'Invalid error correction level "%s". Supported levels: L, M, Q, H.',
            $level
        ));
    }
}
