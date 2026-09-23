<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit\Encoding;

use Fahad\QrCode\Encoding\EncodingMode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EncodingModeTest extends TestCase
{
    public function test_auto_detects_numeric_mode(): void
    {
        $this->assertSame(EncodingMode::Numeric, EncodingMode::detect('1234567890'));
        $this->assertSame(EncodingMode::Numeric, EncodingMode::detect('0'));
        $this->assertSame(EncodingMode::Numeric, EncodingMode::detect('999999'));
    }

    public function test_auto_detects_alphanumeric_mode(): void
    {
        $this->assertSame(EncodingMode::Alphanumeric, EncodingMode::detect('HELLO WORLD'));
        $this->assertSame(EncodingMode::Alphanumeric, EncodingMode::detect('QR CODE 123'));
        $this->assertSame(EncodingMode::Alphanumeric, EncodingMode::detect('$100.00%'));
        $this->assertSame(EncodingMode::Alphanumeric, EncodingMode::detect('A-B+C*D/E:F'));
    }

    public function test_auto_detects_byte_mode(): void
    {
        $this->assertSame(EncodingMode::Byte, EncodingMode::detect('Hello World')); // lowercase 'e'
        $this->assertSame(EncodingMode::Byte, EncodingMode::detect('Hello বাংলা'));
        $this->assertSame(EncodingMode::Byte, EncodingMode::detect('emoji 🚀'));
        $this->assertSame(EncodingMode::Byte, EncodingMode::detect('test@example.com')); // '@' is not alphanumeric in QR
    }

    public function test_mode_indicators(): void
    {
        $this->assertSame(0b0001, EncodingMode::Numeric->modeIndicator());
        $this->assertSame(0b0010, EncodingMode::Alphanumeric->modeIndicator());
        $this->assertSame(0b0100, EncodingMode::Byte->modeIndicator());
        $this->assertSame(0b0100, EncodingMode::Auto->modeIndicator());
    }

    public function test_resolve_string_and_enum(): void
    {
        $this->assertSame(EncodingMode::Numeric, EncodingMode::resolve('numeric'));
        $this->assertSame(EncodingMode::Alphanumeric, EncodingMode::resolve('ALPHANUMERIC'));
        $this->assertSame(EncodingMode::Byte, EncodingMode::resolve('byte'));
        $this->assertSame(EncodingMode::Numeric, EncodingMode::resolve('auto', '123'));
        $this->assertSame(EncodingMode::Numeric, EncodingMode::resolve(EncodingMode::Numeric));
    }

    public function test_resolve_throws_for_invalid_mode_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported encoding mode "invalid"/');

        EncodingMode::resolve('invalid');
    }

    public function test_validate_payload_for_numeric_mode(): void
    {
        EncodingMode::Numeric->validatePayload('123456');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Numeric encoding mode/');

        EncodingMode::Numeric->validatePayload('123A');
    }

    public function test_validate_payload_for_alphanumeric_mode(): void
    {
        EncodingMode::Alphanumeric->validatePayload('HELLO 123');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Alphanumeric encoding mode/');

        EncodingMode::Alphanumeric->validatePayload('hello'); // lowercase invalid
    }

    public function test_validate_payload_for_byte_mode(): void
    {
        EncodingMode::Byte->validatePayload('Anything goes 123! @#$ বাংলা');
        $this->assertTrue(true);
    }
}
