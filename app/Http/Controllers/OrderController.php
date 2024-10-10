<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Account;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;

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

                // Delete the order
                $order->delete();

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false]);
    }
}
