<?php
/**
 * DIAGNÓSTICO E CORREÇÃO: Histórico de Garantias
 * Execute este arquivo para verificar e corrigir o histórico
 */

require_once 'config.php';
requireAdmin();

$pdo = getConnection();

echo "<h2>🔍 Diagnóstico do Histórico de Garantias</h2>";
echo "<pre>";

// 1. Verificar se a tabela existe
echo "\n1️⃣  Verificando tabela warranty_history...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_history'");
    $table_exists = $stmt->rowCount() > 0;

    if ($table_exists) {
        echo "   ✅ Tabela warranty_history existe\n";

        // Verificar estrutura
        $stmt = $pdo->query("DESCRIBE warranty_history");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        echo "   📋 Colunas: " . implode(', ', $columns) . "\n";

        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_history");
        $total = $stmt->fetch()['total'];
        echo "   📊 Total de registros: $total\n";

        if ($total > 0) {
            // Mostrar últimos registros
            $stmt = $pdo->query("
                SELECT
                    h.id,
                    h.product_id,
                    h.action_type,
                    h.created_at,
                    p.name as product_name
                FROM warranty_history h
                LEFT JOIN products p ON h.product_id = p.id
                ORDER BY h.created_at DESC
                LIMIT 5
            ");
            $recent = $stmt->fetchAll();

            echo "\n   📝 Últimos 5 registros:\n";
            foreach ($recent as $r) {
                echo "      - ID {$r['id']}: Produto '{$r['product_name']}' ({$r['action_type']}) em {$r['created_at']}\n";
            }
        }

    } else {
        echo "   ❌ Tabela warranty_history NÃO existe!\n";
        echo "   🔧 Criando tabela...\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `warranty_history` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT NOT NULL,
                `action_type` VARCHAR(50) NOT NULL,
                `old_values` JSON DEFAULT NULL,
                `new_values` JSON DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `change_description` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_product_id` (`product_id`),
                INDEX `idx_action_type` (`action_type`),
                INDEX `idx_created_at` (`created_at`),
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "   ✅ Tabela criada com sucesso!\n";
    }

} catch (PDOException $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 2. Verificar se as garantias existem
echo "\n2️⃣  Verificando produtos com garantia...\n";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM products
        WHERE warranty_start_date IS NOT NULL
    ");
    $with_warranty = $stmt->fetch()['total'];

    echo "   📦 Produtos com garantia: $with_warranty\n";

    if ($with_warranty > 0) {
        // Listar alguns produtos
        $stmt = $pdo->query("
            SELECT id, name, warranty_start_date, warranty_end_date
            FROM products
            WHERE warranty_start_date IS NOT NULL
            ORDER BY warranty_start_date DESC
            LIMIT 5
        ");
        $products = $stmt->fetchAll();

        echo "\n   �� Alguns produtos com garantia:\n";
        foreach ($products as $p) {
            echo "      - ID {$p['id']}: {$p['name']} (início: {$p['warranty_start_date']})\n";
        }
    }

} catch (PDOException $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 3. Teste de registro de histórico
echo "\n3️⃣  Testando função de registro...\n";
try {
    // Pegar primeiro produto com garantia
    $stmt = $pdo->query("
        SELECT id FROM products
        WHERE warranty_start_date IS NOT NULL
        LIMIT 1
    ");
    $test_product = $stmt->fetch();

    if ($test_product) {
        $test_id = $test_product['id'];

        // Incluir função se não estiver carregada
        if (!function_exists('registerWarrantyHistory')) {
            require_once 'includes/warranty_functions.php';
        }

        // Testar registro
        $result = registerWarrantyHistory(
            $pdo,
            $test_id,
            'UPDATE',
            ['test_old' => 'valor_antigo'],
            ['test_new' => 'valor_novo'],
            $_SESSION['user_id']
        );

        if ($result) {
            echo "   ✅ Registro de teste criado com sucesso!\n";
            echo "   📋 Produto ID: $test_id\n";

            // Verificar se foi gravado
            $stmt = $pdo->prepare("
                SELECT * FROM warranty_history
                WHERE product_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$test_id]);
            $last_record = $stmt->fetch();

            if ($last_record) {
                echo "   ✅ Confirmado: Registro gravado no banco\n";
                echo "      - ID: {$last_record['id']}\n";
                echo "      - Ação: {$last_record['action_type']}\n";
                echo "      - Data: {$last_record['created_at']}\n";
            }
        } else {
            echo "   ❌ Falha ao criar registro de teste\n";
        }
    } else {
        echo "   ⚠️  Nenhum produto com garantia para testar\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

// 4. Verificar integrações
echo "\n4️⃣  Verificando integrações...\n";
try {
    // Verificar se edit_warranty.php chama registerWarrantyHistory
    $edit_file = file_get_contents(__DIR__ . '/edit_warranty.php');
    if (strpos($edit_file, 'registerWarrantyHistory') !== false) {
        echo "   ✅ edit_warranty.php usa registerWarrantyHistory\n";
    } else {
        echo "   ⚠️  edit_warranty.php NÃO chama registerWarrantyHistory\n";
        echo "      → Precisa ser corrigido manualmente\n";
    }

} catch (Exception $e) {
    echo "   ⚠️  Não foi possível verificar integrações\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ DIAGNÓSTICO COMPLETO!\n";
echo "\n💡 RECOMENDAÇÕES:\n";
echo "   1. Verifique se warranty_history tem registros\n";
echo "   2. Teste editar uma garantia e veja se aparece no histórico\n";
echo "   3. Se não funcionar, verifique os logs de erro do PHP\n";
echo "\n🔗 Links úteis:\n";
echo "   - Ver produtos com garantia: <a href='warranties.php'>warranties.php</a>\n";
echo "   - Ver histórico de um produto: <a href='warranty_history_view.php?id=1'>warranty_history_view.php?id=1</a>\n";

echo "</pre>";
?>

<style>
pre {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.6;
}
</style>
