<?php
// ========================================
// DIAGNÓSTICO: SISTEMA DE GARANTIAS
// ========================================

$config_path = __DIR__ . '/../../config.php';
if (!file_exists($config_path)) {
    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Config.php não encontrado</div>';
    return;
}

require_once $config_path;

try {
    $pdo = getConnection();

    $warranty_tables = [
        'products' => 'Produtos com Garantia',
        'ready_machines' => 'Máquinas com Garantia',
        'warranty_history' => 'Histórico de Garantias (Geral)',
        'warehouse_warranty_history' => 'Histórico de Garantias (Armazém)'
    ];

    echo '<h4 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Tabelas de Garantia</h4>';
    echo '<table class="table table-bordered">';
    echo '<thead class="table-light"><tr><th>Tabela</th><th>Status</th><th>Registros</th></tr></thead>';
    echo '<tbody>';

    $all_ok = true;
    foreach ($warranty_tables as $table => $description) {
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($description) . '</strong><br><small class="text-muted">' . $table . '</small></td>';

        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $count = $stmt->fetch()['total'];

            echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span></td>';
            echo '<td>' . number_format($count) . ' registro(s)</td>';
        } catch (PDOException $e) {
            $all_ok = false;
            echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Não existe</span></td>';
            echo '<td class="text-danger">-</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';

    if ($all_ok) {
        echo '<div class="alert alert-success">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>Sistema de Garantias OK!</strong> Todas as tabelas estão presentes.';
        echo '</div>';

        // Estatísticas
        echo '<h5 class="mt-4 mb-3"><i class="fas fa-chart-bar me-2"></i>Estatísticas de Garantias</h5>';
        echo '<div class="row">';

        // Produtos com garantia (contando itens where has_warranty = 1)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1");
        $total_prod = $stmt->fetch()['total'];

        // Máquinas com garantia (contando itens where has_warranty = 1)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE has_warranty = 1");
        $total_mach = $stmt->fetch()['total'];

        // Armazém com garantia
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse WHERE has_warranty = 1");
        $total_warehouse = $stmt->fetch()['total'];

        // Histórico de garantias de armazém
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warehouse_warranty_history");
        $total_warehouse_history = $stmt->fetch()['total'];

        echo '<div class="col-md-3">';
        echo '<div class="card border-primary">';
        echo '<div class="card-body text-center">';
        echo '<i class="fas fa-box fa-2x text-primary mb-2"></i>';
        echo '<h3>' . number_format($total_prod) . '</h3>';
        echo '<small class="text-muted">Produtos com Garantia</small>';
        echo '</div></div></div>';

        echo '<div class="col-md-3">';
        echo '<div class="card border-info">';
        echo '<div class="card-body text-center">';
        echo '<i class="fas fa-desktop fa-2x text-info mb-2"></i>';
        echo '<h3>' . number_format($total_mach) . '</h3>';
        echo '<small class="text-muted">Máquinas com Garantia</small>';
        echo '</div></div></div>';

        echo '<div class="col-md-3">';
        echo '<div class="card border-warning">';
        echo '<div class="card-body text-center">';
        echo '<i class="fas fa-warehouse fa-2x text-warning mb-2"></i>';
        echo '<h3>' . number_format($total_warehouse) . '</h3>';
        echo '<small class="text-muted">Itens Armazém c/ Garantia</small>';
        echo '</div></div></div>';

        echo '<div class="col-md-3">';
        echo '<div class="card border-success">';
        echo '<div class="card-body text-center">';
        echo '<i class="fas fa-history fa-2x text-success mb-2"></i>';
        echo '<h3>' . number_format($total_warehouse_history) . '</h3>';
        echo '<small class="text-muted">Histórico Armazém</small>';
        echo '</div></div></div>';

        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo '<strong>Algumas tabelas estão faltando.</strong> Execute os scripts de migration.';
        echo '</div>';
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-times-circle me-2"></i>';
    echo '<strong>Erro:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>
