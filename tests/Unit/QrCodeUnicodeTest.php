<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\Encoding\Eci;
use Fahad\QrCode\QrCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the public builder with Unicode payloads: every script must render
 * without error, and the ECI switch must be reflected through the fluent API.
 */
final class QrCodeUnicodeTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function unicodePayloads(): array
    {
        return [
            'English' => ['Hello World'],
            'Bangla' => ['হ্যালো বাংলাদেশ'],
            'Arabic' => ['مرحبا بالعالم'],
            'Chinese' => ['你好世界'],
            'Japanese' => ['こんにちは世界'],
            'Emoji' => ['Hello 👋'],
        ];
    }

    #[DataProvider('unicodePayloads')]
    public function test_svg_generation_succeeds_for_unicode(string $payload): void
    {
        $svg = QrCode::make($payload)->svg()->generate();

        $this->assertStringContainsString('<svg', $svg);
    }

    #[DataProvider('unicodePayloads')]
    public function test_png_generation_succeeds_for_unicode(string $payload): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }

        $png = QrCode::make($payload)->png()->generate();

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
    }

    #[DataProvider('unicodePayloads')]
    public function test_base64_data_uri_for_unicode(string $payload): void
    {
        $uri = QrCode::make($payload)->svg()->base64();

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
    }

    public function test_eci_defaults_to_auto(): void
    {
        $this->assertNull(QrCode::make('你好')->toArray()['eci']);
    }

    public function test_eci_can_be_toggled_through_the_builder(): void
    {
        $this->assertFalse(QrCode::make('你好')->eci(false)->toArray()['eci']);
        $this->assertTrue(QrCode::make('你好')->eci(true)->toArray()['eci']);
        $this->assertTrue(QrCode::make('你好')->eci()->toArray()['eci']);
    }

    public function test_disabling_eci_changes_the_rendered_output(): void
    {
        // With and without the UTF-8 declaration the module data differs, so
        // the two SVGs must not be identical for a multibyte payload.
        $withEci = QrCode::make('你好世界')->eci(true)->svg()->generate();
        $withoutEci = QrCode::make('你好世界')->eci(false)->svg()->generate();

        $this->assertNotSame($withEci, $withoutEci);
    }

    public function test_eci_helper_detects_multibyte_utf8(): void
    {
        $this->assertTrue(Eci::isMultibyteUtf8('你好'));
        $this->assertTrue(Eci::isMultibyteUtf8('Hello 👋'));
        $this->assertFalse(Eci::isMultibyteUtf8('Hello World'));
        $this->assertFalse(Eci::isMultibyteUtf8("\xff\xfe"), 'invalid UTF-8 must not be labelled UTF-8');
    }
}
