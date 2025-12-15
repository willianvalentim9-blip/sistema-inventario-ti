<?php
require_once '../../config.php';
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

// ===== NOVO: Sempre filtrar itens deletados =====
$where_conditions[] = "(is_deleted = FALSE OR is_deleted IS NULL)";

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
    
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE (is_deleted = FALSE OR is_deleted IS NULL) AND category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $categories_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
    $products = []; $categories = []; $total_products = 0; $total_pages = 0;
}
?>

<?php include '../../includes/header.php'; ?>

<?php
// O sistema de flash message existente já exibirá o alerta aqui
if (isset($_SESSION["flash_message"])) {
    echo 
    '<div class="alert alert-' . htmlspecialchars($_SESSION["flash_type"]) . ' alert-dismissible fade show" role="alert">
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
            <a href="scanner.php" class="btn btn-sm btn-secondary-custom"><i class="fas fa-qrcode me-1"></i> Scanner</a>
        </div>

        <div class="btn-group">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-download me-1"></i> Exportar Dados
            </button>
        </div>
    </div>
</div>


<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Produto, fabricante, modelo, código de barras, série..." value="<?php echo htmlspecialchars($search); ?>">
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
                    <a href="modules/barcode/barcode_print.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline-info" data-bs-toggle="tooltip" title="Imprimir Código de Barras" target="_blank"><i class="fas fa-barcode"></i></a>
                    <button type="button" class="btn btn-outline-success" onclick="openStockInModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>, <?php echo $product['max_quantity'] ?? 0; ?>)" data-bs-toggle="tooltip" title="Dar Entrada"><i class="fas fa-plus-circle"></i></button>
                    <button type="button" class="btn btn-outline-warning" onclick="openStockOutModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>)" data-bs-toggle="tooltip" title="Dar Baixa"><i class="fas fa-minus-circle"></i></button>
                    <button type="button" class="btn btn-outline-danger" onclick="openDeleteModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Excluir"><i class="fas fa-trash"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="openHistoryModal('product_history_view.php?id=<?php echo $product['id']; ?>&modal=true', 'Histórico: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Ver Histórico"><i class="fas fa-history"></i></button>
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
    <?php if (!empty($products)): ?>
        <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card card-custom h-100 product-card shadow-sm border-0">
                    <!-- Imagem/Ícone -->
                    <div class="card-img-container position-relative">
                        <?php if (!empty($product['image'])): ?>
                            <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="product-icon-placeholder" style="display: none; height: 180px; align-items: center; justify-content: center;">
                                <i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-3x text-muted"></i>
                            </div>
                        <?php else: ?>
                            <div class="product-icon-placeholder d-flex align-items-center justify-content-center" style="height: 180px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                                <i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-3x text-white opacity-75"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badges no topo -->
                        <div class="position-absolute top-0 start-0 w-100 p-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="badge bg-dark text-light shadow-sm">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category']); ?>
                                </span>
                                <span class="badge <?php echo getStatusBadgeClass($product['status']); ?> shadow-sm">
                                    <?php echo getStatusText($product['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Corpo do Card -->
                    <div class="card-body p-3">
                        <!-- Nome -->
                        <h6 class="card-title text-primary-custom mb-2 fw-bold" style="min-height: 40px;">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h6>

                        <!-- Detalhes -->
                        <div class="product-details small mb-3">
                            <?php if (!empty($product['model'])): ?>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-cube text-muted me-2" style="width: 14px;"></i>
                                    <strong class="me-1">Modelo:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($product['model']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($product['manufacturer'])): ?>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-industry text-muted me-2" style="width: 14px;"></i>
                                    <strong class="me-1">Fabricante:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($product['manufacturer']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($product['serial_number'])): ?>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-barcode text-muted me-2" style="width: 14px;"></i>
                                    <strong class="me-1">N° Série:</strong>
                                    <span class="text-muted small"><?php echo htmlspecialchars($product['serial_number']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Estoque e Preço -->
                        <div class="d-flex justify-content-between align-items-center mb-2 py-2 border-top border-bottom">
                            <div>
                                <small class="text-muted d-block">Estoque</small>
                                <span class="badge <?php echo getQuantityBadgeClass($product['quantity'], $product['min_quantity']); ?> fs-6">
                                    <?php echo $product['quantity']; ?> un
                                </span>
                            </div>
                            <?php if ($product['price'] > 0): ?>
                            <div class="text-end">
                                <small class="text-muted d-block">Preço</small>
                                <strong class="text-success">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer com Ações -->
                    <div class="card-footer bg-light border-0 p-2">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="openActionModal('view_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="openActionModal('edit_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="openStockInModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>, <?php echo $product['max_quantity'] ?? 0; ?>)" data-bs-toggle="tooltip" title="Entrada">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="openStockOutModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['quantity']; ?>)" data-bs-toggle="tooltip" title="Baixa">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="openDeleteModal('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="openHistoryModal('product_history_view.php?id=<?php echo $product['id']; ?>&modal=true', 'Histórico: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" data-bs-toggle="tooltip" title="Histórico">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
<?php else: ?><div class="text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><h5 class="text-muted">Nenhum produto encontrado</h5><p class="text-muted">Tente uma busca diferente ou adicione novos produtos.</p><a href="add_product.php" class="btn btn-primary-custom"><i class="fas fa-plus me-1"></i> Adicionar Produto</a></div><?php endif; ?>
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

<!-- Modal de Exportação -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-download me-2"></i> Exportar Dados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-4">Selecione o tipo de dados que deseja exportar em formato CSV:</p>
                
                <!-- Produtos -->
                <div class="card border-success cursor-pointer export-card" onclick="downloadExport('products')">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-3x text-success mb-2"></i>
                        <h6 class="card-title mt-2">Todos os Produtos</h6>
                        <p class="card-text small text-muted mb-2">
                            Exporta: ID, Nome, Categoria, Fabricante, Modelo, Série, Código de Barras, Quantidade, Preço, Status, Garantia e mais
                        </p>
                        <small class="text-muted d-block">
                            📊 <?php 
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
                                $count = $stmt->fetch()['total'];
                                echo "$count registros";
                            } catch (Exception $e) {
                                echo "- registros";
                            }
                            ?>
                        </small>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Formato:</strong> CSV (Excel compatível) • <strong>Encoding:</strong> UTF-8 • <strong>Separador:</strong> Ponto e vírgula (;)
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para download de exportação
function downloadExport(type) {
    const url = `export_warranties_csv.php?type=${type}`;
    const link = document.createElement('a');
    link.href = url;
    link.click();
    
    // Fechar modal após clique
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    if (modal) {
        modal.hide();
    }
}

// Estilo para cards de exportação
const style = document.createElement('style');
style.innerHTML = `
    .export-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .export-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .export-card i {
        transition: all 0.3s ease;
    }
    
    .export-card:hover i {
        transform: scale(1.1);
    }
    
    .cursor-pointer {
        cursor: pointer;
    }
`;
if (document.head) {
    document.head.appendChild(style);
}

// ========================================
// BUSCA AUTOMÁTICA AO DIGITAR (sem precisar clicar em Filtrar)
// ========================================
const searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const productRows = document.querySelectorAll('tbody tr');
        let visibleCount = 0;

        productRows.forEach(row => {
            // Pega o texto de todas as células da linha
            const rowText = row.textContent.toLowerCase();

            // Se o texto da linha contém o termo de busca, mostra; senão, esconde
            if (rowText.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mostra mensagem se nenhum produto encontrado
        const noResults = document.getElementById('no-results-message');
        if (noResults) {
            noResults.remove();
        }

        if (visibleCount === 0 && searchTerm !== '') {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                const tr = document.createElement('tr');
                tr.id = 'no-results-message';
                tr.innerHTML = `
                    <td colspan="100%" class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum produto encontrado para "${searchTerm}"</h5>
                        <p class="text-muted">Tente buscar por outro termo</p>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>