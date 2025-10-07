<?php
// ========================================
// PÁGINA DE LOG DE SAÍDA DE MÁQUINAS
// ========================================
require_once 'config.php';
requireLogin();

$page_title = 'Histórico de Saídas de Máquinas';

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
        $where_conditions[] = "(mo.machine_name LIKE ? OR mo.reason LIKE ? OR mo.details LIKE ? OR u.username LIKE ?)";
        $params = array_fill(0, 4, "%{$search}%");
    }
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(mo.output_date) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(mo.output_date) <= ?";
        $params[] = $date_to;
    }
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM machine_outputs mo LEFT JOIN users u ON mo.user_id = u.id {$where_clause}");
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);

    $outputs_stmt = $pdo->prepare("
        SELECT mo.*, u.username FROM machine_outputs mo
        LEFT JOIN users u ON mo.user_id = u.id
        {$where_clause} ORDER BY mo.output_date DESC LIMIT {$per_page} OFFSET {$offset}
    ");
    $outputs_stmt->execute($params);
    $outputs = $outputs_stmt->fetchAll();
} catch (PDOException $e) {
    $outputs = []; $total_records = 0; $total_pages = 0;
}
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-arrow-circle-down me-2"></i>Histórico de Saídas de Máquinas</h1>
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
        <span><i class="fas fa-list me-2"></i>Registros de Saída (<?php echo number_format($total_records); ?>)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($outputs)): ?>
            <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><p>Nenhuma saída de máquina registrada.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr><th>Data/Hora</th><th>Máquina</th><th>Qtd.</th><th>Motivo</th><th>Valor</th><th>Detalhes</th><th>Usuário</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outputs as $output): ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($output['output_date'])); ?></small></td>
                                <td><strong><?php echo htmlspecialchars($output['machine_name']); ?></strong></td>
                                <td><span class="badge bg-danger"><?php echo number_format($output['quantity_removed']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($output['reason']); ?></span></td>
                                <td>
                                    <?php
                                    if (isset($output['final_sale_price']) && is_numeric($output['final_sale_price'])) {
                                        $total_value = $output['final_sale_price'] * $output['quantity_removed'];
                                        echo '<strong class="text-success">R$ ' . number_format($total_value, 2, ',', '.') . '</strong>';
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td><small><?php echo nl2br(htmlspecialchars($output['details'] ?? 'N/A')); ?></small></td>
                                <td><small><?php echo htmlspecialchars($output['username'] ?? 'Sistema'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>