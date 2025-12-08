<?php
require_once 'config.php';
requireLogin();

echo "<h1>Teste - Criar item deletado no Armazém</h1>";

try {
    $pdo = getConnection();

    // Verificar se já existe algum item no warehouse
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse");
    $total = $stmt->fetch();

    echo "<p>Total de itens no warehouse: {$total['total']}</p>";

    if ($total['total'] == 0) {
        echo "<h2>Criando item de teste...</h2>";

        // Criar um item de teste
        $stmt = $pdo->prepare("
            INSERT INTO warehouse (name, category, quantity, status, created_by, created_at)
            VALUES ('Item de Teste', 'Servidor', 10, 'available', ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $new_id = $pdo->lastInsertId();

        echo "<p>✓ Item criado com ID: $new_id</p>";

        // Deletar (soft delete) o item
        $stmt = $pdo->prepare("
            UPDATE warehouse
            SET is_deleted = TRUE,
                deleted_at = NOW(),
                deleted_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $new_id]);

        echo "<p>✓ Item marcado como deletado (soft delete)</p>";
    } else {
        echo "<h2>Marcando primeiro item como deletado...</h2>";

        // Pegar o primeiro item ativo
        $stmt = $pdo->query("SELECT id, name FROM warehouse WHERE is_deleted = FALSE OR is_deleted IS NULL LIMIT 1");
        $item = $stmt->fetch();

        if ($item) {
            $stmt = $pdo->prepare("
                UPDATE warehouse
                SET is_deleted = TRUE,
                    deleted_at = NOW(),
                    deleted_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $item['id']]);

            echo "<p>✓ Item '{$item['name']}' (ID: {$item['id']}) marcado como deletado</p>";
        } else {
            echo "<p style='color: orange;'>Todos os itens já estão deletados</p>";
        }
    }

    echo "<h2>Verificando itens deletados:</h2>";
    $stmt = $pdo->query("
        SELECT id, name, category, quantity, is_deleted, deleted_at
        FROM warehouse
        WHERE is_deleted = TRUE
    ");
    $deleted_items = $stmt->fetchAll();

    if (empty($deleted_items)) {
        echo "<p style='color: red;'>Nenhum item deletado encontrado!</p>";
    } else {
        echo "<p style='color: green;'>✓ Encontrados " . count($deleted_items) . " itens deletados:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Quantidade</th><th>Deletado em</th></tr>";
        foreach($deleted_items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['name']}</td>";
            echo "<td>{$item['category']}</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>{$item['deleted_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<p><a href='deleted_items.php'>Ir para Itens Deletados</a></p>";
    echo "<p><a href='warehouse.php'>Ir para Armazém</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>ERRO:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
