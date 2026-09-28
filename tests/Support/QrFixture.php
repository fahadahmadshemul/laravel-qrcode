<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Support;

/**
 * One integration fixture: a payload plus the settings it is generated with
 * and the properties a correct decode must reproduce.
 *
 * {@see QrFixtures} holds the catalogue; the integration tests generate each
 * fixture through the production pipeline and assert it decodes back to
 * {@see self::$data}.
 */
final class QrFixture
{
    /**
     * @param  string  $data  The exact payload to encode and expect back.
     * @param  string  $level  Error-correction level: L, M, Q or H.
     * @param  int|null  $expectedVersion  Version the engine must select (null = don't assert).
     * @param  bool|null  $expectedUtf8Eci  Whether a UTF-8 ECI header must be present
     *                                      (true), absent (false), or unchecked (null).
     */
    public function __construct(
        public readonly string $data,
        public readonly string $level,
        public readonly ?int $expectedVersion = null,
        public readonly ?bool $expectedUtf8Eci = null,
    ) {}
}
