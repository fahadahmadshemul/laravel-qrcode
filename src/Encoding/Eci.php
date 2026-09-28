<?php

declare(strict_types=1);

namespace Fahad\QrCode\Encoding;

/**
 * Extended Channel Interpretation (ECI) support for Unicode payloads.
 *
 * QR byte mode has no character set of its own: ISO/IEC 18004 defines its
 * default interpretation as ISO-8859-1 (Latin-1). Storing raw UTF-8 bytes
 * without an ECI header therefore relies on the decoder *guessing* the
 * character set. Emitting an ECI header (mode 0111 + designator 26) declares
 * UTF-8 explicitly, which is what the ZXing reference encoder does and what
 * makes multibyte text (Bangla, Arabic, CJK, emoji, …) round-trip reliably
 * on strictly conformant decoders.
 *
 * This class is part of the framework-agnostic QR engine and must not depend
 * on Laravel.
 */
final class Eci
{
    /**
     * ECI mode indicator (4 bits), ISO/IEC 18004 Section 7.4.
     */
    public const MODE_INDICATOR = 0b0111;

    /**
     * ECI Assignment Number for UTF-8 (AIM ECI 000026).
     */
    public const UTF8 = 26;

    /**
     * Resolve whether a UTF-8 ECI header should be emitted for the payload.
     *
     * ECI only applies to byte-mode segments; numeric and alphanumeric modes
     * are ASCII-only by definition. The $option argument mirrors the public
     * builder switch:
     *   - null  → automatic: emit only when the payload is genuine multibyte
     *             UTF-8, so pure ASCII / Latin-1 / binary data is untouched;
     *   - true  → force the header on (still byte-mode only);
     *   - false → never emit it (raw bytes, legacy behaviour).
     */
    public static function resolve(?bool $option, EncodingMode $mode, string $data): bool
    {
        if ($mode !== EncodingMode::Byte && $mode !== EncodingMode::Auto) {
            return false;
        }

        return match ($option) {
            true => true,
            false => false,
            null => self::isMultibyteUtf8($data),
        };
    }

    /**
     * The number of bits an ECI header occupies in the bit stream:
     * 4-bit mode indicator + 8-bit designator (assignment numbers 0–127 use
     * the single-byte form, and UTF-8 = 26 falls in that range).
     */
    public static function headerBits(): int
    {
        return 4 + 8;
    }

    /**
     * Whether the string is valid UTF-8 that actually contains at least one
     * multibyte (non-ASCII) character.
     *
     * Pure ASCII needs no ECI (it is identical under Latin-1 and UTF-8), and
     * invalid UTF-8 (arbitrary binary or Latin-1 text) must not be mislabelled
     * as UTF-8, so both cases return false.
     */
    public static function isMultibyteUtf8(string $data): bool
    {
        // Fast reject: no bytes with the high bit set means pure ASCII.
        if (preg_match('/[\x80-\xff]/', $data) !== 1) {
            return false;
        }

        // The /u modifier makes PCRE validate the subject as UTF-8; an
        // ill-formed sequence yields false (PREG_BAD_UTF8_ERROR).
        return preg_match('//u', $data) === 1;
    }
}
