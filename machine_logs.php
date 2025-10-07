<?php
// ========================================
// PÁGINA DE LOGS DE MÁQUINAS
// ========================================
require_once 'config.php';
requireAdmin();

$page_title = 'Logs de Máquinas';

// Parâmetros de filtro
$filter_machine = $_GET['machine'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getConnection();
    
    // Constrói a query com filtros
    $where_conditions = ["al.table_name = 'ready_machines'"]; // Filtro principal
    $params = [];
    
    if (!empty($filter_machine)) { $where_conditions[] = "rm.name LIKE ?"; $params[] = "%{$filter_machine}%"; }
    if (!empty($filter_action)) { $where_conditions[] = "al.action = ?"; $params[] = $filter_action; }
    if (!empty($filter_user)) { $where_conditions[] = "u.username LIKE ?"; $params[] = "%{$filter_user}%"; }
    if (!empty($filter_date_from)) { $where_conditions[] = "DATE(al.created_at) >= ?"; $params[] = $filter_date_from; }
    if (!empty($filter_date_to)) { $where_conditions[] = "DATE(al.created_at) <= ?"; $params[] = $filter_date_to; }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Conta total de registros
    $count_query = "SELECT COUNT(al.id) as total FROM system_logs al LEFT JOIN users u ON al.user_id = u.id LEFT JOIN ready_machines rm ON al.record_id = rm.id {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Busca os registros da página atual
    $logs_query = "
        SELECT 
            al.*,
            u.username,
            rm.name as machine_name
        FROM system_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN ready_machines rm ON al.record_id = rm.id
        {$where_clause}
        ORDER BY al.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll();
    
    // Lista de ações para filtro
    $actions_stmt = $pdo->query("SELECT DISTINCT action FROM system_logs WHERE table_name = 'ready_machines' ORDER BY action");
    $actions_list = $actions_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar dados: ' . $e->getMessage();
    $logs = []; $actions_list = []; $total_records = 0; $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-desktop me-2"></i>Logs de Máquinas</h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-header card-header-custom"><i class="fas fa-filter me-2"></i>Filtros de Pesquisa</div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-4"><label for="machine" class="form-label form-label-custom">Nome da Máquina</label><input type="text" class="form-control form-control-custom" id="machine" name="machine" value="<?php echo htmlspecialchars($filter_machine); ?>"></div>
                <div class="col-md-3"><label for="action" class="form-label form-label-custom">Ação</label><select class="form-select form-control-custom" id="action" name="action"><option value="">Todas</option><?php foreach ($actions_list as $action): ?><option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filter_action === $action['action'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($action['action']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label for="date_from" class="form-label form-label-custom">De</label><input type="date" class="form-control form-control-custom" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>"></div>
                <div class="col-md-2"><label for="date_to" class="form-label form-label-custom">Até</label><input type="date" class="form-control form-control-custom" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>"></div>
                <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-primary-custom w-100"><i class="fas fa-search"></i></button></div>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span>Logs (<?php echo number_format($total_records); ?> registros)</span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($error_message)): echo "<div class='alert alert-danger'>{$error_message}</div>"; endif; ?>
        <?php if (empty($logs)): ?>
            <div class="text-center text-muted py-4"><p>Nenhum log encontrado.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead><tr><th>Data/Hora</th><th>Máquina</th><th>Usuário</th><th>Ação</th><th>Detalhes</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($log['machine_name'] ?? 'ID #' . $log['record_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?></td>
                            <td>
                                <?php
                                $action_badges = ['CREATE' => 'bg-success', 'UPDATE' => 'bg-warning text-dark', 'DELETE' => 'bg-danger', 'MACHINE_SOLD' => 'bg-info', 'STOCK_OUT_MACHINE' => 'bg-secondary'];
                                $badge_class = $action_badges[$log['action']] ?? 'bg-dark';
                                echo "<span class=\"badge {$badge_class}\">" . htmlspecialchars($log['action']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="showLogDetails(<?php echo $log['id']; ?>)"><i class="fas fa-eye"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
            <nav class="mt-3"><ul class="pagination justify-content-center">
                <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a></li><?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
                <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="logDetailsModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detalhes do Log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="logDetailsContent"></div></div></div></div>
<script>
function showLogDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    const content = document.getElementById('logDetailsContent');
    content.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    fetch('get_log_details.php?id=' + logId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="row">';
                if (data.log.old_values) {
                    html += '<div class="col-md-6"><h6>Valores Anteriores:</h6><pre class="bg-light p-2 rounded small"><code>' + JSON.stringify(JSON.parse(data.log.old_values), null, 2) + '</code></pre></div>';
                }
                if (data.log.new_values) {
                    html += '<div class="col-md-6"><h6>Valores Novos:</h6><pre class="bg-light p-2 rounded small"><code>' + JSON.stringify(JSON.parse(data.log.new_values), null, 2) + '</code></pre></div>';
                }
                html += '</div>';
                if (data.log.user_agent) {
                    html += '<hr><h6 class="mt-3">User Agent:</h6><small class="text-muted font-monospace">' + data.log.user_agent + '</small>';
                }
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        });
}
</script>

<?php include 'includes/footer.php'; ?>
