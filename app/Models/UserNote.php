<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNote extends Model
{
    use SoftDeletes;

    protected $fillable = array(
        'user_id',
        'author_id',
        'note',
    );

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Only the note's author or an admin may remove it.
     */
    public function canBeDeletedBy(?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        return (int) $this->author_id === (int) $actor->id || $actor->hasRole('admin');
    }
}
