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

    public function test_upload_scales_wide_image_down_to_max_width_keeping_aspect_ratio(): void
    {
        $service = new ImageUploadService();
        $upload = UploadedFile::fake()->image('wide.jpg', 1200, 800);

        $path = $service->upload($upload, 'assets/ps4', 500, ImageUploadService::WEBP_LOSSLESS);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([500, 333], $this->dimensionsOf($path));
        $this->assertTrue($this->isLosslessWebp($path));
    }

    public function test_upload_never_upscales_narrow_image(): void
    {
        $service = new ImageUploadService();
        $upload = UploadedFile::fake()->image('narrow.png', 300, 200);

        $path = $service->upload($upload, 'assets/ps4', 500, ImageUploadService::WEBP_LOSSLESS);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([300, 200], $this->dimensionsOf($path));
    }

    public function test_upload_keeps_transparency_when_resizing(): void
    {
        $service = new ImageUploadService();
        $source = $this->tempPublic . '/transparent.png';
        $img = imagecreatetruecolor(1000, 1000);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagepng($img, $source);
        imagedestroy($img);

        $path = $service->upload(
            new UploadedFile($source, 'transparent.png', 'image/png', null, true),
            'assets/ps4',
            500,
            ImageUploadService::WEBP_LOSSLESS
        );

        $result = imagecreatefromwebp($this->absolute($path));
        $alpha = (imagecolorat($result, 250, 250) >> 24) & 0x7F;
        $this->assertSame(127, $alpha);
    }

    public function test_webp_within_max_width_is_stored_without_re_encoding(): void
    {
        $service = new ImageUploadService();
        $source = $this->tempPublic . '/small.webp';
        imagewebp(imagecreatetruecolor(400, 300), $source, 90);
        $originalBytes = file_get_contents($source);

        $path = $service->upload(new UploadedFile($source, 'small.webp', 'image/webp', null, true), 'assets/ps4', 500);

        $this->assertSame($originalBytes, file_get_contents($this->absolute($path)));
    }

    public function test_wide_webp_is_resized(): void
    {
        $service = new ImageUploadService();
        $source = $this->tempPublic . '/wide.webp';
        imagewebp(imagecreatetruecolor(1000, 400), $source, 90);

        $path = $service->upload(new UploadedFile($source, 'wide.webp', 'image/webp', null, true), 'assets/ps4', 500);

        $this->assertSame([500, 200], $this->dimensionsOf($path));
    }

    public function test_interlaced_png_upload_is_resized(): void
    {
        $service = new ImageUploadService();
        $source = $this->tempPublic . '/interlaced_wide.png';
        $img = imagecreatetruecolor(1000, 500);
        imageinterlace($img, true);
        imagepng($img, $source);
        imagedestroy($img);

        $path = $service->upload(
            new UploadedFile($source, 'interlaced_wide.png', 'image/png', null, true),
            'assets/ps4',
            500,
            ImageUploadService::WEBP_LOSSLESS
        );

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([500, 250], $this->dimensionsOf($path));
    }

    private function absolute(string $relativePath): string
    {
        return $this->tempPublic . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /** @return array{0: int, 1: int} */
    private function dimensionsOf(string $relativePath): array
    {
        $size = getimagesize($this->absolute($relativePath));

        return [$size[0], $size[1]];
    }

    private function isLosslessWebp(string $relativePath): bool
    {
        return substr(file_get_contents($this->absolute($relativePath)), 12, 4) === 'VP8L';
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
