<?php

declare(strict_types=1);

namespace Fahad\QrCode;

use Fahad\QrCode\Encoding\EncodingMode;
use Fahad\QrCode\Exceptions\InvalidErrorCorrectionLevelException;
use Fahad\QrCode\Exceptions\QrCodeException;
use Fahad\QrCode\Exceptions\UnsupportedFormatException;
use Fahad\QrCode\Matrix\MatrixBuilder;
use Fahad\QrCode\Renderer\PngRenderer;
use Fahad\QrCode\Renderer\Renderer;
use Fahad\QrCode\Renderer\SvgRenderer;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Stringable;

/**
 * Fluent QR code builder.
 *
 * Collects generation options and delegates to the framework-agnostic QR
 * engine (Encoding / ErrorCorrection / Matrix / Renderer namespaces) on
 * {@see QrCode::generate()}. No algorithm code lives in this class.
 *
 * @phpstan-consistent-constructor
 */
class QrCode implements Responsable, Stringable
{
    /**
     * Supported error correction levels, ordered by recovery capability.
     */
    public const ERROR_CORRECTION_LEVELS = ['L', 'M', 'Q', 'H'];

    /**
     * Supported output formats.
     */
    public const FORMATS = ['svg', 'png'];

    /**
     * Default package configuration stored for static make() calls.
     *
     * @var array<string, mixed>
     */
    protected static array $defaultConfig = [];

    protected string $data = '';

    protected int $size;

    protected int $margin;

    protected string $format;

    protected string $errorCorrection;

    protected string $foregroundColor;

    protected string $backgroundColor;

    protected string $encodingMode;

    /**
     * Create a new builder pre-filled with the package defaults.
     *
     * @param  array{size?: int, margin?: int, format?: string, error_correction?: string, foreground_color?: string, background_color?: string, color?: string, background?: string}  $config
     */
    public function __construct(array $config = [])
    {
        if ($config !== []) {
            self::$defaultConfig = array_merge(self::$defaultConfig, $config);
        }

        $effectiveConfig = array_merge(self::$defaultConfig, $config);

        $this->size = (int) ($effectiveConfig['size'] ?? 300);
        $this->margin = (int) ($effectiveConfig['margin'] ?? 4);
        $this->format = (string) ($effectiveConfig['format'] ?? 'svg');
        $this->errorCorrection = (string) ($effectiveConfig['error_correction'] ?? 'M');
        $this->foregroundColor = (string) ($effectiveConfig['foreground_color'] ?? $effectiveConfig['color'] ?? '#000000');
        $this->backgroundColor = (string) ($effectiveConfig['background_color'] ?? $effectiveConfig['background'] ?? '#ffffff');
        $this->encodingMode = (string) ($effectiveConfig['encoding_mode'] ?? $effectiveConfig['mode'] ?? 'auto');
    }

    /**
     * Begin building a QR code for the given payload.
     * Can be invoked statically or on an instance.
     */
    public static function make(?string $data = null): static
    {
        $instance = new static(self::$defaultConfig);

        if ($data !== null) {
            $instance->data($data);
        }

        return $instance;
    }

    /**
     * Helper method to render a QR code from Blade directives.
     *
     * @param  string  $data
     * @param  array<string, mixed>  $options
     * @return string
     */
    public static function renderFromBlade(string $data, array $options = []): string
    {
        $builder = static::make($data);

        if (isset($options['size'])) {
            $builder->size((int) $options['size']);
        }

        if (isset($options['margin'])) {
            $builder->margin((int) $options['margin']);
        }

        if (isset($options['format'])) {
            $builder->format((string) $options['format']);
        }

        if (isset($options['error_correction'])) {
            $builder->errorCorrection((string) $options['error_correction']);
        }

        if (isset($options['foreground_color']) || isset($options['color'])) {
            $builder->foregroundColor((string) ($options['foreground_color'] ?? $options['color']));
        }

        if (isset($options['background_color']) || isset($options['background'])) {
            $builder->backgroundColor((string) ($options['background_color'] ?? $options['background']));
        }

        if (isset($options['encoding_mode']) || isset($options['mode'])) {
            $builder->encodingMode((string) ($options['encoding_mode'] ?? $options['mode']));
        }

        return $builder->generate();
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
     * Set the encoding mode: "numeric", "alphanumeric", "byte", or "auto".
     */
    public function encodingMode(string|EncodingMode $mode): static
    {
        if ($mode instanceof EncodingMode) {
            $this->encodingMode = $mode->value;
        } else {
            $this->encodingMode = strtolower(trim($mode));
        }

        return $this;
    }

    /**
     * Alias for encodingMode().
     */
    public function mode(string|EncodingMode $mode): static
    {
        return $this->encodingMode($mode);
    }

    /**
     * Set the foreground color (module color), e.g. "#000000", "black", "rgb(0,0,0)".
     */
    public function foregroundColor(string $color): static
    {
        $this->foregroundColor = $color;

        return $this;
    }

    /**
     * Alias for foregroundColor().
     */
    public function foreground(string $color): static
    {
        return $this->foregroundColor($color);
    }

    /**
     * Set the background color, e.g. "#ffffff", "white", "transparent".
     */
    public function backgroundColor(string $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    /**
     * Alias for backgroundColor().
     */
    public function background(string $color): static
    {
        return $this->backgroundColor($color);
    }

    /**
     * Set foreground and optional background colors.
     */
    public function color(string $foregroundColor, ?string $backgroundColor = null): static
    {
        $this->foregroundColor = $foregroundColor;

        if ($backgroundColor !== null) {
            $this->backgroundColor = $backgroundColor;
        }

        return $this;
    }

    /**
     * Set format to SVG and return builder instance.
     */
    public function svg(?string $data = null): static
    {
        if ($data !== null) {
            $this->data($data);
        }

        return $this->format('svg');
    }

    /**
     * Set format to PNG and return builder instance.
     */
    public function png(?string $data = null): static
    {
        if ($data !== null) {
            $this->data($data);
        }

        return $this->format('png');
    }

    /**
     * Generate the QR code and return the rendered output string.
     *
     * @throws QrCodeException
     */
    public function generate(?string $data = null): string
    {
        if ($data !== null) {
            $this->data($data);
        }

        if ($this->data === '') {
            throw QrCodeException::emptyData();
        }

        if (! in_array($this->format, self::FORMATS, true)) {
            throw UnsupportedFormatException::forFormat($this->format);
        }

        $matrix = (new MatrixBuilder)->build($this->data, $this->errorCorrectionLayer(), $this->encodingMode);
        $renderer = $this->renderer();

        return $renderer->render(
            $matrix,
            $this->size,
            $this->margin,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    /**
     * Save the rendered QR code to the specified file path.
     * Automatically creates parent directories when necessary.
     *
     * @param  string  $path
     * @return static
     * @throws Exceptions\FileWriteException|Exceptions\QrCodeException
     */
    public function save(string $path): static
    {
        $directory = dirname($path);

        if ($directory !== '' && $directory !== '.' && ! is_dir($directory)) {
            if (! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw Exceptions\FileWriteException::unableToCreateDirectory($directory);
            }
        }

        $content = $this->generate();

        if (@file_put_contents($path, $content) === false) {
            throw Exceptions\FileWriteException::unableToWriteFile($path);
        }

        return $this;
    }

    /**
     * Generate the QR code and return it encoded as a Base64 string or Data URI.
     *
     * @param  bool  $includeDataUri  Whether to prefix with data: URI scheme (e.g. data:image/png;base64,...).
     * @return string
     */
    public function base64(bool $includeDataUri = true): string
    {
        $content = $this->generate();
        $encoded = base64_encode($content);

        if ($includeDataUri) {
            return sprintf('data:%s;base64,%s', $this->contentType(), $encoded);
        }

        return $encoded;
    }

    /**
     * Create a Laravel HTTP response containing the rendered QR code.
     *
     * @param  int  $status
     * @param  array<string, string>  $headers
     * @return \Illuminate\Http\Response
     */
    public function response(int $status = 200, array $headers = []): Response
    {
        $content = $this->generate();

        $defaultHeaders = [
            'Content-Type' => $this->contentType(),
        ];

        return new Response(
            $content,
            $status,
            array_merge($defaultHeaders, $headers)
        );
    }

    /**
     * Get the HTTP Content-Type header value for the current format.
     */
    public function contentType(): string
    {
        return match ($this->format) {
            'png' => 'image/png',
            default => 'image/svg+xml',
        };
    }

    /**
     * Create an HTTP response that represents the object (Laravel Responsable contract).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request): Response
    {
        return $this->response();
    }

    /**
     * Convert the QR code to its rendered string representation.
     */
    public function __toString(): string
    {
        try {
            return $this->generate();
        } catch (\Throwable) {
            return '';
        }
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
     * @return array{data: string, size: int, margin: int, format: string, error_correction: string, foreground_color: string, background_color: string}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'size' => $this->size,
            'margin' => $this->margin,
            'format' => $this->format,
            'error_correction' => $this->errorCorrection,
            'foreground_color' => $this->foregroundColor,
            'background_color' => $this->backgroundColor,
            'encoding_mode' => $this->encodingMode,
        ];
    }
}
