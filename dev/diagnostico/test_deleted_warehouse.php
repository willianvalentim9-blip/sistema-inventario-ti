<?php
require_once 'config.php';
requireLogin();

echo "<h1>Diagnóstico - Itens Deletados do Armazém</h1>";

echo "<h2>1. Verificando sessão do usuário:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'não definido') . "<br>";
echo "User Role: " . ($_SESSION['user_role'] ?? 'não definido') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'não definido') . "<br>";

$is_admin = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin');
echo "<strong>É administrativo?</strong> " . ($is_admin ? 'SIM' : 'NÃO') . "<br>";

try {
    $pdo = getConnection();

    echo "<h2>2. Verificando se a tabela warehouse existe:</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'warehouse'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        echo "✓ Tabela 'warehouse' existe<br>";
    } else {
        echo "✗ Tabela 'warehouse' NÃO existe<br>";
    }

    echo "<h2>3. Verificando colunas da tabela warehouse:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM warehouse");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach($columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";

    $has_is_deleted = in_array('is_deleted', $columns);
    $has_deleted_at = in_array('deleted_at', $columns);
    echo "<strong>Tem coluna 'is_deleted'?</strong> " . ($has_is_deleted ? 'SIM' : 'NÃO') . "<br>";
    echo "<strong>Tem coluna 'deleted_at'?</strong> " . ($has_deleted_at ? 'SIM' : 'NÃO') . "<br>";

    echo "<h2>4. Contando registros no warehouse:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse");
    $total = $stmt->fetch();
    echo "Total de registros: {$total['total']}<br>";

    if ($has_is_deleted) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = 1");
        $deleted = $stmt->fetch();
        echo "Registros deletados (is_deleted = 1): {$deleted['total']}<br>";

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = TRUE");
        $deleted2 = $stmt->fetch();
        echo "Registros deletados (is_deleted = TRUE): {$deleted2['total']}<br>";

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = 0 OR is_deleted IS NULL");
        $active = $stmt->fetch();
        echo "Registros ativos: {$active['total']}<br>";
    }

    echo "<h2>5. Listando todos os registros do warehouse:</h2>";
    $stmt = $pdo->query("SELECT id, name, category, quantity, is_deleted, deleted_at FROM warehouse LIMIT 10");
    $all_items = $stmt->fetchAll();
    if (empty($all_items)) {
        echo "<p style='color: orange;'>Nenhum registro encontrado no warehouse</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Quantidade</th><th>is_deleted</th><th>deleted_at</th></tr>";
        foreach($all_items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['name']}</td>";
            echo "<td>{$item['category']}</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>" . ($item['is_deleted'] ? 'TRUE' : 'FALSE') . "</td>";
            echo "<td>" . ($item['deleted_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2>6. Testando a query exata do deleted_items.php:</h2>";
    if ($is_admin) {
        $stmt = $pdo->prepare("
            SELECT id, name, category, quantity, deleted_at
            FROM warehouse
            WHERE is_deleted = TRUE
            ORDER BY deleted_at DESC
        ");
        $stmt->execute();
        $deleted_warehouse = $stmt->fetchAll();

        echo "Resultado da query: " . count($deleted_warehouse) . " registros<br>";
        if (!empty($deleted_warehouse)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Quantidade</th><th>deleted_at</th></tr>";
            foreach($deleted_warehouse as $item) {
                echo "<tr>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['name']}</td>";
                echo "<td>{$item['category']}</td>";
                echo "<td>{$item['quantity']}</td>";
                echo "<td>{$item['deleted_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>Query retornou 0 resultados</p>";
        }
    } else {
        echo "<p style='color: red;'>Usuário não é administrativo, query não foi executada</p>";
    }

    echo "<h2>7. Verificando tabela warranties:</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'warranties'");
    $warranties_table = $stmt->fetch();
    if ($warranties_table) {
        echo "✓ Tabela 'warranties' existe<br>";

        $stmt = $pdo->query("SHOW COLUMNS FROM warranties");
        $warranty_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Colunas: " . implode(', ', $warranty_columns) . "<br>";

        $has_warranty_is_deleted = in_array('is_deleted', $warranty_columns);
        echo "<strong>Tem coluna 'is_deleted'?</strong> " . ($has_warranty_is_deleted ? 'SIM' : 'NÃO') . "<br>";

        if ($has_warranty_is_deleted) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE is_deleted = TRUE");
            $deleted_warranties = $stmt->fetch();
            echo "Garantias deletadas: {$deleted_warranties['total']}<br>";
        }
    } else {
        echo "✗ Tabela 'warranties' NÃO existe<br>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>ERRO:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
