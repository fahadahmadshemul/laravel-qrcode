<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

/**
 * Exception raised when saving a QR code file fails.
 */
class FileWriteException extends QrCodeException
{
    public static function unableToCreateDirectory(string $directory): static
    {
        return new static(sprintf('Unable to create destination directory: "%s".', $directory));
    }

    public static function unableToWriteFile(string $path): static
    {
        return new static(sprintf('Unable to write QR code to file: "%s".', $path));
    }
}
