<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Illuminate\Support\Facades\Artisan;

/**
 * Verifies the package config is merged into the application at runtime and
 * can be published to the host application's config directory via the
 * `qrcode-config` tag (Laravel's `vendor:publish` workflow).
 */
final class ConfigPublishingTest extends PackageTestCase
{
    public function test_config_is_merged_into_the_application(): void
    {
        $this->assertSame('svg', config('qrcode.format'));
        $this->assertSame('M', config('qrcode.error_correction'));
        $this->assertSame(300, config('qrcode.size'));
        $this->assertSame(4, config('qrcode.margin'));
        $this->assertSame('auto', config('qrcode.eci'));
    }

    public function test_config_can_be_published_with_the_qrcode_config_tag(): void
    {
        $target = config_path('qrcode.php');

        if (file_exists($target)) {
            @unlink($target);
        }

        $exitCode = Artisan::call('vendor:publish', ['--tag' => 'qrcode-config']);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target, 'Publishing the qrcode-config tag should copy the config file.');

        /** @var array<string, mixed> $published */
        $published = require $target;

        $this->assertSame('svg', $published['format']);
        $this->assertSame('M', $published['error_correction']);

        @unlink($target);
    }

    public function test_published_config_matches_the_packaged_defaults(): void
    {
        /** @var array<string, mixed> $packaged */
        $packaged = require dirname(__DIR__, 2).'/config/qrcode.php';

        $this->assertSame($packaged['size'], config('qrcode.size'));
        $this->assertSame($packaged['format'], config('qrcode.format'));
        $this->assertSame($packaged['error_correction'], config('qrcode.error_correction'));
    }
}
