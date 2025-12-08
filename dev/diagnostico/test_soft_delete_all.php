<?php
/**
 * TESTE COMPLETO DE SOFT DELETE - TODOS OS SETORES
 *
 * Testa o soft delete em:
 * - Produtos
 * - Máquinas
 * - Armazém
 * - Garantias
 * - Templates de Garantia
 * - Fornecedores (se existir)
 */

require_once '../config.php';
requireLogin();

$test_results = [];

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #2c3e50; }
    h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #3498db; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

echo "<h1>🧪 Teste Completo de Soft Delete - Sistema IT Inventory</h1>";
echo "<p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Usuário:</strong> " . htmlspecialchars($_SESSION['username']) . " (" . htmlspecialchars($_SESSION['user_role']) . ")</p>";
echo "<hr>";

try {
    $pdo = getConnection();

    // ========================================
    // TESTE 1: PRODUTOS
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>📦 Teste 1: Produtos (Products)</h2>";

    // Verificar estrutura
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

    echo "<p><strong>Estrutura:</strong> ";
    if ($has_soft_delete) {
        echo "<span class='success'>✓ Soft delete configurado</span></p>";
    } else {
        echo "<span class='error'>✗ Soft delete NÃO configurado</span></p>";
    }

    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = FALSE OR is_deleted IS NULL");
    $active = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = TRUE");
    $deleted = $stmt->fetch()['total'];

    echo "<table>";
    echo "<tr><th>Status</th><th>Quantidade</th></tr>";
    echo "<tr><td>Ativos</td><td>$active</td></tr>";
    echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
    echo "</table>";

    $test_results['products'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    echo "</div>";

    // ========================================
    // TESTE 2: MÁQUINAS
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>💻 Teste 2: Máquinas (Ready Machines)</h2>";

    $stmt = $pdo->query("SHOW COLUMNS FROM ready_machines");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

    echo "<p><strong>Estrutura:</strong> ";
    if ($has_soft_delete) {
        echo "<span class='success'>✓ Soft delete configurado</span></p>";
    } else {
        echo "<span class='error'>✗ Soft delete NÃO configurado</span></p>";
    }

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE is_deleted = FALSE OR is_deleted IS NULL");
    $active = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE is_deleted = TRUE");
    $deleted = $stmt->fetch()['total'];

    echo "<table>";
    echo "<tr><th>Status</th><th>Quantidade</th></tr>";
    echo "<tr><td>Ativos</td><td>$active</td></tr>";
    echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
    echo "</table>";

    $test_results['machines'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    echo "</div>";

    // ========================================
    // TESTE 3: ARMAZÉM
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>📦 Teste 3: Armazém (Warehouse)</h2>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'warehouse'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM warehouse");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

        echo "<p><strong>Estrutura:</strong> ";
        if ($has_soft_delete) {
            echo "<span class='success'>✓ Soft delete configurado</span></p>";
        } else {
            echo "<span class='error'>✗ Soft delete NÃO configurado</span></p>";
        }

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = FALSE OR is_deleted IS NULL");
        $active = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = TRUE");
        $deleted = $stmt->fetch()['total'];

        echo "<table>";
        echo "<tr><th>Status</th><th>Quantidade</th></tr>";
        echo "<tr><td>Ativos</td><td>$active</td></tr>";
        echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
        echo "</table>";

        $test_results['warehouse'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    } else {
        echo "<p><span class='warning'>⚠ Tabela 'warehouse' não existe</span></p>";
        $test_results['warehouse'] = ['exists' => false];
    }
    echo "</div>";

    // ========================================
    // TESTE 4: GARANTIAS
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>🛡️ Teste 4: Garantias (Warranties)</h2>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'warranties'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM warranties");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

        echo "<p><strong>Estrutura:</strong> ";
        if ($has_soft_delete) {
            echo "<span class='success'>✓ Soft delete configurado</span></p>";
        } else {
            echo "<span class='error'>✗ Soft delete NÃO configurado</span></p>";
        }

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE is_deleted = FALSE OR is_deleted IS NULL");
        $active = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE is_deleted = TRUE");
        $deleted = $stmt->fetch()['total'];

        echo "<table>";
        echo "<tr><th>Status</th><th>Quantidade</th></tr>";
        echo "<tr><td>Ativos</td><td>$active</td></tr>";
        echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
        echo "</table>";

        $test_results['warranties'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    } else {
        echo "<p><span class='warning'>⚠ Tabela 'warranties' não existe</span></p>";
        $test_results['warranties'] = ['exists' => false];
    }
    echo "</div>";

    // ========================================
    // TESTE 5: TEMPLATES DE GARANTIA
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>📋 Teste 5: Templates de Garantia (Warranty Templates)</h2>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_templates'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM warranty_templates");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

        echo "<p><strong>Estrutura:</strong> ";
        if ($has_soft_delete) {
            echo "<span class='success'>✓ Soft delete configurado</span></p>";
        } else {
            echo "<span class='error'>✗ Soft delete NÃO configurado - PRECISA EXECUTAR MIGRATION</span></p>";
            echo "<p class='info'>📄 Execute: migrations/2025-12-03-warranty-templates-soft-delete.sql</p>";
        }

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_templates WHERE is_active = TRUE");
        $active = $stmt->fetch()['total'];

        if ($has_soft_delete) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_templates WHERE is_deleted = TRUE");
            $deleted = $stmt->fetch()['total'];
        } else {
            $deleted = 0;
        }

        echo "<table>";
        echo "<tr><th>Status</th><th>Quantidade</th></tr>";
        echo "<tr><td>Ativos</td><td>$active</td></tr>";
        echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
        echo "</table>";

        $test_results['warranty_templates'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    } else {
        echo "<p><span class='warning'>⚠ Tabela 'warranty_templates' não existe</span></p>";
        $test_results['warranty_templates'] = ['exists' => false];
    }
    echo "</div>";

    // ========================================
    // TESTE 6: FORNECEDORES
    // ========================================
    echo "<div class='test-section'>";
    echo "<h2>🏢 Teste 6: Fornecedores (Suppliers)</h2>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'suppliers'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM suppliers");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $has_soft_delete = in_array('is_deleted', $columns) && in_array('deleted_at', $columns);

        echo "<p><strong>Estrutura:</strong> ";
        if ($has_soft_delete) {
            echo "<span class='success'>✓ Soft delete configurado</span></p>";
        } else {
            echo "<span class='error'>✗ Soft delete NÃO configurado - PRECISA EXECUTAR MIGRATION</span></p>";
            echo "<p class='info'>📄 Execute: migrations/2025-12-03-warranty-templates-soft-delete.sql</p>";
        }

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM suppliers");
        $total = $stmt->fetch()['total'];

        if ($has_soft_delete) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM suppliers WHERE is_deleted = FALSE OR is_deleted IS NULL");
            $active = $stmt->fetch()['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM suppliers WHERE is_deleted = TRUE");
            $deleted = $stmt->fetch()['total'];
        } else {
            $active = $total;
            $deleted = 0;
        }

        echo "<table>";
        echo "<tr><th>Status</th><th>Quantidade</th></tr>";
        echo "<tr><td>Ativos</td><td>$active</td></tr>";
        echo "<tr><td>Deletados</td><td>$deleted</td></tr>";
        echo "</table>";

        $test_results['suppliers'] = ['has_soft_delete' => $has_soft_delete, 'active' => $active, 'deleted' => $deleted];
    } else {
        echo "<p><span class='warning'>⚠ Tabela 'suppliers' não existe</span></p>";
        $test_results['suppliers'] = ['exists' => false];
    }
    echo "</div>";

    // ========================================
    // RESUMO FINAL
    // ========================================
    echo "<div class='test-section' style='background-color: #ecf0f1;'>";
    echo "<h2>📊 Resumo Final</h2>";

    $total_configured = 0;
    $total_needs_config = 0;
    $total_not_exists = 0;

    foreach ($test_results as $table => $result) {
        if (isset($result['exists']) && !$result['exists']) {
            $total_not_exists++;
        } elseif (isset($result['has_soft_delete'])) {
            if ($result['has_soft_delete']) {
                $total_configured++;
            } else {
                $total_needs_config++;
            }
        }
    }

    echo "<table>";
    echo "<tr><th>Status</th><th>Quantidade</th></tr>";
    echo "<tr><td><span class='success'>✓ Soft delete configurado</span></td><td>$total_configured</td></tr>";
    echo "<tr><td><span class='error'>✗ Precisa configurar</span></td><td>$total_needs_config</td></tr>";
    echo "<tr><td><span class='warning'>⚠ Tabela não existe</span></td><td>$total_not_exists</td></tr>";
    echo "</table>";

    if ($total_needs_config > 0) {
        echo "<p class='error'><strong>⚠️ AÇÃO NECESSÁRIA:</strong> Execute as migrations SQL para adicionar soft delete nas tabelas faltantes.</p>";
        echo "<p>Execute no MySQL:</p>";
        echo "<pre style='background: #2c3e50; color: #ecf0f1; padding: 10px;'>";
        echo "mysql -u seu_usuario -p seu_banco < migrations/2025-12-03-warranty-soft-delete.sql\n";
        echo "mysql -u seu_usuario -p seu_banco < migrations/2025-12-03-warranty-templates-soft-delete.sql";
        echo "</pre>";
    } else {
        echo "<p class='success'><strong>✓ Tudo OK!</strong> Todos os setores estão com soft delete configurado.</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='test-section' style='background-color: #ffebee;'>";
    echo "<h2 class='error'>❌ Erro no Teste</h2>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='../dashboard.php'>← Voltar ao Dashboard</a> | <a href='../deleted_items.php'>Ver Itens Deletados</a></p>";
?>
