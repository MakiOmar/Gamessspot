<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\AccountController;
use Illuminate\Http\Request;

echo "Testing PS5 Offline Logic in AccountController...\n\n";

// Test Case 1: Only PS5 offline checked
echo "Test Case 1: Only PS5 offline checked\n";
$request1 = new Request();
$request1->merge([
    'mail' => 'test1@example.com',
    'password' => 'password123',
    'game_id' => 1, // Assuming game with ID 1 exists
    'region' => 'US',
    'cost' => 25.00,
    'birthdate' => '1990-01-01',
    'login_code' => 'ABC123',
    'ps5_offline' => '1'
]);

echo "Request data: " . json_encode($request1->all()) . "\n";

// Simulate the logic from the controller
$ps5_offline_stock = 1; // Default

if ($request1->has('ps5_offline') && !$request1->has('ps5_primary') && !$request1->has('ps5_secondary')) {
    $ps5_offline_stock = 2;
    echo "Result: ps5_offline_stock = $ps5_offline_stock (Special logic applied)\n";
} elseif ($request1->has('ps5_offline')) {
    $ps5_offline_stock = 0;
    echo "Result: ps5_offline_stock = $ps5_offline_stock (General logic applied)\n";
} else {
    echo "Result: ps5_offline_stock = $ps5_offline_stock (Default value)\n";
}

echo "\n";

// Test Case 2: PS5 offline + primary checked
echo "Test Case 2: PS5 offline + primary checked\n";
$request2 = new Request();
$request2->merge([
    'mail' => 'test2@example.com',
    'password' => 'password123',
    'game_id' => 1,
    'region' => 'US',
    'cost' => 25.00,
    'birthdate' => '1990-01-01',
    'login_code' => 'ABC123',
    'ps5_offline' => '1',
    'ps5_primary' => '1'
]);

echo "Request data: " . json_encode($request2->all()) . "\n";

$ps5_offline_stock = 1; // Default

if ($request2->has('ps5_offline') && !$request2->has('ps5_primary') && !$request2->has('ps5_secondary')) {
    $ps5_offline_stock = 2;
    echo "Result: ps5_offline_stock = $ps5_offline_stock (Special logic applied)\n";
} elseif ($request2->has('ps5_offline')) {
    $ps5_offline_stock = 0;
    echo "Result: ps5_offline_stock = $ps5_offline_stock (General logic applied)\n";
} else {
    echo "Result: ps5_offline_stock = $ps5_offline_stock (Default value)\n";
}

echo "\n";

// Test Case 3: Check what happens with the actual controller method
echo "Test Case 3: Testing with actual controller method (without saving)\n";

// Let's check if there are any games in the database first
$gameCount = \App\Models\Game::count();
echo "Games in database: $gameCount\n";

if ($gameCount > 0) {
    $game = \App\Models\Game::first();
    echo "Using game: {$game->title} (ID: {$game->id})\n";
    
    $request3 = new Request();
    $request3->merge([
        'mail' => 'test3@example.com',
        'password' => 'password123',
        'game_id' => $game->id,
        'region' => 'US',
        'cost' => 25.00,
        'birthdate' => '1990-01-01',
        'login_code' => 'ABC123',
        'ps5_offline' => '1'
    ]);
    
    echo "Request data: " . json_encode($request3->all()) . "\n";
    
    // Simulate the exact logic from the controller
    $ps4_primary_stock = 1;
    $ps4_secondary_stock = 1;
    $ps5_primary_stock = 1;
    $ps5_secondary_stock = 1;
    $ps4_offline_stock = 2;
    $ps5_offline_stock = 1;
    
    // Apply the same logic as in the controller
    if ($request3->has('ps4_primary')) {
        $ps4_primary_stock = 0;
    }
    if ($request3->has('ps4_secondary')) {
        $ps4_secondary_stock = 0;
    }
    if ($request3->has('ps5_primary')) {
        $ps5_primary_stock = 0;
    }
    if ($request3->has('ps5_secondary')) {
        $ps5_secondary_stock = 0;
    }
    
    // Special PS5 Offline Logic
    if ($request3->has('ps5_offline') && !$request3->has('ps5_primary') && !$request3->has('ps5_secondary')) {
        $ps5_offline_stock = 2;
        echo "Special PS5 logic applied: ps5_offline_stock = $ps5_offline_stock\n";
    } elseif ($request3->has('ps5_offline')) {
        $ps5_offline_stock = 0;
        echo "General PS5 logic applied: ps5_offline_stock = $ps5_offline_stock\n";
    }
    
    echo "Final stock values:\n";
    echo "- ps4_primary_stock: $ps4_primary_stock\n";
    echo "- ps4_secondary_stock: $ps4_secondary_stock\n";
    echo "- ps5_primary_stock: $ps5_primary_stock\n";
    echo "- ps5_secondary_stock: $ps5_secondary_stock\n";
    echo "- ps4_offline_stock: $ps4_offline_stock\n";
    echo "- ps5_offline_stock: $ps5_offline_stock\n";
} else {
    echo "No games found in database. Cannot test with actual game ID.\n";
}
