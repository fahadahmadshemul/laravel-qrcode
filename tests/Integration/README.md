# Integration testing strategy: decode verification

These tests answer one question the unit tests cannot: **can the QR codes this
package generates actually be decoded back to the original input?** A
well-formed SVG or PNG is not evidence of correctness — the module data inside
it has to be right. So every fixture is generated through the *production*
pipeline and then decoded, and the decoded bytes are compared against the exact
input.

## Two decoders, on purpose

| Tier | Decoder | Reads from | Covers |
|------|---------|-----------|--------|
| Independent | `khanamiryan/qrcode-detector-decoder` (a ZXing port) | the rendered **PNG** | short text, URLs, all Unicode scripts, max Version 1, every ECC level, versions 1–8 |
| Full-matrix | in-repo `QrStreamDecoder` + `MatrixCodewordReader` | the rendered **module matrix** | everything above **plus** high-density symbols up to Version 40 |

Why both:

- The **independent** decoder ([`QrCodeZxingDecodeTest`](QrCodeZxingDecodeTest.php))
  is foreign code. If it reads our PNG, the symbol is genuinely conformant, not
  merely self-consistent. It also independently proves the **UTF-8 ECI**
  handling: the reader honours the ECI header and returns proper UTF-8, so a
  passing Bangla/Arabic/CJK/emoji fixture confirms the declaration is correct.
- The **full-matrix** decoder ([`QrCodeMatrixDecodeTest`](QrCodeMatrixDecodeTest.php))
  is deterministic and covers the entire version range. It reads the actual
  modules back out of the matrix (reverse mask + zig-zag + de-interleave), so it
  is far stronger than any "looks like a PNG" check, and it reaches the dense,
  high-version codes the pure-PHP ZXing port cannot.

### Why the independent decoder does not cover every fixture

The bundled ZXing port is a pure-PHP reader and is not as robust as a hardware
scanner or the reference Java implementation: on dense / high-version symbols it
frequently fails to *locate* the code and returns "not found". This is a
limitation of that reader, **not** of the generator — the in-repo matrix decoder
reads those same symbols back correctly. To keep the independent tier
deterministic (never flaky), its fixture set is restricted to the versions the
port reads reliably; the rest are verified in the full-matrix tier.

## The decoder dependency

- Package: **`khanamiryan/qrcode-detector-decoder`**, declared under
  `require-dev` in `composer.json`. It is a **test-only** dependency.
- **The production QR engine never imports or calls a decoder.** The engine
  (the `Fahad\QrCode` namespace) generates codes from scratch. All contact with
  the decoder is funnelled through a single test helper,
  [`tests/Support/ZxingDecoder.php`](../Support/ZxingDecoder.php), so the
  boundary is easy to audit — `grep -r Zxing src/` returns nothing.
- It needs the **GD** (or Imagick) extension to rasterise the PNG. When neither
  the package nor an image extension is present, the independent tests skip
  cleanly instead of failing.

## Fixtures

All fixtures live in one catalogue, [`tests/Support/QrFixtures.php`](../Support/QrFixtures.php),
as [`QrFixture`](../Support/QrFixture.php) value objects carrying the payload,
ECC level, expected version, and (for Unicode) the expected ECI state. Expected
versions are pinned so the fixtures also guard against capacity-selection
regressions.

## Running

```bash
composer install                       # pulls the dev-only decoder
php vendor/bin/phpunit --testsuite Integration
```
