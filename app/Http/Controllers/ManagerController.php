<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
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

    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'full_price' => 'required|numeric|min:0',
            'ps4_primary_price' => 'nullable|numeric|min:0',
            'ps4_secondary_price' => 'nullable|numeric|min:0',
            'ps4_offline_price' => 'nullable|numeric|min:0',
            'ps5_primary_price' => 'nullable|numeric|min:0',
            'ps5_offline_price' => 'nullable|numeric|min:0',
            'ps4_image_url' => 'nullable|string',
            'ps5_image_url' => 'nullable|string',
            'ps4_primary_status' => 'required|boolean',
            'ps4_secondary_status' => 'required|boolean',
            'ps4_offline_status' => 'required|boolean',
            'ps5_primary_status' => 'required|boolean',
            'ps5_offline_status' => 'required|boolean',
        ]);

        // Update the game with the new data
        $game->update($request->all());

        return response()->json(['message' => 'Game updated successfully!']);
    }
}
