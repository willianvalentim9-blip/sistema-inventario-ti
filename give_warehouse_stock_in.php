<?php
ob_start();
require_once 'config.php';
require_once 'modules/logs/log_functions.php';
requireLogin();

header('Content-Type: application/json');

$pdo = null;

try {
    $pdo = getConnection();
    error_log("give_warehouse_stock_in.php: Conexão com o banco de dados estabelecida.");

    $warehouse_id = intval($_POST['warehouse_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $details = trim($_POST['details_field'] ?? '');
    $unit_price_str = str_replace(['.', ','], ['', '.'], $_POST['unit_price'] ?? '0');
    $unit_price = !empty($unit_price_str) ? floatval($unit_price_str) : null;

    if ($warehouse_id <= 0 || $quantity <= 0 || empty($reason)) {
        error_log("give_warehouse_stock_in.php: Dados inválidos - warehouse_id: $warehouse_id, quantity: $quantity, reason: $reason");
        $response = ['success' => false, 'message' => 'Dados inválidos.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ?");
    $stmt->execute([$warehouse_id]);
    $item = $stmt->fetch();

    if (!$item) {
        error_log("give_warehouse_stock_in.php: Item não encontrado para ID: $warehouse_id");
        $response = ['success' => false, 'message' => 'Item do warehouse não encontrado.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // Verifica se a entrada excede a quantidade máxima
    if (isset($item['max_quantity']) && $item['max_quantity'] > 0) {
        if (($item['quantity'] + $quantity) > $item['max_quantity']) {
            $message = 'A quantidade a ser adicionada (' . $quantity . ') excede o limite máximo de estoque de ' . $item['max_quantity'] . ' unidades. Estoque atual: ' . $item['quantity'] . '.';
            error_log("give_warehouse_stock_in.php: Tentativa de exceder o estoque máximo para o item ID: $warehouse_id");
            $response = ['success' => false, 'message' => $message];
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
    }

    $old_quantity = $item['quantity'];
    $new_quantity = $old_quantity + $quantity;

    $stmt = $pdo->prepare("UPDATE warehouse SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_quantity, $warehouse_id]);

    logProductMovement(
        $warehouse_id,
        $_SESSION["user_id"],
        "entrada",
        $quantity,
        $old_quantity,
        $new_quantity,
        $reason . (!empty($details) ? " - $details" : "") . (!empty($notes) ? " - $notes" : ""),
        "warehouse"
    );

    logProductInput(
        $warehouse_id,
        $_SESSION["user_id"],
        $quantity,
        $reason,
        (!empty($details) ? "$details" : "") . (!empty($notes) ? " - $notes" : ""),
        $item["name"],
        $item["category"],
        $item["serial_number"] ?? '',
        $item["barcode"] ?? '',
        $unit_price,
        "warehouse"
    );

    // Verifica estoque baixo após a entrada
    $low_stock_alert = checkAndLogLowStock($warehouse_id, "warehouse");
    
    $response = [
        'success' => true, 
        'message' => 'Entrada de estoque registrada com sucesso!',
        'low_stock_alert' => $low_stock_alert
    ];
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro em give_warehouse_stock_in.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Erro ao processar a entrada de estoque: ' . $e->getMessage()];
    ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json', true, 400);
    }
    echo json_encode($response);
}
