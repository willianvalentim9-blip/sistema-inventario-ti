<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    
    // Pegar o primeiro item com warranty
    $stmt = $pdo->query("SELECT * FROM warehouse WHERE has_warranty = 1 AND (is_deleted = FALSE OR is_deleted IS NULL) LIMIT 1");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        echo "Item encontrado ID: " . $item['id'] . "\n";
        echo "===========================================\n";
        echo "Nome: " . $item['name'] . "\n";
        echo "warranty_provider: " . ($item['warranty_provider'] ?? 'VAZIO') . "\n";
        echo "warranty_client_name: " . ($item['warranty_client_name'] ?? 'VAZIO') . "\n";
        echo "warranty_ticket_number: " . ($item['warranty_ticket_number'] ?? 'VAZIO') . "\n";
        echo "warranty_label: " . ($item['warranty_label'] ?? 'VAZIO') . "\n";
        echo "invoice_number: " . ($item['invoice_number'] ?? 'VAZIO') . "\n";
        echo "warranty_start_date: " . ($item['warranty_start_date'] ?? 'VAZIO') . "\n";
        echo "warranty_end_date: " . ($item['warranty_end_date'] ?? 'VAZIO') . "\n";
        echo "warranty_period_value: " . ($item['warranty_period_value'] ?? 'VAZIO') . "\n";
        echo "warranty_period_unit: " . ($item['warranty_period_unit'] ?? 'VAZIO') . "\n";
        echo "warranty_supplier_id: " . ($item['warranty_supplier_id'] ?? 'VAZIO') . "\n";
    } else {
        echo "Nenhum item com warranty encontrado\n";
    }
} catch(Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>
