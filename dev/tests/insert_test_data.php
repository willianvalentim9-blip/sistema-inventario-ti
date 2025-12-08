<?php
/**
 * Script: Inserir histórico de teste com IDs corretos
 */

require 'config.php';
requireLogin();

// Apenas admin
if ($_SESSION['user_role'] !== 'admin') {
    die('Apenas administradores podem acessar este teste');
}

$pdo = getConnection();

echo "<h1>✅ Inserindo Dados de Teste no Histórico</h1>";

try {
    // Obter primeira máquina
    $stmt = $pdo->query("SELECT id, name FROM ready_machines LIMIT 1");
    $machine = $stmt->fetch();
    
    // Obter primeiro produto
    $stmt = $pdo->query("SELECT id, name FROM products LIMIT 1");
    $product = $stmt->fetch();
    
    // Obter primeiro item de warehouse
    $stmt = $pdo->query("SELECT id, name FROM warehouse WHERE is_deleted = FALSE OR is_deleted IS NULL LIMIT 1");
    $warehouse_item = $stmt->fetch();
    
    echo "<div class='alert alert-info'>";
    echo "<h3>IDs Encontrados:</h3>";
    if ($machine) echo "<p>✓ Máquina ID: " . $machine['id'] . " - " . htmlspecialchars($machine['name']) . "</p>";
    if ($product) echo "<p>✓ Produto ID: " . $product['id'] . " - " . htmlspecialchars($product['name']) . "</p>";
    if ($warehouse_item) echo "<p>✓ Warehouse ID: " . $warehouse_item['id'] . " - " . htmlspecialchars($warehouse_item['name']) . "</p>";
    echo "</div>";
    
    $inserted = 0;
    
    // Inserir para máquina
    if ($machine) {
        $stmt = $pdo->prepare("
            INSERT INTO warranty_history (machine_id, user_id, action_type, change_description, created_at)
            VALUES (?, ?, 'CREATE', 'Histórico de teste - Máquina', NOW())
        ");
        $stmt->execute([$machine['id'], $_SESSION['user_id']]);
        echo "<div class='alert alert-success'>✓ Histórico inserido para MÁQUINA ID: " . $machine['id'] . "</div>";
        $inserted++;
    }
    
    // Inserir para produto
    if ($product) {
        $stmt = $pdo->prepare("
            INSERT INTO warranty_history (product_id, user_id, action_type, change_description, created_at)
            VALUES (?, ?, 'CREATE', 'Histórico de teste - Produto', NOW())
        ");
        $stmt->execute([$product['id'], $_SESSION['user_id']]);
        echo "<div class='alert alert-success'>✓ Histórico inserido para PRODUTO ID: " . $product['id'] . "</div>";
        $inserted++;
    }
    
    // Inserir para warehouse
    if ($warehouse_item) {
        $stmt = $pdo->prepare("
            INSERT INTO warranty_history (warehouse_id, user_id, action_type, change_description, created_at)
            VALUES (?, ?, 'CREATE', 'Histórico de teste - Warehouse', NOW())
        ");
        $stmt->execute([$warehouse_item['id'], $_SESSION['user_id']]);
        echo "<div class='alert alert-success'>✓ Histórico inserido para WAREHOUSE ID: " . $warehouse_item['id'] . "</div>";
        $inserted++;
    }
    
    echo "<hr>";
    echo "<h2>✅ Total de registros inseridos: " . $inserted . "</h2>";
    
    echo "<h3>🔗 Próximos Passos:</h3>";
    echo "<ol>";
    if ($machine) {
        echo "<li><a href='warranties.php?tab=machines' target='_blank'>Ir para Garantias → Máquinas</a></li>";
        echo "<li>Clique no botão 'Ver Histórico' (ícone de relógio) para a máquina</li>";
    }
    if ($warehouse_item) {
        echo "<li><a href='warranties.php?tab=warehouse' target='_blank'>Ir para Garantias → Armazém</a></li>";
        echo "<li>Clique no botão 'Ver Histórico' (ícone de relógio) para o item</li>";
    }
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    error_log("Erro ao inserir histórico: " . $e->getMessage());
}

echo "<hr>";
echo "<a href='check_ids.php' class='btn btn-info me-2'>Ver IDs Disponíveis</a>";
echo "<a href='warranties.php' class='btn btn-primary'>Ir para Garantias</a>";

?>
