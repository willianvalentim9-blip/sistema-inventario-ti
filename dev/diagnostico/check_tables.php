<?php
require_once 'config.php';

try {
    $pdo = getConnection();

    echo "<h2>Tabelas no banco de dados:</h2>";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Verificar colunas da tabela warehouse
    echo "<h2>Colunas da tabela 'warehouse':</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM warehouse");
    $columns = $stmt->fetchAll();
    echo "<ul>";
    foreach($columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";

    // Verificar se há registros deletados no warehouse
    echo "<h2>Registros no warehouse:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse");
    $total = $stmt->fetch();
    echo "Total: {$total['total']}<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE is_deleted = TRUE");
    $deleted = $stmt->fetch();
    echo "Deletados: {$deleted['total']}<br>";

    // Verificar colunas da tabela warranties
    echo "<h2>Colunas da tabela 'warranties':</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM warranties");
    $columns = $stmt->fetchAll();
    echo "<ul>";
    foreach($columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";

    // Verificar se há registros deletados em warranties
    echo "<h2>Registros em warranties:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties");
    $total = $stmt->fetch();
    echo "Total: {$total['total']}<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE is_deleted = TRUE");
    $deleted = $stmt->fetch();
    echo "Deletados: {$deleted['total']}<br>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
