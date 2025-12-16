<?php
// ========================================
// PÁGINA DE LOG DE ENTRADA DE MÁQUINAS (VERSÃO CORRIGIDA)
// ========================================
require_once '../../config.php';
requireLogin();

$page_title = 'Histórico de Entradas de Máquinas';

// Parâmetros de Filtro
$search = trim($_GET['search'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getConnection();

    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(mi.machine_name LIKE ? OR mi.reason LIKE ? OR mi.details LIKE ? OR u.username LIKE ?)";
        $params = array_fill(0, 4, "%{$search}%");
    }
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(mi.input_date) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(mi.input_date) <= ?";
        $params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $count_query = "SELECT COUNT(*) as total FROM machine_inputs mi LEFT JOIN users u ON mi.user_id = u.id {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);

    $inputs_query = "
        SELECT 
            mi.id,
            mi.input_date,
            mi.machine_name,
            mi.machine_serial_number,
            mi.quantity_added,
            mi.reason,
            mi.machine_cost_price,
            mi.details,
            u.username
        FROM machine_inputs mi
        LEFT JOIN users u ON mi.user_id = u.id
        {$where_clause}
        ORDER BY mi.input_date DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";

    $inputs_stmt = $pdo->prepare($inputs_query);
    $inputs_stmt->execute($params);
    $inputs = $inputs_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao carregar dados de entrada de máquinas: " . $e->getMessage());
    $inputs = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-arrow-circle-up me-2"></i>
        Histórico de Entradas de Máquinas
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar por Termo</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Nome da máquina, motivo, detalhes..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label form-label-custom"><i class="fas fa-calendar-alt me-1"></i> Data Inicial</label>
                <input type="date" class="form-control form-control-custom" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label form-label-custom"><i class="fas fa-calendar-alt me-1"></i> Data Final</label>
                <input type="date" class="form-control form-control-custom" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="col-md-1">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom" data-bs-toggle="tooltip" title="Filtrar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
            <div class="mt-3">
                <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-list me-2"></i>
            Registros de Entrada (<?php echo number_format($total_records); ?>)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($inputs)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhuma entrada de máquina registrada.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Máquina</th>
                            <th>Qtd.</th>
                            <th>Motivo</th>
                            <th>Valor de Custo</th>
                            <th>Detalhes</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inputs as $input): ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($input['input_date'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($input['machine_name']); ?></strong>
                                    <br><small class="text-muted">SN: <?php echo htmlspecialchars($input["machine_serial_number"] ?? 'N/A'); ?></small>
                                </td>
                                <td><span class="badge bg-success"><?php echo number_format($input['quantity_added']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($input["reason"] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php
                                    if (isset($input['machine_cost_price']) && is_numeric($input['machine_cost_price'])) {
                                        $total_value = $input['machine_cost_price'] * $input['quantity_added'];
                                        echo '<strong class="text-info">R$ ' . number_format($total_value, 2, ',', '.') . '</strong>';
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td><small><?php echo nl2br(htmlspecialchars($input["details"] ?? 'N/A')); ?></small></td>
                                <td><small><?php echo htmlspecialchars($input["username"] ?? 'Sistema'); ?></small></td>
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