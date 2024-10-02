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
}
