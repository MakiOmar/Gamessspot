<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Return the collection of accounts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Account::with('game')->get();
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
            'Login Code'
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
            $account->login_code
        ];
    }
}
