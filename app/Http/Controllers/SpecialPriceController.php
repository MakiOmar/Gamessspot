<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\StoresProfile;
use App\Models\SpecialPrice;

class SpecialPriceController extends Controller
{
    public function showSpecialPricesForm($gameId)
    {
        $game = Game::findOrFail($gameId);
        $storeProfiles = StoresProfile::all(); // Fetch all store profiles

        return view('manager.special_prices', compact('game', 'storeProfiles'));
    }
    public function storeSpecialPrices(Request $request, $gameId)
    {
        // Validate the request
        $request->validate([
            'store_profile_id'    => 'required|exists:stores_profile,id',
            'ps4_primary_price'   => 'required|numeric|min:0',
            'ps4_secondary_price' => 'required|numeric|min:0',
            'ps4_offline_price'   => 'required|numeric|min:0',
            'ps5_primary_price'   => 'required|numeric|min:0',
            'ps5_secondary_price' => 'required|numeric|min:0',
            'ps5_offline_price'   => 'required|numeric|min:0',
        ]);

        // Create or update the special price using Eloquent
        SpecialPrice::updateOrCreate(
            [
                'game_id' => $gameId,
                'store_profile_id' => $request->input('store_profile_id'),
            ],
            $request->only([
                'ps4_primary_price',
                'ps4_secondary_price',
                'ps4_offline_price',
                'ps5_primary_price',
                'ps5_secondary_price',
                'ps5_offline_price',
            ])
        );

        // Return JSON response
        return response()->json([
            'message' => 'Special prices saved successfully!',
        ], 200);
    }

    public function getGamesWithSpecialPrices($storeProfileId)
    {
        // Fetch the store profile by ID
        $storeProfile = StoresProfile::findOrFail($storeProfileId);

        // Fetch the special prices along with their game titles for the given store profile
        $specialPrices = SpecialPrice::where('special_prices.store_profile_id', $storeProfileId)
            ->join('games', 'special_prices.game_id', '=', 'games.id')
            ->select(
                'games.title',  // Fetch game title
                'special_prices.id',
                'special_prices.ps4_primary_price',
                'special_prices.ps4_secondary_price',
                'special_prices.ps4_offline_price',
                'special_prices.ps5_primary_price',
                'special_prices.ps5_secondary_price',
                'special_prices.ps5_offline_price'
            )
            ->get();

        // Pass both special prices and store profile to the view
        return view('manager.store_prices', compact('specialPrices', 'storeProfile'));
    }
    public function edit($id)
    {
        // Fetch the special price record by its ID
        $specialPrice = SpecialPrice::findOrFail($id);

        // Return the special price as a JSON response
        return response()->json($specialPrice);
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'ps4_primary_price'   => 'required|numeric|min:0',
            'ps4_secondary_price' => 'required|numeric|min:0',
            'ps4_offline_price'   => 'required|numeric|min:0',
            'ps5_primary_price'   => 'required|numeric|min:0',
            'ps5_secondary_price' => 'required|numeric|min:0',
            'ps5_offline_price'   => 'required|numeric|min:0',
        ]);

        // Find the special price by ID
        $specialPrice = SpecialPrice::findOrFail($id);

        // Update the special price with the new values
        $specialPrice->update([
            'ps4_primary_price'   => $request->input('ps4_primary_price'),
            'ps4_secondary_price' => $request->input('ps4_secondary_price'),
            'ps4_offline_price'   => $request->input('ps4_offline_price'),
            'ps5_primary_price'   => $request->input('ps5_primary_price'),
            'ps5_secondary_price' => $request->input('ps5_secondary_price'),
            'ps5_offline_price'   => $request->input('ps5_offline_price'),
        ]);

        // Return success response
        return response()->json(['message' => 'Special prices updated successfully!']);
    }
}
