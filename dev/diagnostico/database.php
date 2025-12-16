<?php
// ========================================
// DIAGNÓSTICO: BANCO DE DADOS
// ========================================

$config_path = __DIR__ . '/../../config.php';
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

    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle me-2"></i>';
    echo '<strong>Conexão estabelecida com sucesso!</strong>';
    echo '</div>';

    // Informações da conexão
    echo '<h4 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informações da Conexão</h4>';
    echo '<table class="table table-bordered table-sm">';
    echo '<tr><td width="30%"><strong>Host</strong></td><td>' . DB_HOST . '</td></tr>';
    echo '<tr><td><strong>Database</strong></td><td>' . DB_NAME . '</td></tr>';
    echo '<tr><td><strong>Usuário</strong></td><td>' . DB_USER . '</td></tr>';
    echo '<tr><td><strong>Charset</strong></td><td>' . DB_CHARSET . '</td></tr>';
    echo '</table>';

    // Verificar tabelas principais
    echo '<h4 class="mt-4 mb-3"><i class="fas fa-table me-2"></i>Tabelas do Sistema</h4>';

    $required_tables = [
        'users' => 'Usuários',
        'products' => 'Produtos',
        'ready_machines' => 'Máquinas Prontas',
        'warehouse' => 'Armazém',
        'product_movements' => 'Movimentações de Produtos',
        'machine_movements' => 'Movimentações de Máquinas',
        'admin_logs' => 'Logs de Admin',
        'system_settings' => 'Configurações do Sistema',
        'warranty_history' => 'Histórico de Garantias',
        'warehouse_warranty_history' => 'Histórico de Garantias (Armazém)'
    ];

    echo '<table class="table table-bordered table-hover">';
    echo '<thead class="table-light">';
    echo '<tr><th>Tabela</th><th>Status</th><th>Registros</th></tr>';
    echo '</thead><tbody>';

    $all_tables_ok = true;
    foreach ($required_tables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $count = $stmt->fetch()['total'];

            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($description) . '</strong><br><small class="text-muted">' . $table . '</small></td>';
            echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span></td>';
            echo '<td>' . number_format($count) . ' registro(s)</td>';
            echo '</tr>';
        } catch (PDOException $e) {
            $all_tables_ok = false;
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($description) . '</strong><br><small class="text-muted">' . $table . '</small></td>';
            echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Não encontrada</span></td>';
            echo '<td class="text-danger">Erro: ' . htmlspecialchars($e->getMessage()) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Verificar colunas de Soft Delete
    echo '<h4 class="mt-4 mb-3"><i class="fas fa-trash-restore me-2"></i>Verificação de Soft Delete</h4>';

    $soft_delete_tables = ['products', 'ready_machines', 'warehouse'];
    echo '<table class="table table-bordered">';
    echo '<thead class="table-light">';
    echo '<tr><th>Tabela</th><th>is_deleted</th><th>deleted_at</th></tr>';
    echo '</thead><tbody>';

    foreach ($soft_delete_tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $has_is_deleted = in_array('is_deleted', $columns);
            $has_deleted_at = in_array('deleted_at', $columns);

            echo '<tr>';
            echo '<td><strong>' . $table . '</strong></td>';
            echo '<td>';
            if ($has_is_deleted) {
                echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
            } else {
                echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Falta</span>';
            }
            echo '</td>';
            echo '<td>';
            if ($has_deleted_at) {
                echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>';
            } else {
                echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Falta</span>';
            }
            echo '</td>';
            echo '</tr>';
        } catch (PDOException $e) {
            echo '<tr>';
            echo '<td><strong>' . $table . '</strong></td>';
            echo '<td colspan="2" class="text-danger">Erro ao verificar</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Estatísticas gerais
    echo '<h4 class="mt-4 mb-3"><i class="fas fa-chart-bar me-2"></i>Estatísticas Gerais</h4>';
    echo '<div class="row">';

    $stats = [
        ['table' => 'products', 'label' => 'Produtos', 'icon' => 'fa-boxes', 'color' => 'primary'],
        ['table' => 'ready_machines', 'label' => 'Máquinas', 'icon' => 'fa-desktop', 'color' => 'info'],
        ['table' => 'warehouse', 'label' => 'Armazém', 'icon' => 'fa-warehouse', 'color' => 'warning'],
        ['table' => 'users', 'label' => 'Usuários', 'icon' => 'fa-users', 'color' => 'success']
    ];

    foreach ($stats as $stat) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$stat['table']}`");
            $count = $stmt->fetch()['total'];

            echo '<div class="col-md-3 mb-3">';
            echo '<div class="card border-' . $stat['color'] . '">';
            echo '<div class="card-body text-center">';
            echo '<i class="fas ' . $stat['icon'] . ' fa-3x text-' . $stat['color'] . ' mb-2"></i>';
            echo '<h3 class="mb-0">' . number_format($count) . '</h3>';
            echo '<small class="text-muted">' . $stat['label'] . '</small>';
            echo '</div></div></div>';
        } catch (PDOException $e) {
            echo '<div class="col-md-3 mb-3">';
            echo '<div class="card border-danger">';
            echo '<div class="card-body text-center">';
            echo '<i class="fas fa-exclamation-triangle fa-3x text-danger mb-2"></i>';
            echo '<small class="text-danger">Erro ao consultar</small>';
            echo '</div></div></div>';
        }
    }
    echo '</div>';

    // Estatísticas de Garantias
    echo '<h4 class="mt-4 mb-3"><i class="fas fa-shield-alt me-2"></i>Estatísticas de Garantias</h4>';
    echo '<div class="row">';

    $warranty_stats = [
        ['table' => 'products', 'condition' => 'WHERE has_warranty = 1', 'label' => 'Produtos c/ Garantia', 'icon' => 'fa-box-check', 'color' => 'success'],
        ['table' => 'ready_machines', 'condition' => 'WHERE has_warranty = 1', 'label' => 'Máquinas c/ Garantia', 'icon' => 'fa-desktop', 'color' => 'info'],
        ['table' => 'warehouse', 'condition' => 'WHERE has_warranty = 1', 'label' => 'Armazém c/ Garantia', 'icon' => 'fa-warehouse', 'color' => 'warning']
    ];

    foreach ($warranty_stats as $stat) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$stat['table']}` {$stat['condition']}");
            $count = $stmt->fetch()['total'];

            echo '<div class="col-md-4 mb-3">';
            echo '<div class="card border-' . $stat['color'] . '">';
            echo '<div class="card-body text-center">';
            echo '<i class="fas ' . $stat['icon'] . ' fa-2x text-' . $stat['color'] . ' mb-2"></i>';
            echo '<h4 class="mb-0">' . number_format($count) . '</h4>';
            echo '<small class="text-muted">' . $stat['label'] . '</small>';
            echo '</div></div></div>';
        } catch (PDOException $e) {
            echo '<div class="col-md-4 mb-3">';
            echo '<div class="card border-danger">';
            echo '<div class="card-body text-center">';
            echo '<i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>';
            echo '<small class="text-danger">Erro</small>';
            echo '</div></div></div>';
        }
    }
    echo '</div>';

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-times-circle me-2"></i>';
    echo '<strong>Erro de Conexão!</strong><br>';
    echo 'Mensagem: ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<small>Código: ' . $e->getCode() . '</small>';
    echo '</div>';

    echo '<h5 class="mt-4">Possíveis Soluções:</h5>';
    echo '<ul>';
    echo '<li>Verifique se o MySQL/MariaDB está rodando</li>';
    echo '<li>Confirme as credenciais em config.php</li>';
    echo '<li>Verifique se o banco de dados existe</li>';
    echo '<li>Confirme as permissões do usuário</li>';
    echo '</ul>';
}
?>
