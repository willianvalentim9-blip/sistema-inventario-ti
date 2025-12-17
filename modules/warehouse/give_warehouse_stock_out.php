<?php
// ========================================
// API PARA DAR BAIXA EM WAREHOUSE
// ========================================

header('Content-Type: application/json');

require_once '../../config.php';
requireLogin();

// Verificar permissão - admin e administrativo podem fazer saída de estoque
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores e administrativos podem fazer saída de estoque.']);
    exit;
}

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    $warehouse_id = intval($_POST['warehouse_id'] ?? 0);
    $quantity_to_remove = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $details_field = trim($_POST['details_field'] ?? '');
    $unit_price_str = str_replace(['.', ','], ['', '.'], $_POST['unit_price'] ?? '0');
    $unit_price = !empty($unit_price_str) ? floatval($unit_price_str) : null;

    if ($warehouse_id <= 0) throw new Exception('ID do item inválido.');
    if ($quantity_to_remove <= 0) throw new Exception('A quantidade para baixa deve ser maior que zero.');
    if (empty($reason)) throw new Exception('O motivo da baixa é obrigatório.');

    $pdo = getConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? FOR UPDATE");
    $stmt->execute([$warehouse_id]);
    $item = $stmt->fetch();

    if (!$item) throw new Exception('Item do warehouse não encontrado.');
    if ($item['quantity'] < $quantity_to_remove) {
        throw new Exception("Quantidade insuficiente. Estoque atual: {$item['quantity']}.");
    }

    $previous_quantity = $item['quantity'];
    $new_quantity = $previous_quantity - $quantity_to_remove;

    // ========================================
    // VALIDAÇÃO INTELIGENTE DE ESTOQUE MÍNIMO
    // ========================================
    $min_quantity = intval($item['min_quantity'] ?? 0);
    $will_be_below_min = ($new_quantity < $min_quantity && $min_quantity > 0);
    $warning_message = '';

    $update_stmt = $pdo->prepare("UPDATE warehouse SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_stmt->execute([$new_quantity, $warehouse_id]);
    
    $full_details = $notes;
    if (!empty($details_field)) {
        $full_details = "Cliente/Depto: " . $details_field . "\n" . $notes;
    }

    $stmt_output = $pdo->prepare("
        INSERT INTO warehouse_outputs 
            (warehouse_id, user_id, quantity_removed, reason, details, unit_price, warehouse_name, warehouse_category, warehouse_serial_number, warehouse_barcode)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_output->execute([
        $warehouse_id,
        $_SESSION['user_id'],
        $quantity_to_remove,
        $reason,
        trim($full_details),
        $unit_price,
        $item['name'],
        $item['category'],
        $item['serial_number'] ?? '',
        $item['barcode'] ?? ''
    ]);
    
    $log_reason = $reason . ' - ' . trim($full_details);
    logProductMovement(
        $warehouse_id,
        $_SESSION["user_id"],
        "saida",
        $quantity_to_remove,
        $previous_quantity,
        $new_quantity,
        $log_reason,
        "warehouse"
    );

    $pdo->commit();

    // Verifica estoque baixo após a saída
    $low_stock_alert = checkAndLogLowStock($warehouse_id, "warehouse");

    // ========================================
    // MENSAGEM INTELIGENTE COM AVISO DE ESTOQUE MÍNIMO
    // ========================================
    $success_msg = "Baixa de {$quantity_to_remove} unidade(s) de '{$item['name']}' registrada com sucesso!";

    if ($will_be_below_min) {
        $warning_message = "⚠️ ATENÇÃO: Estoque ficou abaixo do mínimo configurado!\n\n";
        $warning_message .= "• Estoque atual: {$new_quantity} unidade(s)\n";
        $warning_message .= "• Mínimo configurado: {$min_quantity} unidade(s)\n";
        $warning_message .= "• Diferença: " . ($min_quantity - $new_quantity) . " unidade(s) abaixo do mínimo\n\n";
        $warning_message .= "📦 Recomendação: Solicitar reposição de estoque.";
    }

    $response['success'] = true;
    $response['message'] = $success_msg;
    $response['warning'] = $warning_message;
    $response['stock_info'] = [
        'current' => $new_quantity,
        'min' => $min_quantity,
        'max' => intval($item['max_quantity'] ?? 0),
        'below_min' => $will_be_below_min
    ];
    $response['low_stock_alert'] = $low_stock_alert;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("Erro em give_warehouse_stock_out.php: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>
