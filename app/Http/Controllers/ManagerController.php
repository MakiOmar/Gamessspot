<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
         // Log the entire request input data
        Log::info('Update Request Data: ', $request->all());

        $game = Game::findOrFail($id);

        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:games,code,' . $game->id,
            'full_price' => 'required|numeric|min:0',
            'ps4_primary_price' => 'nullable|numeric|min:0',
            'ps4_secondary_price' => 'nullable|numeric|min:0',
            'ps4_offline_price' => 'nullable|numeric|min:0',
            'ps5_primary_price' => 'nullable|numeric|min:0',
            'ps5_offline_price' => 'nullable|numeric|min:0',
            'ps4_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048', // Validate image upload
            'ps5_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048', // Validate image upload
            'ps4_primary_status' => 'required|boolean',
            'ps4_secondary_status' => 'required|boolean',
            'ps4_offline_status' => 'required|boolean',
            'ps5_primary_status' => 'required|boolean',
            'ps5_offline_status' => 'required|boolean',
        ]);
        $data = $request->except('_token', 'ps4_image', 'ps5_image'); // Exclude image files from mass assignment

        // Handle PS4 image upload
        if ($request->hasFile('ps4_image')) {
            $imageFile = $request->file('ps4_image');
            $imageName = $imageFile->getClientOriginalName(); // Get original file name
            $imageFile->move(public_path('assets/uploads/ps4'), $imageName); // Move file to /public/assets/uploads/ps4
            $data['ps4_image_url'] = 'assets/uploads/ps4/' . $imageName; // Save relative path to database
        }

        // Handle PS5 image upload
        if ($request->hasFile('ps5_image')) {
            $imageFile = $request->file('ps5_image');
            $imageName = $imageFile->getClientOriginalName(); // Get original file name
            $imageFile->move(public_path('assets/uploads/ps5'), $imageName); // Move file to /public/assets/uploads/ps5
            $data['ps5_image_url'] = 'assets/uploads/ps5/' . $imageName; // Save relative path to database
        }
        // Update the game with the new data
        $game->update($data);

        return response()->json(['message' => 'Game updated successfully!']);
    }

    public function store(Request $request)
    {
        // Validate and store the new game
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:games,code',
            'full_price' => 'required|numeric|min:0',
            'ps4_primary_price' => 'nullable|numeric|min:0',
            'ps4_secondary_price' => 'nullable|numeric|min:0',
            'ps4_offline_price' => 'nullable|numeric|min:0',
            'ps5_primary_price' => 'nullable|numeric|min:0',
            'ps5_offline_price' => 'nullable|numeric|min:0',
            'ps4_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048', // Validate image upload
            'ps5_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif,svg|max:2048', // Validate image upload
            'ps4_primary_status' => 'required|boolean',
            'ps4_secondary_status' => 'required|boolean',
            'ps4_offline_status' => 'required|boolean',
            'ps5_primary_status' => 'required|boolean',
            'ps5_offline_status' => 'required|boolean',
        ]);

        // Handle PS4 image upload
        if ($request->hasFile('ps4_image')) {
            $imageFile = $request->file('ps4_image');
            $imageName = $imageFile->getClientOriginalName(); // Get original file name
            $imageFile->move(public_path('assets/uploads/ps4'), $imageName); // Move file to /public/assets/uploads/ps4
            $validatedData['ps4_image_url'] = 'assets/uploads/ps4/' . $imageName; // Save relative path to database
        }

        // Handle PS5 image upload
        if ($request->hasFile('ps5_image')) {
            $imageFile = $request->file('ps5_image');
            $imageName = $imageFile->getClientOriginalName(); // Get original file name
            $imageFile->move(public_path('assets/uploads/ps5'), $imageName); // Move file to /public/assets/uploads/ps5
            $validatedData['ps5_image_url'] = 'assets/uploads/ps5/' . $imageName; // Save relative path to database
        }

        // Create the new game
        Game::create($validatedData);

        return response()->json(['message' => 'Game created successfully!']);
    }
}
