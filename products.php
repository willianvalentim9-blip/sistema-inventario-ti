<?php
require_once 'config.php';
requireLogin();

$page_title = 'Produtos';

// Parâmetros de busca e filtro
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$view_mode = $_GET['view'] ?? 'table';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    // Adiciona a busca por código de barras, qr code e número de série
    $where_conditions[] = "(name LIKE ? OR model LIKE ? OR manufacturer LIKE ? OR description LIKE ? OR barcode = ? OR qr_code = ? OR serial_number = ?)";
    $search_like = "%$search%";
    $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like]);
    $params = array_merge($params, [$search, $search, $search]);
}

if (!empty($category_filter)) { $where_conditions[] = "category = ?"; $params[] = $category_filter; }
if (!empty($status_filter)) { $where_conditions[] = "status = ?"; $params[] = $status_filter; }
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $pdo = getConnection();
    $count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];

    // ========================================
    // *** INÍCIO DA CORREÇÃO ***
    // ========================================
    // Se uma busca foi feita (com texto ou filtros) e não retornou resultados
    if ($total_products === 0 && (!empty($search) || !empty($category_filter) || !empty($status_filter))) {
        // Define uma mensagem flash de erro para o usuário
        $searchTerm = !empty($search) ? $search : 'filtros aplicados';
        $_SESSION['flash_message'] = "Nenhum produto encontrado para a busca: <strong>" . htmlspecialchars($searchTerm) . "</strong>. Os filtros foram resetados.";
        $_SESSION['flash_type'] = 'danger'; // Classe 'danger' para o alerta vermelho

        // Redireciona para a página de produtos sem os parâmetros de busca para limpar os filtros
        header('Location: products.php');
        exit;
    }
    // ========================================
    // *** FIM DA CORREÇÃO ***
    // ========================================

    $total_pages = ceil($total_products / $per_page);
    
    $sql = "SELECT * FROM products $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $categories_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
    $products = []; $categories = []; $total_products = 0; $total_pages = 0;
}
?>

<?php include 'includes/header.php'; ?>

<?php
// O sistema de flash message existente já exibirá o alerta aqui
if (isset($_SESSION["flash_message"])) {
    echo 
    '<div class="alert alert-' . $_SESSION["flash_type"] . ' alert-dismissible fade show" role="alert">
        ' . $_SESSION["flash_message"] . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION["flash_message"]);
    unset($_SESSION["flash_type"]);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-boxes me-2"></i> Produtos <span class="badge badge-custom-secondary ms-2"><?php echo number_format($total_products); ?></span></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_product.php" class="btn btn-sm btn-primary-custom"><i class="fas fa-plus me-1"></i> Novo Produto</a>
        </div>
        
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#quickStockInModal">
                <i class="fas fa-bolt me-1"></i> Entrada Expressa
            </button>
        </div>

        <div class="btn-group">
            <a href="scanner.php" class="btn btn-sm btn-secondary-custom"><i class="fas fa-qrcode me-1"></i> Scanner</a>
        </div>
    </div>
</div>


<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Nome, modelo, código de barras..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3"><label for="category" class="form-label form-label-custom"><i class="fas fa-folder me-1"></i> Categoria</label><select class="form-select form-control-custom" id="category" name="category"><option value="">Todas</option><?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label for="status" class="form-label form-label-custom"><i class="fas fa-flag me-1"></i> Status</label><select class="form-select form-control-custom" id="status" name="status"><option value="">Todos</option><option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Disponível</option><option value="in_use" <?php echo $status_filter === 'in_use' ? 'selected' : ''; ?>>Em Uso</option><option value="defective" <?php echo $status_filter === 'defective' ? 'selected' : ''; ?>>Defeituoso</option><option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option></select></div>
            <div class="col-md-2"><label class="form-label">&nbsp;</label><div class="d-grid"><button type="submit" class="btn btn-primary-custom"><i class="fas fa-search me-1"></i> Filtrar</button></div></div>
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
        </form>
        <?php if (!empty($search) || !empty($category_filter) || !empty($status_filter)): ?><div class="mt-3"><a href="products.php?view=<?php echo htmlspecialchars($view_mode); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times me-1"></i> Limpar Filtros</a></div><?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><span class="text-muted">Mostrando <?php echo min($offset + 1, $total_products); ?> - <?php echo min($offset + $per_page, $total_products); ?> de <?php echo number_format($total_products); ?> produtos</span></div>
    <div class="btn-group" role="group">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'table'])); ?>" class="btn btn-sm <?php echo $view_mode === 'table' ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>"><i class="fas fa-table me-1"></i> Tabela</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'cards'])); ?>" class="btn btn-sm <?php echo $view_mode === 'cards' ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>"><i class="fas fa-th-large me-1"></i> Cards</a>
    </div>
</div>

<?php
function getCategoryIcon($category) { $icons = ['CPU' => 'fa-microchip', 'RAM' => 'fa-memory', 'SSD' => 'fa-hdd', 'HDD' => 'fa-hdd', 'GPU' => 'fa-tv', 'Motherboard' => 'fa-microchip', 'PSU' => 'fa-plug', 'Case' => 'fa-cube', 'Cable' => 'fa-ethernet', 'Monitor' => 'fa-desktop', 'Keyboard' => 'fa-keyboard', 'Mouse' => 'fa-mouse', 'Network' => 'fa-network-wired', 'Other' => 'fa-box-open']; return $icons[$category] ?? 'fa-box'; }
function getStatusBadgeClass($status) { $classes = ['available' => 'bg-success', 'in_use' => 'bg-warning text-dark', 'defective' => 'bg-danger', 'maintenance' => 'bg-info']; return $classes[$status] ?? 'bg-secondary'; }
function getStatusText($status) { $texts = ['available' => 'Disponível', 'in_use' => 'Em Uso', 'defective' => 'Defeituoso', 'maintenance' => 'Manutenção']; return $texts[$status] ?? ucfirst($status); }

function getQuantityBadgeClass($quantity, $min_quantity) {
    if ($quantity <= 0) {
        return 'bg-danger';
    }
    if ($min_quantity > 0 && $quantity <= $min_quantity) {
        return 'bg-warning text-dark';
    }
    return 'bg-success';
}
?>

<?php if ($view_mode === 'table'): ?>
    <?php if (!empty($products)): ?>
    <div class="card card-custom"><div class="card-body p-0">
        <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-header-custom">
            <tr><th>Produto</th><th>Categoria</th><th>Fabricante</th><th>Quantidade</th><th>Preço</th><th>Status</th><th>Ações</th></tr>
        </thead><tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><div class="d-flex align-items-center"><div class="product-icon me-3"><i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-2x text-primary-custom"></i></div><div><h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6><?php if (!empty($product['model'])): ?><small class="text-muted"><?php echo htmlspecialchars($product['model']); ?></small><?php endif; ?></div></div></td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category']); ?></span></td>
                <td><?php echo htmlspecialchars($product['manufacturer'] ?: '-'); ?></td>
                <td><span class="badge <?php echo getQuantityBadgeClass($product['quantity'], $product['min_quantity']); ?>"><?php echo $product['quantity']; ?></span></td>
                <td><?php if ($product['price']): ?><strong class="text-success">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></strong><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                <td><span class="badge <?php echo getStatusBadgeClass($product['status']); ?>"><?php echo getStatusText($product['status']); ?></span></td>
                <td><div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary" onclick="openActionModal('view_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Visualizar"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="openActionModal('edit_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Editar"><i class="fas fa-edit"></i></button>
                    <a href="barcode_print.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline-info" data-bs-toggle="tooltip" title="Imprimir Código de Barras" target="_blank"><i class="fas fa-barcode"></i></a>
                    <button type="button" class="btn btn-outline-success" onclick="openStockInModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>, <?php echo $product['max_quantity'] ?? 0; ?>)" data-bs-toggle="tooltip" title="Dar Entrada"><i class="fas fa-plus-circle"></i></button>
                    <button type="button" class="btn btn-outline-warning" onclick="openStockOutModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>)" data-bs-toggle="tooltip" title="Dar Baixa"><i class="fas fa-minus-circle"></i></button>
                    <button type="button" class="btn btn-outline-danger" onclick="openDeleteModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Excluir"><i class="fas fa-trash"></i></button>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php else: ?>
        <div class="text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><h5 class="text-muted">Nenhum produto encontrado</h5><p class="text-muted">Tente uma busca diferente ou adicione novos produtos.</p><a href="add_product.php" class="btn btn-primary-custom"><i class="fas fa-plus me-1"></i> Adicionar Produto</a></div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($view_mode === 'cards'): ?>
    <?php if (!empty($products)): ?><div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-xl-2 col-lg-2 col-md-6 mb-4"><div class="card card-custom h-100 product-card">
                <?php if (!empty($product['image'])): ?><img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="product-icon-placeholder" style="display: none; height: 300px; align-items: center; justify-content: center;"><i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-4x text-muted"></i></div><?php else: ?><div class="product-icon-placeholder d-flex align-items-center justify-content-center"><i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-4x text-muted"></i></div><?php endif; ?>
                <div class="card-body d-flex flex-column"><div class="d-flex justify-content-between align-items-start mb-2"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category']); ?></span><span class="badge <?php echo getStatusBadgeClass($product['status']); ?>"><?php echo getStatusText($product['status']); ?></span></div><h6 class="card-title text-primary-custom mb-1"><?php echo htmlspecialchars($product['name']); ?></h6><?php if (!empty($product['model'])): ?><p class="text-muted small mb-2"><?php echo htmlspecialchars($product['model']); ?></p><?php endif; ?><div class="mt-auto"><div class="d-flex justify-content-between align-items-center mt-2"><div><small class="text-muted">Estoque:</small><br>
                <span class="badge <?php echo getQuantityBadgeClass($product['quantity'], $product['min_quantity']); ?>"><?php echo $product['quantity']; ?></span>
                </div><?php if ($product['price'] > 0): ?><div><small class="text-muted">Preço:</small><br><strong class="text-success">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></strong></div><?php endif; ?></div></div></div>
                <div class="card-footer bg-transparent"><div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openActionModal('view_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Visualizar"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openActionModal('edit_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Editar"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="openStockInModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>, <?php echo $product['max_quantity'] ?? 0; ?>)" title="Dar Entrada"><i class="fas fa-plus-circle"></i></button>
                    <a href="barcode_print.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline-info btn-sm" target="_blank" title="Código de Barras"><i class="fas fa-barcode"></i></a>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="openStockOutModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>)" title="Dar Baixa"><i class="fas fa-minus-circle"></i></button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="openDeleteModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Excluir"><i class="fas fa-trash"></i></button>
                </div></div>
            </div></div>
        <?php endforeach; ?>
    </div><?php else: ?><div class="text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><h5 class="text-muted">Nenhum produto encontrado</h5><p class="text-muted">Tente uma busca diferente ou adicione novos produtos.</p><a href="add_product.php" class="btn btn-primary-custom"><i class="fas fa-plus me-1"></i> Adicionar Produto</a></div><?php endif; ?>
<?php endif; ?>

<?php if ($total_pages > 1): ?>
    <nav aria-label="Paginação de produtos" class="mt-4"><ul class="pagination justify-content-center">
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
    </ul></nav>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>