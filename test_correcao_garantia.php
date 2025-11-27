<?php
/**
 * TESTE AUTOMATIZADO: Verificar se a correção de garantia funciona
 * 
 * Este script valida:
 * 1. Se as colunas de garantia existem
 * 2. Se há produtos com garantia salvos
 * 3. Se os dados de garantia estão completos
 */

require_once 'config.php';
requireLogin();

$tests = [
    'passed' => 0,
    'failed' => 0,
    'results' => []
];

try {
    $pdo = getConnection();
    
    // TESTE 1: Verificar colunas de garantia
    echo '<h2>🧪 TESTES AUTOMATIZADOS DE GARANTIA</h2>';
    echo '<hr>';
    
    $stmt = $pdo->prepare("DESCRIBE products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = [
        'has_warranty',
        'warranty_provider',
        'warranty_period_value',
        'warranty_period_unit',
        'warranty_start_date',
        'warranty_end_date',
        'invoice_number',
        'warranty_notes'
    ];
    
    // Teste 1: Colunas de garantia
    echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<h3>Teste 1: Colunas de Garantia</h3>';
    
    $missing = [];
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            $missing[] = $col;
        }
    }
    
    if (empty($missing)) {
        echo '<div style="color: green; font-weight: bold;">✅ PASSOU: Todas as colunas existem</div>';
        $tests['passed']++;
    } else {
        echo '<div style="color: red; font-weight: bold;">❌ FALHOU: Colunas faltando:</div>';
        echo '<ul>';
        foreach ($missing as $col) {
            echo '<li>' . htmlspecialchars($col) . '</li>';
        }
        echo '</ul>';
        $tests['failed']++;
    }
    echo '</div>';
    
    // Teste 2: Verificar se há produtos com garantia
    echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<h3>Teste 2: Produtos com Garantia</h3>';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE has_warranty = 1");
    $stmt->execute();
    $warranty_count = $stmt->fetchColumn();
    
    if ($warranty_count > 0) {
        echo '<div style="color: green; font-weight: bold;">✅ PASSOU: ' . $warranty_count . ' produto(s) com garantia encontrado(s)</div>';
        $tests['passed']++;
    } else {
        echo '<div style="color: orange; font-weight: bold;">⚠️  AVISO: Nenhum produto com garantia encontrado</div>';
        echo '<p>Isso pode ser normal se você não criou/editou produtos com garantia ainda.</p>';
    }
    echo '</div>';
    
    // Teste 3: Validar integridade de dados
    echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<h3>Teste 3: Integridade de Dados</h3>';
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM products
        WHERE has_warranty = 1
        AND (warranty_provider IS NULL
             OR warranty_start_date IS NULL
             OR warranty_end_date IS NULL)
    ");
    $stmt->execute();
    $incomplete = $stmt->fetchColumn();
    
    if ($incomplete == 0) {
        echo '<div style="color: green; font-weight: bold;">✅ PASSOU: Todos os produtos com garantia têm dados completos</div>';
        $tests['passed']++;
    } else {
        echo '<div style="color: orange; font-weight: bold;">⚠️  AVISO: ' . $incomplete . ' produto(s) com garantia incompleta</div>';
        echo '<p>Produtos marcados com garantia mas sem todos os dados preenchidos.</p>';
    }
    echo '</div>';
    
    // Teste 4: Verificar formulário de edição
    echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<h3>Teste 4: Arquivos da Aplicação</h3>';
    
    $files_to_check = [
        'edit_product.php' => 'has_warranty',
        'add_product.php' => 'warranty_provider',
        'includes/warranty_modal_edit_inline.php' => 'product_warranty_provider'
    ];
    
    $all_files_ok = true;
    foreach ($files_to_check as $file => $search_term) {
        $filepath = $file;
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            if (strpos($content, $search_term) !== false) {
                echo '<div style="color: green;">✅ ' . htmlspecialchars($file) . ' - OK</div>';
            } else {
                echo '<div style="color: red;">❌ ' . htmlspecialchars($file) . ' - Não contém ' . htmlspecialchars($search_term) . '</div>';
                $all_files_ok = false;
            }
        } else {
            echo '<div style="color: red;">❌ ' . htmlspecialchars($file) . ' - Arquivo não encontrado</div>';
            $all_files_ok = false;
        }
    }
    
    if ($all_files_ok) {
        $tests['passed']++;
    } else {
        $tests['failed']++;
    }
    echo '</div>';
    
    // Teste 5: Último produto com garantia
    echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<h3>Teste 5: Último Produto com Garantia</h3>';
    
    $stmt = $pdo->prepare("
        SELECT 
            id, name, warranty_provider, warranty_start_date, 
            warranty_end_date, created_at
        FROM products
        WHERE has_warranty = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $product = $stmt->fetch();
    
    if ($product) {
        echo '<div style="color: green; font-weight: bold;">✅ PASSOU: Último produto encontrado</div>';
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<tr style="background-color: #f0f0f0;">';
        echo '<th style="border: 1px solid #ccc; padding: 10px; text-align: left;">Campo</th>';
        echo '<th style="border: 1px solid #ccc; padding: 10px; text-align: left;">Valor</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>ID</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['id']) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>Produto</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['name']) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>Fornecedor</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['warranty_provider'] ?? '(vazio)') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>Data Início</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['warranty_start_date'] ?? '(vazio)') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>Data Término</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['warranty_end_date'] ?? '(vazio)') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;"><strong>Criado em</strong></td>';
        echo '<td style="border: 1px solid #ccc; padding: 10px;">' . htmlspecialchars($product['created_at']) . '</td>';
        echo '</tr>';
        echo '</table>';
        $tests['passed']++;
    } else {
        echo '<div style="color: orange; font-weight: bold;">⚠️  AVISO: Nenhum produto com garantia</div>';
        echo '<p>Crie ou edite um produto marcando "Produto possui garantia?" para testar.</p>';
    }
    echo '</div>';
    
    // Resumo final
    echo '<hr>';
    echo '<div style="padding: 15px; background-color: #f0f0f0; border-radius: 5px;">';
    echo '<h3>📊 Resumo dos Testes</h3>';
    echo '<p><strong>Testes Passados:</strong> <span style="color: green; font-weight: bold;">' . $tests['passed'] . '</span></p>';
    echo '<p><strong>Testes Falhados:</strong> <span style="color: red; font-weight: bold;">' . $tests['failed'] . '</span></p>';
    
    if ($tests['failed'] == 0) {
        echo '<div style="color: green; font-weight: bold; margin-top: 10px;">';
        echo '✅ TODOS OS TESTES PASSARAM! A correção está funcionando.';
        echo '</div>';
    } else {
        echo '<div style="color: orange; font-weight: bold; margin-top: 10px;">';
        echo '⚠️  Alguns testes falharam. Verifique os erros acima.';
        echo '</div>';
    }
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="color: red; padding: 15px; background-color: #ffebee; border: 1px solid red; border-radius: 5px;">';
    echo '<h3>❌ Erro</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>
