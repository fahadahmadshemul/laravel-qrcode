<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Fahad\QrCode\ErrorCorrection\GaloisField;
use Fahad\QrCode\ErrorCorrection\ReedSolomon;

$field = new GaloisField;
$rs = new ReedSolomon($field);

$method = new ReflectionMethod(ReedSolomon::class, 'generator');
$method->setAccessible(true);

foreach ([7, 10, 13, 17] as $degree) {
    $generator = $method->invoke($rs, $degree);
    $logs = array_map(static fn (int $c): int => $c === 0 ? -1 : $field->log($c), $generator);
    echo "deg {$degree} alpha-exponents: " . implode(' ', $logs) . PHP_EOL;
    echo "deg {$degree} integer coeffs : " . implode(' ', $generator) . PHP_EOL;
}

// Known data block from the widely published "HELLO WORLD" 1-M example.
$data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];
$ecc = $rs->encodeBlock($data, 10);
echo 'ecc(10): ' . implode(' ', $ecc) . PHP_EOL;

// Syndrome verification for all four V1 EC levels.
foreach ([7, 10, 13, 17] as $n) {
    $block = array_slice(array_merge($data, [0, 0, 0, 0, 0, 0, 0]), 0, 19);
    $eccN = $rs->encodeBlock($block, $n);
    $values = array_map(static fn (int $i): int => $field->exponent($i), range(0, $n - 1));
    $syndromes = $field->evaluatePolynomial(array_merge($block, $eccN), $values);
    echo "level n={$n} syndromes zero: " . (array_filter($syndromes) === [] ? 'YES' : 'NO') . PHP_EOL;
}