<?php

namespace App\Http\Controllers;

use App\Models\Account; // Assuming Account is the model
use Illuminate\Http\Request;
use App\Models\Game;

class AccountController extends Controller
{
    // Display the accounts table
    public function index()
    {
        // Assuming you want paginated accounts
        $accounts = Account::paginate(10); // Adjust pagination as needed

        $games = Game::all(); // Fetch all games

        // Get the flag emojis from the config
        $flags = config('flags.flags'); // This retrieves the array of flags

        // Return the view with the accounts data
        return view('manager.accounts', compact('accounts', 'games', 'flags'));
    }
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
        'mail' => 'required|email',
        'password' => 'required|string',
        'game_id' => 'required|exists:games,id',
        'region' => 'required|string|max:2',
        'cost' => 'required|numeric',
        'birthdate' => 'required|date',
        'login_code' => 'required|string',
        'ps4_primary' => 'nullable|boolean',
        'ps4_secondary' => 'nullable|boolean',
        'ps4_offline1' => 'nullable|boolean',
        'ps4_offline2' => 'nullable|boolean',
        'ps5_primary' => 'nullable|boolean',
        'ps5_secondary' => 'nullable|boolean',
        'ps5_offline' => 'nullable|boolean',
        ]);

        // Store the new account
        Account::create([
        'mail' => $request->mail,
        'password' => $request->password,
        'game_id' => $request->game_id,
        'region' => $request->region,
        'cost' => $request->cost,
        'birthdate' => $request->birthdate,
        'login_code' => $request->login_code,
        'ps4_primary_stock' => $request->ps4_primary ? 1 : 0,
        'ps4_secondary_stock' => $request->ps4_secondary ? 1 : 0,
        'ps4_offline_stock' => $request->ps4_offline1 ? 1 : 0,
        'ps5_primary_stock' => $request->ps5_primary ? 1 : 0,
        'ps5_secondary_stock' => $request->ps5_secondary ? 1 : 0,
        'ps5_offline_stock' => $request->ps5_offline ? 1 : 0,
        ]);

        return response()->json(['success' => 'Account created successfully!']);
    }
}
