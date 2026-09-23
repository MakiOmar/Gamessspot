<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemOpsSetting extends Model
{
    protected $fillable = array(
        'auto_backup_enabled',
        'auto_backup_interval',
        'last_backup_at',
    );

    protected $casts = array(
        'auto_backup_enabled' => 'boolean',
        'last_backup_at' => 'datetime',
    );

    /**
     * Single settings row used by System Ops.
     */
    public static function current(): self
    {
        $row = static::query()->first();

        if ($row) {
            return $row;
        }

        return static::query()->create(array(
            'auto_backup_enabled' => true,
            'auto_backup_interval' => 'daily',
            'last_backup_at' => null,
        ));
    }
}
