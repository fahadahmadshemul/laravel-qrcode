<?php

declare(strict_types=1);

namespace Fahad\QrCode;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class QrCodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/qrcode.php', 'qrcode');

        $this->app->singleton('qrcode', fn (): QrCode => new QrCode(
            $this->app->make('config')->get('qrcode', [])
        ));

        $this->app->alias('qrcode', QrCode::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/qrcode.php' => config_path('qrcode.php'),
            ], 'qrcode-config');
        }

        $this->registerBladeDirective();
    }

    protected function registerBladeDirective(): void
    {
        if (class_exists(Blade::class)) {
            Blade::directive('qrcode', function (string $expression): string {
                return "<?php echo \\Fahad\\QrCode\\Facades\\QrCode::renderFromBlade({$expression}); ?>";
            });
        }
    }

    /**
     * Facade alias registered automatically by Laravel package discovery.
     *
     * @return array<string, class-string>
     */
    public static function packageAliases(): array
    {
        return ['QrCode' => Facades\QrCode::class];
    }
}
