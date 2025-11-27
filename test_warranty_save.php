<?php
/**
 * TESTE: VERIFICAR SALVAMENTO DE GARANTIA
 * 
 * Este script verifica os últimos produtos cadastrados e se os dados de garantia foram salvos
 */

require_once 'config.php';
requireLogin();

try {
    $pdo = getConnection();
    
    // Buscar os últimos 10 produtos com dados de garantia
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            has_warranty, 
            warranty_provider, 
            warranty_period_value, 
            warranty_period_unit, 
            warranty_start_date, 
            warranty_end_date,
            invoice_number,
            warranty_notes,
            created_at
        FROM products 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo '<div style="padding: 20px; font-family: Arial;">';
    echo '<h2>🔍 Teste de Salvamento de Garantia</h2>';
    
    echo '<table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">';
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<th>ID</th>';
    echo '<th>Produto</th>';
    echo '<th>Tem Garantia?</th>';
    echo '<th>Fornecedor</th>';
    echo '<th>Período</th>';
    echo '<th>Data Início</th>';
    echo '<th>Data Fim</th>';
    echo '<th>NF</th>';
    echo '<th>Criado em</th>';
    echo '</tr>';
    
    $warranty_count = 0;
    foreach ($products as $product) {
        if ($product['has_warranty']) {
            $warranty_count++;
        }
        
        echo '<tr style="background-color: ' . ($product['has_warranty'] ? '#e8f5e9' : '#ffebee') . '">';
        echo '<td>' . htmlspecialchars($product['id']) . '</td>';
        echo '<td>' . htmlspecialchars($product['name']) . '</td>';
        echo '<td>' . ($product['has_warranty'] ? '✅ Sim' : '❌ Não') . '</td>';
        echo '<td>' . htmlspecialchars($product['warranty_provider'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars(($product['warranty_period_value'] ?? '-') . ' ' . ($product['warranty_period_unit'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars($product['warranty_start_date'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($product['warranty_end_date'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($product['invoice_number'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($product['created_at']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    echo '<div style="margin-top: 20px; padding: 10px; background-color: #f0f0f0; border-left: 4px solid #2196F3;">';
    echo '<strong>📊 Resumo:</strong><br>';
    echo 'Total de produtos: ' . count($products) . '<br>';
    echo 'Produtos com garantia: <strong style="color: green;">' . $warranty_count . '</strong><br>';
    echo 'Produtos sem garantia: <strong style="color: red;">' . (count($products) - $warranty_count) . '</strong>';
    echo '</div>';
    
    echo '<div style="margin-top: 20px; padding: 10px; background-color: #fff9c4; border-left: 4px solid #fbc02d;">';
    echo '<strong>💡 Dica:</strong> Se nenhum produto aparece com garantia verde (com dados), o problema está no salvamento.';
    echo '</div>';
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="padding: 20px; color: red;">';
    echo '<strong>❌ Erro:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>
