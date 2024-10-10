<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings
{
    /**
     * Return the collection of orders to export, including the seller name, account email, and game name.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Order::join('accounts', 'orders.account_id', '=', 'accounts.id')
                    ->join('users', 'orders.seller_id', '=', 'users.id')
                    ->join('games', 'accounts.game_id', '=', 'games.id')  // Join with the games table
                    ->select([
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
                    ])->get();
    }

    /**
     * Define the headers for the exported file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
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
        ];
    }
}
