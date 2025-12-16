<?php
// ========================================
// API PARA RESTAURAR GARANTIA (SOFT DELETE)
// APENAS ADMINISTRATIVOS
// ========================================

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

requireLogin();

// Verificar se é administrativo (controle de acesso restrito)
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administrativos podem restaurar garantias.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$warranty_id = intval($input['id'] ?? 0);

if ($warranty_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $result = restoreWarranty($warranty_id, $_SESSION['user_id']);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Garantia restaurada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Garantia não encontrada ou já foi restaurada']);
    }
} catch (Exception $e) {
    error_log("Erro ao restaurar garantia: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao restaurar garantia']);
}
?>
