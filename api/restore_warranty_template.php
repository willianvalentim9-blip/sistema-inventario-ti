<?php
/**
 * API para restaurar template de garantia deletado (soft delete)
 */

require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $template_id = intval($data['id'] ?? 0);

    if ($template_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $pdo = getConnection();

    // Verificar se o template existe e está deletado
    $stmt = $pdo->prepare("SELECT name FROM warranty_templates WHERE id = ? AND is_deleted = TRUE");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch();

    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'Template não encontrado ou não está deletado']);
        exit;
    }

    // Restaurar o template (soft undelete)
    $stmt = $pdo->prepare("
        UPDATE warranty_templates
        SET is_deleted = FALSE,
            deleted_at = NULL,
            deleted_by = NULL,
            is_active = 1
        WHERE id = ?
    ");
    $stmt->execute([$template_id]);

    // Log da atividade
    logAdminActivity($_SESSION['user_id'], 'RESTORE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, null, ['name' => $template['name']]);

    echo json_encode([
        'success' => true,
        'message' => 'Template "' . $template['name'] . '" restaurado com sucesso!'
    ]);

} catch (PDOException $e) {
    error_log("Erro ao restaurar template: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao restaurar template']);
}
