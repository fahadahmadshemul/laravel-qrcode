<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\Facades\QrCode as QrCodeFacade;
use Fahad\QrCode\QrCode;
use Fahad\QrCode\QrCodeServiceProvider;

/**
 * Verifies Laravel package auto-discovery metadata and that the provider,
 * once loaded, boots correctly. Discovery itself is driven by composer.json's
 * extra.laravel block, so we assert that block is well-formed in addition to
 * checking the runtime effect of loading the provider.
 */
final class ServiceProviderDiscoveryTest extends PackageTestCase
{
    public function test_composer_declares_provider_for_auto_discovery(): void
    {
        $composer = $this->packageComposer();

        $this->assertContains(
            QrCodeServiceProvider::class,
            $composer['extra']['laravel']['providers'] ?? [],
            'The service provider must be declared under extra.laravel.providers for package auto-discovery.'
        );
    }

    public function test_composer_declares_facade_alias_for_auto_discovery(): void
    {
        $composer = $this->packageComposer();

        $this->assertSame(
            QrCodeFacade::class,
            $composer['extra']['laravel']['aliases']['QrCode'] ?? null,
            'The QrCode facade alias must be declared under extra.laravel.aliases.'
        );
    }

    public function test_provider_is_loaded_into_the_application(): void
    {
        $this->assertArrayHasKey(
            QrCodeServiceProvider::class,
            $this->laravel()->getLoadedProviders(),
            'The service provider should be registered and loaded by the container.'
        );
    }

    public function test_provider_binds_the_qrcode_singleton(): void
    {
        $this->assertTrue($this->laravel()->bound('qrcode'));
        $this->assertInstanceOf(QrCode::class, $this->laravel()->make('qrcode'));
    }

    public function test_package_aliases_helper_matches_composer_metadata(): void
    {
        $this->assertSame(
            ['QrCode' => QrCodeFacade::class],
            QrCodeServiceProvider::packageAliases()
        );
    }

    /**
     * Read the package's own composer.json (not the Testbench skeleton's).
     *
     * @return array<string, mixed>
     */
    private function packageComposer(): array
    {
        $path = dirname(__DIR__, 2).'/composer.json';
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, 'Unable to read package composer.json.');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
