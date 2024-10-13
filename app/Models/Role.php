<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

     // Define the table name if it's not the default 'roles'
     protected $table = 'roles';

     // Specify the fields that are mass assignable
     protected $fillable = ['name', 'capabilities'];


    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }

    /**
     * Get the capabilities as an array from the JSON field.
     *
     * @return array
     */
    public function getCapabilitiesAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    /**
     * Set the capabilities as a JSON string.
     *
     * @param array $capabilities
     */
    public function setCapabilitiesAttribute(array $capabilities): void
    {
        $this->attributes['capabilities'] = json_encode($capabilities);
    }

    /**
     * Check if a role has a specific capability.
     *
     * @param string $capability
     * @return bool
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities);
    }
}
