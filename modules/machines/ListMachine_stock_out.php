<?php
// ========================================
// PÁGINA DE LISTAGEM DE SAÍDA DE MÁQUINAS
// ========================================
require_once '../../config.php';
requireLogin();

$page_title = 'Saída de Máquinas';

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getConnection();

    $where_conditions = ["status IN ('sold', 'removed')"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR serial_number LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $count_query = "SELECT COUNT(*) as total FROM ready_machines {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);

    $machines_query = "
        SELECT 
            id, name, serial_number, sale_price, status, created_at, updated_at
        FROM ready_machines
        {$where_clause}
        ORDER BY updated_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";

    $machines_stmt = $pdo->prepare($machines_query);
    $machines_stmt->execute($params);
    $machines = $machines_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao carregar dados de saída de máquinas: " . $e->getMessage());
    $machines = [];
    $total_records = 0;
    $total_pages = 0;
}

function getMachineStatusBadgeClass($status) {
    $classes = ['sold' => 'bg-info', 'removed' => 'bg-danger'];
    return $classes[$status] ?? 'bg-secondary';
}
function getMachineStatusText($status) {
    $texts = ['sold' => 'Vendida', 'removed' => 'Removida'];
    return $texts[$status] ?? ucfirst($status);
}
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-minus-circle me-2"></i>
        Saídas de Máquinas
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-9">
                <label for="search" class="form-label form-label-custom">
                    <i class="fas fa-search me-1"></i>
                    Buscar Máquina
                </label>
                <input type="text"
                       class="form-control form-control-custom"
                       id="search"
                       name="search"
                       placeholder="Nome, número de série..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
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
            Registros de Saída (<?php echo number_format($total_records); ?>)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($machines)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-desktop fa-3x mb-3"></i>
                <p>Nenhuma máquina com saída registrada encontrada.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Data da Baixa</th>
                            <th>Máquina</th>
                            <th>Nº de Série</th>
                            <th>Preço de Venda</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($machines as $machine): ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($machine['updated_at'])); ?></small></td>
                                <td><strong><?php echo htmlspecialchars($machine['name']); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($machine['serial_number'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <?php if ($machine['sale_price']): ?>
                                        <strong class="text-success">R$ <?php echo number_format($machine['sale_price'], 2, ',', '.'); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo getMachineStatusBadgeClass($machine['status']); ?>"><?php echo getMachineStatusText($machine['status']); ?></span></td>
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

<?php include '../../includes/footer.php'; ?>