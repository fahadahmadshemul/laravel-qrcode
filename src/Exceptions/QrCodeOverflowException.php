<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

final class QrCodeOverflowException extends QrCodeException
{
    public static function forVersion(int $version, string $level, int $length, int $capacity): static
    {
        return new self(sprintf(
            'Payload of %d bytes exceeds Version %d byte-mode capacity at ECC level %s (%d bytes max).',
            $length,
            $version,
            $level,
            $capacity
        ));
    }
}
