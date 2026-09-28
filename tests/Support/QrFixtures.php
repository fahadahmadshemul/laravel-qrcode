<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

/**
 * Canonical catalogue of QR integration fixtures.
 *
 * The fixtures deliberately span every axis the brief calls for — short text,
 * URLs, Unicode across scripts, the maximum a Version 1 symbol can hold, all
 * four error-correction levels, and a spread of symbol versions from 1 up to
 * the largest (40).
 *
 * Two providers are exposed because the two decoders used by the integration
 * suite have different reach:
 *
 *   - {@see self::independentlyDecodable()} is the subset that the bundled,
 *     third-party ZXing port ({@see ZxingDecoder}) reliably reads. Every entry
 *     here has been confirmed to round-trip through an *independent* decoder,
 *     so it proves the generated image is readable by foreign software — not
 *     merely that it "looks like" a QR code.
 *
 *   - {@see self::fullMatrix()} adds the high-density / high-version symbols.
 *     The pure-PHP ZXing port cannot reliably locate those (a limitation of
 *     that reader, documented in tests/Integration/README.md), so they are
 *     verified with the spec-faithful in-repo decoder instead, which reads the
 *     actual module data back out of the rendered matrix.
 *
 * Expected versions are pinned so the fixtures double as a guard against
 * capacity-selection regressions.
 */
final class QrFixtures
{
    /**
     * Fixtures the independent ZXing decoder is known to read reliably.
     *
     * @return array<string, array{QrFixture}>
     */
    public static function independentlyDecodable(): array
    {
        return self::asProvider(self::baseline());
    }

    /**
     * Every fixture, including high-version / high-density symbols that only
     * the in-repo matrix decoder can verify.
     *
     * @return array<string, array{QrFixture}>
     */
    public static function fullMatrix(): array
    {
        return self::asProvider([...self::baseline(), ...self::highVersion()]);
    }

    /**
     * The subset that pins a UTF-8 ECI expectation (the Unicode fixtures):
     * multibyte payloads expect the header present, ASCII expects it absent.
     *
     * @return array<string, array{QrFixture}>
     */
    public static function withEciExpectation(): array
    {
        return self::asProvider(
            array_filter(self::baseline(), static fn (QrFixture $fixture): bool => $fixture->expectedUtf8Eci !== null)
        );
    }

    /**
     * The independently-decodable baseline, covering every requested category.
     *
     * @return array<string, QrFixture>
     */
    private static function baseline(): array
    {
        return [
            // --- short text ---
            'short-text/greeting' => new QrFixture('Hi', 'M', 1),
            'short-text/message' => new QrFixture('Order #42 is ready', 'M', 2),

            // --- URLs ---
            'url/https' => new QrFixture('https://laravel.com', 'M', 2),
            'url/deep-link' => new QrFixture('https://github.com/fahadahmadshemul/laravel-qrcode', 'M', 4),
            'url/alphanumeric' => new QrFixture('HTTPS://EXAMPLE.COM/A', 'M', 2),

            // --- Unicode (byte mode; multibyte payloads must declare UTF-8 via ECI) ---
            'unicode/english' => new QrFixture('Hello World', 'M', 1, false),
            'unicode/bangla' => new QrFixture('হ্যালো বাংলাদেশ', 'M', 4, true),
            'unicode/arabic' => new QrFixture('مرحبا بالعالم', 'M', 2, true),
            'unicode/chinese' => new QrFixture('你好世界', 'M', 1, true),
            'unicode/japanese' => new QrFixture('こんにちは世界', 'M', 2, true),
            'unicode/emoji' => new QrFixture('Hello 👋', 'M', 1, true),

            // --- maximum Version 1 capacity at level L (41 numeric / 25 alnum / 17 byte) ---
            'max-v1/numeric' => new QrFixture('12345678901234567890123456789012345678901', 'L', 1),
            'max-v1/alphanumeric' => new QrFixture('ABCDEFGHIJKLMNOPQRSTUVWXY', 'L', 1),
            'max-v1/byte' => new QrFixture('lowercase bytes17', 'L', 1),

            // --- each error-correction level (same payload; higher ECC costs capacity) ---
            'ecc/L' => new QrFixture('INVOICE-7788', 'L', 1),
            'ecc/M' => new QrFixture('INVOICE-7788', 'M', 1),
            'ecc/Q' => new QrFixture('INVOICE-7788', 'Q', 1),
            'ecc/H' => new QrFixture('INVOICE-7788', 'H', 2),

            // --- a spread of symbol versions ---
            'version/v1' => new QrFixture('PING', 'M', 1),
            'version/v2' => new QrFixture('checkpoint-alpha', 'M', 2),
            'version/v3' => new QrFixture('shipment tracking code: 12345', 'M', 3),
            'version/v4' => new QrFixture('Meeting at 3pm in room 204, bring the report.', 'M', 4),
            'version/v6' => new QrFixture(str_repeat('Order line item; ', 6), 'M', 6),
            'version/v8' => new QrFixture(str_repeat('Order line item; ', 8), 'M', 8),
        ];
    }

    /**
     * High-version / high-density symbols. Verified with the in-repo matrix
     * decoder because the bundled pure-PHP ZXing port cannot reliably read
     * codes this dense (see tests/Integration/README.md).
     *
     * @return array<string, QrFixture>
     */
    private static function highVersion(): array
    {
        return [
            'high-version/v40-numeric' => new QrFixture(str_repeat('1234567890', 700), 'L', 40),
            'high-version/v38-byte' => new QrFixture(str_repeat('Lorem ipsum dolor sit. ', 90), 'M', 38),
            'high-version/v35-alphanumeric' => new QrFixture(str_repeat('DATA-1234 ', 180), 'Q', 35),
            'high-version/v28-high-ecc' => new QrFixture(str_repeat('secure ', 90), 'H', 28),
        ];
    }

    /**
     * Wrap each fixture in the single-argument shape PHPUnit data providers use.
     *
     * @param  array<string, QrFixture>  $fixtures
     * @return array<string, array{QrFixture}>
     */
    private static function asProvider(array $fixtures): array
    {
        return array_map(static fn (QrFixture $fixture): array => [$fixture], $fixtures);
    }
}
