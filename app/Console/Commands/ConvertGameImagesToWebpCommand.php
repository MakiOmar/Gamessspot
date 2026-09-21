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
                            {--limit=0 : Max games to process (0 = all)}';

    protected $description = 'Convert each game PS4/PS5 image to WebP on disk and update image URLs in the database';

    public function handle(ImageUploadService $images): int
    {
        if (!$images->canEncodeWebp()) {
            $this->error('PHP GD WebP support is not available (imagewebp missing).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $deleteOriginal = ! (bool) $this->option('keep-original');
        $limit = (int) $this->option('limit');

        $query = Game::query()
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

        $total = $limit > 0 ? min($limit, (clone $query)->count()) : (clone $query)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(50, function ($games) use (
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

                // Gallery images attached to this game
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
                    }
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

        $this->info(($dryRun ? '[dry-run] ' : '') . "Converted: {$converted}, already WebP/skipped: {$skipped}, failed: {$failed}, games updated: {$updatedGames}");

        if (!$dryRun && $updatedGames > 0) {
            CacheManager::invalidateGames();
            $this->info('Game cache invalidated.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  int  $converted
     * @param  int  $skipped
     * @param  int  $failed
     */
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

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'webp') {
            $skipped++;

            return $path;
        }

        if ($dryRun) {
            if (is_file(public_path($path))) {
                $this->newLine();
                $this->line("[dry-run] {$label}: {$path} → .webp");
                $converted++;

                return $path;
            }

            $this->newLine();
            $this->warn("[dry-run] missing file {$label}: {$path}");
            $failed++;

            return null;
        }

        $newPath = $images->convertExistingPublicImage($path, $deleteOriginal);
        if ($newPath === null) {
            $failed++;
            $this->newLine();
            $this->warn("Skipped/failed {$label}: {$path}");

            return null;
        }

        if ($newPath !== $path) {
            $converted++;
        } else {
            $skipped++;
        }

        return $newPath;
    }
}
