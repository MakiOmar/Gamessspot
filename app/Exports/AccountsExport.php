<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * Accounts query, read in chunks by the exporter to keep memory flat.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return Account::query()
            ->with(array('game:id,title', 'trader:id,name', 'purchaseOrder:id,po_number'))
            ->orderBy('id');
    }

    /**
     * Define the headings for the Excel sheet.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Mail',
            'Password',
            'Game',
            'Region',
            'Cost',
            'Birthdate',
            'Login Code',
            'Is Full',
            'PS4 Primary Stock',
            'PS4 Secondary Stock',
            'PS4 Offline Stock',
            'PS5 Primary Stock',
            'PS5 Secondary Stock',
            'PS5 Offline Stock',
            'Trader',
            'PO Number',
            'Purchase Date',
            'Original Cost',
        ];
    }

    /**
     * Map the data to match the import format.
     *
     * @param $account
     * @return array
     */
    public function map($account): array
    {
        return [
            $account->mail,
            $account->password,
            $account->game->title ?? 'N/A',
            $account->region,
            $account->cost,
            $account->birthdate,
            $account->login_code,
            $account->is_full ? '1' : '0',
            $account->ps4_primary_stock,
            $account->ps4_secondary_stock,
            $account->ps4_offline_stock,
            $account->ps5_primary_stock,
            $account->ps5_secondary_stock,
            $account->ps5_offline_stock,
            $account->trader?->name,
            $account->purchaseOrder?->po_number,
            $account->purchase_date?->toDateString(),
            $account->original_cost,
        ];
    }
}
