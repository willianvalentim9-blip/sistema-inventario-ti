<?php
require_once '../../config.php';

header('Content-Type: application/json');

requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir usuários.']);
    exit;
}

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

if ($user_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Você não pode excluir a si mesmo.']);
    exit;
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        logAdminActivity($_SESSION["user_id"], "DELETE_USER", "users", $user_id);
        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou não pôde ser excluído.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao excluir usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir usuário.']);
}
?>