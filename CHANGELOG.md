# Changelog

All notable changes to `fahad/laravel-qrcode` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial package scaffolding: Composer manifest with PSR-4 namespace `Fahad\QrCode\`,
  source/test directory layout, PHPUnit, PHPStan, Pint and GitHub Actions CI
  (PHP 8.2–8.4 × Laravel 10–12).
- Laravel integration: service provider (container binding, config publishing,
  package auto-discovery), `QrCode` facade, publishable `config/qrcode.php`.
- Fluent public API: `QrCode::make($data)->size()->margin()->format()->errorCorrection()->generate()`
  with `svg` and `png` output.
- Framework-agnostic QR engine (no Laravel dependency, no third-party QR library):
  - `Encoding`: `BitBuffer`, `Encoder`/`AbstractEncoder`/`ByteEncoder`, `DataEncoder`
    (mode indicator, 8-bit character count, byte data, terminator, bit padding,
    `0xEC`/`0x11` pad codewords).
  - `ErrorCorrection`: `GaloisField` (GF(2^8), polynomial `0x11D`),
    `ReedSolomon` (generator polynomial, systematic encoding, block interleaving),
    `ErrorCorrectionLevel` (Version 1 codeword tables for L/M/Q/H).
  - `Matrix`: `QrMatrix` (module grid + function-module tracking), `FinderPattern`,
    `TimingPattern`, `FormatInformation` (BCH(15,5), XOR `0x5412`, both copies
    and the dark module), `DataPlacer` (spec zigzag order), `Mask` + `MaskPattern`
    (all eight masks, penalty rules N1–N4) and the `MatrixBuilder` pipeline.
  - `Renderer`: `Renderer` contract, `SvgRenderer`, `PngRenderer`.
- Version 1 (21×21) only, byte mode, UTF-8 input, error correction levels L/M/Q/H.
- 61 unit tests covering every engine component plus payloads, format bits,
  pattern placement, penalty scoring and a full matrix round-trip back to the
  original codewords.
