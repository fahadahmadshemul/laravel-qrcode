<?php

declare(strict_types=1);

namespace Fahad\QrCode\Tests\Unit;

use Fahad\QrCode\Exceptions\FileWriteException;
use Fahad\QrCode\QrCode;
use PHPUnit\Framework\TestCase;

final class FileSaveTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/qrcode_tests_' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
    }

    public function test_saves_svg_file_and_creates_parent_directory(): void
    {
        $filePath = $this->tempDir . '/app/public/qrcode.svg';

        $builder = QrCode::make('http://a.co')
            ->svg();

        $returned = $builder->save($filePath);

        $this->assertSame($builder, $returned, 'save() must return current QrCode instance for fluent usage');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        $this->assertStringStartsWith('<?xml version="1.0"', $content);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $content);
    }

    public function test_saves_png_file_and_creates_parent_directory(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PNG testing.');
        }

        $filePath = $this->tempDir . '/storage/downloads/qrcode.png';

        $builder = QrCode::make('http://a.co')
            ->png();

        $returned = $builder->save($filePath);

        $this->assertSame($builder, $returned);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($content, 0, 8));
    }

    public function test_write_failure_throws_file_write_exception(): void
    {
        // Try saving to an invalid path that cannot be created or written (e.g. filename as directory)
        $invalidDirFile = $this->tempDir . '/invalid_dir_file';
        mkdir($this->tempDir, 0755, true);
        touch($invalidDirFile);

        $invalidPath = $invalidDirFile . '/cannot_create/qrcode.svg';

        $this->expectException(FileWriteException::class);
        QrCode::make('http://a.co')->save($invalidPath);
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
