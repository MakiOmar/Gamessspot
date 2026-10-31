<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\CacheManager;
use App\Services\ImageUploadService;
use Illuminate\Console\Command;

class ConvertGameImagesToWebpCommand extends Command
{
    protected $signature = 'images:convert-games-to-webp
                            {--dry-run : Report what would change without writing files or DB}
                            {--keep-original : Keep the original file after creating WebP}
                            {--limit=0 : Max games to process (0 = all)}
                            {--max-width=' . Game::IMAGE_MAX_WIDTH . ' : Scale wider images down to this width, keeping aspect ratio (0 = no resizing)}
                            {--quality=lossless : WebP quality 0-100, or "lossless"}';

    protected $description = 'Resize each game PS4/PS5/gallery image and convert it to WebP on disk, updating image URLs in the database';

    private ?int $maxWidth = null;

    private int $quality = ImageUploadService::WEBP_LOSSLESS;

    public function handle(ImageUploadService $images): int
    {
        if (!$images->canEncodeWebp()) {
            $this->error('PHP GD WebP support is not available (imagewebp missing).');

            return self::FAILURE;
        }

        if (!$this->parseImageOptions()) {
            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $deleteOriginal = ! (bool) $this->option('keep-original');
        $limit = (int) $this->option('limit');

        $baseQuery = Game::query()
            ->with('galleryImages')
            ->where(function ($q) {
                $q->whereNotNull('ps4_image_url')->where('ps4_image_url', '!=', '')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('ps5_image_url')->where('ps5_image_url', '!=', '');
                    })
                    ->orWhereHas('galleryImages');
            })
            ->orderBy('id');

        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $updatedGames = 0;
        $processedGames = 0;

        $total = (clone $baseQuery)->count();
        if ($limit > 0) {
            $total = min($limit, $total);
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $baseQuery->chunkById(50, function ($games) use (
            $images,
            $dryRun,
            $deleteOriginal,
            $limit,
            &$converted,
            &$skipped,
            &$failed,
            &$updatedGames,
            &$processedGames,
            $bar
        ) {
            foreach ($games as $game) {
                if ($limit > 0 && $processedGames >= $limit) {
                    return false;
                }

                $dirty = false;
                $galleryChanged = false;

                foreach (['ps4_image_url', 'ps5_image_url'] as $column) {
                    $result = $this->processPath(
                        $images,
                        $game->{$column},
                        $dryRun,
                        $deleteOriginal,
                        "game #{$game->id} {$column}",
                        $converted,
                        $skipped,
                        $failed
                    );
                    if ($result !== null && $result !== $game->{$column}) {
                        $game->{$column} = $result;
                        $dirty = true;
                    }
                }

                foreach ($game->galleryImages as $gallery) {
                    $result = $this->processPath(
                        $images,
                        $gallery->path,
                        $dryRun,
                        $deleteOriginal,
                        "game #{$game->id} gallery #{$gallery->id}",
                        $converted,
                        $skipped,
                        $failed
                    );
                    if ($result !== null && $result !== $gallery->path && !$dryRun) {
                        $gallery->path = $result;
                        $gallery->save();
                        $galleryChanged = true;
                    }
                }

                if ($galleryChanged && !$dirty) {
                    $updatedGames++;
                }

                if ($dirty && !$dryRun) {
                    $game->save();
                    $updatedGames++;
                }

                $processedGames++;
                $bar->advance();
            }

            if ($limit > 0 && $processedGames >= $limit) {
                return false;
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(
            ($dryRun ? '[dry-run] ' : '')
            . "Converted/resized: {$converted}, already optimized: {$skipped}, failed: {$failed}, games updated: {$updatedGames}"
        );

        if (!$dryRun && $updatedGames > 0) {
            CacheManager::invalidateGames();
            $this->info('Game cache invalidated.');
        }

        return self::SUCCESS;
    }

    private function processPath(
        ImageUploadService $images,
        ?string $path,
        bool $dryRun,
        bool $deleteOriginal,
        string $label,
        int &$converted,
        int &$skipped,
        int &$failed
    ): ?string {
        if (!$path) {
            return null;
        }

        $isWebp = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'webp';
        $tooWide = $images->exceedsWidth(public_path($path), $this->maxWidth);
        if ($isWebp && !$tooWide) {
            $skipped++;

            return $path;
        }

        if ($dryRun) {
            if (is_file(public_path($path))) {
                $actions = array_filter([
                    $tooWide ? "resize to {$this->maxWidth}px wide" : null,
                    $isWebp ? null : 'convert to .webp',
                ]);
                $this->newLine();
                $this->line("[dry-run] {$label}: {$path} → " . implode(', ', $actions));
                $converted++;

                return $path;
            }

            $this->newLine();
            $this->warn("[dry-run] missing file {$label}: {$path}");
            $failed++;

            return null;
        }

        $newPath = $images->convertExistingPublicImage($path, $deleteOriginal, $this->maxWidth, $this->quality);
        if ($newPath === null) {
            $failed++;
            $this->newLine();
            $this->warn("Skipped/failed {$label}: {$path}");

            return null;
        }

        $converted++;

        return $newPath;
    }

    private function parseImageOptions(): bool
    {
        $maxWidth = filter_var($this->option('max-width'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($maxWidth === false) {
            $this->error('--max-width must be a whole number of pixels (0 disables resizing).');

            return false;
        }
        $this->maxWidth = $maxWidth > 0 ? $maxWidth : null;

        $quality = strtolower((string) $this->option('quality'));
        if ($quality === 'lossless') {
            $this->quality = ImageUploadService::WEBP_LOSSLESS;

            return true;
        }

        $quality = filter_var($quality, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if ($quality === false) {
            $this->error('--quality must be between 0 and 100, or "lossless".');

            return false;
        }
        $this->quality = $quality;

        return true;
    }
}
