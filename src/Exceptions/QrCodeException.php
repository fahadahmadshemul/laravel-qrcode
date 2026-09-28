<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

use RuntimeException;

/**
 * Base exception for every failure raised by the package.
 *
 * @phpstan-consistent-constructor
 */
class QrCodeException extends RuntimeException
{
    public static function emptyData(): static
    {
        return new static('Cannot generate a QR code for empty data.');
    }

    public static function invalidSize(int $size): static
    {
        return new static(sprintf(
            'QR code size must be a positive number of pixels; got %d.',
            $size
        ));
    }

    public static function invalidMargin(int $margin): static
    {
        return new static(sprintf(
            'QR code margin must be zero or a positive number of modules; got %d.',
            $margin
        ));
    }

    public static function encoderNotImplemented(string $format): static
    {
        return new static(sprintf(
            'QR encoding engine is not implemented yet; unable to render "%s" output.',
            $format
        ));
    }
}
