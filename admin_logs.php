<?php
// ========================================
// PÁGINA DE LOGS DE ADMINISTRADOR (VERSÃO CORRIGIDA)
// ========================================
// Esta página exibe os logs de ações administrativas do sistema

// Inclui o arquivo de configuração
require_once 'config.php';

// Verifica se o usuário está logado e é admin
requireAdmin();

// Define variáveis para o template
$page_title = 'Logs de Administrador';

// Parâmetros de filtro
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_table = $_GET['table'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// ========================================
// CARREGA DADOS PARA A PÁGINA
// ========================================
try {
    $pdo = getConnection();
    
    // Constrói a query com filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($filter_user)) {
        $where_conditions[] = "u.username LIKE ?";
        $params[] = "%{$filter_user}%";
    }
    
    if (!empty($filter_action)) {
        $where_conditions[] = "al.action LIKE ?";
        $params[] = "%{$filter_action}%";
    }
    
    if (!empty($filter_table)) {
        $where_conditions[] = "al.table_name = ?";
        $params[] = $filter_table;
    }
    
    if (!empty($filter_date_from)) {
        $where_conditions[] = "DATE(al.timestamp) >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "DATE(al.timestamp) <= ?";
        $params[] = $filter_date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Conta total de registros
    $count_query = "
        SELECT COUNT(*) as total
        FROM admin_logs al
        LEFT JOIN users u ON al.user_id = u.id
        {$where_clause}
    ";
    
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Busca os registros da página atual
    $logs_query = "
        SELECT 
            al.*,
            u.username
        FROM admin_logs al
        LEFT JOIN users u ON al.user_id = u.id
        {$where_clause}
        ORDER BY al.timestamp DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll();
    
    // Lista de ações para filtro
    $actions_stmt = $pdo->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions_list = $actions_stmt->fetchAll();
    
    // Lista de tabelas para filtro
    $tables_stmt = $pdo->query("SELECT DISTINCT table_name FROM admin_logs WHERE table_name IS NOT NULL ORDER BY table_name");
    $tables_list = $tables_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar dados: ' . $e->getMessage();
    $logs = [];
    $actions_list = [];
    $tables_list = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-clipboard-list me-2"></i>
        Logs de Administrador
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-header card-header-custom">
        <i class="fas fa-filter me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <label for="user" class="form-label form-label-custom">Usuário</label>
                    <input type="text" class="form-control form-control-custom" id="user" name="user" value="<?php echo htmlspecialchars($filter_user); ?>" placeholder="Nome do usuário">
                </div>
                <div class="col-md-3">
                    <label for="action" class="form-label form-label-custom">Ação</label>
                    <select class="form-select form-control-custom" id="action" name="action">
                        <option value="">Todas</option>
                        <?php foreach ($actions_list as $action): ?>
                            <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filter_action === $action['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($action['action']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="table" class="form-label form-label-custom">Tabela</label>
                    <select class="form-select form-control-custom" id="table" name="table">
                        <option value="">Todas</option>
                        <?php foreach ($tables_list as $table): ?>
                            <option value="<?php echo htmlspecialchars($table['table_name']); ?>" <?php echo $filter_table === $table['table_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($table['table_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label form-label-custom">Data Inicial</label>
                    <input type="date" class="form-control form-control-custom" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label form-label-custom">Data Final</label>
                    <input type="date" class="form-control form-control-custom" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
            </div>
            <div class="row mt-3">
                 <div class="col-12 text-end">
                    <a href="admin_logs.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>
                        Limpar Filtros
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary-custom">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-list me-2"></i>
            Logs (<?php echo number_format($total_records); ?> registros)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (isset($error_message)): echo "<div class='alert alert-danger m-3'>{$error_message}</div>"; endif; ?>
        <?php if (empty($logs)): ?>
            <div class="text-center text-muted py-4">
                <p>Nenhum log encontrado com os filtros aplicados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Tabela Afetada</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></small></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><code><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></code></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($total_pages > 1 && !empty($logs)): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>