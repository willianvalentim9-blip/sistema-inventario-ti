<?php
require_once 'config.php';

$pdo = getConnection();

// Check if there are suppliers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_suppliers WHERE is_active = 1");
$result = $stmt->fetch();
echo "Total suppliers: " . $result['total'] . "\n";

// Check products with warranties
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1");
$result = $stmt->fetch();
echo "Products with warranty: " . $result['total'] . "\n";

// List the first product with warranty
$stmt = $pdo->query("SELECT id, name, warranty_supplier_id, warranty_provider FROM products WHERE has_warranty = 1 LIMIT 1");
$product = $stmt->fetch();
if ($product) {
    echo "First product: " . $product['name'] . " (ID: " . $product['id'] . ")\n";
    echo "Warranty supplier ID: " . ($product['warranty_supplier_id'] ?? 'NULL') . "\n";
    echo "Warranty provider: " . ($product['warranty_provider'] ?? 'NULL') . "\n";
}

// List all suppliers
$stmt = $pdo->query("SELECT id, name, is_active FROM warranty_suppliers");
$suppliers = $stmt->fetchAll();
echo "\nAll suppliers:\n";
foreach ($suppliers as $supplier) {
    echo "  - " . $supplier['name'] . " (ID: " . $supplier['id'] . ", Active: " . ($supplier['is_active'] ? 'Yes' : 'No') . ")\n";
}
?>
