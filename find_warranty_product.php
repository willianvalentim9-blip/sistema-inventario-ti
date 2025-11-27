<?php
require_once 'config.php';

$pdo = getConnection();

// Find first product with warranty
$stmt = $pdo->query("SELECT id, name, warranty_supplier_id FROM products WHERE has_warranty = 1 LIMIT 1");
$product = $stmt->fetch();

if ($product) {
    echo "Found product with warranty:\n";
    echo "ID: " . $product['id'] . "\n";
    echo "Name: " . $product['name'] . "\n";
    echo "Warranty Supplier ID: " . ($product['warranty_supplier_id'] ?? 'NULL') . "\n";
    echo "\nEdit URL: <a href='edit_warranty.php?id=" . $product['id'] . "'>edit_warranty.php?id=" . $product['id'] . "</a>\n";
} else {
    echo "No products with warranty found in database.\n";
}
?>
