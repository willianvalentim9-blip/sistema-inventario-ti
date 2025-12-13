<?php
require_once '../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getConnection();
    
    try {
        $stmt = $pdo->query("SELECT * FROM warranty_suppliers WHERE is_active = 1 ORDER BY name ASC");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $suppliers
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar fornecedores: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
