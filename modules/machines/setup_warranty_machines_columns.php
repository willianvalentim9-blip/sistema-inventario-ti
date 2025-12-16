<?php
/**
 * Script para adicionar colunas de garantia à tabela ready_machines
 * Acesse via browser: http://localhost/sistema4/setup_warranty_machines_columns.php
 */

require_once '../../config.php';
require_once 'includes/warranty_functions.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Garantia de Máquinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">⚙️ Setup - Adicionar Colunas de Garantia</h4>
                    </div>
                    <div class="card-body">
<?php

try {
    $pdo = getConnection();
    
    // Verificar se as colunas já existem
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ready_machines' AND COLUMN_NAME LIKE 'warranty_%'");
    $existing_columns = $stmt->fetchAll();
    
    if (!empty($existing_columns)) {
        echo "<div class='alert alert-success'>";
        echo "✓ <strong>Sucesso!</strong> As colunas de garantia já existem em ready_machines";
        echo "</div>";
        
        echo "<div class='alert alert-info'>";
        echo "<strong>Colunas encontradas:</strong><br>";
        foreach ($existing_columns as $col) {
            echo "• " . htmlspecialchars($col['COLUMN_NAME']) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "⏳ Adicionando colunas de garantia...";
        echo "</div>";
        
        // Adicionar colunas uma por uma
        $columns_to_add = [
            "warranty_template_id INT DEFAULT NULL",
            "warranty_start_date DATE DEFAULT NULL",
            "warranty_end_date DATE DEFAULT NULL",
            "warranty_provider VARCHAR(255) DEFAULT NULL",
            "warranty_notes TEXT DEFAULT NULL"
        ];
        
        foreach ($columns_to_add as $col_def) {
            try {
                $pdo->exec("ALTER TABLE ready_machines ADD COLUMN $col_def");
                echo "<div class='alert alert-info'>✓ Adicionado: $col_def</div>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<div class='alert alert-info'>ℹ️ Coluna já existe: $col_def</div>";
                } else {
                    throw $e;
                }
            }
        }
        
        // Adicionar índices
        try {
            $pdo->exec("CREATE INDEX idx_warranty_template_id ON ready_machines(warranty_template_id)");
            echo "<div class='alert alert-success'>✓ Índice criado: idx_warranty_template_id</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-info'>ℹ️ Índice já existe</div>";
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_warranty_dates ON ready_machines(warranty_start_date, warranty_end_date)");
            echo "<div class='alert alert-success'>✓ Índice criado: idx_warranty_dates</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-info'>ℹ️ Índice já existe</div>";
        }
        
        echo "<div class='alert alert-success mt-3'>";
        echo "✓ <strong>Setup concluído com sucesso!</strong>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "✗ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

?>
                    </div>
                    <div class="card-footer bg-light">
                        <a href="warranties.php?tab=machines" class="btn btn-primary">
                            ← Voltar para Garantias (Máquinas)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
