<?php
require_once 'config.php';

echo "<h2>Estrutura das Tabelas de Garantia</h2>";

try {
    $pdo = getConnection();

    // Verificar warranty_templates
    echo "<h3>Tabela: warranty_templates</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM warranty_templates");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    $has_is_deleted = false;
    foreach($columns as $col) {
        if ($col['Field'] === 'is_deleted') $has_is_deleted = true;
    }
    echo "<p><strong>Tem soft delete?</strong> " . ($has_is_deleted ? 'SIM' : 'NÃO') . "</p>";

    // Verificar warranty_suppliers (se existir)
    echo "<h3>Tabela: warranty_suppliers</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_suppliers'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM warranty_suppliers");
        $columns = $stmt->fetchAll();
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
        foreach($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        $has_is_deleted = false;
        foreach($columns as $col) {
            if ($col['Field'] === 'is_deleted') $has_is_deleted = true;
        }
        echo "<p><strong>Tem soft delete?</strong> " . ($has_is_deleted ? 'SIM' : 'NÃO') . "</p>";
    } else {
        echo "<p style='color: red;'>Tabela não existe</p>";
    }

    // Verificar suppliers
    echo "<h3>Tabela: suppliers</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'suppliers'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM suppliers");
        $columns = $stmt->fetchAll();
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
        foreach($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        $has_is_deleted = false;
        foreach($columns as $col) {
            if ($col['Field'] === 'is_deleted') $has_is_deleted = true;
        }
        echo "<p><strong>Tem soft delete?</strong> " . ($has_is_deleted ? 'SIM' : 'NÃO') . "</p>";
    } else {
        echo "<p style='color: red;'>Tabela não existe</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
