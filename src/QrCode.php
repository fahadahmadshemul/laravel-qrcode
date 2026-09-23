<?php

declare(strict_types=1);

namespace Fahad\QrCode;

use Fahad\QrCode\Exceptions\InvalidErrorCorrectionLevelException;
use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\UnsupportedFormatException;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\Renderer;
use Fahad\QrCode\Renderer\SvgRenderer;

/**
 * Fluent QR code builder.
 *
 * Collects generation options and delegates to the framework-agnostic QR
 * engine (Encoding / ErrorCorrection / Matrix / Renderer namespaces) on
 * {@see QrCode::generate()}. No algorithm code lives in this class.
 *
 * @phpstan-consistent-constructor
 */
class QrCode
{
    /**
     * Supported error correction levels, ordered by recovery capability.
     */
    public const ERROR_CORRECTION_LEVELS = ['L', 'M', 'Q', 'H'];

    /**
     * Supported output formats.
     */
    public const FORMATS = ['svg', 'png'];

    protected string $data = '';

    protected int $size;

    protected int $margin;

    protected string $format;

    protected string $errorCorrection;

    /**
     * Create a new builder pre-filled with the package defaults.
     *
     * @param  array{size?: int, margin?: int, format?: string, error_correction?: string}  $config
     */
    public function __construct(array $config = [])
    {
        $this->size = (int) ($config['size'] ?? 300);
        $this->margin = (int) ($config['margin'] ?? 4);
        $this->format = (string) ($config['format'] ?? 'svg');
        $this->errorCorrection = (string) ($config['error_correction'] ?? 'M');
    }

    /**
     * Begin building a QR code for the given payload.
     */
    public static function make(string $data): static
    {
        return (new static)->data($data);
    }

    /**
     * Set the payload to encode.
     */
    public function data(string $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the size of the resulting image in pixels.
     */
    public function size(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Set the quiet-zone margin around the QR code, expressed in modules.
     */
    public function margin(int $margin): static
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * Set the output format, e.g. "svg" or "png".
     */
    public function format(string $format): static
    {
        $this->format = strtolower($format);

        return $this;
    }

    /**
     * Set the error correction level: L (7%), M (15%), Q (25%) or H (30%).
     */
    public function errorCorrection(string $level): static
    {
        $level = strtoupper($level);

        if (! in_array($level, self::ERROR_CORRECTION_LEVELS, true)) {
            throw InvalidErrorCorrectionLevelException::forLevel($level);
        }

        $this->errorCorrection = $level;

        return $this;
    }

    /**
     * Generate the QR code and return the rendered output.
     *
     * @throws QrCodeException
     */
    public function generate(): string
    {
        if ($this->data === '') {
            throw QrCodeException::emptyData();
        }

        if (! in_array($this->format, self::FORMATS, true)) {
            throw UnsupportedFormatException::forFormat($this->format);
        }

        $matrix = (new MatrixBuilder)->build($this->data, $this->errorCorrectionLayer());
        $renderer = $this->renderer();

        return $renderer->render($matrix, $this->size, $this->margin);
    }

    private function errorCorrectionLayer(): string
    {
        return $this->errorCorrection;
    }

    private function renderer(): Renderer
    {
        return match ($this->format) {
            'png' => new PngRenderer,
            default => new SvgRenderer,
        };
    }

    /**
     * The prepared generation options.
     *
     * @return array{data: string, size: int, margin: int, format: string, error_correction: string}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'size' => $this->size,
            'margin' => $this->margin,
            'format' => $this->format,
            'error_correction' => $this->errorCorrection,
        ];
    }
}
