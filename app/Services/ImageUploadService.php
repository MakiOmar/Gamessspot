<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    /** WebP quality (0–100). */
    public const WEBP_QUALITY = 82;

    /** Pixel-exact WebP encoding, for uploads where no extra compression loss is acceptable. */
    public const WEBP_LOSSLESS = IMG_WEBP_LOSSLESS;

    /** Extensions we never convert (vector / unsupported by GD rasterize). */
    private const SKIP_EXTENSIONS = ['svg', 'svgz'];

    /** Raster formats GD can load for WebP conversion. */
    private const CONVERTIBLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /**
     * Upload an image, converting to WebP when possible.
     *
     * @param  string  $directory  Short name (`galleries`) → `assets/uploads/galleries`,
     *                             or a public-relative path (`assets/ps4`, `logos`).
     * @param  int|null  $maxWidth  Downscale wider images to this width (aspect ratio kept, never upscaled).
     * @param  int  $quality  WebP quality 0–100, or WEBP_LOSSLESS.
     * @return string Relative path from the public root (e.g. assets/ps4/foo.webp)
     */
    public function upload(
        UploadedFile $imageFile,
        string $directory,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): string {
        $relativeDir = $this->resolvePublicDirectory($directory);
        $absoluteDir = public_path($relativeDir);

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException("Unable to create upload directory: {$relativeDir}");
        }

        $stem = $this->sanitizeBaseName(
            pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
        ) . '_' . uniqid();
        $sourceExt = strtolower($imageFile->getClientOriginalExtension() ?: $imageFile->extension() ?: '');

        // Skip WebP for SVG (and when GD WebP is unavailable)
        if (in_array($sourceExt, self::SKIP_EXTENSIONS, true) || !$this->canEncodeWebp()) {
            $filename = $stem . '.' . ($sourceExt ?: 'bin');
            $imageFile->move($absoluteDir, $filename);

            return $relativeDir . '/' . $filename;
        }

        $filename = $stem . '.webp';
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        // Re-encoding a WebP that already fits would only add compression loss
        if ($sourceExt === 'webp' && !$this->exceedsWidth($imageFile->getRealPath(), $maxWidth)) {
            $imageFile->move($absoluteDir, $filename);

            return $relativeDir . '/' . $filename;
        }

        $converted = $this->convertUploadedFileToWebp($imageFile, $absolutePath, $maxWidth, $quality);

        if (!$converted) {
            Log::warning('Image WebP conversion failed; storing original file', [
                'file' => $imageFile->getClientOriginalName(),
                'directory' => $relativeDir,
            ]);
            $filename = $stem . '.' . ($sourceExt ?: 'jpg');
            $imageFile->move($absoluteDir, $filename);

            return $relativeDir . '/' . $filename;
        }

        return $relativeDir . '/' . $filename;
    }

    /**
     * Convert an existing public-relative image to WebP on disk, optionally scaling it down.
     *
     * @param  int|null  $maxWidth  Downscale wider images to this width (aspect ratio kept, never upscaled).
     * @param  int  $quality  WebP quality 0–100, or WEBP_LOSSLESS.
     * @return string|null New relative path, same path if already WebP, or null if skipped/failed
     */
    public function convertExistingPublicImage(
        string $relativePath,
        bool $deleteOriginal = true,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): ?string {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return null;
        }

        $absolutePath = public_path($relativePath);
        if (!is_file($absolutePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($ext, self::SKIP_EXTENSIONS, true)) {
            return null;
        }

        if ($ext === 'webp') {
            if (!$this->exceedsWidth($absolutePath, $maxWidth)) {
                return $relativePath;
            }

            return $this->resizeWebpInPlace($absolutePath, $maxWidth, $quality) ? $relativePath : null;
        }

        if (!in_array($ext, self::CONVERTIBLE_EXTENSIONS, true) || !$this->canEncodeWebp()) {
            return null;
        }

        $dir = str_replace('\\', '/', dirname($relativePath));
        $baseName = $this->sanitizeBaseName(pathinfo($absolutePath, PATHINFO_FILENAME));
        $newRelative = ($dir === '.' ? '' : $dir . '/') . $baseName . '.webp';
        $newAbsolute = public_path($newRelative);

        // Avoid clobbering a different existing file with the same stem
        if (is_file($newAbsolute) && realpath($newAbsolute) !== realpath($absolutePath)) {
            $newRelative = ($dir === '.' ? '' : $dir . '/') . $baseName . '_' . time() . '.webp';
            $newAbsolute = public_path($newRelative);
        }

        if (!$this->convertFilePathToWebp($absolutePath, $newAbsolute, $maxWidth, $quality)) {
            return null;
        }

        if ($deleteOriginal && realpath($absolutePath) !== realpath($newAbsolute)) {
            @unlink($absolutePath);
        }

        return $newRelative;
    }

    public function canEncodeWebp(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    /**
     * Short names map under assets/uploads/; paths with a slash are used as-is under public/.
     */
    public function resolvePublicDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        if ($directory === '' || str_contains($directory, '/') || str_starts_with($directory, 'assets') || $directory === 'logos') {
            return $directory !== '' ? $directory : 'assets/uploads';
        }

        return 'assets/uploads/' . $directory;
    }

    protected function sanitizeBaseName(string $name): string
    {
        $name = Str::slug($name, '_');

        return $name !== '' ? $name : 'image';
    }

    public function exceedsWidth(string $absolutePath, ?int $maxWidth): bool
    {
        if ($maxWidth === null) {
            return false;
        }

        $size = @getimagesize($absolutePath);

        return $size !== false && $size[0] > $maxWidth;
    }

    protected function convertUploadedFileToWebp(
        UploadedFile $file,
        string $destinationAbsolute,
        ?int $maxWidth,
        int $quality
    ): bool {
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return false;
        }

        return $this->encodeBinaryAsWebp($binary, $destinationAbsolute, $maxWidth, $quality);
    }

    protected function convertFilePathToWebp(
        string $sourceAbsolute,
        string $destinationAbsolute,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): bool {
        $binary = @file_get_contents($sourceAbsolute);
        if ($binary === false || $binary === '') {
            return false;
        }

        return $this->encodeBinaryAsWebp($binary, $destinationAbsolute, $maxWidth, $quality);
    }

    /**
     * Encode to a sibling temp file first so a failed encode never destroys the original.
     */
    protected function resizeWebpInPlace(string $absolutePath, int $maxWidth, int $quality): bool
    {
        if (!$this->canEncodeWebp()) {
            return false;
        }

        $tmpAbsolute = $absolutePath . '.' . uniqid('resize_', false) . '.webp';
        if (!$this->convertFilePathToWebp($absolutePath, $tmpAbsolute, $maxWidth, $quality)) {
            @unlink($tmpAbsolute);

            return false;
        }

        if (!@rename($tmpAbsolute, $absolutePath)) {
            @unlink($tmpAbsolute);

            return false;
        }

        return true;
    }

    /**
     * Encode image binary as WebP. Interlaced PNGs are decoded in a child process
     * with stderr discarded so libpng does not print noisy CLI warnings.
     */
    protected function encodeBinaryAsWebp(
        string $binary,
        string $destinationAbsolute,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): bool {
        $dir = dirname($destinationAbsolute);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        // Interlaced PNGs trigger: "libpng warning: Interlace handling should be turned on..."
        if ($this->isInterlacedPng($binary)) {
            return $this->encodeInterlacedPngAsWebpSilenced($binary, $destinationAbsolute, $maxWidth, $quality);
        }

        return $this->encodeBinaryAsWebpInProcess($binary, $destinationAbsolute, $maxWidth, $quality);
    }

    /**
     * PNG signature + IHDR interlace flag (byte offset 28) === 1.
     */
    protected function isInterlacedPng(string $binary): bool
    {
        if (strlen($binary) < 29) {
            return false;
        }

        if (!str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return false;
        }

        return ord($binary[28]) === 1;
    }

    protected function encodeBinaryAsWebpInProcess(
        string $binary,
        string $destinationAbsolute,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): bool {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }

        return $this->writeGdImageAsWebp($this->scaleDownToWidth($image, $maxWidth), $destinationAbsolute, $quality);
    }

    /**
     * Resample to $maxWidth keeping aspect ratio and transparency; narrower images are returned untouched.
     */
    protected function scaleDownToWidth(\GdImage $image, ?int $maxWidth): \GdImage
    {
        $width = imagesx($image);
        if ($maxWidth === null || $maxWidth < 1 || $width <= $maxWidth) {
            return $image;
        }

        $height = imagesy($image);
        $targetHeight = max(1, (int) round($height * $maxWidth / $width));

        $scaled = imagecreatetruecolor($maxWidth, $targetHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $maxWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        return $scaled;
    }

    /**
     * @param  \GdImage|resource  $image
     */
    protected function writeGdImageAsWebp($image, string $destinationAbsolute, int $quality = self::WEBP_QUALITY): bool
    {
        // Ensure non-interlaced raster and keep alpha
        if (function_exists('imageinterlace')) {
            imageinterlace($image, false);
        }
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $ok = imagewebp($image, $destinationAbsolute, $quality);
        imagedestroy($image);

        return (bool) $ok && is_file($destinationAbsolute);
    }

    /**
     * Decode interlaced PNG in a PHP child with stderr → NUL/dev/null, then write WebP.
     */
    protected function encodeInterlacedPngAsWebpSilenced(
        string $binary,
        string $destinationAbsolute,
        ?int $maxWidth = null,
        int $quality = self::WEBP_QUALITY
    ): bool {
        $tmpSrc = tempnam(sys_get_temp_dir(), 'png_i_');
        $tmpPhp = tempnam(sys_get_temp_dir(), 'webp_w_');
        if ($tmpSrc === false || $tmpPhp === false) {
            return $this->encodeBinaryAsWebpInProcess($binary, $destinationAbsolute, $maxWidth, $quality);
        }

        $tmpSrcPng = $tmpSrc . '.png';
        @unlink($tmpSrc);
        $tmpPhpScript = $tmpPhp . '.php';
        @unlink($tmpPhp);

        try {
            if (file_put_contents($tmpSrcPng, $binary) === false) {
                return false;
            }

            $script = <<<'PHP'
<?php
$src = $argv[1] ?? '';
$dest = $argv[2] ?? '';
$quality = (int) ($argv[3] ?? 82);
$maxWidth = (int) ($argv[4] ?? 0);
if ($src === '' || $dest === '' || !is_file($src)) {
    exit(1);
}
$binary = file_get_contents($src);
$image = @imagecreatefromstring($binary);
if ($image === false) {
    exit(1);
}
$width = imagesx($image);
if ($maxWidth > 0 && $width > $maxWidth) {
    $height = imagesy($image);
    $targetHeight = max(1, (int) round($height * $maxWidth / $width));
    $scaled = imagecreatetruecolor($maxWidth, $targetHeight);
    imagealphablending($scaled, false);
    imagesavealpha($scaled, true);
    imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
    imagecopyresampled($scaled, $image, 0, 0, 0, 0, $maxWidth, $targetHeight, $width, $height);
    imagedestroy($image);
    $image = $scaled;
}
if (function_exists('imageinterlace')) {
    imageinterlace($image, false);
}
imagepalettetotruecolor($image);
imagealphablending($image, true);
imagesavealpha($image, true);
$ok = imagewebp($image, $dest, $quality);
imagedestroy($image);
exit($ok && is_file($dest) ? 0 : 1);
PHP;

            if (file_put_contents($tmpPhpScript, $script) === false) {
                return $this->encodeBinaryAsWebpInProcess($binary, $destinationAbsolute, $maxWidth, $quality);
            }

            $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
            $cmd = [
                PHP_BINARY,
                $tmpPhpScript,
                $tmpSrcPng,
                $destinationAbsolute,
                (string) $quality,
                (string) ($maxWidth ?? 0),
            ];

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['file', $nullDevice, 'w'],
            ];

            $process = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
            if (!is_resource($process)) {
                return $this->encodeBinaryAsWebpInProcess($binary, $destinationAbsolute, $maxWidth, $quality);
            }

            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $exitCode = proc_close($process);

            return $exitCode === 0 && is_file($destinationAbsolute);
        } finally {
            @unlink($tmpSrcPng);
            @unlink($tmpPhpScript);
        }
    }
}
