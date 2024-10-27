<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property string $title
 * @property string $code
 * @property string $full_price
 * @property string $ps4_primary_price
 * @property int $ps4_primary_status
 * @property string $ps4_secondary_price
 * @property int $ps4_secondary_status
 * @property string $ps4_offline_price
 * @property int $ps4_offline_status
 * @property string $ps5_primary_price
 * @property int $ps5_primary_status
 * @property string $ps5_offline_price
 * @property int $ps5_offline_status
 * @property string|null $ps4_image_url
 * @property string|null $ps5_image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Account> $accounts
 * @property-read int|null $accounts_count
 * @method static \Database\Factories\GameFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Game newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Game newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Game query()
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereFullPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4ImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4OfflinePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4OfflineStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4PrimaryPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4PrimaryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4SecondaryPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs4SecondaryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5ImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5OfflinePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5OfflineStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5PrimaryPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5PrimaryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Game extends Model
{
    use HasFactory;

    // Define the fillable fields (excluding _token)
    protected $fillable = array(
        'title',
        'code',
        'full_price',
        'ps4_primary_price',
        'ps4_secondary_price',
        'ps4_offline_price',
        'ps5_primary_price',
        'ps5_offline_price',
        'ps4_image_url',
        'ps5_image_url',
        'ps4_primary_status',
        'ps4_secondary_status',
        'ps4_offline_status',
        'ps5_primary_status',
        'ps5_offline_status',
    );

    /**
     * The accounts that belong to the game.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    // In Game model
    public function specialPrices()
    {
        return $this->hasMany(SpecialPrice::class);
    }
}
