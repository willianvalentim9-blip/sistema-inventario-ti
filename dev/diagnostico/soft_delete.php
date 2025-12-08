<?php
// ========================================
// DIAGNÓSTICO: SOFT DELETE
// ========================================

$config_path = __DIR__ . '/../config.php';
if (!file_exists($config_path)) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>Erro:</strong> Arquivo config.php não encontrado.';
    echo '</div>';
    return;
}

require_once $config_path;

try {
    $pdo = getConnection();

    echo '<h4 class="mb-3"><i class="fas fa-database me-2"></i>Verificação de Colunas</h4>';

    // Verificar products
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>Tabela: products</strong></div>';
    echo '<div class="card-body">';

    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $has_is_deleted = in_array('is_deleted', $columns);
    $has_deleted_at = in_array('deleted_at', $columns);

    echo '<table class="table table-sm mb-0">';
    echo '<tr>';
    echo '<td width="30%"><strong>Coluna is_deleted</strong></td>';
    echo '<td>';
    if ($has_is_deleted) {
        echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
    } else {
        echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO Existe</span>';
        echo '<p class="mb-0 mt-2 text-danger"><small>Execute a migration SQL para criar esta coluna</small></p>';
    }
    echo '</td></tr>';

    echo '<tr>';
    echo '<td><strong>Coluna deleted_at</strong></td>';
    echo '<td>';
    if ($has_deleted_at) {
        echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
    } else {
        echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO Existe</span>';
        echo '<p class="mb-0 mt-2 text-danger"><small>Execute a migration SQL para criar esta coluna</small></p>';
    }
    echo '</td></tr>';
    echo '</table>';
    echo '</div></div>';

    // Verificar ready_machines
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-info text-white"><strong>Tabela: ready_machines</strong></div>';
    echo '<div class="card-body">';

    $stmt = $pdo->query("DESCRIBE ready_machines");
    $columns_m = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $has_is_deleted_m = in_array('is_deleted', $columns_m);
    $has_deleted_at_m = in_array('deleted_at', $columns_m);

    echo '<table class="table table-sm mb-0">';
    echo '<tr>';
    echo '<td width="30%"><strong>Coluna is_deleted</strong></td>';
    echo '<td>';
    if ($has_is_deleted_m) {
        echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
    } else {
        echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO Existe</span>';
    }
    echo '</td></tr>';

    echo '<tr>';
    echo '<td><strong>Coluna deleted_at</strong></td>';
    echo '<td>';
    if ($has_deleted_at_m) {
        echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
    } else {
        echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO Existe</span>';
    }
    echo '</td></tr>';
    echo '</table>';
    echo '</div></div>';

    // Contadores
    if ($has_is_deleted && $has_is_deleted_m) {
        echo '<h4 class="mt-4 mb-3"><i class="fas fa-chart-pie me-2"></i>Estatísticas de Soft Delete</h4>';
        echo '<div class="row">';

        // Produtos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $total_products = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = FALSE OR is_deleted IS NULL");
        $active_products = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = TRUE");
        $deleted_products = $stmt->fetch()['total'];

        echo '<div class="col-md-6">';
        echo '<div class="card">';
        echo '<div class="card-header bg-primary text-white"><strong>Produtos</strong></div>';
        echo '<div class="card-body">';
        echo '<table class="table table-sm mb-0">';
        echo '<tr><td><strong>Total</strong></td><td><span class="badge bg-secondary">' . $total_products . '</span></td></tr>';
        echo '<tr><td><strong>Ativos</strong></td><td><span class="badge bg-success">' . $active_products . '</span></td></tr>';
        echo '<tr><td><strong>Deletados</strong></td><td><span class="badge bg-danger">' . $deleted_products . '</span></td></tr>';
        echo '</table>';
        echo '</div></div></div>';

        // Máquinas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines");
        $total_machines = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE is_deleted = FALSE OR is_deleted IS NULL");
        $active_machines = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE is_deleted = TRUE");
        $deleted_machines = $stmt->fetch()['total'];

        echo '<div class="col-md-6">';
        echo '<div class="card">';
        echo '<div class="card-header bg-info text-white"><strong>Máquinas</strong></div>';
        echo '<div class="card-body">';
        echo '<table class="table table-sm mb-0">';
        echo '<tr><td><strong>Total</strong></td><td><span class="badge bg-secondary">' . $total_machines . '</span></td></tr>';
        echo '<tr><td><strong>Ativas</strong></td><td><span class="badge bg-success">' . $active_machines . '</span></td></tr>';
        echo '<tr><td><strong>Deletadas</strong></td><td><span class="badge bg-danger">' . $deleted_machines . '</span></td></tr>';
        echo '</table>';
        echo '</div></div></div>';

        echo '</div>';

        // Últimos itens deletados
        if ($deleted_products > 0 || $deleted_machines > 0) {
            echo '<h5 class="mt-4 mb-3"><i class="fas fa-history me-2"></i>Últimos Itens Deletados</h5>';

            if ($deleted_products > 0) {
                echo '<h6 class="text-muted">Produtos:</h6>';
                $stmt = $pdo->query("SELECT id, name, deleted_at FROM products WHERE is_deleted = TRUE ORDER BY deleted_at DESC LIMIT 5");
                $recent_products = $stmt->fetchAll();

                echo '<table class="table table-sm table-bordered">';
                echo '<thead><tr><th>ID</th><th>Nome</th><th>Deletado em</th></tr></thead><tbody>';
                foreach ($recent_products as $p) {
                    echo '<tr>';
                    echo '<td>#' . $p['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($p['name']) . '</td>';
                    echo '<td>' . date('d/m/Y H:i', strtotime($p['deleted_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }

            if ($deleted_machines > 0) {
                echo '<h6 class="text-muted mt-3">Máquinas:</h6>';
                $stmt = $pdo->query("SELECT id, name, deleted_at FROM ready_machines WHERE is_deleted = TRUE ORDER BY deleted_at DESC LIMIT 5");
                $recent_machines = $stmt->fetchAll();

                echo '<table class="table table-sm table-bordered">';
                echo '<thead><tr><th>ID</th><th>Nome</th><th>Deletado em</th></tr></thead><tbody>';
                foreach ($recent_machines as $m) {
                    echo '<tr>';
                    echo '<td>#' . $m['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($m['name']) . '</td>';
                    echo '<td>' . date('d/m/Y H:i', strtotime($m['deleted_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        }
    }

    // Resultado final
    if ($has_is_deleted && $has_deleted_at && $has_is_deleted_m && $has_deleted_at_m) {
        echo '<div class="alert alert-success mt-4">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>Soft Delete Configurado!</strong> Todas as colunas necessárias estão presentes.';
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger mt-4">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo '<strong>Soft Delete NÃO Configurado!</strong><br>';
        echo 'Execute o SQL em: <code>EXECUTE_ESTE_SQL.sql</code> ou acesse <code>run_migration.php</code>';
        echo '</div>';
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-times-circle me-2"></i>';
    echo '<strong>Erro:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>
