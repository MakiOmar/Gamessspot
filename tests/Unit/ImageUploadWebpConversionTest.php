<?php

namespace Tests\Unit;

use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageUploadWebpConversionTest extends TestCase
{
    private string $tempPublic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPublic = storage_path('framework/testing/webp_' . uniqid());
        mkdir($this->tempPublic . '/assets/uploads/galleries', 0777, true);
        mkdir($this->tempPublic . '/assets/ps4', 0777, true);

        $this->app->usePublicPath($this->tempPublic);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPublic);
        parent::tearDown();
    }

    public function test_upload_converts_png_to_webp(): void
    {
        $service = new ImageUploadService();
        $this->assertTrue($service->canEncodeWebp());

        $png = $this->makePngUploadedFile();
        $path = $service->upload($png, 'galleries');

        $this->assertStringEndsWith('.webp', $path);
        $this->assertFileExists($this->tempPublic . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
        $this->assertStringStartsWith('assets/uploads/galleries/', $path);
    }

    public function test_convert_existing_public_image_updates_extension(): void
    {
        $service = new ImageUploadService();
        $sourceRelative = 'assets/ps4/sample_cover.png';
        $sourceAbsolute = $this->tempPublic . '/assets/ps4/sample_cover.png';
        imagepng(imagecreatetruecolor(10, 10), $sourceAbsolute);

        $newPath = $service->convertExistingPublicImage($sourceRelative, true);

        $this->assertSame('assets/ps4/sample_cover.webp', $newPath);
        $this->assertFileExists($this->tempPublic . '/assets/ps4/sample_cover.webp');
        $this->assertFileDoesNotExist($sourceAbsolute);
    }

    public function test_svg_upload_is_not_forced_to_webp(): void
    {
        $service = new ImageUploadService();
        $svgPath = $this->tempPublic . '/tmp.svg';
        file_put_contents($svgPath, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');

        $upload = new UploadedFile($svgPath, 'icon.svg', 'image/svg+xml', null, true);
        $path = $service->upload($upload, 'galleries');

        $this->assertStringEndsWith('.svg', $path);
        $this->assertFileExists($this->tempPublic . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    }

    public function test_interlaced_png_converts_to_webp_quietly(): void
    {
        $service = new ImageUploadService();
        $sourceAbsolute = $this->tempPublic . '/assets/ps4/interlaced_cover.png';

        $img = imagecreatetruecolor(12, 12);
        imageinterlace($img, true);
        imagepng($img, $sourceAbsolute);
        imagedestroy($img);

        $this->assertTrue($this->isInterlacedPngFile($sourceAbsolute));

        $newPath = $service->convertExistingPublicImage('assets/ps4/interlaced_cover.png', true);

        $this->assertSame('assets/ps4/interlaced_cover.webp', $newPath);
        $this->assertFileExists($this->tempPublic . '/assets/ps4/interlaced_cover.webp');
        $this->assertFileDoesNotExist($sourceAbsolute);
    }

    private function makePngUploadedFile(): UploadedFile
    {
        $tmp = $this->tempPublic . '/upload_src.png';
        $img = imagecreatetruecolor(8, 8);
        imagepng($img, $tmp);
        imagedestroy($img);

        return new UploadedFile($tmp, 'cover.png', 'image/png', null, true);
    }

    private function isInterlacedPngFile(string $absolute): bool
    {
        $binary = file_get_contents($absolute);

        return strlen($binary) >= 29
            && str_starts_with($binary, "\x89PNG\r\n\x1a\n")
            && ord($binary[28]) === 1;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
