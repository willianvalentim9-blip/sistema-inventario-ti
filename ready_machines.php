<?php
// ========================================
// PÁGINA DE LISTAGEM DE MÁQUINAS PRONTAS (VERSÃO FINAL CORRIGIDA)
// ========================================
require_once 'config.php';
requireLogin();

$page_title = 'Máquinas';

// Parâmetros de busca e filtros
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$windows_filter = $_GET['windows'] ?? '';
$price_min = floatval($_GET['price_min'] ?? 0);
$price_max = floatval($_GET['price_max'] ?? 0);
$view_mode = $_GET['view'] ?? 'table'; // 'table' ou 'cards'
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Constrói a query com base nos filtros
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR processor LIKE ? OR description LIKE ? OR specifications LIKE ? OR serial_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}
if (!empty($status_filter)) { 
    $where_conditions[] = "status = ?"; 
    $params[] = $status_filter; 
}
if (!empty($windows_filter)) {
    if ($windows_filter === 'windows_10') $where_conditions[] = "windows_10_compatible = 1";
    elseif ($windows_filter === 'windows_11') $where_conditions[] = "windows_11_compatible = 1";
}
if ($price_min > 0) { 
    $where_conditions[] = "sale_price >= ?"; 
    $params[] = $price_min; 
}
if ($price_max > 0) { 
    $where_conditions[] = "sale_price <= ?"; 
    $params[] = $price_max; 
}
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $pdo = getConnection();
    
    // Contagem total para paginação
    $count_sql = "SELECT COUNT(*) as total FROM ready_machines $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_machines = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_machines / $per_page);
    
    // Busca das máquinas para a página atual
    $sql = "SELECT * FROM ready_machines $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $machines = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar máquinas: " . $e->getMessage());
    $machines = []; 
    $total_machines = 0; 
    $total_pages = 0;
}

// Funções de ajuda para badges e ícones
function getMachineStatusBadgeClass($status) { $classes = ['available' => 'bg-success', 'sold' => 'bg-secondary', 'reserved' => 'bg-warning text-dark', 'maintenance' => 'bg-info text-dark', 'testing' => 'bg-primary', 'removed' => 'bg-danger']; return $classes[$status] ?? 'bg-secondary'; }
function getMachineStatusText($status) { $texts = ['available' => 'Disponível', 'sold' => 'Vendida/Esgotada', 'reserved' => 'Reservada', 'maintenance' => 'Manutenção', 'testing' => 'Em Teste', 'removed' => 'Removida']; return $texts[$status] ?? ucfirst($status); }
function getQuantityBadgeClass($quantity) { if ($quantity <= 0) return 'bg-danger'; if ($quantity < 3) return 'bg-warning text-dark'; return 'bg-success'; }

?>

<?php include 'includes/header.php'; ?>

<?php
// Bloco para exibir mensagens de feedback (sucesso/erro)
if (isset($_SESSION["flash_message"])) {
    $alert_type = $_SESSION["flash_type"] ?? 'info';
    $icon = $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    echo 
    '<div class="alert alert-' . htmlspecialchars($alert_type) . ' alert-dismissible fade show" role="alert">
        <i class="fas ' . $icon . ' me-2"></i>
        ' . htmlspecialchars($_SESSION["flash_message"]) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION["flash_message"]);
    unset($_SESSION["flash_type"]);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-desktop me-2"></i> Máquinas<span class="badge badge-custom-secondary ms-2"><?php echo number_format($total_machines); ?></span></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2"><a href="add_machine.php" class="btn btn-sm btn-primary-custom"><i class="fas fa-plus me-1"></i> Nova Máquina</a></div>
        <div class="btn-group"><a href="scanner.php" class="btn btn-sm btn-secondary-custom"><i class="fas fa-qrcode me-1"></i> Scanner</a></div>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-2">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Nome, processador..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label form-label-custom"><i class="fas fa-flag me-1"></i> Status</label>
                <select class="form-select form-control-custom" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="available" <?php if($status_filter === 'available') echo 'selected'; ?>>Disponível</option>
                    <option value="sold" <?php if($status_filter === 'sold') echo 'selected'; ?>>Vendida/Esgotada</option>
                    <option value="reserved" <?php if($status_filter === 'reserved') echo 'selected'; ?>>Reservada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="windows" class="form-label form-label-custom"><i class="fab fa-windows me-1"></i> Windows</label>
                <select class="form-select form-control-custom" id="windows" name="windows">
                    <option value="">Todos</option>
                    <option value="windows_10" <?php if($windows_filter === 'windows_10') echo 'selected'; ?>>Windows 10</option>
                    <option value="windows_11" <?php if($windows_filter === 'windows_11') echo 'selected'; ?>>Windows 11</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="price_min" class="form-label form-label-custom"><i class="fas fa-dollar-sign me-1"></i> Preço Mín</label>
                <input type="number" class="form-control form-control-custom" id="price_min" name="price_min" placeholder="R$" value="<?php echo $price_min > 0 ? $price_min : ''; ?>">
            </div>
            <div class="col-md-2">
                <label for="price_max" class="form-label form-label-custom"><i class="fas fa-dollar-sign me-1"></i> Preço Máx</label>
                <input type="number" class="form-control form-control-custom" id="price_max" name="price_max" placeholder="R$" value="<?php echo $price_max > 0 ? $price_max : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-search me-1"></i> Filtrar</button>
                </div>
            </div>
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
        </form>
         <?php if (!empty($search) || !empty($status_filter) || !empty($windows_filter) || $price_min > 0 || $price_max > 0): ?>
            <div class="mt-3">
                <a href="ready_machines.php?view=<?php echo htmlspecialchars($view_mode); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><span class="text-muted">Mostrando <?php echo min($offset + 1, $total_machines); ?> - <?php echo min($offset + $per_page, $total_machines); ?> de <?php echo number_format($total_machines); ?> máquinas</span></div>
    <div class="btn-group" role="group">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'table'])); ?>" class="btn btn-sm <?php echo $view_mode === 'table' ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>"><i class="fas fa-table me-1"></i> Tabela</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'cards'])); ?>" class="btn btn-sm <?php echo $view_mode === 'cards' ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>"><i class="fas fa-th-large me-1"></i> Cards</a>
    </div>
</div>

<?php if (empty($machines)): ?>
    <div class="text-center py-5">
        <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhuma máquina encontrada</h5>
        <p>Use o botão "Nova Máquina" para adicionar a primeira.</p>
        <a href="add_machine.php" class="btn btn-primary-custom mt-2"><i class="fas fa-plus me-1"></i> Adicionar Máquina</a>
    </div>
<?php else: ?>
    <?php if ($view_mode === 'table'): ?>
        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-header-custom">
                            <tr><th>Máquina</th><th>Especificações</th><th>Quantidade</th><th>Preço Venda</th><th>Status</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($machines as $machine): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="machine-icon me-3"><i class="fas fa-desktop fa-2x text-primary-custom"></i></div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($machine['name']); ?></h6>
                                            <small class="text-muted">SN: <?php echo htmlspecialchars($machine['serial_number'] ?: 'N/A'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><small><?php echo htmlspecialchars($machine['processor'] . ' / ' . $machine['memory']); ?></small></td>
                                <td><span class="badge <?php echo getQuantityBadgeClass($machine['quantity']); ?> fs-6"><?php echo $machine['quantity']; ?></span></td>
                                <td><?php if ($machine['sale_price']): ?><strong class="text-success">R$ <?php echo number_format($machine['sale_price'], 2, ',', '.'); ?></strong><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                <td><span class="badge <?php echo getMachineStatusBadgeClass($machine['status']); ?>"><?php echo getMachineStatusText($machine['status']); ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="openActionModal('view_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Visualizar"><i class="fas fa-eye"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="openActionModal('edit_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Editar"><i class="fas fa-edit"></i></button>
                                        <a href="barcode_print.php?code=<?php echo htmlspecialchars($machine['barcode'] ?: $machine['serial_number'] ?: 'M'.$machine['id']); ?>" class="btn btn-outline-info" data-bs-toggle="tooltip" title="Imprimir Código de Barras" target="_blank"><i class="fas fa-barcode"></i></a>
                                        <button type="button" class="btn btn-outline-success" onclick="openMachineStockInModal(<?php echo $machine['id']; ?>, '<?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Dar Entrada"><i class="fas fa-plus-circle"></i></button>
                                        <button type="button" class="btn btn-outline-warning" onclick="openActionModal('machine_stock_out.php?id=<?php echo $machine['id']; ?>', 'Dar Baixa: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Dar Baixa" <?php echo $machine['quantity'] <= 0 ? 'disabled' : ''; ?>><i class="fas fa-minus-circle"></i></button>
                                        <button type="button" class="btn btn-outline-danger" onclick="openDeleteModal('machine', <?php echo $machine['id']; ?>, '<?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Excluir"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($view_mode === 'cards'): ?>
        <div class="row">
            <?php foreach ($machines as $machine): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card card-custom h-100 product-card">
                        <?php
                        $image_path = 'uploads/machines/' . htmlspecialchars($machine['image'] ?? '');
                        $image_exists = !empty($machine['image']) && file_exists($image_path);
                        ?>
                        <?php if ($image_exists): ?>
                            <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($machine['name']); ?>" style="height: 180px; object-fit: cover;">
                        <?php else: ?>
                            <div class="product-icon-placeholder d-flex align-items-center justify-content-center" style="height: 180px; background-color: #f8f9fa;">
                                <i class="fas fa-desktop fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title text-primary-custom mb-1 flex-grow-1"><?php echo htmlspecialchars($machine['name']); ?></h6>
                                <span class="badge <?php echo getMachineStatusBadgeClass($machine['status']); ?> ms-2"><?php echo getMachineStatusText($machine['status']); ?></span>
                            </div>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($machine['processor']); ?></p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        <small class="text-muted">Estoque:</small><br>
                                        <span class="badge <?php echo getQuantityBadgeClass($machine['quantity']); ?> fs-6"><?php echo $machine['quantity']; ?></span>
                                    </div>
                                    <?php if ($machine['sale_price'] > 0): ?>
                                        <div>
                                            <small class="text-muted">Preço:</small><br>
                                            <strong class="text-success">R$ <?php echo number_format($machine['sale_price'], 2, ',', '.'); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openActionModal('view_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Visualizar"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openActionModal('edit_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Editar"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="openMachineStockInModal(<?php echo $machine['id']; ?>, '<?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Dar Entrada"><i class="fas fa-plus-circle"></i></button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="openActionModal('machine_stock_out.php?id=<?php echo $machine['id']; ?>', 'Dar Baixa: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" <?php echo $machine['quantity'] <= 0 ? 'disabled' : ''; ?> title="Dar Baixa"><i class="fas fa-minus-circle"></i></button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="openDeleteModal('machine', <?php echo $machine['id']; ?>, '<?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Excluir"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação de máquinas" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"><i class="fas fa-angle-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif; ?>
                <?php $start_page = max(1, $page - 2); $end_page = min($total_pages, $page + 2); for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i class="fas fa-angle-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>