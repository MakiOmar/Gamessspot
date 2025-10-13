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
        // Retrieve all games from the database
        $games = Game::paginate(100);

        // Return the view with the games data
        return view('manager.games', compact('games'));
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

        // Forget cache for all pages of PS4 games
        for ($page = 1; $page <= $ps4TotalPages; $page++) {
            Cache::forget("ps4_games_page_{$page}");
        }

        // Forget cache for all pages of PS5 games
        for ($page = 1; $page <= $ps5TotalPages; $page++) {
            Cache::forget("ps5_games_page_{$page}");
        }
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
        $psGames = DB::table('accounts')
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

        // Determine if the primary stock is active
        $this->isPrimaryActive($psGames, $primary_stock, $offline_stock, $n);

        $storeProfiles = StoresProfile::all(); // Fetch all store profiles

        // Return the view with the games, platform indicator, and store profiles.
        return view('manager.games_listings', compact('psGames', 'n', 'storeProfiles'));
    }
    protected function isPrimaryActive(&$psGames, $primary_stock, $offline_stock, $n)
    {
        foreach ($psGames as $game) {
            $oldestAccount = DB::table('accounts')
                ->where('game_id', $game->id)
                ->where($offline_stock, 0)
                ->where($primary_stock, '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();
            if ($oldestAccount && 5 !== $n) {
                $game->is_primary_active = true;
            } elseif (5 === $n) {
                $game->is_primary_active = true;
            } else {
                $game->is_primary_active = false;
            }
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

        // Fetch all games that have any stock for this platform
        $psGames = DB::table( 'accounts' )
            ->select(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url} as image_url",
                "games.{$offline_price} as offline_price",
                "games.{$primary_price} as primary_price",
                "games.{$secondary_price} as secondary_price",
                "games.{$offline_status} as offline_status",
                "games.{$primary_status} as primary_status",
                "games.{$secondary_status} as secondary_status",
                DB::raw( "SUM(accounts.ps{$platform}_offline_stock) as total_offline_stock" ),
                DB::raw( "SUM(accounts.ps{$platform}_primary_stock) as total_primary_stock" ),
                DB::raw( "SUM(accounts.ps{$platform}_secondary_stock) as total_secondary_stock" )
            )
            ->join( 'games', 'accounts.game_id', '=', 'games.id' )
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                "games.{$offline_price}",
                "games.{$primary_price}",
                "games.{$secondary_price}",
                "games.{$offline_status}",
                "games.{$primary_status}",
                "games.{$secondary_status}"
            )
            ->havingRaw( "SUM(accounts.ps{$platform}_offline_stock) > 0 OR SUM(accounts.ps{$platform}_primary_stock) > 0 OR SUM(accounts.ps{$platform}_secondary_stock) > 0" )
            ->paginate( 20 );

        // Transform the data to include availability information for each type
        $transformed_games = $psGames->getCollection()->map(
            function ($game) use ($platform) {
                // Calculate availability for each type
                // Note: Currently only primary and secondary are enabled for customer self-service
                // To enable offline purchases, uncomment the 'offline' line below
                $types = array(
                    // 'offline'   => $this->calculateTypeAvailability( $game->id, $platform, 'offline', $game ),
                    'primary'   => $this->calculateTypeAvailability( $game->id, $platform, 'primary', $game ),
                    'secondary' => $this->calculateTypeAvailability( $game->id, $platform, 'secondary', $game ),
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

        return response()->json( $psGames );
    }

    /**
     * Calculate availability for a specific game type.
     *
     * @param int    $game_id
     * @param int    $platform
     * @param string $type
     * @param object $game
     * @return array
     */
    private function calculateTypeAvailability($game_id, $platform, $type, $game)
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
            $available_account = Account::where( 'game_id', $game_id )
                ->where( 'ps4_offline_stock', 0 )
                ->where( 'ps4_primary_stock', '>', 0 )
                ->exists();

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
        // Determine if the primary stock is active
        foreach ($psGames as $game) {
            $oldestAccount = DB::table('accounts')
                ->where('game_id', $game->id)
                ->where($primary_stock, '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            $game->is_primary_active = false;

            if ($oldestAccount) {
                // Check if offline stock is 0 for the oldest account
                if ($oldestAccount->$offline_stock == 0) {
                    $game->is_primary_active = true;
                }
            }
        }
        return $psGames;
    }
}
