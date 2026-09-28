# Laravel QrCode

A self-contained QR code generator for Laravel. The entire QR encoding
pipeline — data encoding, Reed–Solomon error correction, matrix construction,
masking and rendering — is implemented from scratch in this package. It has
**no third-party QR libraries** in its runtime dependencies.

## Features

- Pure-PHP QR engine written from scratch (ISO/IEC 18004), no external QR
  dependencies at runtime.
- SVG output (always available) and PNG output (via the GD extension).
- Full QR **Version 1–40** support with automatic version selection.
- All four error correction levels: **L, M, Q, H**.
- Numeric, alphanumeric and byte encoding modes, with automatic mode detection.
- UTF-8 ECI support so multibyte payloads (e.g. Bangla, Arabic, CJK, emoji)
  decode back to the original text.
- Fluent, chainable builder with a Laravel facade.
- Configurable size, quiet-zone margin, and foreground/background colors
  (including a transparent background).
- Save to a file, return a Base64 / data-URI string, or return an HTTP response.
- `@qrcode` Blade directive.
- Laravel auto-discovery; supports Laravel 9 through 13.

## Requirements

- PHP **8.2** or higher
- `illuminate/support` `^9.0 | ^10.0 | ^11.0 | ^12.0 | ^13.0`
- **ext-gd** — only required for PNG output. SVG works without it.

## Installation

```bash
composer require fahadahmadshemul/laravel-qrcode
```

## Laravel auto-discovery

The package registers itself automatically through Laravel's package
discovery — no manual setup is required. It provides:

- Service provider: `Fahad\QrCode\QrCodeServiceProvider`
- Facade alias: `QrCode` → `Fahad\QrCode\Facades\QrCode`

To customize the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=qrcode-config
```

This publishes `config/qrcode.php` (size, margin, format, error correction,
and ECI defaults).

## Basic usage

Use the facade and chain builder methods, then call `generate()`:

```php
use Fahad\QrCode\Facades\QrCode;

$svg = QrCode::make('https://example.com')->generate();
```

The builder is also directly instantiable (and container-resolvable) without
the facade:

```php
use Fahad\QrCode\QrCode;

$svg = (new QrCode)->make('https://example.com')->generate();
```

Because the builder implements `Stringable`, casting it to a string generates
the code as well:

```php
$svg = (string) QrCode::make('https://example.com');
```

## SVG generation

SVG is the default format. Output is a compact, standalone XML document using a
single `<path>` element for all modules, with `shape-rendering="crispEdges"`.

```php
$svg = QrCode::make('https://example.com')
    ->format('svg')   // or ->svg()
    ->size(300)
    ->generate();
```

## PNG generation

PNG requires the GD extension. The requested pixel size is rounded down to a
whole number of modules, and rendered dimensions are capped at 5000px to
prevent excessive memory allocation.

```php
$png = QrCode::make('https://example.com')
    ->format('png')   // or ->png()
    ->size(512)
    ->generate();
```

## Error correction

Choose a level with `errorCorrection()`. Higher levels tolerate more damage at
the cost of larger symbols. An invalid level throws
`InvalidErrorCorrectionLevelException`.

| Level | Recovery |
|-------|----------|
| `L`   | ~7%      |
| `M`   | ~15% (default) |
| `Q`   | ~25%     |
| `H`   | ~30%     |

```php
$svg = QrCode::make('https://example.com')
    ->errorCorrection('H')
    ->generate();
```

## Size and margin

`size()` sets the image side length in pixels; `margin()` sets the quiet-zone
width measured in modules (default 4).

```php
$svg = QrCode::make('https://example.com')
    ->size(400)
    ->margin(2)
    ->generate();
```

## Colors

Set foreground and background colors independently, or both at once with
`color()`. Hex codes and a set of named colors (black, white, red, green,
blue, yellow, cyan, magenta, gray/grey) are supported for both formats; SVG
additionally accepts any color string valid as an SVG `fill`. A background of
`transparent` (or `none`) omits the background entirely.

```php
QrCode::make('https://example.com')
    ->foregroundColor('#1a1a2e')     // or ->foreground(...)
    ->backgroundColor('#ffffff')     // or ->background(...)
    ->generate();

// Shorthand: foreground, then optional background
QrCode::make('https://example.com')->color('#000000', 'transparent')->generate();
```

## File saving

`save()` writes the rendered code to disk, creating parent directories as
needed. It throws `FileWriteException` on failure.

```php
QrCode::make('https://example.com')
    ->png()
    ->save(storage_path('app/qrcodes/example.png'));
```

## Base64

`base64()` returns the encoded code. By default it prefixes a data URI (using
the format's content type); pass `false` for the raw Base64 string.

```php
$dataUri = QrCode::make('https://example.com')->png()->base64();
// data:image/png;base64,iVBORw0KGgo...

$raw = QrCode::make('https://example.com')->svg()->base64(false);
```

## HTTP response

`response()` returns an `Illuminate\Http\Response` with the correct
`Content-Type`. The builder also implements `Responsable`, so you can return it
directly from a controller.

```php
use Fahad\QrCode\Facades\QrCode;

Route::get('/qr', function () {
    return QrCode::make('https://example.com')->png()->response();
});

// Or, relying on the Responsable contract:
Route::get('/qr', fn () => QrCode::make('https://example.com')->svg());
```

## Blade directive

The `@qrcode` directive renders a QR code inline (best suited to SVG). It
accepts the payload and an optional options array.

```blade
@qrcode('https://example.com')

@qrcode($url, ['size' => 400, 'margin' => 2, 'error_correction' => 'H'])
```

Supported option keys: `size`, `margin`, `format`, `error_correction`,
`foreground_color` / `color`, `background_color` / `background`,
`encoding_mode` / `mode`, and `eci`.

## Supported QR versions

All **40 versions** (1 through 40) defined by ISO/IEC 18004 are supported,
from a 21×21 module symbol (Version 1) up to 177×177 (Version 40). The
smallest version that fits the payload at the chosen error correction level is
selected automatically. Payloads exceeding Version 40 capacity throw
`QrCodeOverflowException`.

## Supported encoding modes

Set the mode with `encodingMode()` (alias `mode()`), or leave it on `auto`:

- **Numeric** — digits `0–9`.
- **Alphanumeric** — digits, uppercase `A–Z`, space, and `$ % * + - . / :`.
- **Byte** — any binary / UTF-8 string.
- **Auto** (default) — detects the most efficient valid mode for the payload.

```php
QrCode::make('12345')->mode('numeric')->generate();
```

For multibyte byte-mode payloads, a UTF-8 ECI header is declared automatically.
Control it with `eci(true|false|null)`, where `null` is automatic.

## Testing

```bash
composer test          # PHPUnit test suite
composer analyse       # PHPStan (level 8)
composer format        # Laravel Pint (auto-fix)
composer format:test   # Laravel Pint (check only)
```

The suite covers the encoding pipeline, error correction, every version/ECC
combination, rendering, and the public API. Integration tests decode the
generated codes with a third-party decoder (a dev-only dependency) to confirm
they are readable.

## Architecture

The QR engine is framework-agnostic and organized into focused namespaces:

- `Fahad\QrCode\Encoding` — segment building, bit buffers, and mode encoders.
- `Fahad\QrCode\ErrorCorrection` — Galois field arithmetic and Reed–Solomon
  error-correction coding.
- `Fahad\QrCode\Matrix` — version table, finder/alignment/timing patterns,
  format/version information, data placement, and mask-pattern selection.
- `Fahad\QrCode\Renderer` — the `SvgRenderer` and `PngRenderer`.

`Fahad\QrCode\QrCode` is the fluent builder that collects options and delegates
to the engine; no algorithm code lives in it. The Laravel integration
(service provider, facade, Blade directive, config) is a thin layer over that
engine.

## License

Released under the MIT License.
