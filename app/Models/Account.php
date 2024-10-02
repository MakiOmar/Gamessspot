<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /**
     * The games that belong to the account.
     */
    public function games()
    {
        return $this->belongsToMany(Game::class);
    }
}
