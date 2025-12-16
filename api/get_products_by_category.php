<?php
// API para buscar produtos por categoria
// Usada para popular select dinâmico de componentes
// Retorna informações de estoque para alertas

header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

try {
    // Valida se o usuário está logado
    requireLogin();
    
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if (empty($category)) {
        http_response_code(400);
        echo json_encode(['error' => 'Categoria é obrigatória']);
        exit;
    }
    
    $pdo = getConnection();
    
    // ✅ NOVO: Suporta múltiplas categorias separadas por vírgula
    $categories = array_map('trim', explode(',', $category));
    $categories = array_filter($categories); // Remove strings vazias
    
    error_log("DEBUG: Buscando por categorias: " . json_encode($categories));
    
    if (empty($categories)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhuma categoria válida']);
        exit;
    }
    
    // Busca limite de estoque baixo da configuração (padrão 5)
    $minStockQuery = "SELECT COUNT(*) FROM products WHERE quantity <= 5 AND status = 'available'";
    $minStockStmt = $pdo->prepare($minStockQuery);
    $minStockStmt->execute();
    
    // ✅ NOVO: Query com suporte a múltiplas categorias
    // Cria placeholder dinâmico: ? , ?, ?
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    
    $query = "SELECT id, name, manufacturer, model, category, quantity, price, serial_number, barcode, min_quantity 
              FROM products 
              WHERE category IN ($placeholders) 
              AND status = 'available'
              AND quantity > 0
              ORDER BY quantity DESC, name ASC 
              LIMIT 50";
    
    $params = $categories;
    
    // Adiciona filtro de busca se fornecido
    if (!empty($search)) {
        $query = "SELECT id, name, manufacturer, model, category, quantity, price, serial_number, barcode, min_quantity 
                  FROM products 
                  WHERE category IN ($placeholders) 
                  AND status = 'available'
                  AND quantity > 0
                  AND (name LIKE ? OR manufacturer LIKE ? OR model LIKE ? OR serial_number LIKE ?)
                  ORDER BY quantity DESC, name ASC 
                  LIMIT 50";
        $searchTerm = "%{$search}%";
        $params = array_merge($categories, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    error_log("DEBUG: Query = " . $query);
    error_log("DEBUG: Params = " . json_encode($params));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adiciona status de estoque para cada produto
    foreach ($products as &$product) {
        $product['stock_status'] = getStockStatus($product['quantity'], $product['min_quantity'] ?? 5);
    }
    
    if (empty($products)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Nenhum produto encontrado nesta categoria com estoque disponível'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $products,
            'count' => count($products)
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}

/**
 * Determina status do estoque
 */
function getStockStatus($quantity, $minQuantity = 5) {
    if ($quantity <= 0) {
        return [
            'status' => 'out_of_stock',
            'label' => 'Sem Estoque',
            'color' => 'danger',
            'message' => 'Este produto não tem estoque disponível'
        ];
    } elseif ($quantity < $minQuantity) {
        return [
            'status' => 'low_stock',
            'label' => 'Estoque Baixo',
            'color' => 'warning',
            'message' => "Estoque baixo! Apenas {$quantity} unidade(s) disponível(is)"
        ];
    } else {
        return [
            'status' => 'in_stock',
            'label' => 'Em Estoque',
            'color' => 'success',
            'message' => "Estoque disponível: {$quantity} unidade(s)"
        ];
    }
}
?>
