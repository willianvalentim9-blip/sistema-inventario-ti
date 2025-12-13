<?php
require_once '../../config.php';

try {
    $pdo = getConnection();
    
    // Procurar itens com has_warranty = 1 E que têm ao menos algum campo preenchido
    $stmt = $pdo->query("
        SELECT id, name, has_warranty, warranty_client_name, warranty_ticket_number,
               warranty_label, invoice_number, warranty_provider, warranty_start_date
        FROM warehouse
        WHERE has_warranty = 1 AND (is_deleted = FALSE OR is_deleted IS NULL)
        ORDER BY updated_at DESC
        LIMIT 10
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "❌ Nenhum item com has_warranty = 1 encontrado\n";
    } else {
        echo "✅ Itens com warranty encontrados:\n\n";
        foreach ($items as $item) {
            echo "ID: {$item['id']} | Nome: " . htmlspecialchars($item['name']) . "\n";
            echo "  - Client: " . ($item['warranty_client_name'] ?? '[VAZIO]') . "\n";
            echo "  - Ticket: " . ($item['warranty_ticket_number'] ?? '[VAZIO]') . "\n";
            echo "  - Label: " . ($item['warranty_label'] ?? '[VAZIO]') . "\n";
            echo "  - Invoice: " . ($item['invoice_number'] ?? '[VAZIO]') . "\n";
            echo "  - Provider: " . ($item['warranty_provider'] ?? '[VAZIO]') . "\n";
            echo "  - Start: " . ($item['warranty_start_date'] ?? '[VAZIO]') . "\n";
            echo "\n";
        }
    }
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
