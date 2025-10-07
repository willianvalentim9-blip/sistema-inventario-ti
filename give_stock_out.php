<?php
// ========================================
// API PARA DAR BAIXA EM PRODUTOS (VERSÃO CORRIGIDA E FINAL)
// ========================================

header('Content-Type: application/json');

require_once 'config.php';
requireLogin();

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity_to_remove = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $details_field = trim($_POST['details_field'] ?? '');
    $unit_price_str = str_replace(['.', ','], ['', '.'], $_POST['unit_price'] ?? '0');
    $unit_price = !empty($unit_price_str) ? floatval($unit_price_str) : null;

    if ($product_id <= 0) throw new Exception('ID do produto inválido.');
    if ($quantity_to_remove <= 0) throw new Exception('A quantidade para baixa deve ser maior que zero.');
    if (empty($reason)) throw new Exception('O motivo da baixa é obrigatório.');

    $pdo = getConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) throw new Exception('Produto não encontrado.');
    if ($product['quantity'] < $quantity_to_remove) {
        throw new Exception("Quantidade insuficiente. Estoque atual: {$product['quantity']}.");
    }

    $previous_quantity = $product['quantity'];
    $new_quantity = $previous_quantity - $quantity_to_remove;

    $update_stmt = $pdo->prepare("UPDATE products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_stmt->execute([$new_quantity, $product_id]);
    
    $full_details = $notes;
    if (!empty($details_field)) {
        $full_details = "Cliente/Depto: " . $details_field . "\n" . $notes;
    }

    $stmt_output = $pdo->prepare("
        INSERT INTO product_outputs 
            (product_id, user_id, quantity_removed, reason, details, unit_price, product_name, product_category, product_serial_number, product_barcode)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_output->execute([
        $product_id,
        $_SESSION['user_id'],
        $quantity_to_remove,
        $reason,
        trim($full_details),
        $unit_price,
        $product['name'],
        $product['category'],
        $product['serial_number'],
        $product['barcode']
    ]);
    
    $log_reason = $reason . ' - ' . trim($full_details);
    logProductMovement(
        $product_id,
        $_SESSION["user_id"],
        "saida",
        $quantity_to_remove,
        $previous_quantity,
        $new_quantity,
        $log_reason
    );

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Baixa de {$quantity_to_remove} unidade(s) de '{$product['name']}' registrada com sucesso!";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("Erro em give_stock_out.php: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>
