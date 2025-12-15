<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE ITEM WAREHOUSE COM SOFT DELETE
// ========================================

require_once '../../config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir itens do warehouse.']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$warehouse_id = intval($input['id'] ?? 0);

if ($warehouse_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do item inválido.']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Inicia uma transação
    $pdo->beginTransaction();

    // Busca os dados do item ANTES de deletar para o log
    $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$warehouse_id]);
    $item_to_delete = $stmt->fetch();
    
    if (!$item_to_delete) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item do warehouse não encontrado ou já foi deletado.']);
        exit;
    }

    // Realiza soft delete (marca como deletado)
    $result = softDeleteWarehouse($warehouse_id, $_SESSION['user_id']);
    
    if ($result) {
        // Registra a ação
        logAdminActivity($_SESSION["user_id"], "DELETE", "warehouse", $warehouse_id, $item_to_delete, null);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Item do warehouse excluído com sucesso. (Dados preservados para recuperação)',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Não foi possível excluir o item do warehouse.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir item warehouse: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir item.']);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
