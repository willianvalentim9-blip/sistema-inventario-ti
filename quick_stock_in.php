<?php
// Garante que os arquivos essenciais são carregados primeiro
require_once 'config.php';
// A função logProductInput está neste arquivo, então ele é essencial.
require_once 'modules/logs/log_functions.php';

// A função requireLogin() está em config.php
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    $product_code = trim($_POST['product_code'] ?? '');
    $quantity_to_add = intval($_POST['add_quantity'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? 0;

    if (empty($product_code)) {
        throw new Exception('O código do produto é obrigatório.');
    }
    if ($quantity_to_add <= 0) {
        throw new Exception('A quantidade deve ser maior que zero.');
    }
    if ($user_id <= 0) {
        throw new Exception('Sessão do usuário inválida. Por favor, faça login novamente.');
    }

    $pdo = getConnection();
    $pdo->beginTransaction();

    // **CORREÇÃO PRINCIPAL AQUI**
    // Buscamos mais colunas (category, serial_number, etc.) para usar na função de log.
    $stmt = $pdo->prepare("
        SELECT id, name, quantity, max_quantity, category, serial_number, barcode, price 
        FROM products 
        WHERE barcode = :code OR serial_number = :code OR qr_code = :code 
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([':code' => $product_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404); // Not Found
        throw new Exception("Produto com o código '{$product_code}' não foi encontrado.");
    }

    $product_id = $product['id'];
    $previous_quantity = (int)$product['quantity'];
    $new_quantity = $previous_quantity + $quantity_to_add;

    // Verifica a quantidade máxima, se definida e for maior que zero
    if (!empty($product['max_quantity']) && $product['max_quantity'] > 0 && $new_quantity > $product['max_quantity']) {
        throw new Exception("A adição excede a quantidade máxima de estoque ({$product['max_quantity']}).");
    }

    // Atualiza a quantidade do produto
    $update_stmt = $pdo->prepare("UPDATE products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_stmt->execute([$new_quantity, $product_id]);
    
    $reason = "Entrada Expressa via Leitor";

    // 1. Log na tabela 'product_movements' (histórico geral)
    logProductMovement(
        $product_id,
        $user_id,
        'entrada',
        $quantity_to_add,
        $previous_quantity,
        $new_quantity,
        $reason 
    );

    // 2. Log na tabela 'product_inputs' (histórico específico de entradas)
    // Esta chamada estava faltando e provavelmente causava o erro.
    logProductInput(
        $product_id,
        $user_id,
        $quantity_to_add,
        "Entrada Expressa", // Motivo principal
        "Entrada via leitor de código", // Detalhes
        $product["name"],
        $product["category"],
        $product["serial_number"],
        $product["barcode"],
        $product["price"] // Usando o preço do produto como 'unit_price' de referência
    );

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "{$quantity_to_add}x '{$product['name']}' adicionado(s). Novo estoque: {$new_quantity}.";

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    if (http_response_code() === 200) {
        http_response_code(400); // Bad Request
    }

    $response['message'] = $e->getMessage();
    error_log("[quick_stock_in.php] Erro: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>