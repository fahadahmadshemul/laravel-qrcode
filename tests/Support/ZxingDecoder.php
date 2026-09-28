<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

use Fahad\QrCode;
use Zxing\QrReader;

/**
 * Thin adapter over the third-party ZXing QR reader used *only* by the test
 * suite for independent decode verification.
 *
 * ── Dependency boundary ──────────────────────────────────────────────────
 * The production QR engine (the {@see QrCode} namespace) generates QR
 * codes from scratch and NEVER references a decoder. This class is the single
 * point where the dev-only `khanamiryan/qrcode-detector-decoder` package is
 * touched, so the coupling stays confined to tests. See
 * tests/Integration/README.md for the full rationale.
 *
 * The reader is a pure-PHP port of ZXing and needs the GD (or Imagick)
 * extension to rasterise the PNG. {@see self::isAvailable()} lets tests skip
 * cleanly when neither the package nor GD is present.
 */
final class ZxingDecoder
{
    /**
     * Whether an independent decode can run in this environment.
     */
    public static function isAvailable(): bool
    {
        return class_exists(QrReader::class)
            && (extension_loaded('gd') || extension_loaded('imagick'));
    }

    /**
     * Decode PNG image bytes with the independent reader.
     *
     * Returns the decoded string, or null when the reader could not locate or
     * read a QR symbol in the image (ZXing returns false in that case).
     *
     * The reader can emit low-level notices while probing a bitmap it cannot
     * read; those are suppressed here so a *failure to decode* surfaces as a
     * null return (which the caller asserts on) rather than as a PHPUnit
     * warning-to-failure. Genuine engine defects still show up as a mismatch
     * between the decoded text and the payload.
     */
    public static function decodePng(string $png): ?string
    {
        // Swallow the low-level notices the reader can raise while probing a
        // bitmap it cannot read, so a failed decode surfaces as a null return
        // rather than a PHPUnit warning. restore_error_handler() pops exactly
        // this handler again, leaving the stack as we found it.
        set_error_handler(static fn (): bool => true);

        try {
            $decoded = (new QrReader($png, QrReader::SOURCE_TYPE_BLOB))->text();
        } finally {
            restore_error_handler();
        }

        return is_string($decoded) ? $decoded : null;
    }
}
