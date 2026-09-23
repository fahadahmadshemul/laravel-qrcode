<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\QrCodeServiceProvider;
use Illuminate\Support\Facades\Blade;
use Orchestra\Testbench\TestCase;

final class BladeDirectiveTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            QrCodeServiceProvider::class,
        ];
    }

    public function test_blade_directive_is_registered(): void
    {
        $directives = Blade::getCustomDirectives();
        $this->assertArrayHasKey('qrcode', $directives);
    }

    public function test_renders_blade_directive_with_single_argument(): void
    {
        $compiled = Blade::compileString("@qrcode('http://a.co')");
        $this->assertStringContainsString('renderFromBlade(\'http://a.co\')', $compiled);

        $rendered = Blade::render("@qrcode('http://a.co')");
        $this->assertStringStartsWith('<?xml version="1.0"', $rendered);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $rendered);
    }

    public function test_renders_blade_directive_with_options_array(): void
    {
        $rendered = Blade::render("@qrcode('http://a.co', ['size' => 400, 'margin' => 2])");
        $this->assertStringStartsWith('<?xml version="1.0"', $rendered);
        $this->assertStringContainsString('width="400"', $rendered);
        $this->assertStringContainsString('viewBox="0 0 25 25"', $rendered);
    }

    public function test_renders_blade_directive_with_variable_expression(): void
    {
        $rendered = Blade::render("@qrcode(\$url, ['size' => 500])", ['url' => 'http://a.co']);
        $this->assertStringStartsWith('<?xml version="1.0"', $rendered);
        $this->assertStringContainsString('width="500"', $rendered);
    }
}
