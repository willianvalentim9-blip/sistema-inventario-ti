<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE MÁQUINA COM SOFT DELETE
// ========================================
// Este script processa a exclusão (soft delete) de uma máquina pronta e registra a ação no log de admin

require_once 'config.php';
require_once 'includes/machine_components_functions.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir máquinas.']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$machine_id = intval($input['id'] ?? 0);
$return_stock = isset($input['return_stock']) ? (bool)$input['return_stock'] : true;
$pdo = null;

if ($machine_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID da máquina inválido.']);
    exit;
}

try {
    $pdo = getConnection();

    // Inicia uma transação
    $pdo->beginTransaction();

    // Primeiro, busca todos os dados da máquina ANTES de deletar
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? AND is_deleted = FALSE");
    $stmt->execute([$machine_id]);
    $machine_to_delete = $stmt->fetch();

    if (!$machine_to_delete) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Máquina não encontrada ou já foi deletada.']);
        exit;
    }

    // Busca componentes antes de excluir
    $components = getMachineComponents($pdo, $machine_id);
    $components_count = count($components);

    // Devolve componentes ao estoque se solicitado
    if ($return_stock && $components_count > 0) {
        $return_result = returnMachineComponentsToStock($pdo, $machine_id, 'exclusao');

        if (!$return_result) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao devolver componentes ao estoque.']);
            exit;
        }

        logAdminActivity(
            $_SESSION["user_id"],
            'RETURN_STOCK',
            'machine_products',
            $machine_id,
            "Devolvidos {$components_count} componentes ao estoque"
        );
    }

    // Realiza soft delete (marca como deletada)
    $result = softDeleteMachine($machine_id, $_SESSION['user_id']);

    if ($result) {
        // Registra a ação
        logAdminActivity($_SESSION["user_id"], "DELETE", "ready_machines", $machine_id, $machine_to_delete, null);

        $pdo->commit();

        $message = 'Máquina excluída com sucesso.';
        if ($return_stock && $components_count > 0) {
            $message .= " {$components_count} componente(s) devolvido(s) ao estoque.";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted_at' => date('Y-m-d H:i:s'),
            'components_returned' => $return_stock ? $components_count : 0
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Não foi possível excluir a máquina.']);
    }

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir máquina: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir máquina.']);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>