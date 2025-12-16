<?php
// ========================================
// PÁGINA DE HISTÓRICO DE MOVIMENTAÇÕES
// ========================================
// Esta página exibe o histórico completo de movimentações de produtos

// Inclui o arquivo de configuração
require_once '../../config.php';

// Verifica se o usuário está logado
requireLogin();

// Define variáveis para o template
$page_title = 'Histórico de Movimentações';

// Parâmetros de filtro
$filter_product = $_GET['product'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// ========================================
// CARREGA DADOS PARA A PÁGINA
// ========================================
try {
    $pdo = getConnection();
    
    // Constrói a query com filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($filter_product)) {
        $where_conditions[] = "p.name LIKE ?";
        $params[] = "%{$filter_product}%";
    }
    
    if (!empty($filter_type)) {
        $where_conditions[] = "pm.movement_type = ?";
        $params[] = $filter_type;
    }
    
    if (!empty($filter_user)) {
        $where_conditions[] = "u.username LIKE ?";
        $params[] = "%{$filter_user}%";
    }
    
    if (!empty($filter_date_from)) {
        $where_conditions[] = "DATE(pm.created_at) >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "DATE(pm.created_at) <= ?";
        $params[] = $filter_date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Conta total de registros
    $count_query = "
        SELECT COUNT(*) as total
        FROM product_movements pm
        LEFT JOIN products p ON pm.product_id = p.id
        LEFT JOIN users u ON pm.user_id = u.id
        {$where_clause}
    ";
    
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Busca os registros da página atual
    $offset = ($page - 1) * $per_page;
    
    $movements_query = "
        SELECT 
            pm.*,
            p.name as product_name,
            p.category,
            u.username
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
    
    // Lista de produtos para filtro
    $products_stmt = $pdo->query("SELECT DISTINCT name FROM products ORDER BY name");
    $products_list = $products_stmt->fetchAll();
    
    // Lista de usuários para filtro
    $users_stmt = $pdo->query("SELECT DISTINCT username FROM users ORDER BY username");
    $users_list = $users_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar dados: ' . $e->getMessage();
    $movements = [];
    $products_list = [];
    $users_list = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<!-- ========================================
     CABEÇALHO DA PÁGINA
     ======================================== -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-history me-2"></i>
        Histórico de Movimentações
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="product_movement.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>
                Nova Movimentação
            </a>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportCSV()">
                <i class="fas fa-download me-1"></i>
                Exportar CSV
            </button>
        </div>
    </div>
</div>

<!-- ========================================
     FILTROS
     ======================================== -->
<div class="card card-custom mb-4">
    <div class="card-header card-header-custom">
        <i class="fas fa-filter me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="product" class="form-label form-label-custom">Produto</label>
                        <input type="text" 
                               class="form-control form-control-custom" 
                               id="product" 
                               name="product" 
                               value="<?php echo htmlspecialchars($filter_product); ?>"
                               placeholder="Nome do produto">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="type" class="form-label form-label-custom">Tipo</label>
                        <select class="form-select form-control-custom" id="type" name="type">
                            <option value="">Todos</option>
                            <option value="entrada" <?php echo $filter_type === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                            <option value="saida" <?php echo $filter_type === 'saida' ? 'selected' : ''; ?>>Saída</option>
                            <option value="ajuste" <?php echo $filter_type === 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="user" class="form-label form-label-custom">Usuário</label>
                        <input type="text" 
                               class="form-control form-control-custom" 
                               id="user" 
                               name="user" 
                               value="<?php echo htmlspecialchars($filter_user); ?>"
                               placeholder="Nome do usuário">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="date_from" class="form-label form-label-custom">Data Inicial</label>
                        <input type="date" 
                               class="form-control form-control-custom" 
                               id="date_from" 
                               name="date_from" 
                               value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="date_to" class="form-label form-label-custom">Data Final</label>
                        <input type="date" 
                               class="form-control form-control-custom" 
                               id="date_to" 
                               name="date_to" 
                               value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================
     RESULTADOS
     ======================================== -->
<div class="card card-custom">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-list me-2"></i>
            Movimentações (<?php echo number_format($total_records); ?> registros)
        </span>
        <?php if ($total_pages > 1): ?>
            <small class="text-muted">
                Página <?php echo $page; ?> de <?php echo $total_pages; ?>
            </small>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($movements)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhuma movimentação encontrada com os filtros aplicados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Estoque Anterior</th>
                            <th>Estoque Atual</th>
                            <th>Usuário</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y H:i', strtotime($movement['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($movement['product_name']); ?></strong>
                                    <?php if ($movement['category']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($movement['category']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $type_badges = [
                                        'entrada' => '<span class="badge bg-success"><i class="fas fa-arrow-up me-1"></i>Entrada</span>',
                                        'saida' => '<span class="badge bg-danger"><i class="fas fa-arrow-down me-1"></i>Saída</span>',
                                        'ajuste' => '<span class="badge bg-warning"><i class="fas fa-edit me-1"></i>Ajuste</span>'
                                    ];
                                    echo $type_badges[$movement['movement_type']] ?? '';
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($movement['quantity']); ?></strong>
                                </td>
                                <td>
                                    <?php echo number_format($movement['previous_quantity']); ?>
                                </td>
                                <td>
                                    <strong class="<?php 
                                        echo $movement['new_quantity'] > $movement['previous_quantity'] ? 'text-success' : 
                                             ($movement['new_quantity'] < $movement['previous_quantity'] ? 'text-danger' : 'text-warning'); 
                                    ?>">
                                        <?php echo number_format($movement['new_quantity']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($movement['username'] ?? 'Sistema'); ?></small>
                                </td>
                                <td>
                                    <?php if ($movement['reason']): ?>
                                        <small><?php echo htmlspecialchars($movement['reason']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- ========================================
                 PAGINAÇÃO
                 ======================================== -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================
     JAVASCRIPT ESPECÍFICO DA PÁGINA
     ======================================== -->
<script>
function exportCSV() {
    // Constrói URL com filtros atuais
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    // Cria link temporário para download
    const link = document.createElement('a');
    link.href = 'export_movements.php?' + params.toString();
    link.download = 'movimentacoes_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include 'includes/footer.php'; ?>

