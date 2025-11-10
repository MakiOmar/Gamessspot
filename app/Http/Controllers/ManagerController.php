<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\StoresProfile;
use App\Models\SpecialPrice;
use App\Services\ImageUploadService;
use App\Services\CacheManager;

class ManagerController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }
    /**
     * Show the list of games in a table for managers.
     *
     * @return \Illuminate\View\View
     */
    public function showGames()
    {
        // Get current page from request
        $page = request()->get('page', 1);
        
        // Get cache key for this listing (no store profile - global view)
        $cacheKey = CacheManager::getGameListingKey('all', $page, null);
        
        // ✅ Cache game listings with pagination
        $games = CacheManager::getGameListing('all', $page, function () {
            return Game::paginate(100);
        }, null);
        
        // Get cache metadata
        $cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
        $fromCache = CacheManager::wasCacheHit($cacheKey);

        // Return the view with the games data
        return view('manager.games', compact('games', 'cacheKey', 'cacheMetadata', 'fromCache'));
    }
    // Show the game data for editing
    public function edit($id)
    {
        $game = Game::findOrFail($id);
        return response()->json($game); // Return the game data as JSON
    }
    /**
     * Fetch a single game by its ID via API.
     *
     * @param int $id The ID of the game.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGameById($id)
    {
        $game = DB::table('games')
            ->leftJoin('accounts', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('special_prices', function ($join) {
                $join->on('games.id', '=', 'special_prices.game_id')
                    ->where('special_prices.store_profile_id', '=', 17);
            })
            ->where('games.id', $id)
            ->select(
                'games.id',
                'games.title',
                'games.code',
                'games.full_price',
                'games.ps4_image_url',
                'games.ps5_image_url',
                'games.created_at',
                'games.updated_at',
                'games.ps4_primary_status',
                'games.ps4_secondary_status',
                'games.ps5_primary_status',
                'games.ps5_secondary_status',
                DB::raw("COALESCE(special_prices.ps4_primary_price, games.ps4_primary_price) as ps4_primary_price"),
                DB::raw("COALESCE(special_prices.ps4_secondary_price, games.ps4_secondary_price) as ps4_secondary_price"),
                DB::raw("COALESCE(special_prices.ps4_offline_price, games.ps4_offline_price) as ps4_offline_price"),
                DB::raw("COALESCE(special_prices.ps5_primary_price, games.ps5_primary_price) as ps5_primary_price"),
                DB::raw("COALESCE(special_prices.ps5_secondary_price, games.ps5_secondary_price) as ps5_secondary_price"),
                DB::raw("COALESCE(special_prices.ps5_offline_price, games.ps5_offline_price) as ps5_offline_price"),
                DB::raw('COALESCE(SUM(accounts.ps4_primary_stock), 0) as ps4_primary_stock'),
                DB::raw('COALESCE(SUM(accounts.ps4_secondary_stock), 0) as ps4_secondary_stock'),
                DB::raw('COALESCE(SUM(accounts.ps4_offline_stock), 0) as ps4_offline_stock'),
                DB::raw('COALESCE(SUM(accounts.ps5_primary_stock), 0) as ps5_primary_stock'),
                DB::raw('COALESCE(SUM(accounts.ps5_secondary_stock), 0) as ps5_secondary_stock'),
                DB::raw('COALESCE(SUM(accounts.ps5_offline_stock), 0) as ps5_offline_stock')
            )
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                'games.full_price',
                'games.ps4_image_url',
                'games.ps5_image_url',
                'games.created_at',
                'games.updated_at',
                'games.ps4_primary_price',
                'games.ps4_secondary_price',
                'games.ps4_offline_price',
                'games.ps5_primary_price',
                'games.ps5_secondary_price',
                'games.ps5_offline_price',
                'games.ps4_primary_status',
                'games.ps4_secondary_status',
                'games.ps5_primary_status',
                'games.ps5_secondary_status',
                'special_prices.ps4_primary_price',
                'special_prices.ps4_secondary_price',
                'special_prices.ps4_offline_price',
                'special_prices.ps5_primary_price',
                'special_prices.ps5_secondary_price',
                'special_prices.ps5_offline_price'
            )
            ->first();

        if (!$game) {
            return response()->json([
                'error' => 'Game not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $game
        ]);
    }


    private function clearPaginatedGameCache()
    {
        // Calculate total pages for PS4 and PS5
        $ps4GamesCount = Game::where('ps4_offline_status', true)->where('ps5_offline_status', false)->count();
        $ps5GamesCount = Game::where('ps5_offline_status', true)->where('ps4_offline_status', false)->count();

        $paginationLimit = 10; // Assuming 10 games per page
        $ps4TotalPages   = ceil($ps4GamesCount / $paginationLimit);
        $ps5TotalPages   = ceil($ps5GamesCount / $paginationLimit);

        // ✅ Use CacheManager to invalidate all game caches
        CacheManager::invalidateGames();
    }
    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        // Validate the request
        $request->validate([
        'title'                => 'required|string|max:255',
        'code'                 => 'required|string|max:255',
        'full_price'           => 'required|numeric|min:0',
        'ps4_primary_price'    => 'nullable|numeric|min:0',
        'ps4_secondary_price'  => 'nullable|numeric|min:0',
        'ps4_offline_price'    => 'nullable|numeric|min:0',
        'ps5_primary_price'    => 'nullable|numeric|min:0',
        'ps5_secondary_price'  => 'nullable|numeric|min:0',
        'ps5_offline_price'    => 'nullable|numeric|min:0',
        'ps4_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
        'ps5_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
        'ps4_primary_status'   => 'required|boolean',
        'ps4_secondary_status' => 'required|boolean',
        'ps4_offline_status'   => 'required|boolean',
        'ps5_primary_status'   => 'required|boolean',
        'ps5_secondary_status' => 'required|boolean',
        'ps5_offline_status'   => 'required|boolean',
        ]);

        $data = $request->except('_token', 'ps4_image', 'ps5_image'); // Exclude image files from mass assignment
        // Handle PS4 image update
        if ($request->hasFile('ps4_image')) {
            $ps4_image = $request->file('ps4_image');
            $ps4_filename = $this->sanitizeFilename($ps4_image->getClientOriginalName());
            $ps4_path = 'assets/ps4/' . $ps4_filename;

            // Ensure the directory exists
            if (!file_exists(public_path('assets/ps4'))) {
                mkdir(public_path('assets/ps4'), 0777, true);
            }

            // Delete the old image if it exists
            if (!empty($game->ps4_image_url) && file_exists(public_path($game->ps4_image_url))) {
                unlink(public_path($game->ps4_image_url));
            }

            // Move the new image
            $ps4_image->move(public_path('assets/ps4'), $ps4_filename);

            // Store the new image path
            $data['ps4_image_url'] = $ps4_path;
        }

        // Handle PS5 image update
        if ($request->hasFile('ps5_image')) {
            $ps5_image = $request->file('ps5_image');
            $ps5_filename = $this->sanitizeFilename($ps5_image->getClientOriginalName());
            $ps5_path = 'assets/ps5/' . $ps5_filename;

            // Ensure the directory exists
            if (!file_exists(public_path('assets/ps5'))) {
                mkdir(public_path('assets/ps5'), 0777, true);
            }

            // Delete the old image if it exists
            if (!empty($game->ps5_image_url) && file_exists(public_path($game->ps5_image_url))) {
                unlink(public_path($game->ps5_image_url));
            }

            // Move the new image
            $ps5_image->move(public_path('assets/ps5'), $ps5_filename);

            // Store the new image path
            $data['ps5_image_url'] = $ps5_path;
        }

        // Update the game with new data
        $game->update($data);

        // Clear the cache after updating the game
        $this->clearPaginatedGameCache();

        return response()->json(['message' => 'Game updated successfully!']);
    }
    /**
     * Delete a game and related assets.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $game = Game::findOrFail($id);

        try {
            DB::transaction(function () use ($game) {
                // Delete associated images if they exist
                if (!empty($game->ps4_image_url) && file_exists(public_path($game->ps4_image_url))) {
                    @unlink(public_path($game->ps4_image_url));
                }

                if (!empty($game->ps5_image_url) && file_exists(public_path($game->ps5_image_url))) {
                    @unlink(public_path($game->ps5_image_url));
                }

                $game->delete();
            });

            // Invalidate caches impacted by this deletion
            CacheManager::invalidateGames();
            CacheManager::invalidateAccounts();

            return response()->json([
                'success' => true,
                'message' => 'Game deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete game', [
                'game_id' => $game->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete this game. Please try again or contact support.',
            ], 500);
        }
    }
    // Function to sanitize filenames for URL safety
    public function sanitizeFilename($filename)
    {
        // Get file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        // Remove extension from filename
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Replace spaces with underscores
        $name = str_replace(' ', '_', $name);
        // Remove special characters (keep only letters, numbers, - and _)
        $name = preg_replace('/[^A-Za-z0-9\-_]/', '', $name);
        // Ensure lowercase
        $name = strtolower($name);
        // Append a unique ID to prevent duplication
        return $name . '_' . uniqid() . '.' . $extension;
    }
    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
        'title'                => 'required|string|max:255',
        'code'                 => 'required|string|max:255',
        'full_price'           => 'required|numeric|min:0',
        'ps4_primary_price'    => 'nullable|numeric|min:0',
        'ps4_secondary_price'  => 'nullable|numeric|min:0',
        'ps4_offline_price'    => 'nullable|numeric|min:0',
        'ps5_primary_price'    => 'nullable|numeric|min:0',
        'ps5_secondary_price'  => 'nullable|numeric|min:0',
        'ps5_offline_price'    => 'nullable|numeric|min:0',
        'ps4_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
        'ps5_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
        'ps4_primary_status'   => 'required|boolean',
        'ps4_secondary_status' => 'required|boolean',
        'ps4_offline_status'   => 'required|boolean',
        'ps5_primary_status'   => 'required|boolean',
        'ps5_secondary_status' => 'required|boolean',
        'ps5_offline_status'   => 'required|boolean',
        ]);

        // Handle PS4 image upload
        if ($request->hasFile('ps4_image')) {
            $ps4_image = $request->file('ps4_image');
            $ps4_filename = $this->sanitizeFilename($ps4_image->getClientOriginalName());
            $ps4_path = 'assets/ps4/' . $ps4_filename; // Define path in public/assets/ps4/

            // Ensure the directory exists
            if (!file_exists(public_path('assets/ps4'))) {
                mkdir(public_path('assets/ps4'), 0777, true);
            }

            // Move the file to public/assets/ps4/
            $ps4_image->move(public_path('assets/ps4'), $ps4_filename);

            // Store the publicly accessible URL in the database
            $validatedData['ps4_image_url'] = $ps4_path;
        }

        // Handle PS5 image upload
        if ($request->hasFile('ps5_image')) {
            $ps5_image = $request->file('ps5_image');
            $ps5_filename = $this->sanitizeFilename($ps5_image->getClientOriginalName());
            $ps5_path = 'assets/ps5/' . $ps5_filename; // Define path in public/assets/ps5/

            // Ensure the directory exists
            if (!file_exists(public_path('assets/ps5'))) {
                mkdir(public_path('assets/ps5'), 0777, true);
            }

            // Move the file to public/assets/ps5/
            $ps5_image->move(public_path('assets/ps5'), $ps5_filename);

            // Store the publicly accessible URL in the database
            $validatedData['ps5_image_url'] = $ps5_path;
        }

        // Create the new game
        Game::create($validatedData);

        // Clear cache
        $this->clearPaginatedGameCache();

        return response()->json(['message' => 'Game created successfully!']);
    }



    /**
     * Fetch games with dynamic stock based on the $n argument.
     *
     * @param int $n
     * @return \Illuminate\View\View
     */
    public function getGamesByPlatform($n)
    {
        // Get current page from request
        $page = request()->get('page', 1);
        
        // Get current user's store profile ID
        $user = Auth::user();
        $storeProfileId = $user->store_profile_id;
        
        // Get cache key for this platform listing (store-specific)
        $platform = $n == 4 ? 'ps4' : 'ps5';
        $cacheKey = CacheManager::getGameListingKey($platform, $page, $storeProfileId);
        
        // ✅ Cache platform game listings (per store profile)
        $psGames = CacheManager::getGameListing($platform, $page, function () use ($n) {
            return $this->fetchGamesByPlatform($n);
        }, $storeProfileId);

        // Determine if the primary stock is active
        $offline_stock   = "ps{$n}_offline_stock";
        $primary_stock   = "ps{$n}_primary_stock";
        $this->isPrimaryActive($psGames, $primary_stock, $offline_stock, $n);

        // Fetch store profiles (needed by view)
        $storeProfiles = StoresProfile::all();

        // Get cache metadata
        $cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
        $fromCache = CacheManager::wasCacheHit($cacheKey);

        // Return view with games, $n parameter, store profiles, and cache data
        return view('manager.games_listings', compact('psGames', 'n', 'storeProfiles', 'cacheKey', 'cacheMetadata', 'fromCache'));
    }
    
    /**
     * Fetch games by platform (extracted for caching)
     *
     * @param int $n
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function fetchGamesByPlatform($n)
    {
        // Get the current user
        $user = Auth::user();
        $storeProfileId = $user->store_profile_id;

        // Determine the stock and image fields based on the $n value
        $offline_stock   = "ps{$n}_offline_stock";
        $primary_stock   = "ps{$n}_primary_stock";
        $secondary_stock = "ps{$n}_secondary_stock";

        $offline_status   = "ps{$n}_offline_status";
        $primary_status   = "ps{$n}_primary_status";
        $secondary_status = "ps{$n}_secondary_status";

        $offline_price   = "ps{$n}_offline_price";
        $primary_price   = "ps{$n}_primary_price";
        $secondary_price = "ps{$n}_secondary_price";

        $image_url       = "ps{$n}_image_url";

        // Fetch games and their special prices if the user has a store profile
        return DB::table('accounts')
            ->select(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}",
                DB::raw("COALESCE(special_prices.ps{$n}_primary_price, games.ps{$n}_primary_price) as ps{$n}_primary_price"),
                DB::raw("COALESCE(special_prices.ps{$n}_secondary_price, games.ps{$n}_secondary_price) as ps{$n}_secondary_price"),
                DB::raw("COALESCE(special_prices.ps{$n}_offline_price, games.ps{$n}_offline_price) as ps{$n}_offline_price"),
                DB::raw("SUM(accounts.{$offline_stock}) as {$offline_stock}"),
                DB::raw("SUM(accounts.{$primary_stock}) as {$primary_stock}"),
                DB::raw("SUM(accounts.{$secondary_stock}) as {$secondary_stock}")
            )
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('special_prices', function ($join) use ($storeProfileId) {
                $join->on('games.id', '=', 'special_prices.game_id')
                    ->where('special_prices.store_profile_id', '=', $storeProfileId);
            })
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}",
                "games.{$offline_price}",
                "games.{$primary_price}",
                "games.{$secondary_price}",
                "special_prices.ps{$n}_primary_price",
                "special_prices.ps{$n}_secondary_price",
                "special_prices.ps{$n}_offline_price",
            )
            ->havingRaw("SUM(accounts.{$offline_stock}) > 0 OR SUM(accounts.{$primary_stock}) > 0 OR SUM(accounts.{$secondary_stock}) > 0")
            ->get();
    }

    /**
     * Fetch PS4 games that have at least one account with offline stock = 0 and primary stock > 0.
     *
     * @param int|null $storeProfileId
     * @return \Illuminate\Support\Collection
     */
    private function fetchWooCommerceEligiblePS4Games(?int $storeProfileId)
    {
        return DB::table('accounts')
            ->select(
                'games.id',
                'games.title',
                'games.code',
                'games.ps4_image_url',
                'games.ps4_offline_status',
                'games.ps4_primary_status',
                'games.ps4_secondary_status',
                DB::raw('COALESCE(special_prices.ps4_primary_price, games.ps4_primary_price) as ps4_primary_price'),
                DB::raw('COALESCE(special_prices.ps4_secondary_price, games.ps4_secondary_price) as ps4_secondary_price'),
                DB::raw('COALESCE(special_prices.ps4_offline_price, games.ps4_offline_price) as ps4_offline_price'),
                DB::raw('SUM(accounts.ps4_offline_stock) as ps4_offline_stock'),
                DB::raw('SUM(accounts.ps4_primary_stock) as ps4_primary_stock'),
                DB::raw('SUM(accounts.ps4_secondary_stock) as ps4_secondary_stock')
            )
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('special_prices', function ($join) use ($storeProfileId) {
                $join->on('games.id', '=', 'special_prices.game_id');

                if ($storeProfileId !== null) {
                    $join->where('special_prices.store_profile_id', '=', $storeProfileId);
                }
            })
            ->where('accounts.ps4_offline_stock', '=', 0)
            ->where('accounts.ps4_primary_stock', '>', 0)
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                'games.ps4_image_url',
                'games.ps4_offline_status',
                'games.ps4_primary_status',
                'games.ps4_secondary_status',
                'games.ps4_offline_price',
                'games.ps4_primary_price',
                'games.ps4_secondary_price',
                'special_prices.ps4_primary_price',
                'special_prices.ps4_secondary_price',
                'special_prices.ps4_offline_price'
            )
            ->orderBy('games.title')
            ->get();
    }
    protected function isPrimaryActive(&$psGames, $primary_stock, $offline_stock, $n)
    {
        // PS5 always has primary active
        if ( 5 === $n ) {
            foreach ( $psGames as $game ) {
                $game->is_primary_active = true;
            }
            return;
        }

        // Optimize: Get all game IDs at once
        $gameIds = $psGames->pluck('id')->toArray();

        // Single query to get oldest accounts for all games with offline=0 and primary>0
        $oldestAccounts = DB::table('accounts')
            ->select('game_id', DB::raw('MIN(created_at) as oldest_created_at'))
            ->whereIn('game_id', $gameIds)
            ->where($offline_stock, 0)
            ->where($primary_stock, '>', 0)
            ->groupBy('game_id')
            ->pluck('oldest_created_at', 'game_id');

        // Map the results to games
        foreach ( $psGames as $game ) {
            $game->is_primary_active = isset($oldestAccounts[ $game->id ]);
        }
    }
    /**
     * Get games by platform (PS4 or PS5) via API with detailed availability for each type.
     *
     * @param int $platform (4 for PS4, 5 for PS5)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGamesByPlatformApi($platform)
    {
        $platform = (int) $platform;

        // Validate the platform input (should be 4 or 5)
        if ( ! in_array( $platform, array( 4, 5 ) ) ) {
            return response()->json( array( 'error' => 'Invalid platform. Use 4 for PS4 or 5 for PS5.' ), 400 );
        }

        $image_url        = "ps{$platform}_image_url";
        $offline_price    = "ps{$platform}_offline_price";
        $primary_price    = "ps{$platform}_primary_price";
        $secondary_price  = "ps{$platform}_secondary_price";
        $offline_status   = "ps{$platform}_offline_status";
        $primary_status   = "ps{$platform}_primary_status";
        $secondary_status = "ps{$platform}_secondary_status";

        // Store profile ID for special prices
        $storeProfileId = 17;

        // Fetch all games that have any stock for this platform
        // Join with special_prices table to get store-specific prices
        $psGamesQuery = DB::table( 'accounts' )
            ->select(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url} as image_url",
                // Use special prices if available, fallback to games table prices
                DB::raw( "COALESCE(special_prices.{$offline_price}, games.{$offline_price}) as offline_price" ),
                DB::raw( "COALESCE(special_prices.{$primary_price}, games.{$primary_price}) as primary_price" ),
                DB::raw( "COALESCE(special_prices.{$secondary_price}, games.{$secondary_price}) as secondary_price" ),
                "games.{$offline_status} as offline_status",
                "games.{$primary_status} as primary_status",
                "games.{$secondary_status} as secondary_status",
                DB::raw( "SUM(accounts.ps{$platform}_offline_stock) as total_offline_stock" ),
                DB::raw( "SUM(accounts.ps{$platform}_primary_stock) as total_primary_stock" ),
                DB::raw( "SUM(accounts.ps{$platform}_secondary_stock) as total_secondary_stock" )
            )
            ->join( 'games', 'accounts.game_id', '=', 'games.id' )
            ->leftJoin( 'special_prices', function($join) use ($storeProfileId) {
                $join->on('games.id', '=', 'special_prices.game_id')
                     ->where('special_prices.store_profile_id', '=', $storeProfileId)
                     ->where('special_prices.is_available', '=', 1);
            });

        if ( 4 === $platform ) {
            $psGamesQuery
                ->where( 'accounts.ps4_offline_stock', '=', 0 )
                ->where( 'accounts.ps4_primary_stock', '>', 0 );
        }

        $psGames = $psGamesQuery
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "special_prices.{$offline_price}",
                "games.{$offline_price}",
                "special_prices.{$primary_price}",
                "games.{$primary_price}",
                "special_prices.{$secondary_price}",
                "games.{$secondary_price}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}"
            )
            ->havingRaw( "SUM(accounts.ps{$platform}_offline_stock) > 0 OR SUM(accounts.ps{$platform}_primary_stock) > 0 OR SUM(accounts.ps{$platform}_secondary_stock) > 0" )
            ->paginate( 20 );

        // Optimize: Get all game IDs for batch processing
        $gameIds = $psGames->pluck('id')->toArray();

        // For PS4 primary, pre-fetch accounts with offline=0 and primary>0 to avoid N+1
        $ps4PrimaryAvailableGames = array();
        if ( 4 === $platform && ! empty( $gameIds ) ) {
            $ps4PrimaryAvailableGames = DB::table('accounts')
                ->select('game_id')
                ->whereIn('game_id', $gameIds)
                ->where('ps4_offline_stock', 0)
                ->where('ps4_primary_stock', '>', 0)
                ->groupBy('game_id')
                ->pluck('game_id')
                ->toArray();
        }

        // Transform the data to include availability information for each type
        $transformed_games = $psGames->getCollection()->map(
            function ($game) use ($platform, $ps4PrimaryAvailableGames) {
                // Calculate availability for each type
                // Note: Currently only primary and secondary are enabled for customer self-service
                // To enable offline purchases, uncomment the 'offline' line below
                $types = array(
                    // 'offline'   => $this->calculateTypeAvailability( $game->id, $platform, 'offline', $game, $ps4PrimaryAvailableGames ),
                    'primary'   => $this->calculateTypeAvailability( $game->id, $platform, 'primary', $game, $ps4PrimaryAvailableGames ),
                    'secondary' => $this->calculateTypeAvailability( $game->id, $platform, 'secondary', $game, $ps4PrimaryAvailableGames ),
                );

                return array(
                    'id'        => $game->id,
                    'title'     => $game->title,
                    'code'      => $game->code,
                    'image_url' => $game->image_url,
                    'types'     => $types,
                );
            }
        );

        // Set the transformed collection back to the paginator
        $psGames->setCollection( $transformed_games );

        // Automatic debug data for WooCommerce game (ID 138)
        if ( $platform === 4 ) {
            $debugGameId  = 138;
            $gameIsPresent = in_array( $debugGameId, $gameIds, true );

            $debugData = array(
                'aggregates_all' => DB::table('accounts')
                    ->where('game_id', $debugGameId)
                    ->selectRaw('
                        SUM(ps4_offline_stock) as ps4_offline_stock,
                        SUM(ps4_primary_stock) as ps4_primary_stock,
                        SUM(ps4_secondary_stock) as ps4_secondary_stock,
                        SUM(ps5_offline_stock) as ps5_offline_stock,
                        SUM(ps5_primary_stock) as ps5_primary_stock,
                        SUM(ps5_secondary_stock) as ps5_secondary_stock
                    ')
                    ->first(),
                'accounts_all' => DB::table('accounts')
                    ->where('game_id', $debugGameId)
                    ->select(
                        'id',
                        'store_profile_id',
                        'ps4_offline_stock',
                        'ps4_primary_stock',
                        'ps4_secondary_stock',
                        'ps5_offline_stock',
                        'ps5_primary_stock',
                        'ps5_secondary_stock'
                    )
                    ->orderBy('id')
                    ->get(),
                'accounts_wc_candidates' => DB::table('accounts')
                    ->where('game_id', $debugGameId)
                    ->where('ps4_offline_stock', '=', 0)
                    ->where('ps4_primary_stock', '>', 0)
                    ->select(
                        'id',
                        'store_profile_id',
                        'ps4_offline_stock',
                        'ps4_primary_stock'
                    )
                    ->orderBy('id')
                    ->get(),
            );

            Log::info('WooCommerce debug snapshot for game 138', array(
                'platform' => $platform,
                'request_url' => request()->fullUrl(),
                'present_in_results' => $gameIsPresent,
                'payload_summary' => array(
                    'total_ps4_offline_stock' => optional($debugData['aggregates_all'])->ps4_offline_stock,
                    'total_ps4_primary_stock' => optional($debugData['aggregates_all'])->ps4_primary_stock,
                    'wc_candidate_count' => $debugData['accounts_wc_candidates']->count(),
                    'total_accounts_considered' => $debugData['accounts_all']->count(),
                ),
            ));

            if ( $gameIsPresent ) {
                $responsePayload = $psGames->toArray();
                $responsePayload['debug'] = $debugData;

                Log::info('WooCommerce debug payload for game 138', array(
                    'platform' => $platform,
                    'request_url' => request()->fullUrl(),
                    'payload_summary' => array(
                        'total_ps4_offline_stock' => optional($debugData['aggregates_all'])->ps4_offline_stock,
                        'total_ps4_primary_stock' => optional($debugData['aggregates_all'])->ps4_primary_stock,
                        'wc_candidate_count' => $debugData['accounts_wc_candidates']->count(),
                    ),
                    'aggregates' => $debugData['aggregates_all'],
                    'accounts' => $debugData['accounts_all'],
                    'wc_candidates' => $debugData['accounts_wc_candidates'],
                ));

                return response()->json( $responsePayload );
            }
        }

        Log::info('Games platform API called', array(
            'platform' => $platform,
            'request_url' => request()->fullUrl(),
            'result_count' => $psGames->count(),
        ));

        return response()->json( $psGames );
    }

    /**
     * Calculate availability for a specific game type.
     *
     * @param int    $game_id
     * @param int    $platform
     * @param string $type
     * @param object $game
     * @param array  $ps4PrimaryAvailableGames Pre-fetched game IDs with PS4 primary availability
     * @return array
     */
    private function calculateTypeAvailability($game_id, $platform, $type, $game, $ps4PrimaryAvailableGames = array())
    {
        $stock_field  = "total_{$type}_stock";
        $status_field = "{$type}_status";
        $price_field  = "{$type}_price";

        $total_stock = $game->$stock_field;
        $status      = $game->$status_field;
        $price       = $game->$price_field;

        // Base availability check: must have stock and status enabled
        $available = ( $total_stock > 0 && $status );
        $reason    = null;

        if ( ! $status ) {
            $reason = 'This type is currently disabled for this game.';
        } elseif ( $total_stock == 0 ) {
            $reason = 'Out of stock.';
        } elseif ( $platform == 4 && $type === 'primary' ) {
            // PS4 Primary special rule: must have at least one account with offline = 0 AND primary > 0
            // Use pre-fetched data to avoid N+1 query
            $available_account = in_array( $game_id, $ps4PrimaryAvailableGames );

            // Fallback: if the aggregated offline stock is already zero, trust the query result
            if ( ! $available_account && isset( $game->total_offline_stock ) && (int) $game->total_offline_stock === 0 ) {
                $available_account = true;
            }

            if ( ! $available_account ) {
                $available = false;
                $reason    = 'PS4 primary accounts require offline stock to be 0. All available primary accounts currently have offline stock.';
            }
        }

        return array(
            'available' => $available,
            'stock'     => (int) $total_stock,
            'price'     => (float) $price,
            'status'    => $status ? 'enabled' : 'disabled',
            'reason'    => $reason,
        );
    }



    /**
     * Show the list of PS4 games.
     *
     * @return \Illuminate\View\View
     */
    public function showPS4Games()
    {
        return $this->getGamesByPlatform(4);
    }

    /**
     * Show WooCommerce eligible PS4 games (offline stock = 0 with available primary stock).
     *
     * @return \Illuminate\View\View
     */
    public function showWooCommerceEligiblePS4Games()
    {
        $page = request()->get('page', 1);

        $user = Auth::user();
        $storeProfileId = $user->store_profile_id;

        $cacheKey = CacheManager::getGameListingKey('ps4_wc', $page, $storeProfileId);

        $psGames = CacheManager::getGameListing('ps4_wc', $page, function () use ($storeProfileId) {
            return $this->fetchWooCommerceEligiblePS4Games($storeProfileId);
        }, $storeProfileId);

        $this->isPrimaryActive($psGames, 'ps4_primary_stock', 'ps4_offline_stock', 4);

        $storeProfiles = StoresProfile::all();
        $cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
        $fromCache = CacheManager::wasCacheHit($cacheKey);

        $n = 4;
        $pageTitle = 'Manager - WooCommerce Eligible PS4 Games';
        $pageHeading = 'WooCommerce eligible PS4';
        $pageDescription = 'Games with primary accounts that can sync to WooCommerce (offline stock is zero).';
        $showSearch = false;

        return view('manager.games_listings', compact(
            'psGames',
            'n',
            'storeProfiles',
            'cacheKey',
            'cacheMetadata',
            'fromCache',
            'pageTitle',
            'pageHeading',
            'pageDescription',
            'showSearch'
        ));
    }


    public function showPS5Games()
    {
        return $this->getGamesByPlatform(5);
    }

    public function getGamesWithAccountStocks()
    {
        $psGames = DB::table('accounts')
                    ->select(
                        'games.*',
                        'accounts.game_id',
                        DB::raw('SUM(accounts.ps4_offline_stock) as ps4_offline_stock'),
                        DB::raw('SUM(accounts.ps4_primary_stock) as ps4_primary_stock'),
                        DB::raw('SUM(accounts.ps4_secondary_stock) as ps4_secondary_stock'),
                        DB::raw('SUM(accounts.ps5_offline_stock) as ps5_offline_stock'),
                        DB::raw('SUM(accounts.ps5_primary_stock) as ps5_primary_stock'),
                        DB::raw('SUM(accounts.ps5_secondary_stock) as ps5_secondary_stock')
                    )
                    ->join('games', 'accounts.game_id', '=', 'games.id')
                    ->groupBy('accounts.game_id')
                    ->paginate(10); // Paginate 10 results per page

        $storeProfiles = StoresProfile::all(); // Fetch all store profiles
        return view('manager.games_listings', compact('psGames', 'storeProfiles'));
    }
    public function searchPS4Games(Request $request)
    {
        $query = $request->get('query', '');
        $n = 4; // Define the platform as PS4
        $psGames = $this->filterGames($n, $query);
        $offline_stock   = "ps{$n}_offline_stock";
        $primary_stock   = "ps{$n}_primary_stock";
        // Determine if the primary stock is active
        $this->isPrimaryActive($psGames, $primary_stock, $offline_stock, $n);
        $image_url       = "ps{$n}_image_url";
        return view('manager.partials.games_list', compact('psGames', 'n'))->render();
    }

    public function searchPS5Games(Request $request)
    {
        $query = $request->get('query', '');
        $n = 5; // Define the platform as PS5
        $psGames = $this->filterGames($n, $query);
        $offline_stock   = "ps{$n}_offline_stock";
        $primary_stock   = "ps{$n}_primary_stock";
        // Determine if the primary stock is active
        $this->isPrimaryActive($psGames, $primary_stock, $offline_stock, $n);
        $image_url       = "ps{$n}_image_url";

        return view('manager.partials.games_list', compact('psGames', 'n'))->render();
    }
    public function searchGamesByTitle(Request $request)
    {
        $query = $request->get('query', '');

    // Fetch games matching the title
        $games = Game::where('title', 'LIKE', "%{$query}%")->paginate(10); // Paginate results

        return view('manager.partials.games_row', compact('games'))->render(); // Return partial view
    }


    private function filterGames($platform, $query)
    {
        $user = Auth::user();
        $storeProfileId = $user->store_profile_id;

        $image_url = "ps{$platform}_image_url";

        $offline_stock   = "ps{$platform}_offline_stock";
        $primary_stock   = "ps{$platform}_primary_stock";
        $secondary_stock = "ps{$platform}_secondary_stock";

        $offline_status   = "ps{$platform}_offline_status";
        $primary_status   = "ps{$platform}_primary_status";
        $secondary_status = "ps{$platform}_secondary_status";

        $offline_price   = "ps{$platform}_offline_price";
        $primary_price   = "ps{$platform}_primary_price";
        $secondary_price = "ps{$platform}_secondary_price";
        // Fetch games and their special prices if the user has a store profile
        $psGames = DB::table('accounts')
            ->select(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}",
                DB::raw("COALESCE(special_prices.ps{$platform}_primary_price, games.ps{$platform}_primary_price) as ps{$platform}_primary_price"),
                DB::raw("COALESCE(special_prices.ps{$platform}_secondary_price, games.ps{$platform}_secondary_price) as ps{$platform}_secondary_price"),
                DB::raw("COALESCE(special_prices.ps{$platform}_offline_price, games.ps{$platform}_offline_price) as ps{$platform}_offline_price"),
                DB::raw("SUM(accounts.{$offline_stock}) as {$offline_stock}"),
                DB::raw("SUM(accounts.{$primary_stock}) as {$primary_stock}"),
                DB::raw("SUM(accounts.{$secondary_stock}) as {$secondary_stock}")
            )
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('special_prices', function ($join) use ($storeProfileId) {
                $join->on('games.id', '=', 'special_prices.game_id')
                    ->where('special_prices.store_profile_id', '=', $storeProfileId);
            })
            ->where(function ($queryBuilder) use ($query) {
                // Apply search filter
                $queryBuilder->where('games.title', 'LIKE', "%{$query}%")
                             ->orWhere('games.code', 'LIKE', "%{$query}%");
            })
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}",
                "games.{$offline_price}",
                "games.{$primary_price}",
                "games.{$secondary_price}",
                "special_prices.ps{$platform}_primary_price",
                "special_prices.ps{$platform}_secondary_price",
                "special_prices.ps{$platform}_offline_price",
            )
            ->havingRaw("SUM(accounts.{$offline_stock}) > 0 OR SUM(accounts.{$primary_stock}) > 0 OR SUM(accounts.{$secondary_stock}) > 0")
            ->paginate(10);  // Paginate 10 results per page

        // Optimize: Determine if the primary stock is active using single query
        $this->isPrimaryActive($psGames, $primary_stock, $offline_stock, $platform);

        return $psGames;
    }

    /**
     * Show the system health check page for managers
     *
     * @return \Illuminate\View\View
     */
    public function healthCheck()
    {
        $healthData = [];
        
        // PHP Information
        $healthData['php'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        // Laravel Configuration
        $healthData['laravel'] = [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        // Cache Configuration
        $healthData['cache'] = [
            'default_driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores')),
        ];

        // Session Configuration
        $healthData['session'] = [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
        ];

        // Queue Configuration
        $healthData['queue'] = [
            'default' => config('queue.default'),
            'connections' => array_keys(config('queue.connections')),
        ];

        // Database Check
        $healthData['database'] = [
            'default' => config('database.default'),
            'connection' => 'not_checked',
        ];
        
        try {
            DB::connection()->getPdo();
            $healthData['database']['connection'] = 'working';
            $healthData['database']['driver'] = DB::connection()->getDriverName();
            $healthData['database']['database'] = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            $healthData['database']['connection'] = 'error';
            $healthData['database']['message'] = $e->getMessage();
        }

        // Redis Check
        $healthData['redis'] = [
            'configured' => in_array(config('cache.default'), ['redis']) || in_array(config('session.driver'), ['redis']),
            'extension_loaded' => extension_loaded('redis'),
            'status' => 'not_checked',
        ];

        if ($healthData['redis']['extension_loaded']) {
            try {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $redis->ping();
                $healthData['redis']['status'] = 'working';
                $healthData['redis']['host'] = config('database.redis.default.host');
                $healthData['redis']['port'] = config('database.redis.default.port');
            } catch (\Exception $e) {
                $healthData['redis']['status'] = 'error';
                $healthData['redis']['message'] = $e->getMessage();
            }
        } else {
            $healthData['redis']['status'] = 'not_available';
            $healthData['redis']['message'] = 'PHP Redis extension not loaded';
        }

        // File Cache Statistics (if using file driver)
        if (config('cache.default') === 'file') {
            $healthData['file_cache'] = [
                'configured' => true,
                'status' => 'working',
                'path' => config('cache.stores.file.path'),
            ];
            
            try {
                $cachePath = config('cache.stores.file.path');
                
                // Count cache files
                $files = glob($cachePath . '/*');
                $fileCount = is_array($files) ? count($files) : 0;
                
                // Calculate total size
                $totalSize = 0;
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $totalSize += filesize($file);
                        }
                    }
                }
                
                $healthData['file_cache']['statistics'] = [
                    'total_files' => $fileCount,
                    'total_size' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                    'writable' => is_writable($cachePath),
                ];
            } catch (\Exception $e) {
                $healthData['file_cache']['error'] = $e->getMessage();
            }
        }
        
        // Memcached Check (only if configured as cache driver or session driver)
        $memcachedConfigured = in_array(config('cache.default'), ['memcached']) 
                            || in_array(config('session.driver'), ['memcached']);
        
        $healthData['memcached'] = [
            'configured' => $memcachedConfigured,
            'extension_loaded' => extension_loaded('memcached'),
            'status' => 'not_checked',
            'host' => config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
            'port' => config('cache.stores.memcached.servers.0.port', 11211),
        ];
        
        // Only check Memcached if it's actually being used
        if (!$memcachedConfigured) {
            $healthData['memcached']['status'] = 'not_configured';
            $healthData['memcached']['message'] = 'Memcached is not configured as cache or session driver';
        }

        if ($memcachedConfigured && $healthData['memcached']['extension_loaded']) {
            try {
                $memcached = new \Memcached();
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, 1);
                
                $memcached->addServer(
                    config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                    config('cache.stores.memcached.servers.0.port', 11211)
                );
                
                // Get server stats to check if server is reachable
                $stats = $memcached->getStats();
                $serverKey = $healthData['memcached']['host'] . ':' . $healthData['memcached']['port'];
                
                if (empty($stats) || !isset($stats[$serverKey])) {
                    $healthData['memcached']['status'] = 'error';
                    $healthData['memcached']['message'] = 'Cannot connect to Memcached server. Please check if Memcached service is running.';
                    $healthData['memcached']['solution'] = 'Start Memcached service or check host/port configuration.';
                } else {
                    $serverStats = $stats[$serverKey];
                    
                    // Try to set and get a value
                    $testKey = 'health_check_' . time();
                    $setResult = $memcached->set($testKey, 'test', 5);
                    
                    if (!$setResult) {
                        $resultCode = $memcached->getResultCode();
                        $healthData['memcached']['status'] = 'error';
                        $healthData['memcached']['message'] = 'Failed to write to Memcached. Result code: ' . $resultCode;
                        $healthData['memcached']['result_message'] = $memcached->getResultMessage();
                    } else {
                        $getValue = $memcached->get($testKey);
                        $memcached->delete($testKey);
                        
                        if ($getValue === 'test') {
                            $healthData['memcached']['status'] = 'working';
                            
                            // Add memory and performance statistics
                            $healthData['memcached']['memory'] = [
                                'used_bytes' => $serverStats['bytes'],
                                'used_formatted' => $this->formatBytes($serverStats['bytes']),
                                'max_bytes' => $serverStats['limit_maxbytes'],
                                'max_formatted' => $this->formatBytes($serverStats['limit_maxbytes']),
                                'usage_percent' => round(($serverStats['bytes'] / $serverStats['limit_maxbytes']) * 100, 2),
                                'free_bytes' => $serverStats['limit_maxbytes'] - $serverStats['bytes'],
                                'free_formatted' => $this->formatBytes($serverStats['limit_maxbytes'] - $serverStats['bytes']),
                            ];
                            
                            $totalOps = $serverStats['get_hits'] + $serverStats['get_misses'];
                            $healthData['memcached']['performance'] = [
                                'curr_items' => $serverStats['curr_items'],
                                'total_items' => $serverStats['total_items'],
                                'evictions' => $serverStats['evictions'],
                                'get_hits' => $serverStats['get_hits'],
                                'get_misses' => $serverStats['get_misses'],
                                'hit_rate' => $totalOps > 0 
                                    ? round(($serverStats['get_hits'] / $totalOps) * 100, 2) 
                                    : 0,
                            ];
                        } else {
                            $healthData['memcached']['status'] = 'error';
                            $healthData['memcached']['message'] = 'Memcached not responding correctly (read test failed)';
                            $healthData['memcached']['result_code'] = $memcached->getResultCode();
                            $healthData['memcached']['result_message'] = $memcached->getResultMessage();
                        }
                    }
                }
            } catch (\Exception $e) {
                $healthData['memcached']['status'] = 'error';
                $healthData['memcached']['message'] = $e->getMessage();
                $healthData['memcached']['exception'] = get_class($e);
            }
        } else {
            $healthData['memcached']['status'] = 'not_available';
            $healthData['memcached']['message'] = 'PHP Memcached extension not loaded';
        }

        // Storage Check
        $healthData['storage'] = [
            'disk' => config('filesystems.default'),
            'writable' => is_writable(storage_path()),
            'free_space' => $this->formatBytes(disk_free_space(storage_path())),
            'total_space' => $this->formatBytes(disk_total_space(storage_path())),
        ];

        // PHP Extensions Check
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'json', 'tokenizer', 'xml', 'ctype', 'fileinfo'];
        $optionalExtensions = ['redis', 'memcached', 'imagick', 'gd', 'zip', 'curl'];
        
        $healthData['extensions'] = [
            'required' => [],
            'optional' => [],
        ];

        foreach ($requiredExtensions as $ext) {
            $healthData['extensions']['required'][$ext] = extension_loaded($ext);
        }

        foreach ($optionalExtensions as $ext) {
            $healthData['extensions']['optional'][$ext] = extension_loaded($ext);
        }

        // Cache Test
        try {
            $testKey = 'health_check_cache_' . time();
            Cache::put($testKey, 'test_value', 60);
            $testValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            $healthData['cache']['test'] = ($testValue === 'test_value') ? 'working' : 'error';
        } catch (\Exception $e) {
            $healthData['cache']['test'] = 'error';
            $healthData['cache']['test_message'] = $e->getMessage();
        }

        // Get Cache Statistics from CacheManager
        try {
            $cacheStats = CacheManager::getStats();
            $healthData['cache']['stats'] = $cacheStats;
        } catch (\Exception $e) {
            $healthData['cache']['stats'] = ['error' => $e->getMessage()];
        }

        return view('manager.health-check', compact('healthData'));
    }

    /**
     * Helper function to format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
