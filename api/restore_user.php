<?php
// ========================================
// SCRIPT DE RESTAURAÇÃO DE USUÁRIO (UNDO SOFT DELETE)
// ========================================
// Este script restaura um usuário que foi deletado (soft delete)

require_once '../config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem restaurar usuários.']);
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

try {
    $pdo = getConnection();
    
    // Inicia uma transação
    $pdo->beginTransaction();

    // Busca o usuário deletado
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_deleted = TRUE");
    $stmt->execute([$user_id]);
    $user_to_restore = $stmt->fetch();
    
    if (!$user_to_restore) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou já estava ativo.']);
        exit;
    }

    // Realiza restauração (marca como não deletado)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_deleted = FALSE, deleted_at = NULL
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        // Log da atividade admin
        logAdminActivity(
            $_SESSION['user_id'],
            'RESTORE_USER',
            'users',
            $user_id,
            'username: ' . $user_to_restore['username'] . ', email: ' . $user_to_restore['email'],
            'is_deleted = FALSE'
        );
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Usuário restaurado com sucesso.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Falha ao restaurar usuário.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao restaurar usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao restaurar usuário.']);
}
?>
