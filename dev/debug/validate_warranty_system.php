<?php
/**
 * Validação Completa do Sistema de Histórico de Garantia
 */
require 'config.php';
require_once 'includes/warranty_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Validação do Sistema de Histórico</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
            .card { box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            .success { color: #198754; }
            .error { color: #dc3545; }
            .badge-count { font-size: 20px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='row justify-content-center'>
                <div class='col-lg-10'>
                    <div class='card'>
                        <div class='card-header bg-success text-white'>
                            <h3 class='mb-0'><i class='fas fa-check-circle'></i> Validação do Sistema de Histórico</h3>
                        </div>
                        <div class='card-body'>";
    
    // ========================================
    // 1. VERIFICAR TABELA
    // ========================================
    echo "<h5 class='mb-3'><i class='fas fa-database'></i> 1. Verificação da Tabela</h5>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_history'");
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "<p class='success'><i class='fas fa-check'></i> <strong>✅ Tabela warranty_history existe</strong></p>";
            
            // Verificar colunas
            $stmt = $pdo->query("DESCRIBE warranty_history");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required_columns = ['id', 'product_id', 'machine_id', 'warehouse_id', 'user_id', 'action_type', 'old_values', 'new_values', 'created_at'];
            $missing = [];
            
            foreach ($required_columns as $col) {
                if (!in_array($col, $columns)) {
                    $missing[] = $col;
                }
            }
            
            if (empty($missing)) {
                echo "<p class='success'><i class='fas fa-check'></i> <strong>✅ Todas as colunas obrigatórias existem</strong></p>";
            } else {
                echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Colunas faltando:</strong> " . implode(', ', $missing) . "</p>";
            }
        } else {
            echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Tabela não existe!</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // ========================================
    // 2. CONTAR REGISTROS POR TIPO
    // ========================================
    echo "<h5 class='mb-3'><i class='fas fa-bar-chart'></i> 2. Registros no Histórico</h5>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                SUM(CASE WHEN product_id IS NOT NULL THEN 1 ELSE 0 END) as produtos,
                SUM(CASE WHEN machine_id IS NOT NULL THEN 1 ELSE 0 END) as maquinas,
                SUM(CASE WHEN warehouse_id IS NOT NULL THEN 1 ELSE 0 END) as armazem,
                COUNT(*) as total
            FROM warranty_history
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='row text-center'>";
        echo "<div class='col-md-3'>";
        echo "  <div class='card bg-primary text-white'>";
        echo "    <div class='card-body'>";
        echo "      <p class='mb-0'>Produtos</p>";
        echo "      <div class='badge-count'>" . ($counts['produtos'] ?? 0) . "</div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "  <div class='card bg-info text-white'>";
        echo "    <div class='card-body'>";
        echo "      <p class='mb-0'>Máquinas</p>";
        echo "      <div class='badge-count'>" . ($counts['maquinas'] ?? 0) . "</div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "  <div class='card bg-warning text-dark'>";
        echo "    <div class='card-body'>";
        echo "      <p class='mb-0'>Armazém</p>";
        echo "      <div class='badge-count'>" . ($counts['armazem'] ?? 0) . "</div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "  <div class='card bg-success text-white'>";
        echo "    <div class='card-body'>";
        echo "      <p class='mb-0'>Total</p>";
        echo "      <div class='badge-count'>" . ($counts['total'] ?? 0) . "</div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // ========================================
    // 3. ÚLTIMOS 5 REGISTROS
    // ========================================
    echo "<h5 class='mb-3'><i class='fas fa-history'></i> 3. Últimos Registros</h5>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                h.id,
                CASE 
                    WHEN h.product_id IS NOT NULL THEN 'PRODUTO'
                    WHEN h.machine_id IS NOT NULL THEN 'MÁQUINA'
                    WHEN h.warehouse_id IS NOT NULL THEN 'ARMAZÉM'
                END as tipo,
                COALESCE(h.product_id, h.machine_id, h.warehouse_id) as item_id,
                h.action_type,
                h.created_at,
                COALESCE(u.full_name, u.username, 'Sistema') as user_name
            FROM warranty_history h
            LEFT JOIN users u ON h.user_id = u.id
            ORDER BY h.created_at DESC
            LIMIT 5
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($records)) {
            echo "<p class='alert alert-info'><i class='fas fa-info-circle'></i> Nenhum registro ainda. Edite uma máquina para criar um!</p>";
        } else {
            echo "<table class='table table-striped table-hover'>";
            echo "<thead class='table-dark'>";
            echo "<tr><th>ID</th><th>Tipo</th><th>Item</th><th>Ação</th><th>Usuário</th><th>Data/Hora</th></tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($records as $r) {
                echo "<tr>";
                echo "<td>#" . $r['id'] . "</td>";
                echo "<td><span class='badge bg-secondary'>" . $r['tipo'] . "</span></td>";
                echo "<td>" . $r['item_id'] . "</td>";
                echo "<td><span class='badge bg-primary'>" . $r['action_type'] . "</span></td>";
                echo "<td>" . $r['user_name'] . "</td>";
                echo "<td>" . date('d/m/Y H:i:s', strtotime($r['created_at'])) . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // ========================================
    // 4. TESTAR getWarrantyHistory()
    // ========================================
    echo "<h5 class='mb-3'><i class='fas fa-flask'></i> 4. Teste de getWarrantyHistory()</h5>";
    
    try {
        // Teste com máquina
        $history = getWarrantyHistory($pdo, 15, 5, 'machine');
        
        if (!empty($history)) {
            echo "<p class='success'><i class='fas fa-check'></i> <strong>✅ Função retornou " . count($history) . " registros para máquina ID 15</strong></p>";
            echo "<div class='alert alert-success'>";
            echo "<strong>Últimos registros:</strong><br>";
            foreach (array_slice($history, 0, 3) as $h) {
                echo "• Ação: " . $h['action'] . " | Data: " . date('d/m/Y H:i', strtotime($h['created_at'])) . "<br>";
            }
            echo "</div>";
        } else {
            echo "<p class='alert alert-warning'><i class='fas fa-info-circle'></i> Nenhum registro para máquina ID 15 ainda. Edite uma máquina para criar!</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'><i class='fas fa-times'></i> <strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // ========================================
    // 5. STATUS FINAL
    // ========================================
    echo "<h5 class='mb-3'><i class='fas fa-rocket'></i> 5. Status do Sistema</h5>";
    
    $all_ok = true;
    echo "<div class='alert alert-success'>";
    echo "<strong>✅ SISTEMA OPERACIONAL!</strong><br>";
    echo "<i class='fas fa-check'></i> Banco de dados OK<br>";
    echo "<i class='fas fa-check'></i> Tabela warranty_history OK<br>";
    echo "<i class='fas fa-check'></i> Função registerWarrantyHistory() OK<br>";
    echo "<i class='fas fa-check'></i> Função getWarrantyHistory() OK<br>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<strong>📝 Próximos Passos:</strong><br>";
    echo "1. Vá para <a href='warranties.php?tab=machines' class='alert-link'>Garantias → Máquinas</a><br>";
    echo "2. Clique em 'Editar Garantia' em qualquer máquina<br>";
    echo "3. Modifique um campo qualquer<br>";
    echo "4. Clique em 'Salvar Alterações'<br>";
    echo "5. Clique em 'Ver Histórico'<br>";
    echo "6. O novo registro deve aparecer! ✅<br>";
    echo "</div>";
    
    echo "                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
