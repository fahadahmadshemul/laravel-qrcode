<?php

declare(strict_types=1);

namespace Fahad\QrCode\ErrorCorrection;

use RuntimeException;

/**
 * GF(2^8) arithmetic with the QR-code primitive polynomial x^8 + x^4 + x^3
 * + x^2 + 1 (0x11D).
 *
 * Multiplication is table-driven via log/antilog tables. Tables are built
 * once per instance (~256 iterations).
 *
 * This class is part of the framework-agnostic QR engine and must not
 * depend on Laravel.
 */
final class GaloisField
{
    public const PRIMITIVE_POLYNOMIAL = 0x11D;

    private const FIELD_ORDER = 255;

    /** @var int[] Map of exponent => field element (α^i). */
    private array $exponentTable = [];

    /** @var int[] Map of field element => exponent. */
    private array $logTable = [];

    public function __construct()
    {
        $x = 1;

        for ($i = 0; $i < self::FIELD_ORDER; $i++) {
            $this->exponentTable[$i] = $x;
            $this->logTable[$x] = $i;

            $x <<= 1;

            if ($x >= 256) {
                $x ^= self::PRIMITIVE_POLYNOMIAL;
            }
        }

        // α^255 wraps around to α^0 for modular exponent arithmetic.
        $this->exponentTable[self::FIELD_ORDER] = $this->exponentTable[0];
    }

    /**
     * The field element α^i.
     */
    public function exponent(int $i): int
    {
        if ($i < 0 || $i > self::FIELD_ORDER) {
            throw new RuntimeException(sprintf('Exponent %d is out of range [0, 255].', $i));
        }

        return $this->exponentTable[$i];
    }

    /**
     * The exponent i such that α^i = $a.
     */
    public function log(int $a): int
    {
        if ($a <= 0 || $a > 255) {
            throw new RuntimeException(sprintf('Logarithm is undefined for %d in GF(256).', $a));
        }

        return $this->logTable[$a];
    }

    public function multiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return $this->exponentTable[($this->logTable[$a] + $this->logTable[$b]) % self::FIELD_ORDER];
    }

    /**
     * Evaluate a polynomial (coefficients highest degree first) at each of
     * the given field values, using Horner's method.
     *
     * @param  int[]  $coefficients
     * @param  int[]  $values
     * @return int[]
     */
    public function evaluatePolynomial(array $coefficients, array $values): array
    {
        $results = [];

        foreach ($values as $value) {
            $result = 0;

            foreach ($coefficients as $coefficient) {
                $result = $this->multiply($result, $value) ^ $coefficient;
            }

            $results[] = $result;
        }

        return $results;
    }
}
