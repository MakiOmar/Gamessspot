<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Game;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class AccountsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    public function model(array $row)
    {
        // Find game by title
        $game = Game::where('title', $row['game'])->first();

        if (!$game) {
            throw new \Exception("Game '{$row['game']}' not found. Please create the game first.");
        }

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
            'game_id' => $game->id,
            'region' => $row['region'],
            'cost' => $row['cost'],
            'birthdate' => $row['birthdate'],
            'login_code' => $row['login_code'],
            'is_full' => $isFull,
        ], $stocks));
    }

    public function rules(): array
    {
        return [
            '*.mail' => 'required|email|unique:accounts,mail',
            '*.password' => 'required|string',
            '*.game' => 'required|string|exists:games,title',
            '*.region' => 'required|string|max:2',
            '*.cost' => 'required|numeric',
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
