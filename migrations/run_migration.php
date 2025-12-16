<?php
/**
 * SCRIPT PARA EXECUTAR A MIGRATION DE SOFT DELETE
 * Execute este arquivo para corrigir o problema
 */

require_once 'config.php';

echo "<h1>Executando Migration: Soft Delete + Low Stock</h1>\n";
echo "<pre>\n";

try {
    $pdo = getConnection();

    // Lê o arquivo de migration
    $migration_file = __DIR__ . '/migrations/2025-11-28-soft-delete-and-low-stock.sql';

    if (!file_exists($migration_file)) {
        die("❌ ERRO: Arquivo de migration não encontrado em: $migration_file\n");
    }

    echo "📄 Lendo arquivo de migration...\n";
    $sql = file_get_contents($migration_file);

    // Remove comentários de linha única
    $sql = preg_replace('/^--.*$/m', '', $sql);

    // Separa as queries por ponto e vírgula (exceto dentro de DELIMITER)
    echo "⚙️  Processando comandos SQL...\n\n";

    // Processa comandos SQL manualmente para lidar com DELIMITER
    $lines = explode("\n", $sql);
    $current_query = '';
    $delimiter = ';';
    $in_delimiter_block = false;
    $queries_executed = 0;
    $errors = 0;

    foreach ($lines as $line) {
        $line = trim($line);

        // Pula linhas vazias e comentários
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }

        // Detecta mudança de delimiter
        if (stripos($line, 'DELIMITER') === 0) {
            if (!$in_delimiter_block) {
                $delimiter = trim(substr($line, 9));
                $in_delimiter_block = true;
                echo "   🔄 Mudando delimiter para: $delimiter\n";
            } else {
                $delimiter = ';';
                $in_delimiter_block = false;
                echo "   🔄 Restaurando delimiter para: ;\n";

                // Executa o bloco acumulado (stored procedure, trigger, etc)
                if (!empty($current_query)) {
                    try {
                        $pdo->exec($current_query);
                        $queries_executed++;
                        echo "   ✅ Bloco executado com sucesso\n\n";
                    } catch (PDOException $e) {
                        $errors++;
                        echo "   ⚠️  Erro ao executar bloco: " . $e->getMessage() . "\n\n";
                    }
                    $current_query = '';
                }
            }
            continue;
        }

        // Acumula a query
        $current_query .= $line . "\n";

        // Se encontrou o delimiter atual, executa a query
        if (substr($line, -strlen($delimiter)) === $delimiter && $delimiter === ';') {
            // Remove o delimiter
            $current_query = substr($current_query, 0, -strlen($delimiter));
            $current_query = trim($current_query);

            if (!empty($current_query)) {
                try {
                    $pdo->exec($current_query);
                    $queries_executed++;

                    // Mostra apenas os comandos mais importantes
                    $first_words = strtoupper(substr($current_query, 0, 50));
                    if (strpos($first_words, 'ALTER TABLE') !== false ||
                        strpos($first_words, 'CREATE TABLE') !== false ||
                        strpos($first_words, 'CREATE OR REPLACE VIEW') !== false) {
                        echo "   ✅ " . substr($current_query, 0, 80) . "...\n";
                    }

                } catch (PDOException $e) {
                    $errors++;
                    $error_msg = $e->getMessage();

                    // Ignora erros de "já existe" ou "duplicado"
                    if (strpos($error_msg, 'Duplicate column') !== false ||
                        strpos($error_msg, 'already exists') !== false ||
                        strpos($error_msg, 'Cannot add foreign key') !== false) {
                        echo "   ⚠️  Aviso (ignorado): " . substr($error_msg, 0, 100) . "\n";
                    } else {
                        echo "   ❌ Erro: $error_msg\n";
                        echo "   Query: " . substr($current_query, 0, 100) . "...\n\n";
                    }
                }
            }

            $current_query = '';
        }
    }

    // Executa qualquer query restante
    if (!empty($current_query)) {
        try {
            $pdo->exec($current_query);
            $queries_executed++;
        } catch (PDOException $e) {
            $errors++;
            echo "   ❌ Erro na última query: " . $e->getMessage() . "\n";
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RESUMO:\n";
    echo "   ✅ Comandos executados: $queries_executed\n";
    echo "   " . ($errors > 0 ? "⚠️" : "✅") . " Erros/Avisos: $errors\n";

    // Verifica se as colunas foram criadas
    echo "\n🔍 VERIFICANDO RESULTADO:\n";

    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $has_is_deleted = in_array('is_deleted', $columns);
    $has_deleted_at = in_array('deleted_at', $columns);

    echo "   Tabela products:\n";
    echo "   " . ($has_is_deleted ? "✅" : "❌") . " Coluna is_deleted\n";
    echo "   " . ($has_deleted_at ? "✅" : "❌") . " Coluna deleted_at\n\n";

    $stmt = $pdo->query("DESCRIBE ready_machines");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $has_is_deleted_m = in_array('is_deleted', $columns);
    $has_deleted_at_m = in_array('deleted_at', $columns);

    echo "   Tabela ready_machines:\n";
    echo "   " . ($has_is_deleted_m ? "✅" : "❌") . " Coluna is_deleted\n";
    echo "   " . ($has_deleted_at_m ? "✅" : "❌") . " Coluna deleted_at\n\n";

    if ($has_is_deleted && $has_deleted_at && $has_is_deleted_m && $has_deleted_at_m) {
        echo "✅ MIGRATION EXECUTADA COM SUCESSO!\n\n";
        echo "📋 PRÓXIMOS PASSOS:\n";
        echo "   1. Acesse a página de produtos: <a href='products.php'>products.php</a>\n";
        echo "   2. Acesse a página de máquinas: <a href='ready_machines.php'>ready_machines.php</a>\n";
        echo "   3. Agora o sistema deve carregar normalmente!\n";
    } else {
        echo "⚠️  ATENÇÃO: Algumas colunas não foram criadas. Verifique os erros acima.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
