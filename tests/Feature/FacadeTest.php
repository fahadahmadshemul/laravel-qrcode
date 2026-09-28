<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\Facades\QrCode as QrCodeFacade;
use Fahad\QrCode\QrCode;

/**
 * Verifies the QrCode facade resolves to the container-bound singleton and
 * exposes the fluent builder API across Laravel versions.
 */
final class FacadeTest extends PackageTestCase
{
    public function test_facade_resolves_to_a_qrcode_builder(): void
    {
        $this->assertInstanceOf(QrCode::class, QrCodeFacade::make('https://example.com'));
    }

    public function test_facade_root_is_the_bound_singleton(): void
    {
        $this->assertSame(
            $this->laravel()->make('qrcode'),
            QrCodeFacade::getFacadeRoot(),
            'The facade should proxy to the container-bound "qrcode" singleton.'
        );
    }

    public function test_facade_generates_svg_output(): void
    {
        $svg = QrCodeFacade::make('https://example.com')->svg()->generate();

        $this->assertStringStartsWith('<?xml version="1.0"', $svg);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
    }

    public function test_facade_fluent_options_are_honored(): void
    {
        $svg = QrCodeFacade::make('https://example.com')
            ->size(400)
            ->margin(2)
            ->svg()
            ->generate();

        $this->assertStringContainsString('width="400"', $svg);
    }
}
