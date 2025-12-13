<?php
/**
 * PÁGINA DE ENTRADAS
 *
 * Layout com abas: Produtos | Máquinas | Armazém
 * Design igual do warranties.php
 */

require_once '../../config.php';
requireLogin();

$page_title = 'Entradas';
$pdo = getConnection();

// Parâmetros
$tab = $_GET['tab'] ?? 'produtos'; // produtos, maquinas, armazem
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

// Filtros
$search = $_GET['search'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

try {
    // ========================================
    // FUNÇÃO: Buscar movimentações
    // ========================================
    function getMovements($pdo, $movement_type, $search = '', $date_from = '', $date_to = '', $page = 1, $per_page = 15) {
        $where = [];
        $params = [];
        
        // Tipo de movimento
        $where[] = "pm.movement_type = ?";
        $params[] = $movement_type;
        
        // Busca
        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR p.manufacturer LIKE ? OR p.model LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Datas
        if (!empty($date_from)) {
            $where[] = "DATE(pm.movement_date) >= ?";
            $params[] = $date_from;
        }
        if (!empty($date_to)) {
            $where[] = "DATE(pm.movement_date) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM product_movements pm
            LEFT JOIN products p ON pm.product_id = p.id
            LEFT JOIN users u ON pm.user_id = u.id
            WHERE {$where_clause}
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Dados
        $offset = ($page - 1) * $per_page;
        $sql = "
            SELECT 
                pm.*,
                p.name as product_name,
                p.category,
                p.manufacturer,
                u.full_name,
                u.username
            FROM product_movements pm
            LEFT JOIN products p ON pm.product_id = p.id
            LEFT JOIN users u ON pm.user_id = u.id
            WHERE {$where_clause}
            ORDER BY pm.movement_date DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'movements' => $movements,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    }
    
    // ========================================
    // BUSCAR DADOS
    // ========================================
    $data = getMovements($pdo, 'entrada', $search, $filter_date_from, $filter_date_to, $page, $per_page);
    
    // Contar totais por tipo
    $count_query = "SELECT COUNT(*) as total FROM product_movements WHERE movement_type = 'entrada'";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute();
    $total_count = $count_stmt->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Erro em movementations_entrada.php: " . $e->getMessage());
    $data = ['movements' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
    $total_count = 0;
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-arrow-circle-down me-2 text-success"></i>Entradas
    </h1>
</div>

<!-- FILTROS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-control form-control-custom" placeholder="Nome, fabricante..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">De:</label>
                        <input type="date" name="date_from" class="form-control form-control-custom" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Até:</label>
                        <input type="date" name="date_to" class="form-control form-control-custom" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ABAS: Produtos | Máquinas | Armazém -->
<ul class="nav nav-tabs mb-4" id="movementationTabs">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'produtos' ? 'active' : ''; ?>" href="?tab=produtos">
            <i class="fas fa-box me-2"></i> Produtos (<?php echo $total_count; ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'maquinas' ? 'active' : ''; ?>" href="?tab=maquinas">
            <i class="fas fa-desktop me-2"></i> Máquinas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'armazem' ? 'active' : ''; ?>" href="?tab=armazem">
            <i class="fas fa-warehouse me-2"></i> Armazém
        </a>
    </li>
</ul>

<!-- CONTEÚDO DAS ABAS -->
<div class="tab-content">
    <div class="tab-pane fade <?php echo $tab === 'produtos' ? 'show active' : ''; ?>">
        <?php if (empty($data['movements'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Nenhuma entrada registrada
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Fabricante</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Motivo</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['movements'] as $mov): ?>
                        <tr>
                            <td>
                                <small><?php echo date('d/m/Y H:i', strtotime($mov['movement_date'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($mov['product_name'] ?? 'N/A'); ?></strong>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($mov['manufacturer'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <small class="badge bg-primary"><?php echo htmlspecialchars($mov['category'] ?? ''); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-success">+<?php echo $mov['quantity']; ?></span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($mov['reason'] ?? '', 0, 50)); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($mov['full_name'] ?? $mov['username'] ?? 'Sistema'); ?></small>
                            </td>
                            <td>
                                <a href="product_history_view.php?id=<?php echo $mov['product_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver histórico">
                                    <i class="fas fa-history"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINAÇÃO -->
            <?php if ($data['pages'] > 1): ?>
            <nav aria-label="Paginação">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- ABA MÁQUINAS -->
    <div class="tab-pane fade <?php echo $tab === 'maquinas' ? 'show active' : ''; ?>">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Movimentações de máquinas em desenvolvimento
        </div>
    </div>
    
    <!-- ABA ARMAZÉM -->
    <div class="tab-pane fade <?php echo $tab === 'armazem' ? 'show active' : ''; ?>">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Movimentações de armazém em desenvolvimento
        </div>
    </div>
</div>

<style>
.nav-tabs {
    border-bottom: 3px solid #dee2e6;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}

.nav-tabs .nav-link.active {
    color: #007bff !important;
    border-bottom-color: #007bff !important;
    background-color: transparent;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}
</style>

<?php include '../../includes/footer.php'; ?>
