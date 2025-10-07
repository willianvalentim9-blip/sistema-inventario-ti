<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE MÁQUINA PRONTA (VERSÃO CORRIGIDA COM LOG)
// ========================================
// Este script processa a exclusão de uma máquina pronta e registra a ação no log de admin

require_once 'config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
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
$pdo = null;

if ($machine_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID da máquina inválido.']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Inicia uma transação
    $pdo->beginTransaction();

    // Primeiro, busca todos os dados da máquina ANTES de deletar, para fins de log
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ?");
    $stmt->execute([$machine_id]);
    $machine_to_delete = $stmt->fetch();

    if (!$machine_to_delete) {
        throw new Exception('Máquina não encontrada.');
    }

    $machine_image = $machine_to_delete['image'];

    // Exclui a máquina do banco de dados
    $delete_stmt = $pdo->prepare("DELETE FROM ready_machines WHERE id = ?");
    $delete_stmt->execute([$machine_id]);

    if ($delete_stmt->rowCount() > 0) {
        // Se a exclusão do banco de dados foi bem-sucedida, tenta remover o arquivo de imagem
        if (!empty($machine_image)) {
            $image_path = 'uploads/machines/' . $machine_image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Registra a ação de exclusão no log de administrador
        logAdminActivity($_SESSION["user_id"], "DELETE", "ready_machines", $machine_id, $machine_to_delete, null);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Máquina excluída com sucesso.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Máquina não encontrada ou não pôde ser excluída.']);
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