<?php
// ========================================
// API: OBTER HISTÓRICO DO USUÁRIO
// ========================================
// Retorna JSON com todas as ações registradas de um usuário específico

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Verifica autenticação
requireLogin();

// Verifica se é admin
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Valida user_id
$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID inválido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Busca histórico do usuário
    $stmt = $pdo->prepare("
        SELECT 
            id,
            action,
            table_name,
            record_id,
            old_values,
            new_values,
            timestamp
        FROM admin_logs
        WHERE user_id = ?
        ORDER BY timestamp DESC
        LIMIT 100
    ");
    $stmt->execute([$user_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao buscar histórico do usuário: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar histórico'
    ]);
}
?>
