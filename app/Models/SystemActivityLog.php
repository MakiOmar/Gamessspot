<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = array(
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'actor_id',
        'actor_name',
        'meta',
        'created_at',
    );

    protected $casts = array(
        'meta' => 'array',
        'created_at' => 'datetime',
    );
}
