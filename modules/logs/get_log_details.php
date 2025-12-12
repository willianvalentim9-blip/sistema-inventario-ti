<?php
// ========================================
// API PARA BUSCAR DETALHES DE UM LOG
// ========================================
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'ID do log não fornecido.'];
$log_id = intval($_GET['id'] ?? 0);

if ($log_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM system_logs WHERE id = ?");
        $stmt->execute([$log_id]);
        $log = $stmt->fetch();

        if ($log) {
            $response['success'] = true;
            $response['log'] = $log;
            $response['message'] = 'Detalhes do log recuperados com sucesso.';
        } else {
            http_response_code(404);
            $response['message'] = 'Log não encontrado.';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Erro de banco de dados: ' . $e->getMessage();
        error_log("Erro ao buscar detalhes do log: " . $e->getMessage());
    }
} else {
    http_response_code(400);
}

echo json_encode($response);
exit();
?>