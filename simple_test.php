<?php

// Simple test to debug the PS5 logic
echo "Testing PS5 Offline Logic...\n\n";

// Simulate request data as it would come from the form
$requestData = [
    'mail' => 'test@example.com',
    'password' => 'password123',
    'game_id' => '1',
    'region' => 'US',
    'cost' => '25.00',
    'birthdate' => '1990-01-01',
    'login_code' => 'ABC123',
    'ps5_offline' => '1'
];

echo "Request data:\n";
print_r($requestData);

echo "\nTesting conditions:\n";
echo "has('ps5_offline'): " . (isset($requestData['ps5_offline']) ? 'YES' : 'NO') . "\n";
echo "has('ps5_primary'): " . (isset($requestData['ps5_primary']) ? 'YES' : 'NO') . "\n";
echo "has('ps5_secondary'): " . (isset($requestData['ps5_secondary']) ? 'YES' : 'NO') . "\n";

echo "\nCondition check:\n";
$condition1 = isset($requestData['ps5_offline']) && !isset($requestData['ps5_primary']) && !isset($requestData['ps5_secondary']);
echo "Only PS5 offline: " . ($condition1 ? 'TRUE' : 'FALSE') . "\n";

$condition2 = isset($requestData['ps5_offline']);
echo "Has PS5 offline: " . ($condition2 ? 'TRUE' : 'FALSE') . "\n";

echo "\nLogic execution:\n";
$ps5_offline_stock = 1; // Default

if ($condition1) {
    $ps5_offline_stock = 2;
    echo "Special logic applied: ps5_offline_stock = $ps5_offline_stock\n";
} elseif ($condition2) {
    $ps5_offline_stock = 0;
    echo "General logic applied: ps5_offline_stock = $ps5_offline_stock\n";
} else {
    echo "Default value: ps5_offline_stock = $ps5_offline_stock\n";
}

echo "\nFinal result: ps5_offline_stock = $ps5_offline_stock\n";
