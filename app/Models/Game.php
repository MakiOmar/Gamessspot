<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    /**
     * The accounts that belong to the game.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class);
    }
}
