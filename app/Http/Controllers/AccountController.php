<?php

namespace App\Http\Controllers;

use App\Models\Account; // Assuming Account is the model
use Illuminate\Http\Request;
use App\Models\Game;
use App\Exports\AccountsExport;
use Maatwebsite\Excel\Facades\Excel;

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
    public function export()
    {
        return Excel::download(new AccountsExport(), 'accounts.xlsx');
    }

    public function search(Request $request)
    {
        $query = $request->get('search');

        $accounts = Account::with('game')
        ->where('mail', 'like', "%{$query}%")
        ->orWhereHas(
            'game',
            function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");
            }
        )
        ->get();

        return view('manager.partials.account_rows', compact('accounts'))->render(); // Use a partial view to render rows
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate(
            array(
                'mail'          => 'required|email',
                'password'      => 'required|string',
                'game_id'       => 'required|exists:games,id',
                'region'        => 'required|string|max:2',
                'cost'          => 'required|numeric',
                'birthdate'     => 'required|date',
                'login_code'    => 'required|string',
                'ps4_primary'   => 'nullable|boolean',
                'ps4_secondary' => 'nullable|boolean',
                'ps5_primary'   => 'nullable|boolean',
                'ps5_secondary' => 'nullable|boolean',
                'ps4_offline1'  => 'nullable|boolean',
                'ps4_offline2'  => 'nullable|boolean',
                'ps5_offline'   => 'nullable|boolean',
            )
        );

        // Default stock values
        $ps4_primary_stock   = 1;
        $ps4_secondary_stock = 1;
        $ps5_primary_stock   = 1;
        $ps5_secondary_stock = 1;
        $ps4_offline_stock   = 2; // Default offline stock should be 2
        $ps5_offline_stock   = 1;

        // If any of the stocks are checked, set them to zero (except offline logic)
        if ($request->has('ps4_primary')) {
            $ps4_primary_stock = 0;
        }
        if ($request->has('ps4_secondary')) {
            $ps4_secondary_stock = 0;
        }
        if ($request->has('ps5_primary')) {
            $ps5_primary_stock = 0;
        }
        if ($request->has('ps5_secondary')) {
            $ps5_secondary_stock = 0;
        }
        if ($request->has('ps5_offline')) {
            $ps5_offline_stock = 0;
        }

        // Offline stock logic for PS4
        if ($request->has('ps4_offline1')) {
            $ps4_offline_stock = 1; // Add 1 to the default stock if ps4_offline1 is checked
        }
        if ($request->has('ps4_offline2')) {
            $ps4_offline_stock = 0; // Set stock to zero if ps4_offline2 is checked
        }

        // Create the new account with adjusted stock values
        $account = Account::create(
            array(
                'mail'                => $request->mail,
                'password'            => $request->password,
                'game_id'             => $request->game_id,
                'region'              => $request->region,
                'cost'                => $request->cost,
                'birthdate'           => $request->birthdate,
                'login_code'          => $request->login_code,
                'ps4_primary_stock'   => $ps4_primary_stock,
                'ps4_secondary_stock' => $ps4_secondary_stock,
                'ps4_offline_stock'   => $ps4_offline_stock,
                'ps5_primary_stock'   => $ps5_primary_stock,
                'ps5_secondary_stock' => $ps5_secondary_stock,
                'ps5_offline_stock'   => $ps5_offline_stock,
            )
        );

        // Check if the account was created successfully
        if ($account) {
            return response()->json(array( 'success' => 'Account created and game stock updated successfully!' ));
        }

        // If account creation failed, return an error response
        return response()->json(array( 'error' => 'Failed to create account. Please try again.' ), 500);
    }
}
