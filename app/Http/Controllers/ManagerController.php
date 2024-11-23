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
        $games = Game::paginate(5);

        // Return the view with the games data
        return view('manager.games', compact('games'));
    }
    // Show the game data for editing
    public function edit($id)
    {
        $game = Game::findOrFail($id);
        return response()->json($game); // Return the game data as JSON
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
            'code'                 => 'required|string|max:255|unique:games,code,' . $game->id,
            'full_price'           => 'required|numeric|min:0',
            'ps4_primary_price'    => 'nullable|numeric|min:0',
            'ps4_secondary_price'  => 'nullable|numeric|min:0',
            'ps4_offline_price'    => 'nullable|numeric|min:0',
            'ps5_primary_price'    => 'nullable|numeric|min:0',
            'ps5_secondary_price'  => 'nullable|numeric|min:0', // New field
            'ps5_offline_price'    => 'nullable|numeric|min:0',
            'ps4_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
            'ps5_image'            => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048',
            'ps4_primary_status'   => 'required|boolean',
            'ps4_secondary_status' => 'required|boolean',
            'ps4_offline_status'   => 'required|boolean',
            'ps5_primary_status'   => 'required|boolean',
            'ps5_secondary_status' => 'required|boolean', // New field
            'ps5_offline_status'   => 'required|boolean',
        ]);

        $data = $request->except('_token', 'ps4_image', 'ps5_image'); // Exclude image files from mass assignment

        // Use the service for PS4 image upload
        if ($request->hasFile('ps4_image')) {
            $validatedData['ps4_image_url'] = $this->imageUploadService->upload($request->file('ps4_image'), 'ps4');
        }

        // Use the service for PS5 image upload
        if ($request->hasFile('ps5_image')) {
            $validatedData['ps5_image_url'] = $this->imageUploadService->upload($request->file('ps5_image'), 'ps5');
        }

        // Update the game with the new data
        $game->update($data);
        // Clear the cache after updating the game
        $this->clearPaginatedGameCache();
        return response()->json(array( 'message' => 'Game updated successfully!' ));
    }
    public function store(Request $request)
    {
        // Validate and store the new game
        $validatedData = $request->validate([
            'title'                => 'required|string|max:255',
            'code'                 => 'required|string|max:255|unique:games,code',
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

        // Use the service for PS4 image upload
        if ($request->hasFile('ps4_image')) {
            $validatedData['ps4_image_url'] = $this->imageUploadService->upload($request->file('ps4_image'), 'ps4');
        }

        // Use the service for PS5 image upload
        if ($request->hasFile('ps5_image')) {
            $validatedData['ps5_image_url'] = $this->imageUploadService->upload($request->file('ps5_image'), 'ps5');
        }

        // Create the new game
        Game::create($validatedData);
        // Clear the cache after updating the game
        $this->clearPaginatedGameCache();
        return response()->json(array( 'message' => 'Game created successfully!' ));
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
        $image_url       = "ps{$n}_image_url";

        // Fetch games and their special prices if the user has a store profile
        $psGames = DB::table('accounts')
            ->select(
                'games.id',
                'games.title',
                'games.code',
                "games.{$image_url}",
                DB::raw('COALESCE(special_prices.ps4_primary_price, games.ps4_primary_price) as ps4_primary_price'),
                DB::raw('COALESCE(special_prices.ps4_secondary_price, games.ps4_secondary_price) as ps4_secondary_price'),
                DB::raw('COALESCE(special_prices.ps4_offline_price, games.ps4_offline_price) as ps4_offline_price'),
                DB::raw('COALESCE(special_prices.ps5_primary_price, games.ps5_primary_price) as ps5_primary_price'),
                DB::raw('COALESCE(special_prices.ps5_secondary_price, games.ps5_secondary_price) as ps5_secondary_price'),
                DB::raw('COALESCE(special_prices.ps5_offline_price, games.ps5_offline_price) as ps5_offline_price'),
                DB::raw("SUM(accounts.{$offline_stock}) as {$offline_stock}"),
                DB::raw("SUM(accounts.{$primary_stock}) as {$primary_stock}"),
                DB::raw("SUM(accounts.{$secondary_stock}) as {$secondary_stock}")
            )
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('special_prices', function ($join) use ($storeProfileId) {
                $join->on('games.id', '=', 'special_prices.game_id')
                    ->where('special_prices.store_profile_id', '=', $storeProfileId);
            })
            ->groupBy('games.id', 'games.title', 'games.code', 'special_prices.ps4_primary_price', 'special_prices.ps4_secondary_price', 'special_prices.ps4_offline_price', 'special_prices.ps5_primary_price', 'special_prices.ps5_secondary_price', 'special_prices.ps5_offline_price')
            ->havingRaw("SUM(accounts.{$offline_stock}) > 0 OR SUM(accounts.{$primary_stock}) > 0 OR SUM(accounts.{$secondary_stock}) > 0")
            ->paginate(10);  // Paginate 10 results per page

        $storeProfiles = StoresProfile::all(); // Fetch all store profiles

        // Return the view with the games, platform indicator, and store profiles
        return view('manager.games_listings', compact('psGames', 'n', 'storeProfiles'));
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
}
