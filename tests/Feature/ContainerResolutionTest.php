<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Feature;

use Fahad\QrCode\QrCode;

/**
 * Verifies dependency injection and Laravel container resolution: the builder
 * can be resolved by its class name, by the "qrcode" binding, and via
 * constructor/method autowiring, and that the binding is a shared singleton.
 */
final class ContainerResolutionTest extends PackageTestCase
{
    public function test_resolves_by_class_name(): void
    {
        $this->assertInstanceOf(QrCode::class, $this->laravel()->make(QrCode::class));
    }

    public function test_resolves_by_binding_name(): void
    {
        $this->assertInstanceOf(QrCode::class, $this->laravel()->make('qrcode'));
    }

    public function test_binding_is_a_shared_singleton(): void
    {
        $this->assertSame(
            $this->laravel()->make('qrcode'),
            $this->laravel()->make('qrcode'),
            'The "qrcode" binding is registered as a singleton and must return the same instance.'
        );
    }

    public function test_class_alias_and_binding_resolve_to_the_same_instance(): void
    {
        $this->assertSame(
            $this->laravel()->make('qrcode'),
            $this->laravel()->make(QrCode::class),
            'Fahad\QrCode\QrCode is aliased to the "qrcode" singleton.'
        );
    }

    public function test_autowires_into_method_injection(): void
    {
        $resolved = $this->laravel()->call(fn (QrCode $qr): QrCode => $qr);

        $this->assertInstanceOf(QrCode::class, $resolved);
    }

    public function test_autowires_into_constructor_injection(): void
    {
        // Resolving QrCodeConsumer forces the container to build it, injecting
        // the QrCode builder through its constructor; the closure then confirms
        // the same singleton was wired in.
        $qr = $this->laravel()->call(
            fn (QrCodeConsumer $consumer): QrCode => $consumer->qr
        );

        $this->assertInstanceOf(QrCode::class, $qr);
        $this->assertSame($this->laravel()->make('qrcode'), $qr);
    }
}

/**
 * Minimal consumer used to prove constructor autowiring of the builder.
 *
 * @internal
 */
final class QrCodeConsumer
{
    public function __construct(public QrCode $qr) {}
}
