<?php
// ========================================
// API DE BUSCA DE CÓDIGOS
// ========================================
// Esta API busca produtos e máquinas por código QR, código de barras ou SKU/Serial Number

header("Content-Type: application/json");

// Inclui o arquivo de configuração
require_once '../../config.php';

// Verifica se o usuário está logado
requireLogin();

$code = '';

// Tenta obter o código via GET (para uso direto na URL ou testes)
if (isset($_GET["code"])) {
    $code = trim($_GET["code"]);
} 
// Tenta obter o código via POST (para requisições AJAX)
else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $code = trim($input["code"] ?? "");
}

if (empty($code)) {
    echo json_encode(["success" => false, "message" => "Código não fornecido."]);
    exit;
}

try {
    $pdo = getConnection();
    $results = [];
    
    // Busca em produtos (COM ALTERAÇÃO)
    $stmt = $pdo->prepare("
        SELECT 
            id, name, description, category, quantity, price, image, 
            barcode, qr_code, sku, 'product' as type
        FROM products 
        WHERE barcode = ? OR qr_code = ? OR sku = ? OR serial_number = ?
    ");
    $stmt->execute([$code, $code, $code, $code]); // Adicionado mais um $code para o serial_number
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'type' => 'Produto',
            'category' => $row['category'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image' => $row['image'] ? 'uploads/products/' . $row['image'] : null,
            'barcode' => $row['barcode'],
            'qr_code' => $row['qr_code'],
            'sku' => $row['sku'],
            'view_url' => 'view_product.php?id=' . $row['id']
        ];
    }
    
    // Busca em máquinas prontas
    $stmt = $pdo->prepare("
        SELECT 
            id, name, description, image, 
            barcode, qr_code, serial_number, 'machine' as type
        FROM ready_machines 
        WHERE barcode = ? OR qr_code = ? OR serial_number = ?
    ");
    $stmt->execute([$code, $code, $code]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'type' => 'Máquina Pronta',
            'image' => $row['image'] ? 'uploads/machines/' . $row['image'] : null,
            'barcode' => $row['barcode'],
            'qr_code' => $row['qr_code'],
            'serial_number' => $row['serial_number'],
            'view_url' => 'view_machine.php?id=' . $row['id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);
    
} catch (PDOException $e) {
    error_log("Erro na busca de código: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>