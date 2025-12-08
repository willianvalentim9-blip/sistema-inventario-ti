<?php
/**
 * Adicionar colunas de garantia faltantes na tabela warehouse
 */
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getConnection();
    
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'><title>Corrigir Warehouse</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body class='p-5'>";
    echo "<div class='container'>";
    echo "<h2>Corrigindo tabela warehouse...</h2>";
    
    // Verificar se coluna já existe
    $stmt = $pdo->query("DESCRIBE warehouse");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $missing_columns = [];
    
    if (!in_array('warranty_notes', $columns)) {
        $missing_columns[] = 'warranty_notes';
    }
    
    if (empty($missing_columns)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check'></i> <strong>✅ Tabela warehouse já está correta!</strong>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<strong>⚠️ Colunas faltando:</strong> " . implode(', ', $missing_columns);
        echo "</div>";
        
        // Adicionar coluna warranty_notes
        if (in_array('warranty_notes', $missing_columns)) {
            echo "<p>Adicionando coluna warranty_notes...</p>";
            $pdo->exec("ALTER TABLE warehouse ADD COLUMN warranty_notes LONGTEXT NULL AFTER warranty_period_unit");
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> warranty_notes adicionada!</div>";
        }
    }
    
    // Mostrar colunas atuais
    echo "<h4 class='mt-4'>Colunas da tabela warehouse:</h4>";
    $stmt = $pdo->query("DESCRIBE warehouse");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-striped'>";
    echo "<thead class='table-dark'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th></tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($columns as $col) {
        $warranty_related = (strpos($col['Field'], 'warranty') !== false) ? ' style="background-color: #fff3cd;"' : '';
        echo "<tr{$warranty_related}>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
    echo "<div class='alert alert-info mt-4'>";
    echo "<strong>ℹ️ Próximo passo:</strong><br>";
    echo "Agora você pode ir para <a href='warranties.php?tab=warehouse' class='alert-link'>Garantias → Armazém</a>";
    echo " e tentar editar uma garantia novamente!";
    echo "</div>";
    
    echo "</div>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
