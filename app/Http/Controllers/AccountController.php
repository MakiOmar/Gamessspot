<?php

namespace App\Http\Controllers;

use App\Models\Account; // Assuming Account is the model
use Illuminate\Http\Request;
use App\Models\Game;
use App\Exports\AccountsExport;
use App\Imports\AccountsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;

class AccountController extends Controller
{
    // Display the accounts table
    public function index()
    {
        // Assuming you want paginated accounts
        $accounts = Account::orderBy('created_at', 'asc')->paginate(10);

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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
        ]);

        try {
            Excel::import(new AccountsImport, $request->file('file'));
            
            // Clear the cache after import
            Cache::forget('total_account_cost');
            
            return response()->json([
                'success' => 'Accounts imported successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function template()
    {
        $templateData = [
            [
                'Mail', 'Password', 'Game', 'Region', 'Cost', 'Birthdate', 'Login Code'
            ],
            [
                'example@email.com', 'password123', 'Game Title', 'US', '25.00', '1990-01-01', 'ABC123'
            ]
        ];

        return Excel::download(new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function array(): array {
                return $this->data;
            }
        }, 'accounts_template.xlsx');
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
        ->orderBy('created_at', 'asc');
        $accounts = $accounts->paginate(20)->appends($request->all());
        $showing = "<div class=\"mb-2 mb-md-0 mobile-results-count\">Showing {$accounts->firstItem()} to {$accounts->lastItem()} of {$accounts->total()} results</div>";
        // Return the updated rows for the table (assuming a partial view)
        return response()->json([
            'rows' => view('manager.partials.account_rows', compact('accounts'))->render(),
            'pagination' => '<div id="search-pagination">' . $showing . $accounts->links('vendor.pagination.bootstrap-5')->render() . '</div>',
        ]);
    }

    public function getTotalAccountCost()
    {
        $totalCost = Cache::remember('total_account_cost', 600, function () {
            return Account::sum('cost'); // Sum the costs if not already cached
        });

        return response()->json(['total_cost' => $totalCost]);
    }
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate(
            [
                'mail'          => 'required|email|unique:accounts,mail',
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
                'ps5_offline2'  => 'nullable|boolean',
            ],
            [
                'mail.required' => 'The email field is required.',
                'mail.email' => 'Please provide a valid email address.',
                'mail.unique' => 'This email address is already in use.',
            ]
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

        // Debug: Log what we're receiving
        \Log::info('PS5 Debug - Request data:', [
            'ps5_offline' => $request->has('ps5_offline'),
            'ps5_primary' => $request->has('ps5_primary'),
            'ps5_secondary' => $request->has('ps5_secondary'),
            'ps5_offline2' => $request->has('ps5_offline2'),
            'all_request' => $request->all()
        ]);

        // Special PS5 Offline Logic: If only PS5 offline is checked, add two offline stocks
        if ($request->has('ps5_offline') && !$request->has('ps5_primary') && !$request->has('ps5_secondary')) {
            // If only PS5 offline is checked, set stock to 2 (two offline stocks)
            $ps5_offline_stock = 2;
            \Log::info('PS5 Special logic applied: ps5_offline_stock = 2');
        } elseif ($request->has('ps5_offline')) {
            // If PS5 offline is checked with other options, set to 0
            $ps5_offline_stock = 0;
            \Log::info('PS5 General logic applied: ps5_offline_stock = 0');
        } else {
            \Log::info('PS5 Default logic: ps5_offline_stock = 1');
        }

        // Offline stock logic for PS4
        if ($request->has('ps4_offline1')) {
            $ps4_offline_stock = 1; // Add 1 to the default stock if ps4_offline1 is checked
        }
        if ($request->has('ps4_offline2')) {
            $ps4_offline_stock = 0; // Set stock to zero if ps4_offline2 is checked
        }

        // Debug: Log final stock values
        \Log::info('Final stock values:', [
            'ps4_primary_stock' => $ps4_primary_stock,
            'ps4_secondary_stock' => $ps4_secondary_stock,
            'ps4_offline_stock' => $ps4_offline_stock,
            'ps5_primary_stock' => $ps5_primary_stock,
            'ps5_secondary_stock' => $ps5_secondary_stock,
            'ps5_offline_stock' => $ps5_offline_stock,
        ]);

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
            Cache::forget('total_account_cost'); // Clear the cache
            return response()->json(array( 'success' => 'Account created and game stock updated successfully!' ));
        }

        // If account creation failed, return an error response
        return response()->json(array( 'error' => 'Failed to create account. Please try again.' ), 500);
    }

    public function edit($id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $request->validate([
            'mail' => 'required|email|unique:accounts,mail,' . $id,
            'password' => 'required|string',
            'game_id' => 'required|exists:games,id',
            'region' => 'required|string|max:2',
            'cost' => 'required|numeric',
            'birthdate' => 'required|date',
            'login_code' => 'required|string',
        ]);

        $account->update($request->all());

        return response()->json(['success' => 'Account updated successfully!']);
    }
}
