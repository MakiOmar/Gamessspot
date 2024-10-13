<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoresProfile extends Model
{
    use HasFactory;

    protected $table = 'stores_profile';

    public function users()
    {
        return $this->hasMany(User::class, 'store_profile_id');
    }
}
