<?php
/**
 * PÁGINA DE MOVIMENTAÇÕES
 *
 * Mostra movimentações (entrada/saída) com filtros separados por tipo
 */

require_once '../../config.php';
requireLogin();

$page_title = 'Movimentações';
$pdo = getConnection();

// Parâmetros
$movement_type = $_GET['type'] ?? 'entrada'; // entrada ou saida
$item_type = $_GET['item_type'] ?? 'produto'; // produto, maquina ou armazem
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

// Filtros
$search = $_GET['search'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

try {
    // ========================================
    // FUNÇÃO: Buscar movimentações de PRODUTOS
    // ========================================
    function getProductMovements($pdo, $movement_type, $search = '', $date_from = '', $date_to = '', $page = 1, $per_page = 15) {
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
    // FUNÇÃO: Buscar movimentações de MÁQUINAS
    // ========================================
    function getMachineMovements($pdo, $movement_type, $search = '', $date_from = '', $date_to = '', $page = 1, $per_page = 15) {
        $where = [];
        $params = [];
        
        // Tipo de movimento
        $where[] = "mm.movement_type = ?";
        $params[] = $movement_type;
        
        // Busca
        if (!empty($search)) {
            $where[] = "(m.name LIKE ? OR m.manufacturer LIKE ? OR m.model LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Datas
        if (!empty($date_from)) {
            $where[] = "DATE(mm.movement_date) >= ?";
            $params[] = $date_from;
        }
        if (!empty($date_to)) {
            $where[] = "DATE(mm.movement_date) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM machine_movements mm
            LEFT JOIN ready_machines m ON mm.machine_id = m.id
            WHERE {$where_clause}
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Dados
        $offset = ($page - 1) * $per_page;
        $sql = "
            SELECT 
                mm.*,
                m.name as product_name,
                m.manufacturer,
                m.model,
                u.full_name,
                u.username
            FROM machine_movements mm
            LEFT JOIN ready_machines m ON mm.machine_id = m.id
            LEFT JOIN users u ON mm.user_id = u.id
            WHERE {$where_clause}
            ORDER BY mm.movement_date DESC
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
    // FUNÇÃO: Buscar movimentações de ARMAZÉM
    // ========================================
    function getWarehouseMovements($pdo, $movement_type, $search = '', $date_from = '', $date_to = '', $page = 1, $per_page = 15) {
        $where = [];
        $params = [];
        
        // Tipo de movimento
        $where[] = "wm.movement_type = ?";
        $params[] = $movement_type;
        
        // Busca
        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR p.manufacturer LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Datas
        if (!empty($date_from)) {
            $where[] = "DATE(wm.movement_date) >= ?";
            $params[] = $date_from;
        }
        if (!empty($date_to)) {
            $where[] = "DATE(wm.movement_date) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM warehouse_movements wm
            LEFT JOIN warehouse_products p ON wm.product_id = p.id
            WHERE {$where_clause}
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Dados
        $offset = ($page - 1) * $per_page;
        $sql = "
            SELECT 
                wm.*,
                p.name as product_name,
                p.manufacturer,
                u.full_name,
                u.username
            FROM warehouse_movements wm
            LEFT JOIN warehouse_products p ON wm.product_id = p.id
            LEFT JOIN users u ON wm.user_id = u.id
            WHERE {$where_clause}
            ORDER BY wm.movement_date DESC
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
    // BUSCAR DADOS CONFORME TIPO
    // ========================================
    if ($item_type === 'maquina') {
        $data = getMachineMovements($pdo, $movement_type, $search, $filter_date_from, $filter_date_to, $page, $per_page);
    } elseif ($item_type === 'armazem') {
        $data = getWarehouseMovements($pdo, $movement_type, $search, $filter_date_from, $filter_date_to, $page, $per_page);
    } else {
        $data = getProductMovements($pdo, $movement_type, $search, $filter_date_from, $filter_date_to, $page, $per_page);
    }
    
} catch (Exception $e) {
    error_log("Erro em movementations.php: " . $e->getMessage());
    $data = ['movements' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <?php if ($movement_type === 'entrada'): ?>
            <i class="fas fa-arrow-circle-down me-2 text-success"></i>Entradas
        <?php else: ?>
            <i class="fas fa-arrow-circle-up me-2 text-danger"></i>Saídas
        <?php endif; ?>
    </h1>
</div>

<!-- ABAS DE TIPO DE ITEM -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $item_type === 'produto' ? 'active' : ''; ?>" href="movementations.php?type=<?php echo htmlspecialchars($movement_type); ?>&item_type=produto" role="tab">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $item_type === 'maquina' ? 'active' : ''; ?>" href="movementations.php?type=<?php echo htmlspecialchars($movement_type); ?>&item_type=maquina" role="tab">
                    <i class="fas fa-desktop me-2"></i>Máquinas
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $item_type === 'armazem' ? 'active' : ''; ?>" href="movementations.php?type=<?php echo htmlspecialchars($movement_type); ?>&item_type=armazem" role="tab">
                    <i class="fas fa-warehouse me-2"></i>Armazém
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- FILTROS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($movement_type); ?>">
                    <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type); ?>">
                    
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

<!-- TABELA DE MOVIMENTAÇÕES -->
<?php if (empty($data['movements'])): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Nenhuma movimentação registrada
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead class="table-light">
                <tr>
                    <th>Data/Hora</th>
                    <?php if ($item_type === 'produto'): ?>
                        <th>Produto</th>
                        <th>Fabricante</th>
                        <th>Categoria</th>
                    <?php elseif ($item_type === 'maquina'): ?>
                        <th>Máquina</th>
                        <th>Fabricante</th>
                        <th>Modelo</th>
                    <?php else: ?>
                        <th>Item Armazém</th>
                        <th>Fabricante</th>
                    <?php endif; ?>
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
                        <small>
                            <?php if ($item_type === 'produto' && isset($mov['category'])): ?>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($mov['category']); ?></span>
                            <?php elseif ($item_type === 'maquina' && isset($mov['model'])): ?>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($mov['model']); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <?php if ($movement_type === 'entrada'): ?>
                            <span class="badge bg-success">+<?php echo $mov['quantity']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger">-<?php echo $mov['quantity']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?php echo htmlspecialchars(substr($mov['reason'] ?? '', 0, 50)); ?></small>
                    </td>
                    <td>
                        <small><?php echo htmlspecialchars($mov['full_name'] ?? $mov['username'] ?? 'Sistema'); ?></small>
                    </td>
                    <td>
                        <?php if ($item_type === 'maquina'): ?>
                            <a href="machine_history_view.php?id=<?php echo $mov['machine_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver histórico">
                                <i class="fas fa-history"></i>
                            </a>
                        <?php else: ?>
                            <a href="product_history_view.php?id=<?php echo $mov['product_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver histórico">
                                <i class="fas fa-history"></i>
                            </a>
                        <?php endif; ?>
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
                <a class="page-link" href="movementations.php?type=<?php echo $movement_type; ?>&item_type=<?php echo $item_type; ?>&page=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}
</style>

<?php include '../../includes/footer.php'; ?>
