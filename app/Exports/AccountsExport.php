<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountsExport implements FromCollection, WithHeadings
{
    /**
     * Return the collection of accounts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Account::with('game')
            ->select(
                'id',
                'mail',
                'game_id',
                'region',
                'ps4_offline_stock',
                'ps4_primary_stock',
                'ps4_secondary_stock',
                'ps5_offline_stock',
                'ps5_primary_stock',
                'ps5_secondary_stock',
                'cost',
                'password'
            )
            ->get();
    }

    /**
     * Define the headings for the Excel sheet.
     *
     * @return array
     */
    public function headings(): array
    {
        return array(
            'ID',
            'Mail',
            'Game',
            'Region',
            'PS4 Offline',
            'PS4 Primary',
            'PS4 Secondary',
            'PS5 Offline',
            'PS5 Primary',
            'PS5 Secondary',
            'Cost',
            'Password',
        );
    }
}
