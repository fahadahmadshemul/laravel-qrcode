<?php

declare(strict_types=1);

use Fahad\QrCode\Encoding\DataEncoder;
use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\ErrorCorrection\ErrorCorrectionLevel;
use Fahad\QrCode\ErrorCorrection\ReedSolomon;
use Fahad\QrCode\Matrix\FormatInformation;
use Fahad\QrCode\Matrix\Mask;
use Fahad\QrCode\Matrix\MaskPattern;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Matrix\VersionTable;
use Fahad\QrCode\QrCode;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\SvgRenderer;

require __DIR__.'/../vendor/autoload.php';

/**
 * Dependency-free micro-benchmark harness for the QR engine hot paths.
 *
 * Run with `composer bench` or `php benchmarks/generate.php`. Each phase is
 * warmed up, then timed over a version-scaled iteration count with hrtime().
 * Payloads use lowercase bytes so auto-detection forces Byte mode, which makes
 * the version targeting below deterministic at ECC level L.
 */
const ECC = 'L';
const RENDER_SIZE = 512;
const RENDER_MARGIN = 4;

/** Payload byte lengths that land exactly on each target version (Byte mode, ECC-L). */
$targets = [
    'Version 1' => ['len' => 10, 'iters' => 400],
    'Version 10' => ['len' => 250, 'iters' => 150],
    'Version 20' => ['len' => 820, 'iters' => 60],
    'Version 40' => ['len' => 2900, 'iters' => 25],
];

/**
 * Time a closure over $iterations runs (after a short warmup) and return the
 * mean wall time in milliseconds plus the derived ops/sec.
 *
 * @return array{mean_ms: float, ops: float}
 */
function bench(callable $fn, int $iterations): array
{
    for ($i = 0; $i < 3; $i++) {
        $fn();
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsedNs = hrtime(true) - $start;

    $meanMs = ($elapsedNs / $iterations) / 1e6;

    return ['mean_ms' => $meanMs, 'ops' => $meanMs > 0.0 ? 1000.0 / $meanMs : INF];
}

$gd = extension_loaded('gd');

printf(
    "QR generator benchmark  |  PHP %s  |  GD %s  |  ECC %s  |  render %dpx\n",
    PHP_VERSION,
    $gd ? 'yes' : 'no (PNG skipped)',
    ECC,
    RENDER_SIZE
);
printf("%-11s  %-16s  %12s  %12s\n", 'Version', 'Phase', 'mean (ms)', 'ops/sec');
echo str_repeat('-', 56)."\n";

$eccLevel = ErrorCorrectionLevel::fromName(ECC);

foreach ($targets as $label => $config) {
    $data = str_repeat('a', $config['len']);
    $iterations = $config['iters'];

    $spec = VersionTable::forPayload($data, $eccLevel, EncodingMode::Byte, false);

    // Pre-built artefacts so render/score phases measure only their own work.
    $codewords = (new DataEncoder)->encode($data, ECC, $spec, EncodingMode::Byte, false);
    $matrix = (new MatrixBuilder)->build($data, ECC, EncodingMode::Byte, false);
    $blockSpec = $spec->eccSpec($eccLevel);

    $phases = [
        'encode' => static fn () => (new DataEncoder)->encode($data, ECC, $spec, EncodingMode::Byte, false),
        'reed-solomon' => static function () use ($blockSpec, $codewords): void {
            $rs = new ReedSolomon;
            $offset = 0;
            foreach ($blockSpec->blockDataCounts() as $count) {
                $rs->encodeBlock(array_slice($codewords, $offset, $count), $blockSpec->eccPerBlock);
                $offset += $count;
            }
        },
        'matrix-build' => static fn () => (new MatrixBuilder)->build($data, ECC, EncodingMode::Byte, false),
        'mask-score x8' => static function () use ($matrix, $eccLevel): void {
            foreach (MaskPattern::cases() as $pattern) {
                $candidate = clone $matrix;
                (new Mask($candidate))->apply($pattern);
                (new FormatInformation($candidate))->place($eccLevel, $pattern);
                (new Mask($candidate))->score();
            }
        },
        'render:svg' => static fn () => (new SvgRenderer)->render($matrix, RENDER_SIZE, RENDER_MARGIN),
        'generate:svg' => static fn () => QrCode::make($data)->mode('byte')->errorCorrection(ECC)->svg()->size(RENDER_SIZE)->generate(),
    ];

    if ($gd) {
        $phases['render:png'] = static fn () => (new PngRenderer)->render($matrix, RENDER_SIZE, RENDER_MARGIN);
    }

    printf("%-11s  (resolved v%d, %d modules)\n", $label, $spec->number, $spec->size);

    foreach ($phases as $phaseLabel => $fn) {
        $result = bench($fn, $iterations);
        printf("%-11s  %-16s  %12.4f  %12.1f\n", '', $phaseLabel, $result['mean_ms'], $result['ops']);
    }

    echo str_repeat('-', 56)."\n";
}
