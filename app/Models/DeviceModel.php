<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
	use HasFactory;

	protected $fillable = [
		'name',
		'brand',
		'description',
		'is_active',
	];

	protected $casts = [
		'is_active' => 'boolean',
	];

	/**
	 * Get the device repairs for this model.
	 */
	public function deviceRepairs(): HasMany
	{
		return $this->hasMany( DeviceRepair::class );
	}

	/**
	 * Scope for active device models.
	 */
	public function scopeActive( $query )
	{
		return $query->where( 'is_active', true );
	}

	/**
	 * Get full model name (brand + name).
	 */
	public function getFullNameAttribute(): string
	{
		return $this->brand ? "{$this->brand} {$this->name}" : $this->name;
	}
}

