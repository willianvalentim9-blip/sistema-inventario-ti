<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

echo "<h2>Produtos com Garantia</h2>";

// Verificar produtos com has_warranty = 1
$stmt = $pdo->query("SELECT id, name, has_warranty, warranty_end_date, warranty_supplier_id, warranty_start_date FROM products WHERE has_warranty = 1 ORDER BY created_at DESC LIMIT 10");
$products = $stmt->fetchAll();

echo "<pre>";
echo "Total de produtos com garantia: " . count($products) . "\n\n";

foreach ($products as $product) {
    echo "ID: " . $product['id'] . "\n";
    echo "Nome: " . $product['name'] . "\n";
    echo "has_warranty: " . $product['has_warranty'] . "\n";
    echo "warranty_start_date: " . $product['warranty_start_date'] . "\n";
    echo "warranty_end_date: " . $product['warranty_end_date'] . "\n";
    echo "warranty_supplier_id: " . $product['warranty_supplier_id'] . "\n";
    echo "---\n";
}
echo "</pre>";

echo "<h2>Contagem de Garantias por Status</h2>";

// Total de produtos com garantia
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1");
$total = $stmt->fetch()['total'];
echo "Total com has_warranty=1: <strong>$total</strong><br>";

// Garantias ativas (fim >= hoje)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1 AND warranty_end_date >= CURDATE()");
$active = $stmt->fetch()['total'];
echo "Ativas (fim >= hoje): <strong>$active</strong><br>";

// Garantias vencidas (fim < hoje)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1 AND warranty_end_date < CURDATE()");
$expired = $stmt->fetch()['total'];
echo "Vencidas (fim < hoje): <strong>$expired</strong><br>";

// Vencendo em breve (próximos 30 dias)
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM products 
    WHERE has_warranty = 1 
    AND warranty_end_date >= CURDATE() 
    AND warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$expiring = $stmt->fetch()['total'];
echo "Vencendo em 30 dias: <strong>$expiring</strong><br>";

// Crítica (até 7 dias)
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM products 
    WHERE has_warranty = 1 
    AND warranty_end_date >= CURDATE() 
    AND warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$critical = $stmt->fetch()['total'];
echo "Crítica (até 7 dias): <strong>$critical</strong><br>";

echo "<h2>Campos da Tabela Products</h2>";
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll();
echo "<table border='1'>";
foreach ($columns as $col) {
    echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td><td>" . $col['Null'] . "</td></tr>";
}
echo "</table>";
?>
