<?php
/**
 * API para validar estoque de componentes
 * Verifica se algum produto ficará com estoque 0 após adicionar à máquina
 * 
 * POST: JSON com componentes selecionados
 * Response: {success, warning, message}
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

try {
    requireLogin();
    
    // Recebe JSON dos componentes
    $input = file_get_contents('php://input');
    $componentsData = json_decode($input, true);
    
    if (!is_array($componentsData) || empty($componentsData)) {
        echo json_encode(['success' => true, 'message' => 'Sem componentes']);
        exit;
    }
    
    $pdo = getConnection();
    $warningProducts = [];
    
    // Verifica cada componente
    foreach ($componentsData as $componentType => $component) {
        // Extrai productId
        $productId = null;
        
        if (is_array($component) && isset($component['productId'])) {
            $productId = intval($component['productId']);
        } elseif (is_array($component) && isset($component['id'])) {
            $productId = intval($component['id']);
        }
        
        if (!$productId) {
            continue; // Pula componentes sem ID
        }
        
        // Busca o produto
        $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            continue;
        }
        
        // Verifica se ficará com 0 após descontar 1
        if ($product['quantity'] <= 1) {
            // Ficará com 0 ou já está com 1
            $warningProducts[] = [
                'name' => $product['name'],
                'current' => $product['quantity'],
                'after' => max(0, $product['quantity'] - 1)
            ];
        }
    }
    
    if (!empty($warningProducts)) {
        // Há produtos que ficarão com estoque 0
        $productNames = array_map(function($p) {
            return "❌ {$p['name']} ({$p['current']} → {$p['after']} un.)";
        }, $warningProducts);
        
        $message = "⚠️ AVISO: Após adicionar estes componentes, o seguinte(s) produto(s) ficará(ão) com ESTOQUE ZERO:\n\n" . 
                   implode("\n", $productNames);
        
        echo json_encode([
            'success' => false,
            'warning' => true,
            'message' => $message,
            'products' => $warningProducts
        ]);
    } else {
        // Tudo OK
        echo json_encode([
            'success' => true,
            'warning' => false,
            'message' => 'Estoque validado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro em validate_component_stock.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'warning' => false,
        'message' => 'Erro ao validar estoque: ' . $e->getMessage()
    ]);
}
?>
