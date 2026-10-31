<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Create/edit game modals: uploaded covers and gallery images are stored as WebP,
 * scaled down to Game::IMAGE_MAX_WIDTH without cropping.
 */
class ManagerGameImageUploadTest extends TestCase
{
    use DatabaseTransactions;

    private string $tempPublic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPublic = storage_path('framework/testing/game_images_' . uniqid());
        File::makeDirectory($this->tempPublic, 0777, true);
        $this->app->usePublicPath($this->tempPublic);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPublic);
        parent::tearDown();
    }

    private function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $matrix = app(RolePermissionService::class)->defaultMatrix();
        if (isset($matrix['admin'])) {
            $role->capabilities = $matrix['admin'];
            $role->save();
        }

        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }

    private function gamePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Image Upload Game IMGUP',
            'code' => 'IMGUP-' . uniqid(),
            'product_type' => Game::TYPE_GAME,
            'full_price' => 100,
            'ps4_primary_status' => 1,
            'ps4_secondary_status' => 1,
            'ps4_offline_status' => 1,
            'ps5_primary_status' => 1,
            'ps5_secondary_status' => 1,
            'ps5_offline_status' => 1,
        ], $overrides);
    }

    /** @return array{0: int, 1: int} */
    private function dimensionsOf(string $relativePath): array
    {
        $absolute = public_path($relativePath);
        $this->assertFileExists($absolute);
        $size = getimagesize($absolute);

        return [$size[0], $size[1]];
    }

    public function test_create_game_resizes_and_converts_uploaded_images(): void
    {
        $this->actingAs($this->createAdminUser(), 'admin')
            ->post('/manager/games/store', $this->gamePayload([
                'ps4_image' => UploadedFile::fake()->image('ps4 cover.jpg', 1200, 800),
                'ps5_image' => UploadedFile::fake()->image('ps5-cover.png', 2000, 1000),
                'gallery_images' => [UploadedFile::fake()->image('shot.jpg', 1000, 1500)],
            ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['message' => 'Game created successfully!']);

        $game = Game::where('title', 'Image Upload Game IMGUP')->latest('id')->firstOrFail();

        $this->assertStringStartsWith('assets/ps4/', $game->ps4_image_url);
        $this->assertStringEndsWith('.webp', $game->ps4_image_url);
        $this->assertSame([500, 333], $this->dimensionsOf($game->ps4_image_url));

        $this->assertStringStartsWith('assets/ps5/', $game->ps5_image_url);
        $this->assertStringEndsWith('.webp', $game->ps5_image_url);
        $this->assertSame([500, 250], $this->dimensionsOf($game->ps5_image_url));

        $galleryPath = $game->galleryImages()->firstOrFail()->path;
        $this->assertStringEndsWith('.webp', $galleryPath);
        $this->assertSame([500, 750], $this->dimensionsOf($galleryPath));
    }

    public function test_update_game_replaces_cover_with_resized_webp_and_removes_old_file(): void
    {
        File::makeDirectory(public_path('assets/ps4'), 0777, true);
        $oldRelative = 'assets/ps4/old_cover.jpg';
        imagejpeg(imagecreatetruecolor(50, 50), public_path($oldRelative));

        $game = Game::factory()->create(['ps4_image_url' => $oldRelative]);

        $this->actingAs($this->createAdminUser(), 'admin')
            ->put('/manager/games/' . $game->id, $this->gamePayload([
                'title' => $game->title,
                'code' => $game->code,
                'ps4_image' => UploadedFile::fake()->image('new cover.jpg', 1500, 1000),
            ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['message' => 'Game updated successfully!']);

        $newPath = $game->fresh()->ps4_image_url;
        $this->assertNotSame($oldRelative, $newPath);
        $this->assertStringEndsWith('.webp', $newPath);
        $this->assertSame([500, 333], $this->dimensionsOf($newPath));
        $this->assertFileDoesNotExist(public_path($oldRelative));
    }

    public function test_update_without_new_image_keeps_existing_cover(): void
    {
        $game = Game::factory()->create(['ps4_image_url' => 'assets/ps4/keep_me.webp']);

        $this->actingAs($this->createAdminUser(), 'admin')
            ->putJson('/manager/games/' . $game->id, $this->gamePayload([
                'title' => $game->title,
                'code' => $game->code,
            ]))
            ->assertOk();

        $this->assertSame('assets/ps4/keep_me.webp', $game->fresh()->ps4_image_url);
    }
}
