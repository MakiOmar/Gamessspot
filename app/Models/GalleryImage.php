<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GalleryImage extends Model
{
    use HasFactory;

    /** Maximum gallery images per product. */
    public const MAX_PER_PRODUCT = 8;

    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Parent game or card category.
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Public URL for the stored path.
     */
    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }

    /**
     * API payload shape.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'path' => $this->path,
            'sort_order' => $this->sort_order,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (GalleryImage $image) {
            if ($image->path && file_exists(public_path($image->path))) {
                @unlink(public_path($image->path));
            }
        });
    }
}
