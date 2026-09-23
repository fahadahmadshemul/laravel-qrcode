<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

use InvalidArgumentException;

/**
 * QR Code Encoding Modes (ISO/IEC 18004 Section 7.4).
 */
enum EncodingMode: string
{
    case Numeric = 'numeric';
    case Alphanumeric = 'alphanumeric';
    case Byte = 'byte';
    case Auto = 'auto';

    /**
     * Map of Alphanumeric characters to their 0–44 integer values.
     */
    public const ALPHANUMERIC_CHAR_MAP = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
        '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
        'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 'J' => 19,
        'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24,
        'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29,
        'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34,
        'Z' => 35, ' ' => 36, '$' => 37, '%' => 38, '*' => 39,
        '+' => 40, '-' => 41, '.' => 42, '/' => 43, ':' => 44,
    ];

    /**
     * Get the 4-bit mode indicator.
     */
    public function modeIndicator(): int
    {
        return match ($this) {
            self::Numeric => 0b0001,
            self::Alphanumeric => 0b0010,
            self::Byte, self::Auto => 0b0100,
        };
    }

    /**
     * Automatically detect the most efficient valid encoding mode for the payload.
     */
    public static function detect(string $data): self
    {
        if (self::isNumericString($data)) {
            return self::Numeric;
        }

        if (self::isAlphanumericString($data)) {
            return self::Alphanumeric;
        }

        return self::Byte;
    }

    /**
     * Resolve a string or EncodingMode instance to an actual encoding mode enum.
     */
    public static function resolve(string|self $mode, string $data = ''): self
    {
        if ($mode instanceof self) {
            $resolved = $mode;
        } else {
            $normalized = strtolower(trim($mode));
            $resolved = self::tryFrom($normalized) ?? throw new InvalidArgumentException(sprintf(
                'Unsupported encoding mode "%s". Valid modes are: numeric, alphanumeric, byte, auto.',
                $mode
            ));
        }

        if ($resolved === self::Auto) {
            return self::detect($data);
        }

        return $resolved;
    }

    /**
     * Check if payload is valid for the given mode.
     */
    public function validatePayload(string $data): void
    {
        switch ($this) {
            case self::Numeric:
                if (! self::isNumericString($data)) {
                    throw new InvalidArgumentException(
                        'Payload contains characters invalid for Numeric encoding mode. Only digits (0-9) are allowed.'
                    );
                }
                break;

            case self::Alphanumeric:
                if (! self::isAlphanumericString($data)) {
                    throw new InvalidArgumentException(
                        'Payload contains characters invalid for Alphanumeric encoding mode. Only digits (0-9), uppercase letters (A-Z), spaces, and symbols ($ % * + - . / :) are allowed.'
                    );
                }
                break;

            case self::Byte:
            case self::Auto:
                // Any binary / UTF-8 string is valid for Byte mode.
                break;
        }
    }

    public static function isNumericString(string $data): bool
    {
        return $data !== '' && preg_match('/^[0-9]+$/D', $data) === 1;
    }

    public static function isAlphanumericString(string $data): bool
    {
        return $data !== '' && preg_match('/^[0-9A-Z $%*+\-.\/:]+$/D', $data) === 1;
    }
}
