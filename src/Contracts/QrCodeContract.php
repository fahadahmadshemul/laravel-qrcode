<?php

declare(strict_types=1);

namespace Fahad\QrCode\Contracts;

interface QrCodeContract
{
    /**
     * Set the payload to encode.
     */
    public function data(string $data): static;

    /**
     * Set the size of the resulting image in pixels.
     */
    public function size(int $size): static;

    /**
     * Set the quiet-zone margin in modules.
     */
    public function margin(int $margin): static;

    /**
     * Set the output format, e.g. "svg" or "png".
     */
    public function format(string $format): static;

    /**
     * Set the error correction level: L, M, Q or H.
     */
    public function errorCorrection(string $level): static;

    /**
     * Generate the QR code and return the rendered output.
     */
    public function generate(): string;
}
