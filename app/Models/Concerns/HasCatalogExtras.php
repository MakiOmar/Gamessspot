<?php

namespace App\Models\Concerns;

use App\Models\GalleryImage;
use App\Models\Review;
use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;

trait HasCatalogExtras
{
    public function galleryImages(): MorphMany
    {
        return $this->morphMany(GalleryImage::class, 'imageable')->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function approvedReviews(): MorphMany
    {
        return $this->reviews()->approved();
    }

    /**
     * Sync gallery uploads from a request (append up to MAX_PER_PRODUCT).
     */
    public function syncGalleryFromRequest(Request $request, ImageUploadService $uploader): void
    {
        $files = $request->file('gallery_images', []);
        if (!is_array($files) || empty($files)) {
            return;
        }

        $currentCount = $this->galleryImages()->count();
        $nextSort = (int) $this->galleryImages()->max('sort_order') + 1;

        foreach ($files as $file) {
            if ($currentCount >= GalleryImage::MAX_PER_PRODUCT) {
                break;
            }
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $uploader->upload($file, 'galleries');
            $this->galleryImages()->create([
                'path' => $path,
                'sort_order' => $nextSort++,
            ]);
            $currentCount++;
        }
    }

    /**
     * Delete gallery images by id list owned by this product.
     */
    public function deleteGalleryIds(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $this->galleryImages()->whereIn('id', $ids)->get()->each->delete();
    }

    public function galleryApiPayload(): array
    {
        return $this->galleryImages->map->toApiArray()->values()->all();
    }

    public function reviewsApiPayload(): array
    {
        return Review::summaryFor($this);
    }
}
