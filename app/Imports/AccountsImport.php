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
use Illuminate\Validation\Rule;

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
        $ps4_primary_stock = isset($row['ps4_primary_stock']) ? (int)$row['ps4_primary_stock'] : 1;
        $ps4_secondary_stock = isset($row['ps4_secondary_stock']) ? (int)$row['ps4_secondary_stock'] : 1;
        $ps4_offline_stock = isset($row['ps4_offline_stock']) ? (int)$row['ps4_offline_stock'] : 2;
        $ps5_primary_stock = isset($row['ps5_primary_stock']) ? (int)$row['ps5_primary_stock'] : 1;
        $ps5_secondary_stock = isset($row['ps5_secondary_stock']) ? (int)$row['ps5_secondary_stock'] : 1;
        $ps5_offline_stock = isset($row['ps5_offline_stock']) ? (int)$row['ps5_offline_stock'] : 1;

        return new Account([
            'mail' => $row['mail'],
            'password' => $row['password'],
            'game_id' => $game->id,
            'region' => $row['region'],
            'cost' => $row['cost'],
            'birthdate' => $row['birthdate'],
            'login_code' => $row['login_code'],
            'ps4_primary_stock' => $ps4_primary_stock,
            'ps4_secondary_stock' => $ps4_secondary_stock,
            'ps4_offline_stock' => $ps4_offline_stock,
            'ps5_primary_stock' => $ps5_primary_stock,
            'ps5_secondary_stock' => $ps5_secondary_stock,
            'ps5_offline_stock' => $ps5_offline_stock,
        ]);
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
            '*.ps4_primary_stock' => 'nullable|integer|min:0',
            '*.ps4_secondary_stock' => 'nullable|integer|min:0',
            '*.ps4_offline_stock' => 'nullable|integer|min:0',
            '*.ps5_primary_stock' => 'nullable|integer|min:0',
            '*.ps5_secondary_stock' => 'nullable|integer|min:0',
            '*.ps5_offline_stock' => 'nullable|integer|min:0',
        ];
    }
}
