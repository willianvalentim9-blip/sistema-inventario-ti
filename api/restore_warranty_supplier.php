<?php
/**
 * API para restaurar fornecedor de garantia deletado (soft delete)
 */

require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $supplier_id = intval($data['id'] ?? 0);

    if ($supplier_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $pdo = getConnection();

    // Verificar se o fornecedor existe e está deletado
    $stmt = $pdo->prepare("SELECT name FROM warranty_suppliers WHERE id = ? AND is_deleted = TRUE");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado ou não está deletado']);
        exit;
    }

    // Restaurar o fornecedor (soft undelete)
    $stmt = $pdo->prepare("
        UPDATE warranty_suppliers
        SET is_deleted = FALSE,
            deleted_at = NULL,
            deleted_by = NULL,
            is_active = 1
        WHERE id = ?
    ");
    $stmt->execute([$supplier_id]);

    // Log da atividade
    logAdminActivity($_SESSION['user_id'], 'RESTORE_WARRANTY_SUPPLIER', 'warranty_suppliers', $supplier_id, null, ['name' => $supplier['name']]);

    echo json_encode([
        'success' => true,
        'message' => 'Fornecedor "' . $supplier['name'] . '" restaurado com sucesso!'
    ]);

} catch (PDOException $e) {
    error_log("Erro ao restaurar fornecedor: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao restaurar fornecedor']);
}
