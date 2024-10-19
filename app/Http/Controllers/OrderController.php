<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Account;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Report;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Retrieve all orders with pagination
        $orders = Order::with(['seller', 'account.game'])->paginate(10); // Adjust pagination size if needed

        // Return the view with the orders data
        return view('manager.orders', compact('orders'));
    }

    /**
     * Search for orders by buyer phone.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $query = $request->input('search');

        // Search orders by buyer phone
        $orders = Order::where('buyer_phone', 'like', "%$query%")
                        ->with(['seller', 'account.game'])
                        ->get();

        // Return the updated rows for the table
        return view('manager.partials.order_rows', compact('orders'))->render();
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
     * Undo an order by deleting it and updating the corresponding stock.
     */
    public function undo(Request $request)
    {
        $order = Order::find($request->order_id);

        if ($order) {
            // Increment the corresponding stock based on sold_item
            $account = Account::find($order->account_id);

            if ($account) {
                switch ($order->sold_item) {
                    case 'ps4_offline_stock':
                        $account->ps4_offline_stock += 1;
                        break;
                    case 'ps4_primary_stock':
                        $account->ps4_primary_stock += 1;
                        break;
                    case 'ps4_secondary_stock':
                        $account->ps4_secondary_stock += 1;
                        break;
                    case 'ps5_offline_stock':
                        $account->ps5_offline_stock += 1;
                        break;
                    case 'ps5_primary_stock':
                        $account->ps5_primary_stock += 1;
                        break;
                    case 'ps5_secondary_stock':
                        $account->ps5_secondary_stock += 1;
                        break;
                }

                // Save the updated account stock
                $account->save();
                // If the report_id is provided, update the report status to 'solved'
                if ($request->has('report_id')) {
                    $report = Report::find($request->report_id);
                    if ($report && $report->status == 'needs_return') {
                        $report->update(['status' => 'solved']);
                    }
                }
                // Delete related reports before deleting the order
                $order->reports()->delete();
                // Delete the order
                $order->delete();

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false]);
    }
    public function store(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
        'store_profile_id' => 'required|exists:stores_profile,id',
        'game_id' => 'required|exists:games,id',
        'buyer_phone' => 'required|string|max:15',
        'buyer_name' => 'required|string|max:100',
        'price' => 'required|numeric|min:0',
        'type' => 'required|string|max:255',
        'platform' => 'required|string|max:255',
        ]);

        // Determine the sold item field dynamically
        $sold_item = "ps{$validatedData['platform']}_{$validatedData['type']}_stock";
        $sold_item_status = "ps{$validatedData['platform']}_{$validatedData['type']}_status";
        $sold_offline_item = "ps{$validatedData['platform']}_offline_stock";

        // Fetch the appropriate account based on type, stock availability, and game status
        $accountQuery = Account::where('game_id', $validatedData['game_id'])
        ->join('games', 'accounts.game_id', '=', 'games.id')
        ->where("games.{$sold_item_status}", true); // Ensure the game's status is true

        if ($validatedData['type'] === 'offline') {
            // For offline, just check the corresponding stock field has available stock
            $accountQuery->where($sold_item, '>', 0);
        } elseif ($validatedData['type'] === 'primary') {
            // For primary, offline stock must be 0 and primary stock must be greater than 0
            $accountQuery->where('ps' . $validatedData['platform'] . '_offline_stock', 0)
                     ->where($sold_item, '>', 0);
        } elseif ($validatedData['type'] === 'secondary') {
            // For secondary, both offline and primary stocks must be 0, but secondary must have stock
            $accountQuery->where('ps' . $validatedData['platform'] . '_offline_stock', 0)
                     ->where('ps' . $validatedData['platform'] . '_primary_stock', 0)
                     ->where($sold_item, '>', 0);
        }

        // Try to fetch the first matching account
        try {
            $account = $accountQuery->select('accounts.*')->firstOrFail();  // Fail if no matching account is found
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return an error response if no account was found
            return response()->json([
            'message' => 'No account found with the required stock.',
            ], 200);
        }

        // Reduce the corresponding stock by 1 for the account
        $account->decrement($sold_item, 1);

        // After the order has been created, check if this was an "offline" order with only 1 stock left before decrement
        $recentOrder = null;
        $message = null;
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
            Log::debug('Debugging some data.', ['data' => $check_for]);

            if ($recentOrder) {
                // Fetch the seller details
                $seller = User::find($recentOrder->seller_id);
                if ($seller) {
                    if ($validatedData['type'] === 'offline') {
                        $sold = 'one offline';
                    } else {
                        $sold = 'the latest offline';
                    }
                    $message = $seller->name . ' has sold ' . $sold . ' from this account.Please contact him on ' . $seller->phone;
                }
            }
        }
        // Prepare the order data
        $order_data = [
            'seller_id' => Auth::id(),  // Get the currently authenticated user's ID
            'account_id' => $account->id,  // Set the matched account ID
            'buyer_phone' => $validatedData['buyer_phone'],
            'buyer_name' => $validatedData['buyer_name'],
            'price' => $validatedData['price'],  // Set the price
            'notes' => '',  // Optional notes
            'sold_item' => $sold_item,  // Sold item is dynamically set
            ];
        // Create the order
        $order = Order::create($order_data);
        // Return a JSON response on success, including recent order and seller details if applicable
        return response()->json([
        'message' => 'Order created successfully!',
        'account_email' => $account->mail,
        'account_password' => $account->password,  // Ensure this is safely displayed or masked
        'recent_order' => $recentOrder,
        'message' => $message,
        'order_id' => $order->id
        ]);
    }

    public function ordersWithNeedsReturn()
    {
        // Retrieve all orders with a related report that has status 'needs_return'
        $orders = Order::with(['seller', 'account.game'])
        ->whereHas('reports', function ($query) {
            $query->where('status', 'needs_return');
        })
        ->paginate(10); // Adjust pagination as needed
        $needs_return = true;
        // Return the view with the filtered orders
        return view('manager.orders', compact('orders', 'needs_return'));
    }
}
