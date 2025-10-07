<?php
// ========================================
// API PARA DAR BAIXA EM MÁQUINAS (VERSÃO CORRIGIDA PARA AJAX)
// ========================================

// Define o cabeçalho como JSON para a resposta
header('Content-Type: application/json');

require_once 'config.php';
requireLogin();

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    // Lendo dados do formulário via $_POST
    $machine_id = intval($_POST['machine_id'] ?? 0);
    $quantity_to_remove = intval($_POST['quantity'] ?? 1); 
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $final_sale_price_str = str_replace(['.', ','], ['', '.'], $_POST['final_sale_price'] ?? '0');
    $final_sale_price = floatval($final_sale_price_str);

    if ($machine_id <= 0) throw new Exception('ID da máquina inválido.');
    if ($quantity_to_remove <= 0) throw new Exception('A quantidade para baixa deve ser maior que zero.');
    if (empty($reason)) throw new Exception('O motivo da baixa é obrigatório.');

    $pdo = getConnection();
    $pdo->beginTransaction();

    // Bloqueia a linha para evitar problemas de concorrência
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? FOR UPDATE");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();

    if (!$machine) throw new Exception('Máquina não encontrada.');
    if ($machine['quantity'] < $quantity_to_remove) {
        throw new Exception("Quantidade insuficiente. Estoque atual: {$machine['quantity']}.");
    }

    $new_quantity = $machine['quantity'] - $quantity_to_remove;
    
    // Define o novo status apenas se o estoque zerar
    $new_status = $machine['status'];
    if ($new_quantity <= 0) {
        $new_status = ($reason === 'Venda') ? 'sold' : 'removed';
    }

    // 1. Atualiza a quantidade e o status na tabela principal
    $update_stmt = $pdo->prepare("UPDATE ready_machines SET quantity = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $update_stmt->execute([$new_quantity, $new_status, $machine_id]);

    // 2. Insere o registro na tabela de saídas de máquinas
    $stmt_output = $pdo->prepare("
        INSERT INTO machine_outputs 
            (machine_id, user_id, quantity_removed, reason, details, final_sale_price, machine_name, machine_serial_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_output->execute([
        $machine_id,
        $_SESSION['user_id'],
        $quantity_to_remove,
        $reason,
        $notes,
        ($reason === 'Venda' ? $final_sale_price : null),
        $machine['name'],
        $machine['serial_number']
    ]);

    // 3. Registra no log geral de movimentações
    $log_details = "Baixa de {$quantity_to_remove} unidade(s) de '{$machine['name']}'. Motivo: {$reason}.";
    logMachineMovement($machine_id, $_SESSION["user_id"], $reason, $machine["status"], $new_status, $log_details);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Baixa da máquina registrada com sucesso!';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Define um código de erro apropriado para AJAX
    $response['message'] = $e->getMessage();
    error_log("Erro em update_machine_status.php: " . $e->getMessage());
}

// Retorna a resposta em JSON para o JavaScript que fez a requisição
echo json_encode($response);
exit();