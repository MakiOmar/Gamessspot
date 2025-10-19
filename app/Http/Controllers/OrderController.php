<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Account;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Report;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Role;
use Illuminate\Support\Facades\Http;
use App\Services\SettingsService;
use Rawilk\Settings\Facades\Settings;
use App\Jobs\SendInventoryWebhookJob;

class OrderController extends Controller
{
    protected $pagination = 10;
    protected $pos_base;

    public function login()
    {
        // Get POS settings from database
        $this->pos_base = SettingsService::getPosBaseUrl();
        $username = SettingsService::getPosUsername();
        $password = SettingsService::getPosPassword();
        
        // Check if a valid token is already stored in the cache
        $token = Cache::get('api_token');

        // If token is not found or expired, regenerate it
        if (!$token) {
            // API endpoint for login
            $endpoint = $this->pos_base . '/api/login';

            // Request body data
            $data = [
                'username' => $username,
                'password' => $password
            ];

            // Make a POST request to the API
            $response = Http::post($endpoint, $data);

            // Check if the request was successful
            if ($response->successful()) {
                // Parse the token from the response
                $token = $response->json()['token'];

                // Store the token in cache for 3 days
                Cache::put('api_token', $token, now()->addDays(3));

                return $token;
            }

            return false; // Return false if authentication fails
        }

        // Return the existing token from the cache
        return $token;
    }
    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::guard('admin')->user(); // Assuming 'admin' guard is used
        $roles = $user->roles->pluck('name');

        // Get only today's orders
        $ordersQuery = Order::with([
            'seller',
            'account.game', // Load game through account
            'card',
            'reports'
        ])->whereDate('created_at', Carbon::today());

        // Check for 'admin' role
        if ($roles->contains('admin')) {
            // Apply store profile filter if 'id' is provided
            if (request()->has('id')) {
                $storeId = request()->get('id');
                $ordersQuery->where('store_profile_id', $storeId);
            }

            $orders = $ordersQuery->paginate($this->pagination);
            return $this->renderOrders($orders, $user);
        }

        // Check for 'sales' or 'account manager' roles
        if ($roles->intersect(['sales', 'account manager'])->isNotEmpty()) {
            $orders = $ordersQuery
                ->where('seller_id', $user->id)
                ->paginate($this->pagination);

            return $this->renderOrders($orders, $user);
        }

        // Check for 'accountant' role with a specific store profile ID
        if ($roles->contains('accountant') && request()->has('id')) {
            $storeId = request()->get('id');

            $orders = $ordersQuery
                ->where('store_profile_id', $storeId)
                ->orderBy('buyer_name', 'asc')
                ->paginate($this->pagination);

            return $this->renderOrders($orders, $user);
        }

        // Unauthorized case
        abort(403, 'Unauthorized action.');
    }


    /**
 * Get the latest 10 orders for a customer by buyer_phone.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
    public function latestCustomerOrders(Request $request)
    {
        $validatedData = $request->validate([
        'buyer_phone' => 'required|string|max:15',
        ]);

        try {
            $orders = Order::with(['seller', 'account.game', 'card'])
            ->where('buyer_phone', $validatedData['buyer_phone'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

            return response()->json([
            'success' => true,
            'orders' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve orders.',
            'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uniqueBuyers()
    {
        $user = Auth::guard('admin')->user();
        $roles = $user->roles->pluck('name');

    // Check if the user is an admin
        if ($roles->contains('admin')) {
            $uniqueBuyers = Order::select('buyer_phone', 'buyer_name')
            ->groupBy('buyer_phone', 'buyer_name') // Group by buyer_phone and buyer_name to ensure uniqueness
            ->paginate(10); // Paginate the results with 10 per page

            return view('manager.unique_buyers', compact('uniqueBuyers'));
        }

    // Unauthorized case
        abort(403, 'Unauthorized action.');
    }


    /**
     * Render the orders view.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $orders
     * @param \App\Models\User $user
     * @return \Illuminate\Contracts\View\View
     */
    protected function renderOrders($orders, $user)
    {
        $status = request()->get('status', 'all');
        return view('manager.orders', compact('orders', 'user', 'status'));
    }


    /**
     * Search for orders by buyer phone.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        // Get the input values
        $query          = $request->input('search');
        $startDate      = $request->input('start_date');
        $endDate        = $request->input('end_date');
        $storeProfileId = $request->input('store_profile_id');
        $status         = $request->input('status', 'all'); // Default to 'all' if not provided

        // Build the query to filter orders
        $orders = Order::with(array( 'seller', 'account.game' ));
        // Check if the user is an admin
        $user = Auth::user();
        $isAdmin = $user->roles->contains('name', 'admin');
        $isAccountant = $user->roles->contains('name', 'accountant');

        // If the user is not an admin, filter by seller_id
        if (!$isAdmin && !$isAccountant) {
            $orders->where('seller_id', $user->id);
        }

        // Filter by buyer phone if search query exists
        if ($query) {
            $orders->where(
                function ($q) use ($query) {
                    // Search by buyer phone or buyer name
                    $q->where('buyer_phone', 'like', "%$query%")
                    ->orWhere('buyer_name', 'like', "%$query%")
                    // Search by seller name
                    ->orWhereHas('seller', function ($q) use ($query) {
                        $q->where('name', 'like', "%$query%");
                    })
                    // Search by account email (related through account_id)
                    ->orWhereHas(
                        'account',
                        function ($q) use ($query) {
                            $q->where('mail', 'like', "%$query%");
                        }
                    )
                    ->orWhereHas('card', function ($q) use ($query) {
                        $q->where('code', 'like', "%$query%");
                    })
                    // Search by game title (related through account's game)
                    ->orWhereHas(
                        'account.game',
                        function ($q) use ($query) {
                            $q->where('title', 'like', "%$query%");
                        }
                    );
                }
            );
        }

        // Filter by date range if both start and end dates are provided
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                // Filter orders created on the exact date (whole day)
                $orders->whereDate('created_at', $startDate);
            } else {
                // Filter between the date range
                $orders->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Filter by status if it's not 'all'
        if ($status !== 'all') {
            $orders->whereHas(
                'reports',
                function ($q) use ($status) {
                    $q->where('status', $status);
                }
            );
        }
        if ($storeProfileId != 0) {
            $orders->where('orders.store_profile_id', $storeProfileId)->orderBy('buyer_name', 'asc')->orderBy('created_at', 'desc');
        }
        // Execute the query and paginate or get the results
        $orders = $orders->paginate(20)->appends($request->all());
        $showing = "<div class=\"mb-2 mb-md-0 mobile-results-count\">Showing {$orders->firstItem()} to {$orders->lastItem()} of {$orders->total()} results</div>";
        // Return the updated rows for the table (assuming a partial view)
        return response()->json([
            'rows' => view('manager.partials.order_rows', compact('orders', 'status'))->render(),
            'pagination' => '<div id="search-pagination">' . $showing . $orders->links('vendor.pagination.bootstrap-5')->render() . '</div>',
        ]);
    }

    public function searchCustomersHelper(Request $request)
    {
        // Extract search query
        $query = $request->input('search');

        // Ensure admin-only access
        $user = Auth::user();
        /*
        if (!$user->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }
        */
        // Query for unique customers
        $customers = Order::query()
            ->select('buyer_name', 'buyer_phone')
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('buyer_phone', 'like', "%$query%")
                    ->orWhere('buyer_name', 'like', "%$query%");
                });
            })
            ->distinct()
            ->orderBy('buyer_name')
            ->get();
        return $customers;
    }
    public function searchCustomers(Request $request)
    {

        $customers = $this->searchCustomersHelper($request);
        // Render a partial view with the results
        return view('manager.partials.customer_rows', compact('customers'))->render();
    }

    public function quickSearch(Request $request)
    {
        // Get the search query input
        $query = $request->input('search');
        if (empty($query)) {
            $query = $_GET['search'];
        }
        // Build the query to filter orders
        $orders = Order::with(['seller', 'account.game']);

        // Check if the user is an admin
        $user = Auth::user();
        $isAdmin = $user->roles->contains('name', 'admin');

        // If the user is not an admin, filter by seller_id
        if (!$isAdmin) {
            //$orders->where('seller_id', $user->id);
        }
        $buyer   = false;
        $account = false;
        // Determine the type of query
        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            // If query is a valid email, search in account email
            $orders->whereHas('account', function ($q) use ($query) {
                $q->where('mail', 'like', "%$query%");
            });
            $account = true;
        } elseif (preg_match('/^\+?\d+$/', $query)) {
            // If query is a phone number (with or without '+'), search in buyer_phone
            $orders->where('buyer_phone', 'like', "%$query%");
            $buyer   = true;
        } else {
            // Otherwise, search in buyer_name or buyer_phone
            $orders->where(function ($q) use ($query) {
                $q->where('buyer_name', 'like', "%$query%")
                  ->orWhere('buyer_phone', 'like', "%$query%");
            });
            $buyer   = true;
        }

        // Execute the query and get results
        $orders = $orders->paginate(20);
        if ($buyer && $orders && !empty($orders)) {
            $buyer = $orders[0];
        }

        // Return the updated rows for the table (assuming a partial view)
        return view('manager.orders-quick', compact('orders', 'buyer'))->render();
    }


    /**
     * Export the list of orders as an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        return Excel::download(new OrdersExport(), 'orders.xlsx');
    }

    /**
     * Export the list of customers as an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function customersExport()
    {
        return Excel::download(new CustomersExport(), 'customers.xlsx');
    }

    /**
     * Undo an order by deleting it and updating the corresponding stock.
     */
    public function undo(Request $request)
    {
        DB::beginTransaction();

        try {
            $order = Order::find($request->order_id);

            if ($order) {
                // Track game details for webhook
                $gameId = null;
                $platform = null;
                $type = null;
                $newStock = null;
                
                if ($order->card_id) {
                    $this->updateCardStatus($order->card_id);
                } else {
                    // Extract game details from the order before restocking
                    $account = Account::find($order->account_id);
                    
                    if ($account) {
                        $gameId = $account->game_id;
                        
                        // Parse platform and type from sold_item field
                        // Example: "ps4_primary_stock" -> platform: 4, type: primary
                        if (preg_match('/ps(\d+)_(\w+)_stock/', $order->sold_item, $matches)) {
                            $platform = $matches[1];  // e.g., "4" or "5"
                            $type = $matches[2];      // e.g., "primary", "secondary", "offline"
                        }
                    }
                    
                    // Increment the stock
                    $this->incrementAccountStock($order);
                    
                    // Get the new stock count after increment
                    if ($account) {
                        $account->refresh(); // Reload from database
                        $newStock = $account->{$order->sold_item};
                    }
                }

                if ($request->has('report_id')) {
                    $this->updateReportStatus($request->report_id);
                }

                $this->deleteOrderAndReports($order);

                DB::commit();
                Cache::forget('unique_buyer_phone_count');
                
                // ✅ NEW: Send webhook to WordPress when order is undone (stock restored)
                if ($gameId && $platform && $type) {
                    try {
                        \App\Jobs\SendInventoryWebhookJob::dispatch(
                            $gameId,
                            $platform,
                            $type,
                            $newStock,
                            'stock_updated'  // Different event type for restock
                        );
                        
                        \Log::info('Restock webhook dispatched', [
                            'order_id' => $order->id,
                            'game_id'  => $gameId,
                            'platform' => $platform,
                            'type'     => $type,
                            'new_stock' => $newStock,
                        ]);
                    } catch (\Exception $e) {
                        // Don't fail the undo operation if webhook fails
                        \Log::warning('Restock webhook dispatch failed', [
                            'error' => $e->getMessage(),
                            'order_id' => $order->id,
                        ]);
                    }
                }
                
                return response()->json(['success' => true]);
            }

            DB::rollBack();
            Cache::forget('unique_buyer_phone_count');
            return response()->json(['success' => false]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Helper method to update card status
    private function updateCardStatus($cardId)
    {
        $card = Card::find($cardId);
        if ($card) {
            $card->status = true;
            $card->save();
        }
    }

    // Helper method to increment account stock based on sold item
    private function incrementAccountStock($order)
    {
        $account = Account::find($order->account_id);

        if ($account) {
            $stockField = $this->getStockField($order->sold_item);
            $account->$stockField += 1;
            $account->save();
        }
    }

    // Helper method to get the stock field based on sold item
    private function getStockField($soldItem)
    {
        return match ($soldItem) {
            'ps4_offline_stock' => 'ps4_offline_stock',
            'ps4_primary_stock' => 'ps4_primary_stock',
            'ps4_secondary_stock' => 'ps4_secondary_stock',
            'ps5_offline_stock' => 'ps5_offline_stock',
            'ps5_primary_stock' => 'ps5_primary_stock',
            'ps5_secondary_stock' => 'ps5_secondary_stock',
            default => null,
        };
    }

    // Helper method to update report status
    private function updateReportStatus($reportId)
    {
        $report = Report::find($reportId);
        if ($report && $report->status === 'needs_return') {
            $report->update(['status' => 'solved']);
        }
    }

    // Helper method to delete order and related reports
    private function deleteOrderAndReports($order)
    {
        $order->reports()->delete();
        $order->delete();
    }
    public function storeApi(Request $request)
    {
        if ($request->has('card_category_id')) {
            return $this->sellCard($request);
        }

        // Validate incoming data
        $validatedData = $request->validate([
            'store_profile_id' => 'required|exists:stores_profile,id',
            'game_id'          => 'required|exists:games,id',
            'buyer_phone'      => 'required|string|max:15',
            'buyer_name'       => 'required|string|max:100',
            'buyer_email'      => 'required|email',
            'price'            => 'required|numeric|min:0',
            'type'             => 'required|string|in:primary,secondary',
            'platform'         => 'required|string|max:255',
        ]);
        
        // Check if the user already exists by phone number
        $user = User::where('phone', $validatedData['buyer_phone'])->first();

        // If the user does not exist, create a new one
        if (!$user) {
            $user = User::create([
                'name'     => $validatedData['buyer_name'],
                'email'    => $validatedData['buyer_email'] ?? (Str::random(10) . '@' . Str::random(4) . '.com'),
                'phone'    => $validatedData['buyer_phone'],
                'password' => bcrypt(Str::random(12)),
            ]);

            $defaultRole = Role::where('name', 'customer')->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            }
        }

        // Update buyer name to match user record
        $validatedData['buyer_name'] = $user->name;
        
        // Determine the sold item field dynamically
        $sold_item         = "ps{$validatedData['platform']}_{$validatedData['type']}_stock";
        $sold_item_status  = "ps{$validatedData['platform']}_{$validatedData['type']}_status";
        $sold_offline_item = "ps{$validatedData['platform']}_offline_stock";

        // Fetch one available account based on type, stock availability, and game status
        $accountQuery = Account::where('game_id', $validatedData['game_id'])
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->where("games.{$sold_item_status}", true)
            ->orderBy('accounts.created_at', 'asc')
            ->where($sold_item, '>', 0);

        // Special conditions for primary and secondary
        if ($validatedData['platform'] !== '5') {
            $accountQuery->where($sold_offline_item, 0);
        }

        try {
            DB::beginTransaction();

            // Fetch a single account
            $account = $accountQuery->select('accounts.*')->first();

            // Check if no account was found
            if (!$account) {
                return response()->json([
                    'message' => 'No available account matches the specified criteria.',
                ], 422);
            }

            // Reduce the stock by 1
            $account->decrement($sold_item, 1);
            
            // Create the order
            $order_data = [
                'seller_id'        => null,
                'store_profile_id' => $validatedData['store_profile_id'],
                'account_id'       => $account->id,
                'buyer_phone'      => $validatedData['buyer_phone'],
                'buyer_name'       => $validatedData['buyer_name'],
                'price'            => $validatedData['price'],
                'notes'            => '',
                'sold_item'        => $sold_item,
            ];

            $order = Order::create($order_data);

            DB::commit();
            Cache::forget('unique_buyer_phone_count');

            // ✅ NEW: Dispatch webhook to WordPress to invalidate cache
            \App\Jobs\SendInventoryWebhookJob::dispatch(
                $validatedData['game_id'],
                $validatedData['platform'],
                $validatedData['type'],
                $account->$sold_item,  // remaining stock after decrement
                'order_created'
            );

            // Return a JSON response on success
            return response()->json([
                'message'          => 'Order created successfully!',
                'order_id'         => $order->id,
                'account_details'  => [
                    'email'    => $account->mail,
                    'password' => $account->password
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Not possible to create the order. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
            'store_profile_id' => 'required|exists:stores_profile,id',
            'game_id'          => 'required|exists:games,id',
            'buyer_phone'      => 'required|string|max:15',
            'buyer_name'       => 'required|string|max:100',
            'price'            => 'required|numeric|min:0',
            'type'             => 'required|string|max:255',
            'platform'         => 'required|string|max:255',
        ]);

        // Validate order amount against settings
        if (!SettingsService::validateOrderAmount($validatedData['price'])) {
            return redirect()->back()
                ->withErrors(['price' => SettingsService::getOrderAmountErrorMessage($validatedData['price'])])
                ->withInput();
        }

        // Check if the user already exists by phone number
        $user = User::where('phone', $validatedData['buyer_phone'])->first();

        // If the user does not exist, create a new one
        if (!$user) {
            $user = User::create([
                'name'     => $validatedData['buyer_name'],
                'email'    => Str::random(10) . '@' . Str::random(4) . '.com',
                'phone'    => $validatedData['buyer_phone'],
                'password' => bcrypt(Str::random(12)),
            ]);

            // Assign a default role if necessary
            $defaultRole = Role::where('name', 'customer')->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            }
        }

        // Update buyer name to match user record if necessary
        $validatedData['buyer_name'] = $user->name;

        // Determine the sold item field dynamically
        $sold_item         = "ps{$validatedData['platform']}_{$validatedData['type']}_stock";
        $sold_item_status  = "ps{$validatedData['platform']}_{$validatedData['type']}_status";
        $sold_offline_item = "ps{$validatedData['platform']}_offline_stock";

        // Fetch the appropriate account based on type, stock availability, and game status
        $accountQuery = Account::where('game_id', $validatedData['game_id'])
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->where("games.{$sold_item_status}", true)
            ->orderBy('accounts.created_at', 'asc');

        if ($validatedData['type'] === 'offline') {
            $accountQuery->where($sold_item, '>', 0);
        } elseif ($validatedData['type'] === 'primary') {
            if ($validatedData['platform'] === '5') {
                $accountQuery->where($sold_item, '>', 0);
            } else {
                $accountQuery->where($sold_offline_item, 0)->where($sold_item, '>', 0);
            }
        } elseif ($validatedData['type'] === 'secondary') {
            if ($validatedData['platform'] === '5') {
                $accountQuery->where($sold_item, '>', 0);
            } else {
                $accountQuery->where($sold_item, '>', 0);
            }
        }

        try {
            DB::beginTransaction();

            // Fetch the first matching account or fail
            $account = $accountQuery->select('accounts.*')->first();

            // Check if no account was found
            if (!$account) {
                return response()->json([
                    'message' => 'No available account matches the specified criteria.',
                ], 422);
            }

            // Reduce the corresponding stock by 1 for the account
            $account->decrement($sold_item, 1);

            // Check if this was an "offline" order with only 1 stock left before decrement
            $recentOrder = null;
            $message     = null;
            if (
                $validatedData['platform'] == 4 &&
                (
                    ($validatedData['type'] === 'offline' && $account->$sold_item == 0) ||
                    ($validatedData['type'] === 'primary' && $account->$sold_offline_item == 0)
                )
            ) {
                $check_for = $validatedData['type'] === 'primary' ? $sold_offline_item : $sold_item;

                $recentOrder = Order::where('account_id', $account->id)
                    ->where('sold_item', $check_for)
                    ->where('created_at', '>=', now()->subMinutes(11))
                    ->latest()
                    ->first();

                if ($recentOrder) {
                    $seller = User::find($recentOrder->seller_id);
                    if ($seller) {
                        $sold = $validatedData['type'] === 'offline' ? 'one offline' : 'the latest offline';
                        $message = $seller->name . ' has sold ' . $sold . ' from this account. Please contact him on ' . $seller->phone;
                    }
                }
            }

            // Prepare the order data
            $order_data = [
                'seller_id'        => Auth::id(),
                'store_profile_id' => $validatedData['store_profile_id'],
                'account_id'       => $account->id,
                'buyer_phone'      => $validatedData['buyer_phone'],
                'buyer_name'       => $validatedData['buyer_name'],
                'price'            => $validatedData['price'],
                'notes'            => '',
                'sold_item'        => $sold_item,
                'updated_at'       => now(),
                'created_at'       => now(),
            ];

            // Create the order
            $order = Order::create($order_data);

            // Commit the transaction if everything succeeds
            DB::commit();
            Cache::forget('unique_buyer_phone_count');

            // ✅ REPLACE WITH THIS (queued job):
            \App\Jobs\SendInventoryWebhookJob::dispatch(
                $validatedData['game_id'],
                $validatedData['platform'],
                $validatedData['type'],
                $account->$sold_item,
                'order_created'
            );

            // Return a JSON response on success
            return response()->json([
                'message'            => 'Order created successfully!',
                'account_email'      => $account->mail,
                'account_password'   => $account->password,
                'recent_order'       => $recentOrder,
                'additional_message' => $message,
                'order_id'           => $order->id,
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();

            // Return an error response
            return response()->json([
                'message' => 'Not possible to create the order. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function checkStockApi(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
            'game_id'  => 'required|exists:games,id',
            'platform' => 'required|string|max:255',
            'type'     => 'required|string|in:primary,secondary',
        ]);

        // Determine the sold item field dynamically
        $sold_item        = "ps{$validatedData['platform']}_{$validatedData['type']}_stock";
        $sold_item_status = "ps{$validatedData['platform']}_{$validatedData['type']}_status";

        // Fetch available stock
        $availableStock = Account::where('game_id', $validatedData['game_id'])
            ->join('games', 'accounts.game_id', '=', 'games.id')
            ->where("games.{$sold_item_status}", true) // Ensure the game's status is active
            ->sum($sold_item); // Sum the available stock

        // Return response
        return response()->json([
            'stock'         => $availableStock,
            'is_available'  => $availableStock > 0,
        ]);
    }
    public function checkCardStockApi(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
            'category_id'  => 'required|exists:card_categories,id',
        ]);

        // Return response
        return response()->json([
            'stock'         => 5,
            'is_available'  => true,
        ]);
    }



    public function sellCard(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
        'card_category_id' => 'required|exists:card_categories,id',
        'store_profile_id' => 'required|exists:stores_profile,id',
        'buyer_phone'      => 'required|string|max:15',
        'buyer_name'       => 'required|string|max:100',
        'price'            => 'required|numeric|min:0',
        ]);

        // Find an available card with status = true
        $card = Card::where('card_category_id', $validatedData['card_category_id'])
                ->where('status', true)
                ->first();

        if (!$card) {
            return response()->json([
            'success' => false,
            'message' => 'No active code found for this category.'
            ], 200);
        }

        // Prepare the order data
        $orderData = [
        'seller_id'        => Auth::id(),
        'store_profile_id' => $validatedData['store_profile_id'],
        'account_id'       => null,
        'buyer_phone'      => $validatedData['buyer_phone'],
        'buyer_name'       => $validatedData['buyer_name'],
        'price'            => $validatedData['price'],
        'notes'            => '',
        'sold_item'        => 'card',
        'card_id'          => $card->id,
        ];

        // Start a transaction
        DB::beginTransaction();

        try {
            // Create the order
            $order = Order::create($orderData);

            // Mark the card as sold
            $card->update(['status' => false]);

            // Commit the transaction if both operations succeed
            DB::commit();

            return response()->json([
            'success'  => true,
            'message'  => 'Order created successfully!',
            'code'     => $card->code,
            'card_details' => [ 'code' => $card->code ],
            'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            return response()->json([
            'success' => false,
            'message' => 'Failed to create order. Please try again.',
            'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function ordersWithStatus($status)
    {
        // Retrieve all orders with a related report that has status 'needs_return'
        $orders = Order::with(array( 'seller', 'account.game' ))
        ->whereHas(
            'reports',
            function ($query) use ($status) {
                $query->where('status', $status);
            }
        )
        ->paginate($this->pagination); // Adjust pagination as needed
        // Return the view with the filtered orders
        return view('manager.orders', compact('orders', 'status'));
    }

    public function ordersWithNeedsReturn()
    {
        return $this->ordersWithStatus('needs_return');
    }

    public function ordersHasProblem()
    {
        return $this->ordersWithStatus('has_problem');
    }

    public function solvedOrders()
    {
        return $this->ordersWithStatus('solved');
    }
    public function sendToPos(Request $request)
    {
        // Validate that the order_ids are provided and are an array of valid IDs
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id' // Ensure each order ID exists in the orders table
        ]);

        // Get POS settings from database
        $posSkus = SettingsService::getPosSkus();
        $posIds = SettingsService::getPosIds();
        $store_profile_ids = [
            'profile_13' => 1, // New Cairo
            'profile_14' => 3, // Beverly Hills.
            'profile_15' => 4, // Elserag mall.
            'profile_16' => 5, // City stars.
            'profile_17' => 6, // WooComerce
            'profile_18' => 7, // El shorouk city
        ];
        //$user = auth()->user();
        $user = User::findOrFail(44);
        $store_profile_id = $user->store_profile_id ?? '0';
        $pos_location = $store_profile_id == '0' ? 1 : $store_profile_ids['profile_' . $store_profile_id];
        // Get the array of order IDs
        $orderIds = $validated['order_ids'];
        $billing_details  = false;
        $basic_details    = false;
        $order_total      = 0;
        $order_key        = '';
        $line_items       = [];
        $buyer_phone      = false;
        // Filter orders that haven't been sent to POS (pos_order_id is null)
        $unsentOrderIds = Order::whereIn('id', $orderIds)
        ->whereNull('pos_order_id')
        ->pluck('id')
        ->toArray();

        // If all orders are already sent
        if (empty($unsentOrderIds)) {
            return redirect()->route('manager.orders')->with('info', 'All selected orders have already been sent to POS.');
        }
        // Process each order ID (e.g., send each order to the POS system)
        foreach ($orderIds as $orderId) {
            try {
                // Fetch the order
                $order = Order::findOrFail($orderId);
                if ($order->sold_item === 'card') {
                    $card = Card::with('category')->find($order->card_id);
                    $card_code = $card->code;
                    $card_category = $card->category->name;
                    $account_mail = null;
                    $account_password = null;
                    $game_title = null;
                } else {
                    $card = null;
                    $account = Account::with('game:id,title')->findOrFail($order->account_id);
                    $account_mail   = $account->mail;
                    $account_password   = $account->password;
                    $game_title = $account->game->title;
                    $card_code = null;
                    $card_category = null;
                }
                if ($buyer_phone && $order->buyer_phone !== $buyer_phone) {
                    return redirect()->route('manager.orders')->with('error', 'Selected orders are not for the same client.');
                }
                $buyer_phone        = $order->buyer_phone;
                $sold_item          = $order->sold_item;
                $platform           = explode('_', $sold_item);
                $user = User::where('phone', $order->buyer_phone)->first();
                if (isset($platform[1])) {
                    $key = $platform[1];
                    $sku = $posSkus[$key];
                    $pos_product_id = $posIds[$key];
                    $type = $key;
                } else {
                    $sku = $posSkus['card'];
                    $pos_product_id = $posIds['card'];
                    $type = 'card';
                }
                if ($user) {
                    // User found, get the email
                    $email = $user->email;
                } else {
                    $email = null;
                }
                if (! $billing_details) {
                    $billing_details = array(
                        "first_name" => $order->buyer_name,
                        "last_name" => "",
                        "company" => "",
                        "address_1" => "",
                        "address_2" => "",
                        "city" => "cairo",
                        "state" => "EGC",
                        "postcode" => "6108",
                        "country" => "EG",
                        "email" => $email,
                        "phone" => $order->buyer_phone
                    );
                }
                if (! $basic_details) {
                    $basic_details = array(
                        "id" => $order->id,
                        "parent_id" => 0,
                        "status" => "on-hold",
                        "currency" => "EGP",
                        "version" => "9.7.1",
                        "prices_include_tax" => false,
                        "date_created" => $order->created_at->toDateString(),
                        "date_modified" => $order->updated_at->toDateString(),
                        "discount_total" => "0.00",
                        "discount_tax" => "0.00",
                        "shipping_total" => "0.00",
                        "shipping_tax" => "0.00",
                        "cart_tax" => "0.00",
                        "total_tax" => "0.00",
                        "customer_id" => $user->id,
                        "payment_method" => "cod",
                        "payment_method_title" => "Cash on delivery",
                        "transaction_id" => "",
                        "customer_ip_address" => "41.36.182.159",
                        "customer_user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36",
                        "created_via" => "checkout",
                        "customer_note" => "",
                        "date_completed" => null,
                        "date_paid" => null,
                        "meta_data" => [],
                        "is_editable" => true,
                        "needs_payment" => true,
                        "needs_processing" => false,
                        "currency_symbol" => "EGP",
                        "shipping_lines" => array(),
                    );
                }
                $line_items[] = array(
                    "name" => $order->sold_item,
                    "product_id" => $order->sold_item === 'card' ? $order->card_id : $order->account_id,
                    "variation_id" => 0,
                    "quantity" => 1,
                    "tax_class" => "",
                    "subtotal" => "$order->price",
                    "subtotal_tax" => "0.00",
                    "total" => "$order->price",
                    "total_tax" => "0.00",
                    "taxes" => array(),
                    "meta_data" => array(
                        array(
                            "id" => 1207,
                            "key" => "platform",
                            "value" => "$platform[0]"
                        ),
                        array(
                            "id" => 1208,
                            "key" => "game_title",
                            "value" => "$game_title"
                        ),
                        array(
                            "id" => 1217,
                            "key" => "_account",
                            "value" => $account_mail
                        ),array(
                            "id" => 1217,
                            "key" => "type",
                            "value" => $type
                        ),
                        array(
                            "id" => 1218,
                            "key" => "_password",
                            "value" => $account_password
                        ),
                        array(
                            "id" => 1219,
                            "key" => "_pos_product_id",
                            "value" => $pos_product_id
                        ),
                        array(
                            "id" => 1220,
                            "key" => "_card_code",
                            "value" => $card_code
                        ),
                        array(
                            "id" => 1221,
                            "key" => "_card_category",
                            "value" => $card_category
                        )
                    ),
                    "sku" => "$sku",
                    "price" => $order->price,
                    "image" => array(
                        "id" => "",
                        "src" => ""
                    ),
                    "parent_name" => null
                );
                $order_total += $order->price;
                $order_key .= $orderId;
            } catch (\Exception $e) {
                // Store the order ID in the failedOrders array if there's an error
                $failedOrders[] = $orderId;
            }
        }
        if ($billing_details) {
            $basic_details['billing'] = $billing_details;
            $basic_details['shipping'] = $billing_details;
        }
        if ($basic_details) {
            $basic_details['line_items'] = $line_items;
            $basic_details['total'] = $order_total;
            $basic_details['order_key'] = $order_key;
            $basic_details['location_id'] = $pos_location;
        }

        // If there are failed orders, return a custom response
        if (!empty($failedOrders)) {
            return redirect()->route('manager.orders')->with('error', 'Some orders failed to be sent to POS: ' . implode(', ', $failedOrders));
        }
        $token = $this->login();
        if (! $token) {
            return redirect()->route('manager.orders')->with('error', 'Failed to authenticate');
        }
        // API endpoint for creating an order
        $endpoint = $this->pos_base . '/api/accounts/orders/create/1';

        // Make a POST request with Bearer token and $basic_details array as JSON
        $response = Http::withToken($token)  // Set Bearer token
                    ->post($endpoint, $basic_details);  // Send POST request with data

        // Check if the request was successful
        if ($response->successful()) {
            $body = json_decode($response->body());
            $transaction_id = $body->created->id;
            // Loop through the order IDs and update each one
            foreach ($unsentOrderIds as $orderId) {
                Order::where('id', $orderId)->update([
                    'pos_order_id' => $transaction_id
                ]);
            }
            // Return a success message if all orders were sent successfully
            return redirect()->route('manager.orders')->with('success', 'Orders successfully sent to POS');
        } else {
            // Handle errors from the API
            Log::emergency($response->body());
            return redirect()->route('manager.orders')->with('error', 'Failed to create order');
        }
    }
}
