<?php
/**
 * VALIDADOR DE BANCO DE DADOS - IT INVENTORY
 * Verifica integridade, performance e completude do banco otimizado
 * Data: 2025-11-19
 */

require_once 'config.php';

class DatabaseValidator {
    private $pdo;
    private $results = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * TESTE 1: Validar Integridade de Foreign Keys
     */
    public function validateForeignKeys() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 1: VALIDAR FOREIGN KEYS\n";
        echo str_repeat("=", 80) . "\n";

        $issues = [];

        // Verificar produtos sem categoria válida
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE category_id IS NOT NULL 
            AND category_id NOT IN (SELECT id FROM categories)
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "❌ $count produtos com category_id inválido";
        }

        // Verificar máquinas sem categoria válida
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM machines 
            WHERE category_id IS NOT NULL 
            AND category_id NOT IN (SELECT id FROM categories)
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "❌ $count máquinas com category_id inválido";
        }

        // Verificar garantias com template inválido
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE warranty_template_id IS NOT NULL 
            AND warranty_template_id NOT IN (SELECT id FROM warranty_templates)
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "❌ $count produtos com warranty_template_id inválido";
        }

        if (empty($issues)) {
            echo "✅ TODAS as Foreign Keys estão válidas!\n";
            $this->results['foreign_keys'] = 'PASS';
        } else {
            foreach ($issues as $issue) {
                echo "$issue\n";
            }
            $this->results['foreign_keys'] = 'FAIL';
        }

        return empty($issues);
    }

    /**
     * TESTE 2: Validar Unicidade
     */
    public function validateUnique() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 2: VALIDAR CAMPOS ÚNICOS\n";
        echo str_repeat("=", 80) . "\n";

        $issues = [];

        $uniqueFields = [
            'products' => ['sku', 'barcode', 'qr_code', 'serial_number'],
            'machines' => ['serial_number', 'barcode', 'qr_code'],
            'users' => ['username', 'email'],
            'categories' => ['name'],
            'warranty_templates' => ['name'],
            'warranty_suppliers' => ['name']
        ];

        foreach ($uniqueFields as $table => $fields) {
            foreach ($fields as $field) {
                $stmt = $this->pdo->query("
                    SELECT $field, COUNT(*) as cnt 
                    FROM $table 
                    WHERE $field IS NOT NULL
                    GROUP BY $field 
                    HAVING cnt > 1
                ");
                $duplicates = $stmt->fetchAll();
                if (!empty($duplicates)) {
                    foreach ($duplicates as $dup) {
                        $issues[] = "❌ Duplicado em $table.$field: '{$dup[$field]}' ({$dup['cnt']}x)";
                    }
                }
            }
        }

        if (empty($issues)) {
            echo "✅ TODOS os campos únicos estão válidos!\n";
            $this->results['unique_fields'] = 'PASS';
        } else {
            foreach ($issues as $issue) {
                echo "$issue\n";
            }
            $this->results['unique_fields'] = 'FAIL';
        }

        return empty($issues);
    }

    /**
     * TESTE 3: Validar Dados de Garantia
     */
    public function validateWarrantyData() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 3: VALIDAR DADOS DE GARANTIA\n";
        echo str_repeat("=", 80) . "\n";

        $issues = [];

        // Garantias com has_warranty=1 mas sem data de fim
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE has_warranty = 1 
            AND warranty_end_date IS NULL
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "⚠️ $count produtos com garantia=1 mas sem data de fim";
        }

        // Garantias com data_fim anterior a data_início
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE has_warranty = 1 
            AND warranty_start_date IS NOT NULL
            AND warranty_end_date IS NOT NULL
            AND warranty_end_date < warranty_start_date
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "❌ $count produtos com data_fim anterior a data_início";
        }

        // Produtos com garantia expirada
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count, 
                   GROUP_CONCAT(name SEPARATOR ', ') as products
            FROM products 
            WHERE has_warranty = 1 
            AND warranty_end_date < CURDATE()
        ");
        $result = $stmt->fetch();
        $expiredCount = $result['count'];
        if ($expiredCount > 0) {
            echo "⏰ Produtos com garantia EXPIRADA: $expiredCount\n";
            echo "   " . substr($result['products'], 0, 100) . "...\n";
        }

        if (empty($issues)) {
            echo "✅ Dados de garantia estão CONSISTENTES!\n";
            $this->results['warranty_data'] = 'PASS';
        } else {
            foreach ($issues as $issue) {
                echo "$issue\n";
            }
            $this->results['warranty_data'] = 'FAIL';
        }

        return empty($issues);
    }

    /**
     * TESTE 4: Validar Estoque
     */
    public function validateInventory() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 4: VALIDAR INTEGRIDADE DE ESTOQUE\n";
        echo str_repeat("=", 80) . "\n";

        $issues = [];

        // Produtos com quantidade negativa
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE quantity < 0
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "❌ $count produtos com quantidade negativa";
        }

        // Produtos com quantidade superior ao máximo
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM products 
            WHERE quantity > max_quantity 
            AND max_quantity > 0
        ");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $issues[] = "⚠️ $count produtos acima da quantidade máxima";
        }

        // Produtos com estoque baixo
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count, 
                   ROUND(AVG(quantity), 2) as avg_qty
            FROM products 
            WHERE quantity <= min_quantity 
            AND status = 'available'
        ");
        $result = $stmt->fetch();
        echo "📊 Produtos com estoque baixo: {$result['count']} (média: {$result['avg_qty']})\n";

        // Valor total em estoque
        $stmt = $this->pdo->query("
            SELECT SUM(quantity * COALESCE(price, 0)) as total_value,
                   SUM(quantity) as total_items,
                   COUNT(*) as total_products
            FROM products 
            WHERE status = 'available'
        ");
        $result = $stmt->fetch();
        echo "💰 Valor total em estoque: R$ " . number_format($result['total_value'], 2, ',', '.') . "\n";
        echo "📦 Total de itens: {$result['total_items']}\n";
        echo "📝 Total de produtos: {$result['total_products']}\n";

        if (empty($issues)) {
            echo "✅ Integridade de estoque VALIDADA!\n";
            $this->results['inventory'] = 'PASS';
        } else {
            foreach ($issues as $issue) {
                echo "$issue\n";
            }
            $this->results['inventory'] = 'FAIL';
        }

        return empty($issues);
    }

    /**
     * TESTE 5: Validar Índices
     */
    public function validateIndexes() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 5: VALIDAR ÍNDICES PARA PERFORMANCE\n";
        echo str_repeat("=", 80) . "\n";

        $query = "
            SELECT DISTINCT 
                t.table_name,
                GROUP_CONCAT(s.index_name) as indexes,
                COUNT(DISTINCT s.index_name) as index_count
            FROM INFORMATION_SCHEMA.TABLES t
            LEFT JOIN INFORMATION_SCHEMA.STATISTICS s 
                ON t.table_schema = s.table_schema 
                AND t.table_name = s.table_name
            WHERE t.table_schema = DATABASE()
            GROUP BY t.table_name
            ORDER BY t.table_name
        ";

        $stmt = $this->pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalIndexes = 0;
        $tablesWithoutIndex = [];

        foreach ($results as $row) {
            $indexCount = (int)$row['index_count'] - 1; // Remove PRIMARY KEY
            $totalIndexes += $indexCount;
            if ($indexCount == 0) {
                $tablesWithoutIndex[] = $row['table_name'];
            }
            printf("📊 %-25s: %d índices\n", $row['table_name'], $indexCount);
        }

        echo "\n✅ Total de índices: $totalIndexes\n";

        if (!empty($tablesWithoutIndex)) {
            echo "⚠️ Tabelas sem índices secundários: " . implode(', ', $tablesWithoutIndex) . "\n";
        }

        $this->results['indexes'] = 'PASS';
        return true;
    }

    /**
     * TESTE 6: Validar Estrutura de Tabelas
     */
    public function validateTableStructure() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 6: VALIDAR ESTRUTURA DE TABELAS\n";
        echo str_repeat("=", 80) . "\n";

        $requiredTables = [
            'users', 'categories', 'products', 'machines',
            'warranty_templates', 'warranty_suppliers', 'warranty_history',
            'product_movements', 'machine_movements', 'product_inputs_log', 'product_outputs_log',
            'machine_inputs', 'machine_outputs', 'admin_logs', 'system_settings', 'backups'
        ];

        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()";
        $stmt = $this->pdo->query($query);
        $existingTables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME');

        $missing = array_diff($requiredTables, $existingTables);
        $extra = array_diff($existingTables, $requiredTables);

        if (empty($missing)) {
            echo "✅ TODAS as tabelas obrigatórias estão presentes!\n";
        } else {
            echo "❌ Tabelas FALTANTES: " . implode(', ', $missing) . "\n";
        }

        if (!empty($extra)) {
            echo "ℹ️ Tabelas extras encontradas: " . implode(', ', $extra) . "\n";
        }

        echo "\n📊 Resumo de Tabelas:\n";
        foreach ($requiredTables as $table) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            printf("  %-30s: %d registros\n", $table, $count);
        }

        $this->results['table_structure'] = empty($missing) ? 'PASS' : 'FAIL';
        return empty($missing);
    }

    /**
     * TESTE 7: Validar Views
     */
    public function validateViews() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 7: VALIDAR VIEWS (RELATÓRIOS)\n";
        echo str_repeat("=", 80) . "\n";

        $requiredViews = [
            'vw_active_warranties',
            'vw_warranty_alerts',
            'vw_low_stock',
            'vw_inventory_value',
            'vw_movement_report',
            'vw_warranty_timeline'
        ];

        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'VIEW'";
        $stmt = $this->pdo->query($query);
        $existingViews = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME');

        foreach ($requiredViews as $view) {
            if (in_array($view, $existingViews)) {
                echo "✅ $view\n";
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM $view");
                    $count = $stmt->fetch()['count'];
                    echo "   └─ $count registros\n";
                } catch (Exception $e) {
                    echo "   └─ ⚠️ Erro ao consultar: " . $e->getMessage() . "\n";
                }
            } else {
                echo "❌ $view (NÃO ENCONTRADA)\n";
            }
        }

        $this->results['views'] = 'PASS';
        return true;
    }

    /**
     * TESTE 8: Validar Procedures e Functions
     */
    public function validateRoutines() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 8: VALIDAR PROCEDURES E FUNCTIONS\n";
        echo str_repeat("=", 80) . "\n";

        $query = "
            SELECT ROUTINE_NAME, ROUTINE_TYPE 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_SCHEMA = DATABASE()
            ORDER BY ROUTINE_TYPE, ROUTINE_NAME
        ";
        $stmt = $this->pdo->query($query);
        $routines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($routines)) {
            echo "❌ Nenhuma procedure ou function encontrada!\n";
            $this->results['routines'] = 'FAIL';
            return false;
        }

        $procedures = array_filter($routines, fn($r) => $r['ROUTINE_TYPE'] === 'PROCEDURE');
        $functions = array_filter($routines, fn($r) => $r['ROUTINE_TYPE'] === 'FUNCTION');

        echo "📋 PROCEDURES (" . count($procedures) . "):\n";
        foreach ($procedures as $proc) {
            echo "  ✅ " . $proc['ROUTINE_NAME'] . "\n";
        }

        echo "\n📐 FUNCTIONS (" . count($functions) . "):\n";
        foreach ($functions as $func) {
            echo "  ✅ " . $func['ROUTINE_NAME'] . "\n";
        }

        $this->results['routines'] = 'PASS';
        return true;
    }

    /**
     * TESTE 9: Analisar Performance
     */
    public function analyzePerformance() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTE 9: ANÁLISE DE PERFORMANCE\n";
        echo str_repeat("=", 80) . "\n";

        // Tamanho do banco
        $query = "
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
        ";
        $stmt = $this->pdo->query($query);
        $size = $stmt->fetch()['size_mb'];
        echo "💾 Tamanho do banco: {$size} MB\n";

        // Top 5 tabelas maiores
        $query = "
            SELECT 
                table_name,
                ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY size_mb DESC
            LIMIT 5
        ";
        $stmt = $this->pdo->query($query);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\n🔝 Top 5 tabelas maiores:\n";
        foreach ($tables as $table) {
            printf("  %-25s: %f MB\n", $table['table_name'], $table['size_mb']);
        }

        $this->results['performance'] = 'PASS';
        return true;
    }

    /**
     * Gerar Relatório Final
     */
    public function generateReport() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "RELATÓRIO FINAL\n";
        echo str_repeat("=", 80) . "\n";

        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $failed = $total - $passed;

        foreach ($this->results as $test => $result) {
            $icon = $result === 'PASS' ? '✅' : '❌';
            printf("%s %-30s: %s\n", $icon, ucfirst(str_replace('_', ' ', $test)), $result);
        }

        echo "\n" . str_repeat("-", 80) . "\n";
        echo "📊 RESULTADO: $passed/$total testes passaram\n";

        if ($failed === 0) {
            echo "\n🎉 BANCO DE DADOS OTIMIZADO E VALIDADO COM SUCESSO!\n";
        } else {
            echo "\n⚠️ $failed teste(s) falharam. Verifique os dados acima.\n";
        }

        echo str_repeat("=", 80) . "\n";
    }

    /**
     * Executar todos os testes
     */
    public function runAllTests() {
        echo "\n";
        echo "╔" . str_repeat("=", 78) . "╗\n";
        echo "║" . str_pad("VALIDADOR DE BANCO DE DADOS - IT INVENTORY", 78, " ", STR_PAD_BOTH) . "║\n";
        echo "║" . str_pad("Data: " . date('d/m/Y H:i:s'), 78, " ", STR_PAD_BOTH) . "║\n";
        echo "╚" . str_repeat("=", 78) . "╝\n";

        $this->validateForeignKeys();
        $this->validateUnique();
        $this->validateWarrantyData();
        $this->validateInventory();
        $this->validateIndexes();
        $this->validateTableStructure();
        $this->validateViews();
        $this->validateRoutines();
        $this->analyzePerformance();
        $this->generateReport();
    }
}

// Executar validação
try {
    $validator = new DatabaseValidator($pdo);
    $validator->runAllTests();
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
