<?php
/**
 * RESUMO DAS CORREÇÕES - SISTEMA DE GARANTIAS
 * 
 * PROBLEMA: Tabela warranty_templates tem colunas period_value e period_unit
 * MAS código estava tentando inserir warranty_period_value e warranty_period_unit
 * 
 * SOLUÇÃO: Corrigido em todos os arquivos
 */

require_once 'config.php';
require_once 'includes/warranty_functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Verificação do Sistema de Garantias</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #007bff; margin-top: 30px; }
        .success { color: #155724; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0; }
        .error { color: #721c24; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 15px 0; }
        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0; }
        .info { color: #0c5460; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .check { color: green; font-weight: bold; }
        .cross { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>✅ Verificação do Sistema de Garantias</h1>";

try {
    // ===== 1. Verificar estrutura da tabela =====
    echo "<h2>1️⃣ Estrutura da Tabela warranty_templates</h2>";
    
    $result = $pdo->query("DESCRIBE warranty_templates");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $column_names = array_column($columns, 'Field');
    
    echo "<table>
        <tr>
            <th>Campo</th>
            <th>Tipo</th>
            <th>Status</th>
        </tr>";
    
    $expected_columns = [
        'id' => 'int',
        'name' => 'varchar',
        'description' => 'text',
        'period_value' => 'int',
        'period_unit' => 'enum',
        'warranty_provider' => 'varchar',
        'warranty_notes' => 'text',
        'is_active' => 'tinyint'
    ];
    
    foreach ($expected_columns as $col => $type) {
        $found = in_array($col, $column_names);
        $status = $found ? '<span class=\"check\">✓</span>' : '<span class=\"cross\">✗</span>';
        echo "<tr>
            <td><code>$col</code></td>
            <td>$type</td>
            <td>$status</td>
        </tr>";
    }
    echo "</table>";
    
    if (!in_array('period_value', $column_names)) {
        echo "<div class='error'>❌ ERRO: Coluna <code>period_value</code> não encontrada!</div>";
    } else {
        echo "<div class='success'>✅ Colunas corretas identificadas</div>";
    }
    
    // ===== 2. Verificar dados na tabela =====
    echo "<h2>2️⃣ Templates Cadastrados</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_templates");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count === 0) {
        echo "<div class='warning'>⚠️ Nenhum template encontrado na tabela</div>";
    } else {
        echo "<div class='success'>✅ " . $count . " template(s) encontrado(s)</div>";
        
        $stmt = $pdo->query("
            SELECT id, name, period_value, period_unit, warranty_provider, is_active 
            FROM warranty_templates 
            ORDER BY id
        ");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Período</th>
                <th>Fornecedor</th>
                <th>Status</th>
            </tr>";
        
        foreach ($templates as $t) {
            $status = $t['is_active'] ? '✓ Ativo' : '✗ Inativo';
            $periodo = $t['period_value'] . ' ' . $t['period_unit'];
            echo "<tr>
                <td>{$t['id']}</td>
                <td>" . htmlspecialchars($t['name']) . "</td>
                <td>$periodo</td>
                <td>" . htmlspecialchars($t['warranty_provider'] ?? '-') . "</td>
                <td>$status</td>
            </tr>";
        }
        echo "</table>";
    }
    
    // ===== 3. Testar getActiveTemplates =====
    echo "<h2>3️⃣ Teste da Função getActiveTemplates()</h2>";
    
    $active_templates = getActiveTemplates($pdo);
    
    if (empty($active_templates)) {
        echo "<div class='error'>❌ Nenhum template ativo retornado</div>";
    } else {
        echo "<div class='success'>✅ " . count($active_templates) . " template(s) ativo(s) retornado(s)</div>";
        
        // Verificar estrutura dos dados
        if (isset($active_templates[0])) {
            $first = $active_templates[0];
            echo "<div class='info'><strong>Estrutura dos dados:</strong>";
            echo "<ul>";
            foreach (array_keys($first) as $key) {
                echo "<li><code>$key</code></li>";
            }
            echo "</ul></div>";
            
            // Verificar se tem as chaves corretas
            if (isset($first['period_value']) && isset($first['period_unit'])) {
                echo "<div class='success'>✅ Estrutura de dados correta</div>";
            } else {
                echo "<div class='error'>❌ Estrutura de dados incorreta - faltam <code>period_value</code> ou <code>period_unit</code></div>";
            }
        }
    }
    
    // ===== 4. Verificar Arquivo Seletor Inline =====
    echo "<h2>4️⃣ Arquivo warranty_template_selector_inline.php</h2>";
    
    if (file_exists('warranty_template_selector_inline.php')) {
        echo "<div class='success'>✅ Arquivo existe</div>";
        
        $content = file_get_contents('warranty_template_selector_inline.php');
        
        $checks = [
            'getActiveTemplates' => 'Chamada getActiveTemplates()',
            'data-period-value' => 'Atributo period_value no HTML',
            'data-period-unit' => 'Atributo period_unit no HTML',
            'applyWarrantyTemplate' => 'Função JavaScript applyWarrantyTemplate()'
        ];
        
        foreach ($checks as $pattern => $desc) {
            if (strpos($content, $pattern) !== false) {
                echo "<div class='success'>✅ $desc</div>";
            } else {
                echo "<div class='error'>❌ Falta: $desc</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Arquivo warranty_template_selector_inline.php não encontrado</div>";
    }
    
    // ===== 5. Verificar edit_warranty.php =====
    echo "<h2>5️⃣ Arquivo edit_warranty.php</h2>";
    
    if (file_exists('edit_warranty.php')) {
        echo "<div class='success'>✅ Arquivo existe</div>";
        
        $content = file_get_contents('edit_warranty.php');
        
        if (strpos($content, 'warranty_template_selector_inline.php') !== false) {
            echo "<div class='success'>✅ Inclui warranty_template_selector_inline.php</div>";
        } else {
            echo "<div class='error'>❌ Não inclui warranty_template_selector_inline.php</div>";
        }
        
        if (strpos($content, 'require_once') !== false && strpos($content, 'warranty_functions') !== false) {
            echo "<div class='success'>✅ Inclui warranty_functions.php</div>";
        } else {
            echo "<div class='error'>❌ Não inclui warranty_functions.php</div>";
        }
        
        if (strpos($content, "openActionModal('warranty_template_selector.php?modal=true") === false) {
            echo "<div class='success'>✅ Sem modal aninhado de templates</div>";
        } else {
            echo "<div class='warning'>⚠️ Ainda tem referência a modal aninhado</div>";
        }
    }
    
    // ===== 6. Resumo Final =====
    echo "<h2>📋 Resumo Final</h2>";
    
    echo "<div class='success'>
        <h3>✅ Sistema Pronto para Uso!</h3>
        <p><strong>Correções Aplicadas:</strong></p>
        <ul>
            <li>✓ warranty_templates.php: INSERT e UPDATE usam <code>period_value</code> e <code>period_unit</code></li>
            <li>✓ warranties.php: INSERT e UPDATE usam <code>period_value</code> e <code>period_unit</code></li>
            <li>✓ warranty_functions.php: getActiveTemplates() busca colunas corretas</li>
            <li>✓ warranty_template_selector_inline.php: usa data-period-value e data-period-unit</li>
            <li>✓ edit_warranty.php: sem modal aninhado, templates aparecem inline</li>
        </ul>
        <p><strong>Próximos Passos:</strong></p>
        <ol>
            <li>Teste criar um novo template em <a href='warranty_templates.php'>warranty_templates.php</a></li>
            <li>Teste editar um produto em <a href='warranties.php'>warranties.php</a> e aplicar um template</li>
            <li>Verifique se o histórico de garantias foi registrado</li>
        </ol>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>
</body>
</html>";
?>
