<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Exceptions;

use Fahad\QrCode\Exceptions\FileWriteException;
use Fahad\QrCode\Exceptions\InvalidErrorCorrectionLevelException;
use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\QrCodeOverflowException;
use Fahad\QrCode\Exceptions\RenderException;
use Fahad\QrCode\Exceptions\UnsupportedFormatException;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\SvgRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Guards the package exception contract: every failure the package raises must
 * be catchable through the single {@see QrCodeException} base, while remaining a
 * {@see RuntimeException} for callers that catch the SPL type.
 */
final class ExceptionHierarchyTest extends TestCase
{
    /**
     * @return array<string, array{class-string<\Throwable>}>
     */
    public static function packageExceptionProvider(): array
    {
        return [
            'overflow' => [QrCodeOverflowException::class],
            'file-write' => [FileWriteException::class],
            'invalid-ecc' => [InvalidErrorCorrectionLevelException::class],
            'unsupported-format' => [UnsupportedFormatException::class],
            'render' => [RenderException::class],
        ];
    }

    /**
     * @dataProvider packageExceptionProvider
     *
     * @param  class-string<\Throwable>  $exceptionClass
     */
    public function test_every_package_exception_extends_the_base(string $exceptionClass): void
    {
        $this->assertTrue(
            is_subclass_of($exceptionClass, QrCodeException::class),
            "{$exceptionClass} must extend QrCodeException so callers can catch it via the base."
        );
    }

    public function test_base_exception_is_a_runtime_exception(): void
    {
        // Keeps `catch (RuntimeException)` handlers working across the hierarchy.
        // Read the ancestry reflectively so the guard survives a base-class change.
        $ancestors = class_parents(QrCodeException::class);

        $this->assertNotFalse($ancestors);
        $this->assertContains(RuntimeException::class, $ancestors);
    }

    public function test_svg_renderer_invalid_size_is_catchable_as_package_exception(): void
    {
        $matrix = (new MatrixBuilder)->build('HELLO', 'L');

        try {
            (new SvgRenderer)->render($matrix, 0, 4);
            $this->fail('SvgRenderer accepted a non-positive size.');
        } catch (QrCodeException $e) {
            $this->assertInstanceOf(RenderException::class, $e);
        }
    }

    public function test_png_renderer_failure_is_catchable_as_package_exception(): void
    {
        $matrix = (new MatrixBuilder)->build('HELLO', 'L');

        // Whether or not GD is loaded, a failure here (missing GD, or a size too
        // small to fit one pixel per module) must surface as a QrCodeException.
        $this->expectException(QrCodeException::class);
        (new PngRenderer)->render($matrix, 5, 4);
    }
}
