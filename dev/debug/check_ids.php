<?php
/**
 * Script: Verificar IDs disponíveis em machines, products e warehouse
 */

require 'config.php';
requireLogin();

$pdo = getConnection();

echo "<h1>🔍 Verificar IDs Disponíveis</h1>";

// Máquinas
echo "<h2>Máquinas (ready_machines)</h2>";
try {
    $stmt = $pdo->query("SELECT id, name FROM ready_machines LIMIT 10");
    $machines = $stmt->fetchAll();
    
    if (!empty($machines)) {
        echo "<table class='table table-striped'>";
        echo "<tr><th>ID</th><th>Nome</th></tr>";
        foreach ($machines as $m) {
            echo "<tr><td>" . $m['id'] . "</td><td>" . htmlspecialchars($m['name']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='alert alert-warning'>Nenhuma máquina encontrada</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
}

// Produtos
echo "<h2>Produtos (products)</h2>";
try {
    $stmt = $pdo->query("SELECT id, name FROM products LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (!empty($products)) {
        echo "<table class='table table-striped'>";
        echo "<tr><th>ID</th><th>Nome</th></tr>";
        foreach ($products as $p) {
            echo "<tr><td>" . $p['id'] . "</td><td>" . htmlspecialchars($p['name']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='alert alert-warning'>Nenhum produto encontrado</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
}

// Warehouse
echo "<h2>Warehouse (warehouse)</h2>";
try {
    $stmt = $pdo->query("SELECT id, name FROM warehouse WHERE is_deleted = FALSE OR is_deleted IS NULL LIMIT 10");
    $warehouse = $stmt->fetchAll();
    
    if (!empty($warehouse)) {
        echo "<table class='table table-striped'>";
        echo "<tr><th>ID</th><th>Nome</th></tr>";
        foreach ($warehouse as $w) {
            echo "<tr><td>" . $w['id'] . "</td><td>" . htmlspecialchars($w['name']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='alert alert-warning'>Nenhum item de warehouse encontrado</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<a href='javascript:history.back()' class='btn btn-secondary'>Voltar</a>";

?>
