<?php
require_once 'config.php';

$pdo = getConnection();

// Check suppliers
$stmt = $pdo->query("SELECT id, name, is_active FROM warranty_suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== WARRANTY SUPPLIERS ===\n";
echo "Total: " . count($suppliers) . "\n\n";

if (empty($suppliers)) {
    echo "No suppliers found in database.\n";
} else {
    foreach ($suppliers as $s) {
        echo "ID: {$s['id']}, Name: {$s['name']}, Active: " . ($s['is_active'] ? 'Yes' : 'No') . "\n";
    }
}

// Test the API
echo "\n=== API TEST ===\n";
echo "Testing get_warranty_suppliers.php API endpoint:\n";

// Simulate API call
ob_start();
header('Content-Type: application/json');
$_SERVER['REQUEST_METHOD'] = 'GET';

// Read and execute get_warranty_suppliers.php
$api_output = file_get_contents('get_warranty_suppliers.php');
echo "API file size: " . strlen($api_output) . " bytes\n";
?>
