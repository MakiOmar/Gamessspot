<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $mail
 * @property string $region
 * @property int $ps4_offline_stock
 * @property int $ps4_primary_stock
 * @property int $ps4_secondary_stock
 * @property int $ps5_offline_stock
 * @property int $ps5_primary_stock
 * @property int $ps5_secondary_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Game> $games
 * @property-read int|null $games_count
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4OfflineStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4PrimaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4SecondaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5OfflineStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5PrimaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5SecondaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $game_id
 * @property string $cost
 * @property string $password
 * @property string|null $login_code
 * @property string|null $birthdate
 * @property-read \App\Models\Game $game
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereBirthdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereLoginCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePassword($value)
 */
	class Account extends \Eloquent {}
}

namespace App\Models{
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
 * @property string $ps5_secondary_price
 * @property int $ps5_secondary_status
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5SecondaryPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Game wherePs5SecondaryStatus($value)
 */
	class Game extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $seller_id
 * @property int $account_id
 * @property string $buyer_phone
 * @property string $buyer_name
 * @property string $price
 * @property string|null $notes
 * @property string $sold_item
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\User $seller
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereBuyerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereBuyerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSoldItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\StoresProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoresProfile whereUpdatedAt($value)
 */
	class StoresProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $phone
 * @property int $role
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $store_profile_id
 * @property-read \App\Models\StoresProfile|null $storeProfile
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStoreProfileId($value)
 */
	class User extends \Eloquent {}
}

