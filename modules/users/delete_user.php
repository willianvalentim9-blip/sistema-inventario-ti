<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE USUÁRIO COM SOFT DELETE
// ========================================
// Este script processa a exclusão (soft delete) de um usuário via requisição AJAX

require_once '../../config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir usuários.']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário inválido.']);
    exit;
}

// Impede que o usuário se delete a si mesmo
if ($user_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Você não pode excluir a si mesmo.']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Inicia uma transação
    $pdo->beginTransaction();

    // Busca os dados do usuário ANTES de deletar para o log
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$user_id]);
    $user_to_delete = $stmt->fetch();
    
    if (!$user_to_delete) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou já foi deletado.']);
        exit;
    }

    // Realiza soft delete (marca como deletado)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_deleted = TRUE, deleted_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        // Log da atividade admin
        logAdminActivity(
            $_SESSION['user_id'],
            'DELETE_USER',
            'users',
            $user_id,
            'username: ' . $user_to_delete['username'] . ', email: ' . $user_to_delete['email'],
            'is_deleted = TRUE'
        );
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Usuário deletado com sucesso.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Falha ao deletar usuário.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao deletar usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao deletar usuário.']);
}
?>