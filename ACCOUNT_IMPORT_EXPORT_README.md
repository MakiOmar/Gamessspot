# Account Import/Export Functionality - Implementation Guide

## Current Account Database Structure

### Database Schema
The `accounts` table has the following structure:

```sql
CREATE TABLE accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mail VARCHAR(255) NOT NULL,
    region VARCHAR(2) NOT NULL, -- ISO 3166-1 alpha-2 country code
    ps4_offline_stock INT DEFAULT 0,
    ps4_primary_stock INT DEFAULT 0,
    ps4_secondary_stock INT DEFAULT 0,
    ps5_offline_stock INT DEFAULT 0,
    ps5_primary_stock INT DEFAULT 0,
    ps5_secondary_stock INT DEFAULT 0,
    game_id BIGINT NOT NULL, -- Foreign key to games table
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);
```

### Model Fillable Fields
The `Account` model includes additional fields not in the migration but used in the application:

```php
protected $fillable = [
    'mail',
    'password',        // Not in migration but used in controller
    'game_id',
    'region',
    'cost',           // Not in migration but used in controller
    'birthdate',      // Not in migration but used in controller
    'login_code',     // Not in migration but used in controller
    'ps4_primary_stock',
    'ps4_secondary_stock',
    'ps4_offline_stock',
    'ps5_primary_stock',
    'ps5_secondary_stock',
    'ps5_offline_stock',
];
```

## Current Account Insertion Process

### 1. Account Creation via Controller
Accounts are created through the `AccountController::store()` method with the following process:

#### Validation Rules:
- `mail`: Required, valid email, unique
- `password`: Required string
- `game_id`: Required, must exist in games table
- `region`: Required string, max 2 characters
- `cost`: Required numeric
- `birthdate`: Required date
- `login_code`: Required string
- Stock fields: Optional boolean checkboxes (for manual creation only)

#### Automatic Stock Logic:
The controller implements automatic stock management with default values:

```php
// Default stock values (set automatically)
$ps4_primary_stock = 1;
$ps4_secondary_stock = 1;
$ps5_primary_stock = 1;
$ps5_secondary_stock = 1;
$ps4_offline_stock = 2; // Default offline stock
$ps5_offline_stock = 1;

// For manual creation via form, checkboxes can override these defaults:
// If checkboxes are checked, stocks are set to 0
// Special logic for PS4 offline stock:
// - ps4_offline1 checked: stock = 1
// - ps4_offline2 checked: stock = 0
```

**Important:** For import functionality, stock values are always set to the default values automatically, ensuring consistency across all imported accounts.

### 2. Relationships
- **Account belongs to Game**: `$account->game()`
- **Game has many Accounts**: `$game->accounts()`

## Current Export Functionality

### Existing Export Implementation
The project already has export functionality using Laravel Excel:

#### AccountsExport Class:
```php
class AccountsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Account::with('game')
            ->select([
                'id', 'mail', 'game_id', 'region',
                'ps4_offline_stock', 'ps4_primary_stock', 'ps4_secondary_stock',
                'ps5_offline_stock', 'ps5_primary_stock', 'ps5_secondary_stock',
                'cost', 'password'
            ])
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'ID', 'Mail', 'Game', 'Region',
            'PS4 Offline', 'PS4 Primary', 'PS4 Secondary',
            'PS5 Offline', 'PS5 Primary', 'PS5 Secondary',
            'Cost', 'Password'
        ];
    }
}
```

#### Export Route:
```php
Route::get('/accounts/export', [AccountController::class, 'export']);
```

## Import Functionality Implementation Plan

### 1. Create Import Class

Create `app/Imports/AccountsImport.php`:

```php
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
        // Find game by title or create new one
        $game = Game::where('title', $row['game'])->first();
        
        if (!$game) {
            throw new \Exception("Game '{$row['game']}' not found. Please create the game first.");
        }

        // Set default stock values automatically (same logic as in AccountController)
        $ps4_primary_stock = 1;
        $ps4_secondary_stock = 1;
        $ps5_primary_stock = 1;
        $ps5_secondary_stock = 1;
        $ps4_offline_stock = 2; // Default offline stock should be 2
        $ps5_offline_stock = 1;

        return new Account([
            'mail' => $row['mail'],
            'password' => $row['password'],
            'game_id' => $game->id,
            'region' => $row['region'],
            'cost' => $row['cost'],
            'birthdate' => $row['birthdate'],
            'login_code' => $row['login_code'],
            'ps4_offline_stock' => $ps4_offline_stock,
            'ps4_primary_stock' => $ps4_primary_stock,
            'ps4_secondary_stock' => $ps4_secondary_stock,
            'ps5_offline_stock' => $ps5_offline_stock,
            'ps5_primary_stock' => $ps5_primary_stock,
            'ps5_secondary_stock' => $ps5_secondary_stock,
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
            // Note: Stock fields are not validated as they are set automatically
        ];
    }
}
```

### 2. Add Import Method to Controller

Add to `AccountController.php`:

```php
use App\Imports\AccountsImport;
use Maatwebsite\Excel\Facades\Excel;

public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
    ]);

    try {
        Excel::import(new AccountsImport, $request->file('file'));
        
        return response()->json([
            'success' => 'Accounts imported successfully!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Import failed: ' . $e->getMessage()
        ], 422);
    }
}
```

### 3. Add Import Route

Add to `routes/web.php`:

```php
Route::post('/accounts/import', [AccountController::class, 'import'])->name('accounts.import');
```

### 4. Create Import View

Create import form in the accounts view:

```html
<!-- Import Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5>Import Accounts</h5>
    </div>
    <div class="card-body">
        <form id="importForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="importFile" class="form-label">Select Excel/CSV File</label>
                <input type="file" class="form-control" id="importFile" name="file" 
                       accept=".xlsx,.xls,.csv" required>
                <div class="form-text">
                    Supported formats: Excel (.xlsx, .xls) and CSV files. Max size: 10MB<br>
                    <strong>Note:</strong> Stock values are set automatically - you only need to provide the basic account information.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Import Accounts
            </button>
        </form>
        
        <!-- Download Template -->
        <div class="mt-3">
            <a href="{{ route('accounts.template') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download"></i> Download Template
            </a>
        </div>
    </div>
</div>
```

### 5. Create Template Download

Add template download method to `AccountController`:

```php
public function template()
{
    $templateData = [
        [
            'Mail', 'Password', 'Game', 'Region', 'Cost', 'Birthdate', 'Login Code'
        ],
        [
            'example@email.com', 'password123', 'Game Title', 'US', '25.00', '1990-01-01', 'ABC123'
        ]
    ];

    return Excel::download(new class($templateData) implements FromArray, WithHeadings {
        private $data;
        
        public function __construct($data) {
            $this->data = $data;
        }
        
        public function array(): array {
            return $this->data;
        }
        
        public function headings(): array {
            return $this->data[0];
        }
    }, 'accounts_template.xlsx');
}
```

### 6. Enhanced Export with Better Formatting

Update `AccountsExport` to include game title:

```php
class AccountsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Account::with('game')->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Mail', 'Password', 'Game Title', 'Region', 'Cost', 
            'Birthdate', 'Login Code',
            'PS4 Offline', 'PS4 Primary', 'PS4 Secondary',
            'PS5 Offline', 'PS5 Primary', 'PS5 Secondary',
            'Created At'
        ];
    }

    public function map($account): array
    {
        return [
            $account->id,
            $account->mail,
            $account->password,
            $account->game->title ?? 'N/A',
            $account->region,
            $account->cost,
            $account->birthdate,
            $account->login_code,
            $account->ps4_offline_stock,
            $account->ps4_primary_stock,
            $account->ps4_secondary_stock,
            $account->ps5_offline_stock,
            $account->ps5_primary_stock,
            $account->ps5_secondary_stock,
            $account->created_at->format('Y-m-d H:i:s')
        ];
    }
}
```

## Database Migration Updates Needed

### Add Missing Fields to Accounts Table

Create a new migration to add missing fields:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('password')->nullable()->after('mail');
            $table->decimal('cost', 10, 2)->nullable()->after('region');
            $table->date('birthdate')->nullable()->after('cost');
            $table->string('login_code')->nullable()->after('birthdate');
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['password', 'cost', 'birthdate', 'login_code']);
        });
    }
}
```

## Implementation Steps

### Phase 1: Database Updates
1. Run the migration to add missing fields
2. Update existing accounts with default values if needed

### Phase 2: Import Functionality
1. Create `AccountsImport` class
2. Add import method to `AccountController`
3. Add import route
4. Create import form in the view
5. Add template download functionality

### Phase 3: Enhanced Export
1. Update `AccountsExport` with better formatting
2. Add game title to export
3. Include all relevant fields

### Phase 4: Testing & Validation
1. Test import with sample data
2. Validate error handling
3. Test export functionality
4. Ensure data integrity

## File Structure After Implementation

```
app/
├── Exports/
│   └── AccountsExport.php (updated)
├── Imports/
│   └── AccountsImport.php (new)
├── Http/Controllers/
│   └── AccountController.php (updated)
└── Models/
    └── Account.php (existing)

database/migrations/
└── xxxx_xx_xx_add_missing_fields_to_accounts_table.php (new)

resources/views/manager/
└── accounts.blade.php (updated with import form)
```

## Error Handling & Validation

### Import Validation
- File format validation (Excel/CSV only)
- File size limit (10MB)
- Required field validation
- Email uniqueness validation
- Game existence validation
- Data type validation

### Error Reporting
- Row-by-row error reporting
- Detailed error messages
- Skip invalid rows and continue processing
- Summary report of successful/failed imports

## Security Considerations

1. **File Upload Security**: Validate file types and sizes
2. **Data Validation**: Strict validation rules for all fields
3. **SQL Injection Prevention**: Use Eloquent ORM
4. **CSRF Protection**: Include CSRF tokens in forms
5. **Access Control**: Ensure proper authentication/authorization

## Performance Considerations

1. **Batch Processing**: Process imports in chunks for large files
2. **Memory Management**: Use streaming for large files
3. **Database Indexing**: Ensure proper indexes on unique fields
4. **Caching**: Clear relevant caches after import/export operations

This implementation provides a comprehensive import/export system that maintains data integrity while providing flexibility for bulk operations.
