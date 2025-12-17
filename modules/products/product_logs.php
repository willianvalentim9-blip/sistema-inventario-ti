<?php
// ========================================
// PÁGINA DE LISTAGEM DE LOGS DE PRODUTOS
// ========================================
require_once '../../config.php';
requireLogin();

$page_title = 'Logs de Produtos';

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getConnection();

    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "p.name LIKE ? OR pm.movement_type LIKE ? OR pm.reason LIKE ?";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $count_query = "SELECT COUNT(*) as total FROM product_movements pm LEFT JOIN products p ON pm.product_id = p.id {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);

    $movements_query = "
        SELECT 
            pm.id, pm.movement_type, pm.quantity, pm.previous_quantity, pm.new_quantity, pm.reason, pm.created_at,
            p.name as product_name, p.category, u.username
        FROM product_movements pm
        LEFT JOIN products p ON pm.product_id = p.id
        LEFT JOIN users u ON pm.user_id = u.id
        {$where_clause}
        ORDER BY pm.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";

    $movements_stmt = $pdo->prepare($movements_query);
    $movements_stmt->execute($params);
    $movements = $movements_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao carregar logs de produtos: " . $e->getMessage());
    $movements = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-history me-2"></i>
        Logs de Produtos
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-9">
                <label for="search" class="form-label form-label-custom">
                    <i class="fas fa-search me-1"></i>
                    Buscar Log
                </label>
                <input type="text"
                       class="form-control form-control-custom"
                       id="search"
                       name="search"
                       placeholder="Nome do produto, tipo de movimento, motivo..."
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
            Registros de Logs de Produtos (<?php echo number_format($total_records); ?>)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?></small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movements)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhum log de produto encontrado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Qtd.</th>
                            <th>Qtd. Anterior</th>
                            <th>Qtd. Nova</th>
                            <th>Motivo/Detalhes</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($movement['created_at'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($movement['product_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($movement['category']); ?></small>
                                </td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($movement['movement_type'])); ?></span></td>
                                <td><?php echo number_format($movement['quantity']); ?></td>
                                <td><?php echo number_format($movement['previous_quantity']); ?></td>
                                <td><?php echo number_format($movement['new_quantity']); ?></td>
                                <td><small><?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($movement['username'] ?? 'Sistema'); ?></small></td>
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


