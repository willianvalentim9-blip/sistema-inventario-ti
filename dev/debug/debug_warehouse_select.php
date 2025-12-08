<?php
require_once 'config.php';

// ID do item para testar
$warehouse_id = intval($_GET['id'] ?? 1);

try {
    $pdo = getConnection();
    
    echo "=== DEBUG: Testando SELECT ===\n\n";
    
    // 1. Teste SELECT * (o que você estava usando)
    echo "1️⃣ SELECT * FROM warehouse:\n";
    $stmt1 = $pdo->prepare("SELECT * FROM warehouse WHERE id = ?");
    $stmt1->execute([$warehouse_id]);
    $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    if ($result1) {
        echo "✅ Encontrado: " . $result1['name'] . "\n";
        echo "   warranty_client_name: " . ($result1['warranty_client_name'] ?? '[NULL]') . "\n";
        echo "   warranty_ticket_number: " . ($result1['warranty_ticket_number'] ?? '[NULL]') . "\n";
        echo "   warranty_label: " . ($result1['warranty_label'] ?? '[NULL]') . "\n";
        echo "   invoice_number: " . ($result1['invoice_number'] ?? '[NULL]') . "\n";
    } else {
        echo "❌ Não encontrado\n";
    }
    
    echo "\n";
    
    // 2. Teste SELECT explícito (como em produto/máquina)
    echo "2️⃣ SELECT explícito (colunas específicas):\n";
    $stmt2 = $pdo->prepare("SELECT id, name, description, has_warranty, warranty_start_date, warranty_end_date, warranty_provider, warranty_notes, warranty_period_value, warranty_period_unit, warranty_ticket_number, warranty_label, warranty_client_name, invoice_number, warranty_supplier_id FROM warehouse WHERE id = ?");
    $stmt2->execute([$warehouse_id]);
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($result2) {
        echo "✅ Encontrado: " . $result2['name'] . "\n";
        echo "   warranty_client_name: " . ($result2['warranty_client_name'] ?? '[NULL]') . "\n";
        echo "   warranty_ticket_number: " . ($result2['warranty_ticket_number'] ?? '[NULL]') . "\n";
        echo "   warranty_label: " . ($result2['warranty_label'] ?? '[NULL]') . "\n";
        echo "   invoice_number: " . ($result2['invoice_number'] ?? '[NULL]') . "\n";
    } else {
        echo "❌ Não encontrado\n";
    }
    
    echo "\n\n";
    
    // 3. Verificar quais itens têm warranty com dados
    echo "3️⃣ Itens com warranty dados:\n";
    $stmt3 = $pdo->query("SELECT id, name, has_warranty, warranty_client_name, warranty_ticket_number, warranty_label, invoice_number FROM warehouse WHERE has_warranty = 1 AND (is_deleted = FALSE OR is_deleted IS NULL) LIMIT 5");
    $items = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($items)) {
        foreach ($items as $item) {
            echo "\nID: {$item['id']} | {$item['name']}\n";
            echo "  Client: " . ($item['warranty_client_name'] ?? '[vazio]') . "\n";
            echo "  Ticket: " . ($item['warranty_ticket_number'] ?? '[vazio]') . "\n";
            echo "  Label: " . ($item['warranty_label'] ?? '[vazio]') . "\n";
            echo "  Invoice: " . ($item['invoice_number'] ?? '[vazio]') . "\n";
        }
    } else {
        echo "Nenhum item com warranty encontrado\n";
    }
    
} catch(Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString();
}
?>
