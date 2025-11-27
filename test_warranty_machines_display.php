<?php
/**
 * TESTE: Verificar Máquinas com Garantia
 * 
 * Este arquivo testa se máquinas com garantia aparecem em warranties.php
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // Query 1: Contar máquinas com garantia
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ready_machines WHERE has_warranty = 1");
    $machines_with_warranty = $stmt->fetch()['count'];
    
    // Query 2: Contar produtos com garantia
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE has_warranty = 1");
    $products_with_warranty = $stmt->fetch()['count'];
    
    // Query 3: Testar UNION
    $stmt = $pdo->query("
        SELECT id, name, 'product' as item_type FROM products WHERE has_warranty = 1
        UNION ALL
        SELECT id, name, 'machine' as item_type FROM ready_machines WHERE has_warranty = 1
    ");
    $all_items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'machines_with_warranty' => $machines_with_warranty,
        'products_with_warranty' => $products_with_warranty,
        'total_items_with_warranty' => count($all_items),
        'items_by_type' => [
            'products' => array_values(array_filter($all_items, fn($x) => $x['item_type'] === 'product')),
            'machines' => array_values(array_filter($all_items, fn($x) => $x['item_type'] === 'machine'))
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
