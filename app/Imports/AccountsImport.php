<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\TraderPurchaseOrderItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

/**
 * Game, cost, trader, purchase order and purchase date come from the selected
 * purchase order line, not from the spreadsheet.
 */
class AccountsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsEmptyRows
{
    use Importable, SkipsErrors;

    /**
     * @var array<string, mixed>
     */
    private array $sourceAttributes;

    public function __construct(TraderPurchaseOrderItem $item)
    {
        $this->sourceAttributes = Account::sourceAttributesFromItem($item);
    }

    public function model(array $row)
    {
        // Use stock values from Excel file, with defaults if not provided
        $stocks = [
            'ps4_primary_stock' => isset($row['ps4_primary_stock']) ? (int) $row['ps4_primary_stock'] : 1,
            'ps4_secondary_stock' => isset($row['ps4_secondary_stock']) ? (int) $row['ps4_secondary_stock'] : 1,
            'ps4_offline_stock' => isset($row['ps4_offline_stock']) ? (int) $row['ps4_offline_stock'] : 2,
            'ps5_primary_stock' => isset($row['ps5_primary_stock']) ? (int) $row['ps5_primary_stock'] : 1,
            'ps5_secondary_stock' => isset($row['ps5_secondary_stock']) ? (int) $row['ps5_secondary_stock'] : 1,
            'ps5_offline_stock' => isset($row['ps5_offline_stock']) ? (int) $row['ps5_offline_stock'] : 1,
        ];

        $isFull = isset($row['is_full']) ? filter_var($row['is_full'], FILTER_VALIDATE_BOOLEAN) : false;

        if ($isFull && !Account::stocksArePristine($stocks)) {
            throw new \Exception(
                "Cannot enable Is Full for '{$row['mail']}': stocks must be pristine (dual 1/1/2+1/1/1 or PS5-only 0/0/0+1/1/2)."
            );
        }

        return new Account(array_merge([
            'mail' => $row['mail'],
            'password' => $row['password'],
            'region' => $row['region'],
            'birthdate' => $row['birthdate'],
            'login_code' => $row['login_code'],
            'is_full' => $isFull,
        ], $stocks, $this->sourceAttributes));
    }

    public function rules(): array
    {
        return [
            '*.mail' => 'required|email|unique:accounts,mail',
            '*.password' => 'required|string',
            '*.region' => 'required|string|max:2',
            '*.birthdate' => 'required|date',
            '*.login_code' => 'required|string',
            '*.is_full' => 'nullable',
            '*.ps4_primary_stock' => 'nullable|integer|min:0',
            '*.ps4_secondary_stock' => 'nullable|integer|min:0',
            '*.ps4_offline_stock' => 'nullable|integer|min:0',
            '*.ps5_primary_stock' => 'nullable|integer|min:0',
            '*.ps5_secondary_stock' => 'nullable|integer|min:0',
            '*.ps5_offline_stock' => 'nullable|integer|min:0',
        ];
    }
}
