<?php

namespace App\Http\Controllers;

use App\Models\Account; // Assuming Account is the model
use Illuminate\Http\Request;
use App\Models\Game;
use App\Exports\AccountsExport;
use App\Imports\AccountsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    // Display the accounts table
    public function index()
    {
        // Get current page from request
        $page = request()->get('page', 1);
        
        // Get cache key for this listing
        $cacheKey = CacheManager::getAccountListingKey($page);
        
        // ✅ Cache account listings with pagination
        $accounts = CacheManager::getAccountListing($page, function () {
            return Account::orderBy('created_at', 'asc')->paginate(10);
        });

        $games = Game::all(); // Fetch all games

        // Get the flag emojis from the config
        $flags = config('flags.flags'); // This retrieves the array of flags
        
        // Get cache metadata
        $cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
        $fromCache = CacheManager::wasCacheHit($cacheKey);

        // Return the view with the accounts data
        return view('manager.accounts', compact('accounts', 'games', 'flags', 'cacheKey', 'cacheMetadata', 'fromCache'));
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
            
            // ✅ No need to manually clear cache - AccountObserver handles it
            // Observer will automatically invalidate cache when accounts are created
            
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
                'Mail', 'Password', 'Game', 'Region', 'Cost', 'Birthdate', 'Login Code',
                'PS4 Primary Stock', 'PS4 Secondary Stock', 'PS4 Offline Stock',
                'PS5 Primary Stock', 'PS5 Secondary Stock', 'PS5 Offline Stock'
            ],
            [
                'example@email.com', 'password123', 'Game Title', 'US', '25.00', '1990-01-01', 'ABC123',
                '1', '1', '2', '1', '1', '1'
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
        // ✅ Use CacheManager for consistent caching
        $totalCost = CacheManager::getTotalAccountCost();

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

        // Special PS5 Only Logic: If "PS5 Only" is checked, set all PS4 stocks to 0
        if ($request->has('ps5_only')) {
            // If "PS5 Only" is checked, set all PS4 stocks to 0 and PS5 offline stock to 2
            $ps4_primary_stock = 0;
            $ps4_secondary_stock = 0;
            $ps4_offline_stock = 0;
            $ps5_offline_stock = 2;
        } else {
            // Normal logic: If any of the stocks are checked, set them to zero (except offline logic)
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

            // Offline stock logic for PS4 (only when not in PS5 Only mode)
            if ($request->has('ps4_offline1')) {
                $ps4_offline_stock = 1; // Add 1 to the default stock if ps4_offline1 is checked
            }
            if ($request->has('ps4_offline2')) {
                $ps4_offline_stock = 0; // Set stock to zero if ps4_offline2 is checked
            }
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
            // ✅ No need to manually clear cache - AccountObserver handles it automatically
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

    public function updateStock(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'ps4_primary_stock' => 'required|integer|min:0',
            'ps4_secondary_stock' => 'required|integer|min:0',
            'ps4_offline_stock' => 'required|integer|min:0',
            'ps5_primary_stock' => 'required|integer|min:0',
            'ps5_secondary_stock' => 'required|integer|min:0',
            'ps5_offline_stock' => 'required|integer|min:0',
        ]);

        $account->update($validated);

        return response()->json(['success' => 'Account stock levels updated successfully!']);
    }

    /**
     * Remove the specified account from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        try {
            $account->delete();

            // Invalidate caches impacted by account deletion
            CacheManager::invalidateAccounts();
            CacheManager::invalidateGames();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete account', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete this account. Please try again or contact support.',
            ], 500);
        }
    }
}
