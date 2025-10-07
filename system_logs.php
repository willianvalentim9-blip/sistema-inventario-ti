<?php
// ========================================
// PÁGINA DE LISTAGEM DE LOGS DE SISTEMA (ADMIN)
// ========================================
require_once 'config.php';
requireAdmin(); // Apenas administradores podem ver logs de sistema

$page_title = 'Logs do Sistema';

$filter_action = trim($_GET['action'] ?? '');
$filter_user = trim($_GET['user'] ?? '');
$filter_date_from = trim($_GET['date_from'] ?? '');
$filter_date_to = trim($_GET['date_to'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getConnection();

    $where_conditions = [];
    $params = [];

    if (!empty($filter_action)) {
        $where_conditions[] = "al.action = ?";
        $params[] = $filter_action;
    }
    
    if (!empty($filter_user)) {
        $where_conditions[] = "u.username LIKE ?";
        $params[] = "%{$filter_user}%";
    }
    
    if (!empty($filter_date_from)) {
        $where_conditions[] = "DATE(al.created_at) >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "DATE(al.created_at) <= ?";
        $params[] = $filter_date_to;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $count_query = "SELECT COUNT(*) as total FROM admin_logs al LEFT JOIN users u ON al.user_id = u.id {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);

    $logs_query = "
        SELECT 
            al.id, al.action, al.table_name, al.record_id, al.ip_address, al.user_agent, al.created_at,
            u.username
        FROM admin_logs al
        LEFT JOIN users u ON al.user_id = u.id
        {$where_clause}
        ORDER BY al.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";

    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll();

    $actions_stmt = $pdo->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions_list = $actions_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao carregar logs de sistema: " . $e->getMessage());
    $logs = [];
    $actions_list = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-shield-alt me-2"></i>
        Logs do Sistema
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="action" class="form-label form-label-custom">
                    <i class="fas fa-cogs me-1"></i>
                    Ação
                </label>
                <select class="form-select form-control-custom" id="action" name="action">
                    <option value="">Todas</option>
                    <?php foreach ($actions_list as $action_item): ?>
                        <option value="<?php echo htmlspecialchars($action_item['action']); ?>" <?php echo $filter_action === $action_item['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action_item['action']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user" class="form-label form-label-custom">
                    <i class="fas fa-user me-1"></i>
                    Usuário
                </label>
                <input type="text"
                       class="form-control form-control-custom"
                       id="user"
                       name="user"
                       placeholder="Nome de usuário..."
                       value="<?php echo htmlspecialchars($filter_user); ?>">
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label form-label-custom">
                    <i class="fas fa-calendar-alt me-1"></i>
                    De
                </label>
                <input type="date"
                       class="form-control form-control-custom"
                       id="date_from"
                       name="date_from"
                       value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label form-label-custom">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Até
                </label>
                <input type="date"
                       class="form-control form-control-custom"
                       id="date_to"
                       name="date_to"
                       value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-search me-1"></i>
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
            Registros de Logs do Sistema (<?php echo number_format($total_records); ?>)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhum log de sistema encontrado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Tabela</th>
                            <th>ID do Registro</th>
                            <th>IP</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small></td>
                                <td><small><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?></small></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><small><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($log['record_id'] ?? 'N/A'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($log['user_agent'] ?? 'N/A'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
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
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


