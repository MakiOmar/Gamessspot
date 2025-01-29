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

class OrderController extends Controller
{
    protected $pagination = 10;
    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::guard('admin')->user(); // Assuming 'admin' guard is used
        $roles = $user->roles->pluck('name');

        // Check for 'admin' role
        if ($roles->contains('admin')) {
            $orders = Order::with([
            'seller',
            'account.game', // Load game through account
            'card'
            ])->paginate($this->pagination);

            return $this->renderOrders($orders, $user);
        }

        // Check for 'sales' or 'account manager' roles
        if ($roles->intersect(['sales', 'account manager'])->isNotEmpty()) {
            $orders = Order::with([
            'seller',
            'account.game',
            'card'
            ])
            ->where('seller_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->paginate($this->pagination);

            return $this->renderOrders($orders, $user);
        }

        // Check for 'accountant' role with a specific store profile ID
        if ($roles->contains('accountant') && request()->has('id')) {
            $storeId = request()->get('id');

            $orders = Order::with([
            'seller',
            'account.game',
            'card'
            ])
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
        return view('manager.orders', compact('orders', 'user'));
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

        // If the user is not an admin, filter by seller_id
        if (!$isAdmin) {
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
            $orders->whereBetween('created_at', array( $startDate, $endDate ));
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
            $orders->where('orders.store_profile_id', $storeProfileId)->orderBy('buyer_name', 'asc');
        }
        // Execute the query and paginate or get the results
        $orders = $orders->get();

        // Return the updated rows for the table (assuming a partial view)
        return view('manager.partials.order_rows', compact('orders', 'status'))->render();
    }
    public function searchCustomers(Request $request)
    {
        // Extract search query
        $query = $request->input('search');

        // Ensure admin-only access
        $user = Auth::user();
        if (!$user->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

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
            $orders->where('seller_id', $user->id);
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
                if ($order->card_id) {
                    $this->updateCardStatus($order->card_id);
                } else {
                    $this->incrementAccountStock($order);
                }

                if ($request->has('report_id')) {
                    $this->updateReportStatus($request->report_id);
                }

                $this->deleteOrderAndReports($order);

                DB::commit();
                return response()->json(['success' => true]);
            }

            DB::rollBack();
            Cache::forget('unique_buyer_phone_count'); // Clear the cache
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
        // Check if the buyer_phone has been used before and validate buyer_name
        $existingOrder = Order::where('buyer_phone', $validatedData['buyer_phone'])->first();
        if ($existingOrder && $existingOrder->buyer_name !== $validatedData['buyer_name']) {
            return response()->json([
                'message' => "Incorrect buyer name. Should be ( $existingOrder->buyer_name )",
            ], 422);
        }

        $existingOrder = Order::where('buyer_name', $validatedData['buyer_name'])->first();
        if ($existingOrder && $existingOrder->buyer_phone !== $validatedData['buyer_phone']) {
            return response()->json([
                'message' => "Incorrect buyer phone. Should be ( $existingOrder->buyer_phone )",
            ], 422);
        }
        // Determine the sold item field dynamically
        $sold_item         = "ps{$validatedData['platform']}_{$validatedData['type']}_stock";
        $sold_item_status  = "ps{$validatedData['platform']}_{$validatedData['type']}_status";
        $sold_offline_item = "ps{$validatedData['platform']}_offline_stock";

        // Fetch the appropriate account based on type, stock availability, and game status
        $accountQuery = Account::where('game_id', $validatedData['game_id'])
        ->join('games', 'accounts.game_id', '=', 'games.id')
        ->where("games.{$sold_item_status}", true) // Ensure the game's status is true
        ->orderBy('accounts.created_at', 'asc'); // Order by the oldest date


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
                $accountQuery->where($sold_offline_item, 0)
                    ->where($sold_item, '>', 0);
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
                ], 422); // 422 Unprocessable Entity
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

                // Fetch the recent order for this account and item within the last 11 minutes
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
            ];

            // Create the order
            $order = Order::create($order_data);

            // Commit the transaction if everything succeeds
            DB::commit();
            Cache::forget('unique_buyer_phone_count'); // Clear the cache
            // Return a JSON response on success, including recent order and seller details if applicable
            return response()->json([
            'message'          => 'Order created successfully!',
            'account_email'    => $account->mail,
            'account_password' => $account->password, // Be cautious with sensitive data
            'recent_order'     => $recentOrder,
            'additional_message' => $message,
            'order_id'         => $order->id,
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
}
