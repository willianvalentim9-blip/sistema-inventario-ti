<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

echo "<h1>Debug - Dashboard de Garantia</h1>";

// Testar cada query do dashboard
echo "<h2>1. Total de produtos com garantia</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1");
$total_warranty_products = $stmt->fetch()['total'];
echo "Total: <strong>$total_warranty_products</strong><br>";

echo "<h2>2. Garantias ativas (fim >= hoje)</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1 AND warranty_end_date >= CURDATE()");
$active_warranties = $stmt->fetch()['total'];
echo "Ativas: <strong>$active_warranties</strong><br>";

echo "<h2>3. Garantias vencidas (fim < hoje)</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1 AND warranty_end_date < CURDATE()");
$expired_warranties = $stmt->fetch()['total'];
echo "Vencidas: <strong>$expired_warranties</strong><br>";

echo "<h2>4. Vencendo em breve (próximos 30 dias)</h2>";
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM products 
    WHERE has_warranty = 1 
    AND warranty_end_date >= CURDATE() 
    AND warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$expiring_soon = $stmt->fetch()['total'];
echo "Vencendo em 30 dias: <strong>$expiring_soon</strong><br>";

echo "<h2>5. Crítica (próximos 7 dias)</h2>";
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM products 
    WHERE has_warranty = 1 
    AND warranty_end_date >= CURDATE() 
    AND warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$critical_soon = $stmt->fetch()['total'];
echo "Crítica: <strong>$critical_soon</strong><br>";

echo "<h2>6. Produtos críticos (detalhes)</h2>";
$stmt = $pdo->query("
    SELECT 
        id, name, product_code, warranty_end_date,
        DATEDIFF(warranty_end_date, CURDATE()) as days_remaining,
        warranty_supplier_id, warranty_provider
    FROM products 
    WHERE has_warranty = 1 
    AND (warranty_end_date < CURDATE() OR warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
    ORDER BY warranty_end_date ASC
    LIMIT 10
");
$critical_products = $stmt->fetchAll();
echo "Encontrados: " . count($critical_products) . "<br>";
foreach ($critical_products as $prod) {
    echo "ID: {$prod['id']} | Nome: {$prod['name']} | Vence: {$prod['warranty_end_date']} | Dias: {$prod['days_remaining']}<br>";
}

echo "<h2>7. Resumo de Alertas</h2>";
$alerts_summary = [
    'critical' => $expired_warranties + $critical_soon,
    'warning' => $expiring_soon - $critical_soon,
    'attention' => 0,
    'active' => $active_warranties - $expiring_soon
];
echo "<pre>";
print_r($alerts_summary);
echo "</pre>";

echo "<h2>8. Datas importantes (hoje: " . date('Y-m-d') . ")</h2>";
echo "Data curdate(): " . date('Y-m-d', strtotime('CURDATE()')) . "<br>";
echo "Data +30 dias: " . date('Y-m-d', strtotime('+30 days')) . "<br>";
echo "Data +7 dias: " . date('Y-m-d', strtotime('+7 days')) . "<br>";

echo "<h2>9. Todos os produtos com garantia (sem filtros)</h2>";
$stmt = $pdo->query("
    SELECT 
        id, name, product_code, warranty_end_date,
        DATEDIFF(warranty_end_date, CURDATE()) as days_remaining
    FROM products 
    WHERE has_warranty = 1
    ORDER BY warranty_end_date ASC
    LIMIT 20
");
$all_warranty = $stmt->fetchAll();
echo "Total encontrados: " . count($all_warranty) . "<br>";
foreach ($all_warranty as $prod) {
    $status = 'OK';
    if ($prod['warranty_end_date'] < date('Y-m-d')) $status = 'VENCIDO';
    elseif ($prod['warranty_end_date'] <= date('Y-m-d', strtotime('+7 days'))) $status = 'CRÍTICA';
    elseif ($prod['warranty_end_date'] <= date('Y-m-d', strtotime('+30 days'))) $status = 'ATENÇÃO';
    
    echo "ID: {$prod['id']} | Nome: {$prod['name']} | Vence: {$prod['warranty_end_date']} | Dias: {$prod['days_remaining']} | Status: <strong>$status</strong><br>";
}
?>
