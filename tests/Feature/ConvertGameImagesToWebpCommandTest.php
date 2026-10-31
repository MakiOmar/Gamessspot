<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * images:convert-games-to-webp brings already-uploaded game images in line with new uploads:
 * WebP, scaled down to Game::IMAGE_MAX_WIDTH without cropping.
 */
class ConvertGameImagesToWebpCommandTest extends TestCase
{
    use DatabaseTransactions;

    private string $tempPublic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPublic = storage_path('framework/testing/convert_games_' . uniqid());
        foreach (['assets/ps4', 'assets/ps5', 'assets/uploads/galleries'] as $dir) {
            File::makeDirectory($this->tempPublic . '/' . $dir, 0777, true);
        }
        $this->app->usePublicPath($this->tempPublic);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPublic);
        parent::tearDown();
    }

    private function makeImage(string $relativePath, int $width, int $height): string
    {
        $img = imagecreatetruecolor($width, $height);
        $absolute = public_path($relativePath);
        match (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION))) {
            'png' => imagepng($img, $absolute),
            'webp' => imagewebp($img, $absolute, 90),
            default => imagejpeg($img, $absolute),
        };
        imagedestroy($img);

        return $relativePath;
    }

    /** @return array{0: int, 1: int} */
    private function dimensionsOf(string $relativePath): array
    {
        $this->assertFileExists(public_path($relativePath));
        $size = getimagesize(public_path($relativePath));

        return [$size[0], $size[1]];
    }

    private function gameWithImages(): Game
    {
        $game = Game::factory()->create([
            'ps4_image_url' => $this->makeImage('assets/ps4/wide_cover.jpg', 1200, 800),
            'ps5_image_url' => $this->makeImage('assets/ps5/wide_cover.webp', 1000, 400),
        ]);
        $game->galleryImages()->create([
            'path' => $this->makeImage('assets/uploads/galleries/small_shot.png', 300, 200),
            'sort_order' => 0,
        ]);

        return $game;
    }

    public function test_command_resizes_and_converts_existing_game_images(): void
    {
        $game = $this->gameWithImages();
        $narrowWebp = $this->makeImage('assets/ps4/narrow.webp', 400, 300);
        $untouchedGame = Game::factory()->create(['ps4_image_url' => $narrowWebp, 'ps5_image_url' => null]);
        $narrowBytes = file_get_contents(public_path($narrowWebp));

        $this->artisan('images:convert-games-to-webp')->assertSuccessful();

        $game->refresh();
        $this->assertSame('assets/ps4/wide_cover.webp', $game->ps4_image_url);
        $this->assertSame([500, 333], $this->dimensionsOf($game->ps4_image_url));
        $this->assertSame('VP8L', substr(file_get_contents(public_path($game->ps4_image_url)), 12, 4));
        $this->assertFileDoesNotExist(public_path('assets/ps4/wide_cover.jpg'));

        $this->assertSame('assets/ps5/wide_cover.webp', $game->ps5_image_url);
        $this->assertSame([500, 200], $this->dimensionsOf($game->ps5_image_url));

        $galleryPath = $game->galleryImages()->firstOrFail()->path;
        $this->assertSame('assets/uploads/galleries/small_shot.webp', $galleryPath);
        $this->assertSame([300, 200], $this->dimensionsOf($galleryPath));

        $this->assertSame($narrowWebp, $untouchedGame->fresh()->ps4_image_url);
        $this->assertSame($narrowBytes, file_get_contents(public_path($narrowWebp)));
        $this->assertSame([], glob(public_path('assets/ps5') . '/*resize_*'));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $game = $this->gameWithImages();

        $this->artisan('images:convert-games-to-webp', ['--dry-run' => true])->assertSuccessful();

        $game->refresh();
        $this->assertSame('assets/ps4/wide_cover.jpg', $game->ps4_image_url);
        $this->assertSame([1200, 800], $this->dimensionsOf('assets/ps4/wide_cover.jpg'));
        $this->assertSame([1000, 400], $this->dimensionsOf('assets/ps5/wide_cover.webp'));
    }

    public function test_max_width_zero_only_converts_without_resizing(): void
    {
        $game = $this->gameWithImages();

        $this->artisan('images:convert-games-to-webp', ['--max-width' => 0])->assertSuccessful();

        $game->refresh();
        $this->assertSame([1200, 800], $this->dimensionsOf($game->ps4_image_url));
        $this->assertSame([1000, 400], $this->dimensionsOf($game->ps5_image_url));
    }

    public function test_custom_width_and_lossy_quality_are_applied(): void
    {
        $game = $this->gameWithImages();

        $this->artisan('images:convert-games-to-webp', ['--max-width' => 600, '--quality' => 90])
            ->assertSuccessful();

        $path = $game->fresh()->ps4_image_url;
        $this->assertSame([600, 400], $this->dimensionsOf($path));
        $this->assertSame('VP8 ', substr(file_get_contents(public_path($path)), 12, 4));
    }

    public function test_invalid_options_are_rejected(): void
    {
        $this->artisan('images:convert-games-to-webp', ['--quality' => 150])->assertExitCode(2);
        $this->artisan('images:convert-games-to-webp', ['--max-width' => 'wide'])->assertExitCode(2);
    }
}
