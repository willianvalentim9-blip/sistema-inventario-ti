<?php
ob_start();
require_once 'config.php';
require_once 'log_functions.php';
requireLogin();

header('Content-Type: application/json');

$pdo = null;

try {
    $pdo = getConnection();
    error_log("give_stock_in.php: Conexão com o banco de dados estabelecida.");

    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $details = trim($_POST['details_field'] ?? '');
    $unit_price_str = str_replace(['.', ','], ['', '.'], $_POST['unit_price'] ?? '0');
    $unit_price = !empty($unit_price_str) ? floatval($unit_price_str) : null;

    if ($product_id <= 0 || $quantity <= 0 || empty($reason)) {
        error_log("give_stock_in.php: Dados inválidos - product_id: $product_id, quantity: $quantity, reason: $reason");
        $response = ['success' => false, 'message' => 'Dados inválidos.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        error_log("give_stock_in.php: Produto não encontrado para ID: $product_id");
        $response = ['success' => false, 'message' => 'Produto não encontrado.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // <-- ALTERAÇÃO AQUI: Verifica se a entrada excede a quantidade máxima
    if (isset($product['max_quantity']) && $product['max_quantity'] > 0) {
        if (($product['quantity'] + $quantity) > $product['max_quantity']) {
            $message = 'A quantidade a ser adicionada (' . $quantity . ') excede o limite máximo de estoque de ' . $product['max_quantity'] . ' unidades. Estoque atual: ' . $product['quantity'] . '.';
            error_log("give_stock_in.php: Tentativa de exceder o estoque máximo para o produto ID: $product_id");
            $response = ['success' => false, 'message' => $message];
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
    }
    // FIM DA ALTERAÇÃO -->

    $old_quantity = $product['quantity'];
    $new_quantity = $old_quantity + $quantity;

    $stmt = $pdo->prepare("UPDATE products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_quantity, $product_id]);

    logProductMovement(
        $product_id,
        $_SESSION["user_id"],
        "entrada",
        $quantity,
        $old_quantity,
        $new_quantity,
        $reason . (!empty($details) ? " - $details" : "") . (!empty($notes) ? " - $notes" : "")
    );

    logProductInput(
        $product_id,
        $_SESSION["user_id"],
        $quantity,
        $reason,
        (!empty($details) ? "$details" : "") . (!empty($notes) ? " - $notes" : ""),
        $product["name"],
        $product["category"],
        $product["serial_number"],
        $product["barcode"],
        $unit_price
    );

    $response = ['success' => true, 'message' => 'Entrada de estoque registrada com sucesso!'];
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro em give_stock_in.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Erro ao processar a entrada de estoque: ' . $e->getMessage()];
    ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json', true, 400);
    }
    echo json_encode($response);
}