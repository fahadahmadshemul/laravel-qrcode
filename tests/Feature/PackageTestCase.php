<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\Facades\QrCode;
use Fahad\QrCode\QrCodeServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base test case for the Laravel integration / compatibility suite.
 *
 * Boots a minimal Laravel application (via Orchestra Testbench) with the
 * package's service provider and facade alias registered, so the tests below
 * exercise the package the same way a host Laravel application would across
 * Laravel 9–13. Nothing here is version-specific: it uses only Testbench
 * helpers that are stable across every supported testbench major (7–11).
 */
abstract class PackageTestCase extends TestCase
{
    /**
     * Register the package's service provider, mirroring what Laravel's
     * package auto-discovery does from composer.json's extra.laravel.providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            QrCodeServiceProvider::class,
        ];
    }

    /**
     * Register the facade alias, mirroring extra.laravel.aliases.
     *
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'QrCode' => QrCode::class,
        ];
    }

    /**
     * The booted Testbench application, guaranteed non-null.
     *
     * Testbench types the {@see $app} property as nullable; this guard lets
     * the tests resolve services without tripping static analysis, and fails
     * loudly if the application was somehow not booted.
     */
    protected function laravel(): Application
    {
        $app = $this->app;

        if ($app === null) {
            throw new RuntimeException('The Testbench application has not been booted.');
        }

        return $app;
    }

    /**
     * Read an HTTP response body as a string (getContent() is string|false).
     */
    protected function responseBody(Response $response): string
    {
        $content = $response->getContent();

        return $content === false ? '' : $content;
    }
}
