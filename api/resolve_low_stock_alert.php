<?php
// ========================================
// API PARA RESOLVER ALERTA DE ESTOQUE BAIXO
// ========================================

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$notes = trim($input['notes'] ?? '');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $result = resolveLowStockAlert($product_id, $_SESSION['user_id'], $notes);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Alerta resolvido com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao resolver alerta']);
    }
} catch (Exception $e) {
    error_log("Erro ao resolver alerta: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao resolver alerta']);
}
?>
