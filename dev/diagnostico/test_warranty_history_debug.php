<?php
require_once 'config.php';
requireAdmin();

header('Content-Type: text/html; charset=utf-8');

$pdo = getConnection();

echo "<h2>🔍 DEBUG: Histórico de Garantias</h2>";
echo "<pre style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";

// 1. Verificar se a tabela existe
echo "1️⃣  Verificando tabela warranty_history...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_history'");
    $exists = $stmt->rowCount() > 0;

    if ($exists) {
        echo "   ✅ Tabela existe\n\n";

        // Verificar estrutura
        echo "2️⃣  Estrutura da tabela:\n";
        $stmt = $pdo->query("DESCRIBE warranty_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }

        // Contar registros
        echo "\n3️⃣  Total de registros:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_history");
        $total = $stmt->fetch()['total'];
        echo "   📊 Total: $total registros\n";

        if ($total > 0) {
            echo "\n4️⃣  Últimos 10 registros:\n";
            $stmt = $pdo->query("
                SELECT
                    h.id,
                    h.product_id,
                    h.action_type,
                    h.created_at,
                    p.name as product_name,
                    u.name as user_name
                FROM warranty_history h
                LEFT JOIN products p ON h.product_id = p.id
                LEFT JOIN users u ON h.user_id = u.id
                ORDER BY h.created_at DESC
                LIMIT 10
            ");
            $records = $stmt->fetchAll();

            foreach ($records as $r) {
                echo "\n   ID {$r['id']}:\n";
                echo "      Produto: {$r['product_name']} (ID: {$r['product_id']})\n";
                echo "      Ação: {$r['action_type']}\n";
                echo "      Usuário: {$r['user_name']}\n";
                echo "      Data: {$r['created_at']}\n";
            }
        } else {
            echo "\n   ⚠️  Nenhum registro encontrado!\n";
            echo "   Isso significa que:\n";
            echo "   - A tabela existe, mas está vazia\n";
            echo "   - Nenhuma edição de garantia foi registrada ainda\n";
            echo "   - Ou há um erro silencioso ao salvar\n";
        }

        // Testar função registerWarrantyHistory
        echo "\n\n5️⃣  Testando função registerWarrantyHistory()...\n";

        // Buscar um produto com garantia para testar
        $stmt = $pdo->query("
            SELECT id, name
            FROM products
            WHERE warranty_start_date IS NOT NULL
            LIMIT 1
        ");
        $test_product = $stmt->fetch();

        if ($test_product) {
            echo "   Produto de teste: {$test_product['name']} (ID: {$test_product['id']})\n";

            // Incluir função
            require_once 'includes/warranty_functions.php';

            // Tentar registrar
            $old_data = ['warranty_provider' => 'Teste Antigo', 'warranty_start_date' => '2024-01-01'];
            $new_data = ['warranty_provider' => 'Teste Novo', 'warranty_start_date' => '2024-02-01'];

            $result = registerWarrantyHistory(
                $pdo,
                $test_product['id'],
                'UPDATE',
                $old_data,
                $new_data,
                $_SESSION['user_id']
            );

            if ($result) {
                echo "   ✅ Registro de teste criado com sucesso!\n";

                // Verificar se foi salvo
                $stmt = $pdo->prepare("
                    SELECT * FROM warranty_history
                    WHERE product_id = ?
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$test_product['id']]);
                $last = $stmt->fetch();

                if ($last) {
                    echo "   ✅ Confirmado: Registro está no banco\n";
                    echo "      ID: {$last['id']}\n";
                    echo "      Ação: {$last['action_type']}\n";
                    echo "      Old values: {$last['old_values']}\n";
                    echo "      New values: {$last['new_values']}\n";
                }
            } else {
                echo "   ❌ ERRO: Não foi possível criar registro de teste\n";
                echo "   Verifique os logs de erro do PHP\n";
            }
        } else {
            echo "   ⚠️  Nenhum produto com garantia para testar\n";
        }

        // Verificar logs de erro
        echo "\n\n6️⃣  Verificando erros no PHP:\n";
        $error_log = ini_get('error_log');
        if ($error_log && file_exists($error_log)) {
            echo "   📄 Arquivo de log: $error_log\n";
            $lines = file($error_log);
            $last_errors = array_slice($lines, -10);
            echo "   Últimas 10 linhas:\n";
            foreach ($last_errors as $line) {
                if (stripos($line, 'warranty') !== false || stripos($line, 'history') !== false) {
                    echo "   ⚠️  " . htmlspecialchars($line);
                }
            }
        } else {
            echo "   ℹ️  Log de erros não configurado ou não encontrado\n";
        }

    } else {
        echo "   ❌ Tabela NÃO existe!\n";
        echo "   Execute o SQL de criação novamente\n";
    }

} catch (PDOException $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ DIAGNÓSTICO COMPLETO!\n";
echo "</pre>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #0d6efd; border-radius: 4px;'>";
echo "<h4>🎯 Próximos Passos:</h4>";
echo "<ol>";
echo "<li><strong>Se houver registros:</strong> O sistema está funcionando. Teste editar uma garantia.</li>";
echo "<li><strong>Se não houver registros:</strong> Edite qualquer garantia e volte aqui para verificar se foi salvo.</li>";
echo "<li><strong>Se der erro:</strong> Verifique os logs de erro do Apache/PHP.</li>";
echo "</ol>";
echo "<p><a href='warranties.php' class='btn btn-primary'>Ir para Garantias</a></p>";
echo "</div>";
?>
