<?php

declare(strict_types=1);

namespace Fahad\QrCode\Exceptions;

/**
 * Raised when a renderer cannot produce output for an otherwise valid matrix:
 * an invalid target size or margin, a missing GD extension, or a failed GD
 * allocation.
 *
 * Extends {@see QrCodeException} so that callers can catch every failure the
 * package raises — including rendering failures — through the one package base
 * exception. It remains a {@see \RuntimeException} as well, so existing
 * `catch (RuntimeException)` handlers keep working.
 */
final class RenderException extends QrCodeException {}
