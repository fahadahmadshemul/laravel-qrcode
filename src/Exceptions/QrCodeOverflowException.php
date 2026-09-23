<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

/**
 * Exception raised when the payload exceeds the capacity of the supported QR versions.
 */
class QrCodeOverflowException extends QrCodeException
{
    public static function payloadTooLarge(int $bytes, int $version, string $level, int $maxCapacity): static
    {
        return new static(sprintf(
            'Payload of %d bytes exceeds Version %d byte-mode capacity at ECC level %s (%d bytes max).',
            $bytes,
            $version,
            $level,
            $maxCapacity
        ));
    }
}
