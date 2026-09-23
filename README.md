# fahad/laravel-qrcode

A self-contained, production-quality QR code generator for Laravel.

The complete QR encoding engine — mode selection, data encoding, Reed–Solomon
error correction, matrix construction and masking — is implemented **inside
this package** with a clean OOP architecture. No third-party QR-code libraries
(such as `simplesoftwareio/simple-qrcode` or `endroid/qr-code`) are used.

> **Status:** scaffolding phase. This repository currently contains the package
> structure, QA tooling and CI. The QR engine, renderers and Laravel integration
> land in the upcoming milestones — see [Roadmap](#roadmap).

## Requirements

- PHP **8.2+**
- Laravel **10**, **11** or **12** (the QR engine itself is framework-agnostic)

## Installation

```bash
composer require fahad/laravel-qrcode
```

The service provider and `QrCode` facade are registered automatically through
Laravel package discovery. The configuration file can be published with:

```bash
php artisan vendor:publish --tag=qrcode-config
```

## Usage

```php
use Fahad\QrCode\Facades\QrCode;

// SVG markup (default format)
$svg = QrCode::make('https://example.com')
    ->size(300)
    ->margin(4)
    ->format('svg')
    ->errorCorrection('M')
    ->generate();

// PNG binary
$png = QrCode::make('https://example.com')
    ->format('png')
    ->size(290)
    ->generate();
```

Defaults live in `config/qrcode.php`: `size` (300), `margin` (4),
`format` (`svg`) and `error_correction` (`M`).

### Current scope

Version **1** QR symbols only (21×21 modules), byte mode, UTF-8 payloads and
all four error correction levels (L, M, Q, H). Version 1 byte-mode capacity is
17 bytes at level L, 14 at M, 11 at Q and 7 at H; larger payloads raise
`QrCodeOverflowException` until higher versions are implemented.

### Using the engine without Laravel

The QR engine has no Laravel dependency:

```php
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Renderer\SvgRenderer;

$matrix = (new MatrixBuilder)->build('https://example.com', 'M');
$svg = (new SvgRenderer)->render($matrix, 300, 4);
```

## Architecture

The package is deliberately split into small, single-responsibility classes —
no monolithic implementation:

| Namespace                   | Responsibility                                                                                |
|-----------------------------|-----------------------------------------------------------------------------------------------|
| `Fahad\QrCode\Contracts`    | Public builder contract.                                                                      |
| `Fahad\QrCode\Encoding`     | Bit buffer, mode encoders and the byte-mode data pipeline (mode, count, terminator, padding).  |
| `Fahad\QrCode\ErrorCorrection` | GF(2^8) arithmetic, Reed–Solomon ECC, Version 1 level tables.                              |
| `Fahad\QrCode\Matrix`       | Matrix storage, finder/timing/format placement, data placement, masking, assembly pipeline.    |
| `Fahad\QrCode\Renderer`     | Output targets: SVG and PNG.                                                                  |
| `Fahad\QrCode\Exceptions`   | Package exception hierarchy.                                                                  |
| `Fahad\QrCode\Facades`      | Laravel facade.                                                                               |
| `Fahad\QrCode`              | Service provider and fluent `QrCode` entry point (no algorithm code).                          |

## Development

```bash
composer install

composer test          # PHPUnit
composer analyse       # PHPStan (level 8)
composer format        # Laravel Pint (fix coding style)
composer format:test   # Laravel Pint (dry run)
```

## Testing matrix

CI (GitHub Actions) runs the test suite on **PHP 8.2 / 8.3 / 8.4** against
**Laravel 10 / 11 / 12** using [Orchestra Testbench](https://github.com/orchestral/testbench).

## Roadmap

1. ✅ Package scaffolding: `composer.json`, PSR-4 layout, QA tooling, CI.
2. ⬜ Core contracts and value objects (`Contracts`, `Data`).
3. ⬜ QR encoder: analysis, encoding, Reed–Solomon ECC, masking, matrix assembly.
4. ⬜ Renderers (SVG first, then PNG).
5. ⬜ Laravel integration: service provider, facade, publishable config.

## License

MIT — see [LICENSE](LICENSE).
