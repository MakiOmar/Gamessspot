<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    /** WebP quality (0–100). */
    public const WEBP_QUALITY = 82;

    /** Extensions we never convert (vector / unsupported by GD rasterize). */
    private const SKIP_EXTENSIONS = ['svg', 'svgz'];

    /** Raster formats GD can load for WebP conversion. */
    private const CONVERTIBLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /**
     * Upload an image, converting to WebP when possible.
     *
     * @param  string  $directory  Short name (`galleries`) → `assets/uploads/galleries`,
     *                             or a public-relative path (`assets/ps4`, `logos`).
     * @return string Relative path from the public root (e.g. assets/ps4/foo.webp)
     */
    public function upload(UploadedFile $imageFile, string $directory): string
    {
        $relativeDir = $this->resolvePublicDirectory($directory);
        $absoluteDir = public_path($relativeDir);

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException("Unable to create upload directory: {$relativeDir}");
        }

        $baseName = $this->sanitizeBaseName(
            pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)
        );
        $sourceExt = strtolower($imageFile->getClientOriginalExtension() ?: $imageFile->extension() ?: '');

        // Skip WebP for SVG (and when GD WebP is unavailable)
        if (in_array($sourceExt, self::SKIP_EXTENSIONS, true) || !$this->canEncodeWebp()) {
            $filename = $baseName . '_' . time() . '.' . ($sourceExt ?: 'bin');
            $imageFile->move($absoluteDir, $filename);

            return $relativeDir . '/' . $filename;
        }

        $filename = $baseName . '_' . time() . '.webp';
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        $converted = $this->convertUploadedFileToWebp($imageFile, $absolutePath);

        if (!$converted) {
            // Fall back to storing the original file
            $filename = $baseName . '_' . time() . '.' . ($sourceExt ?: 'jpg');
            $imageFile->move($absoluteDir, $filename);

            return $relativeDir . '/' . $filename;
        }

        return $relativeDir . '/' . $filename;
    }

    /**
     * Convert an existing public-relative image to WebP on disk.
     *
     * @return string|null New relative path, same path if already WebP, or null if skipped/failed
     */
    public function convertExistingPublicImage(string $relativePath, bool $deleteOriginal = true): ?string
    {
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
            return $relativePath;
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

        if (!$this->convertFilePathToWebp($absolutePath, $newAbsolute)) {
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

    protected function convertUploadedFileToWebp(UploadedFile $file, string $destinationAbsolute): bool
    {
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return false;
        }

        return $this->encodeBinaryAsWebp($binary, $destinationAbsolute);
    }

    protected function convertFilePathToWebp(string $sourceAbsolute, string $destinationAbsolute): bool
    {
        $binary = @file_get_contents($sourceAbsolute);
        if ($binary === false || $binary === '') {
            return false;
        }

        return $this->encodeBinaryAsWebp($binary, $destinationAbsolute);
    }

    protected function encodeBinaryAsWebp(string $binary, string $destinationAbsolute): bool
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }

        // Preserve transparency for PNG/GIF sources
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $dir = dirname($destinationAbsolute);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($image);

            return false;
        }

        $ok = imagewebp($image, $destinationAbsolute, self::WEBP_QUALITY);
        imagedestroy($image);

        return (bool) $ok && is_file($destinationAbsolute);
    }
}
