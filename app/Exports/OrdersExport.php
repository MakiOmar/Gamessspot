<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class OrdersExport implements FromCollection, WithHeadings
{
    /**
     * Return the collection of orders to export, including the seller name, account email, and game name.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Renamed to be more descriptive
        $searchTerm = $_GET['searchOrder'] ?? '';
        $startDate  = $_GET['startDate'] ?? '';
        $endDate    = $_GET['endDate'] ?? '';
        $storeId    = $_GET['id'] ?? '';
        // Building the query for fetching orders
        return Order::join('accounts', 'orders.account_id', '=', 'accounts.id')
            ->join('users', 'orders.seller_id', '=', 'users.id')
            ->join('games', 'accounts.game_id', '=', 'games.id')  // Join with the games table
            ->select(
                array(
                    'orders.id',
                    'users.name as seller_name',  // Seller name
                    'accounts.mail as account_mail',  // Account email
                    'accounts.password',  // Account password
                    'games.title as game_name',  // Game name
                    'orders.buyer_phone',
                    'orders.buyer_name',
                    'orders.price',
                    'orders.sold_item',
                    'orders.notes',
                    'orders.created_at',
                )
            )
            // Apply store filtering if a store ID is provided
            ->when(
                ! empty($storeId),
                function ($query) use ($storeId) {
                    $query->where('orders.store_profile_id', $storeId);
                }
            )
            // Filter based on the new search term if provided
            ->when(
                ! empty($searchTerm),
                function ($query) use ($searchTerm) {
                    $query->where(
                        function ($q) use ($searchTerm) {
                            $q->where('users.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('accounts.mail', 'like', '%' . $searchTerm . '%')
                            ->orWhere('orders.buyer_phone', 'like', '%' . $searchTerm . '%')
                            ->orWhere('orders.buyer_name', 'like', '%' . $searchTerm . '%');
                        }
                    );
                }
            )
            // Filter orders between start and end dates if provided
            ->when(
                ! empty($startDate) && ! empty($endDate),
                function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('orders.created_at', array( $startDate, $endDate ));
                }
            )
            ->get();
    }
    /**
     * Map the data to include formatting.
     *
     * @param $order
     * @return array
     */
    public function map($order): array
    {
        return [
            " {$order->buyer_phone}", // Add a single quote to force Excel to treat it as text
            $order->buyer_name,
        ];
    }
    /**
     * Define the headers for the exported file.
     *
     * @return array
     */
    public function headings(): array
    {
        return array(
            'ID',
            'Seller Name',  // Seller name
            'Account Mail',  // Account email
            'Password',  // Account password
            'Game Name',  // Game name
            'Buyer Phone',
            'Buyer Name',
            'Price',
            'Sold Item',
            'Notes',
            'Created At',
        );
    }
}
