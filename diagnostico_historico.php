<?php
/**
 * DIAGNÓSTICO COMPLETO - SISTEMA DE HISTÓRICO DE GARANTIA
 * 
 * Verifica todos os componentes necessários para o modal histórico funcionar
 */

require 'config.php';
require_once 'includes/warranty_functions.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$checks = [
    'files' => [],
    'database' => [],
    'functions' => [],
    'data' => []
];

// ============ 1. VERIFICAR ARQUIVOS ============
$required_files = [
    'includes/warranty_functions.php' => 'Funções de garantia',
    'warranty_history_view.php' => 'Visualizador de histórico',
    'js/custom.js' => 'JavaScript de modal',
    'includes/header.php' => 'Header',
    'includes/footer.php' => 'Footer'
];

foreach ($required_files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $checks['files'][] = [
        'name' => $desc,
        'file' => $file,
        'status' => $exists ? '✅' : '❌',
        'ok' => $exists
    ];
}

// ============ 2. VERIFICAR BANCO DE DADOS ============
try {
    $pdo = getConnection();
    
    // Tabela warranty_history
    $stmt = $pdo->query("SELECT COUNT(*) FROM warranty_history");
    $count = $stmt->fetchColumn();
    $checks['database'][] = [
        'name' => 'Tabela warranty_history',
        'status' => '✅',
        'message' => "Existem $count registros",
        'ok' => true
    ];
    
    // Campos corretos
    $stmt = $pdo->query("DESCRIBE warranty_history");
    $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_fields = ['id', 'product_id', 'action_type', 'user_id', 'old_values', 'new_values', 'change_description', 'created_at'];
    
    foreach ($required_fields as $field) {
        $has_field = in_array($field, $fields);
        $checks['database'][] = [
            'name' => "Campo: <code>$field</code>",
            'status' => $has_field ? '✅' : '❌',
            'ok' => $has_field
        ];
    }
    
    // Conexão com usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    $checks['database'][] = [
        'name' => 'Tabela users (para JOIN)',
        'status' => '✅',
        'message' => "$user_count usuários registrados",
        'ok' => true
    ];
    
} catch (Exception $e) {
    $checks['database'][] = [
        'name' => 'Verificação de banco',
        'status' => '❌',
        'message' => $e->getMessage(),
        'ok' => false
    ];
}

// ============ 3. VERIFICAR FUNÇÕES ============
if (function_exists('getWarrantyHistory')) {
    $checks['functions'][] = [
        'name' => 'getWarrantyHistory()',
        'status' => '✅',
        'ok' => true
    ];
}

if (function_exists('registerWarrantyHistory')) {
    $checks['functions'][] = [
        'name' => 'registerWarrantyHistory()',
        'status' => '✅',
        'ok' => true
    ];
}

if (function_exists('getConnection')) {
    $checks['functions'][] = [
        'name' => 'getConnection()',
        'status' => '✅',
        'ok' => true
    ];
}

// ============ 4. VERIFICAR DADOS ============
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, COUNT(wh.id) as history_count
        FROM products p
        LEFT JOIN warranty_history wh ON p.id = wh.product_id
        WHERE p.has_warranty = 1
        GROUP BY p.id
        LIMIT 5
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        $checks['data'][] = [
            'name' => 'Produtos com garantia encontrados',
            'status' => '✅',
            'count' => count($products),
            'ok' => true
        ];
        
        foreach ($products as $product) {
            $checks['data'][] = [
                'name' => htmlspecialchars($product['name']),
                'product_id' => $product['id'],
                'history_count' => $product['history_count'],
                'status' => $product['history_count'] > 0 ? '✅' : '⚠️'
            ];
        }
    } else {
        $checks['data'][] = [
            'name' => 'Produtos com garantia',
            'status' => '⚠️',
            'message' => 'Nenhum produto com garantia encontrado',
            'ok' => false
        ];
    }
} catch (Exception $e) {
    $checks['data'][] = [
        'name' => 'Verificação de dados',
        'status' => '❌',
        'message' => $e->getMessage(),
        'ok' => false
    ];
}

// Contar problemas
$total_checks = array_sum(array_map(function($arr) { return count($arr); }, $checks));
$ok_count = 0;
foreach ($checks as $category) {
    foreach ($category as $check) {
        if (isset($check['ok']) && $check['ok']) $ok_count++;
    }
}

?>
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_values JSON,
                new_values JSON,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product (product_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Tabela criada com sucesso!<br>";
    }

    // 2. Verificar estrutura da tabela
    echo "<h4>2. Estrutura da tabela warranty_history:</h4>";
    $columns = $pdo->query("DESCRIBE warranty_history")->fetchAll();
    echo "<pre>";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Null']}\n";
    }
    echo "</pre>";

    // 3. Contar registros
    echo "<h4>3. Registros no histórico:</h4>";
    $count = $pdo->query("SELECT COUNT(*) as total FROM warranty_history")->fetch();
    echo "Total de registros: <strong>{$count['total']}</strong><br>";

    // 4. Listar últimos registros
    if ($count['total'] > 0) {
        echo "<h4>4. Últimos 10 registros:</h4>";
        echo "<pre>";
        $recent = $pdo->query("
            SELECT h.*, u.name as user_name 
            FROM warranty_history h 
            LEFT JOIN users u ON h.user_id = u.id
            ORDER BY h.created_at DESC 
            LIMIT 10
        ")->fetchAll();
        
        foreach ($recent as $entry) {
            echo "ID: {$entry['id']}\n";
            echo "Produto: {$entry['product_id']}\n";
            echo "Ação: {$entry['action']}\n";
            echo "Usuário: {$entry['user_name']} (ID: {$entry['user_id']})\n";
            echo "Data: {$entry['created_at']}\n";
            echo "---\n";
        }
        echo "</pre>";
    }

    // 5. Testar função
    echo "<h4>5. Testando função registerWarrantyHistory():</h4>";
    $test_product_id = 1;
    $test_old = ['warranty_start_date' => '2024-01-01', 'warranty_period_value' => 12];
    $test_new = ['warranty_start_date' => '2024-01-01', 'warranty_period_value' => 24];
    
    $result = registerWarrantyHistory(
        $pdo,
        $test_product_id,
        'TEST',
        $test_old,
        $test_new,
        $_SESSION['user_id'] ?? 1
    );
    
    if ($result) {
        echo "✅ Teste inseriu registro com sucesso!<br>";
        $latest = $pdo->query("SELECT * FROM warranty_history ORDER BY id DESC LIMIT 1")->fetch();
        echo "Registro criado: ID {$latest['id']} às {$latest['created_at']}<br>";
    } else {
        echo "❌ Erro ao inserir teste<br>";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>

<hr>
<a href="warranties.php" class="btn btn-primary">← Voltar para Garantias</a>
