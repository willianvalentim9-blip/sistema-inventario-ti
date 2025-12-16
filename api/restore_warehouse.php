<?php
// ========================================
// API PARA RESTAURAR ITEM DO WAREHOUSE (SOFT DELETE)
// ========================================

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$warehouse_id = intval($input['id'] ?? 0);

if ($warehouse_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $result = restoreWarehouse($warehouse_id, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Item do warehouse restaurado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item não encontrado ou já foi restaurado']);
    }
} catch (Exception $e) {
    error_log("Erro ao restaurar item warehouse: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao restaurar item do warehouse']);
}
?>
