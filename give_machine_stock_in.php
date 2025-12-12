<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'modules/logs/log_functions.php';
requireLogin();

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de requisição inválido.');
    }

    $pdo = getConnection();

    $machine_id = intval($_POST['machine_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $details = trim($_POST['details_field'] ?? '');

    if ($machine_id <= 0 || $quantity <= 0 || empty($reason)) {
        throw new Exception('Dados inválidos recebidos pelo servidor.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? FOR UPDATE");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();

    if (!$machine) {
        throw new Exception('Máquina não encontrada.');
    }

    $old_quantity = $machine['quantity'];
    $new_quantity = $old_quantity + $quantity;

    // 1. Atualiza o estoque da máquina
    $updateStmt = $pdo->prepare("UPDATE ready_machines SET quantity = ?, status = 'available', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$new_quantity, $machine_id]);

    // 2. Log de movimentação geral
    $full_details_log = $reason . (!empty($details) ? " - $details" : "") . (!empty($notes) ? " | Obs: $notes" : "");
    logMachineMovement(
        $machine_id,
        $_SESSION["user_id"],
        "entrada",
        $machine['status'],
        'available',
        $full_details_log
    );

    // 3. Log específico na tabela de entradas de máquinas
    logMachineInput(
        $machine_id,
        $_SESSION["user_id"],
        $quantity,
        $reason,
        $full_details_log,
        $machine["name"],
        $machine["serial_number"],
        $machine["cost_price"]
    );

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Entrada de estoque da máquina registrada com sucesso!';

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    $response['message'] = 'Erro ao processar a entrada: ' . $e->getMessage();
    error_log("Erro em give_machine_stock_in.php: " . $e->getMessage());
}

echo json_encode($response);
exit();