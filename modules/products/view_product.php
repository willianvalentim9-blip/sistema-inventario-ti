<?php
// ========================================
// PÁGINA DE VISUALIZAÇÃO DE PRODUTO (VERSÃO COMPLETA E CORRIGIDA)
// ========================================
require_once '../../config.php';
requireLogin();

// Verifica se a página está sendo carregada em um modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

$page_title = 'Visualizar Produto';
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    if (!$is_modal) header('Location: products.php');
    exit('ID de produto inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        if (!$is_modal) header('Location: products.php');
        exit('Produto não encontrado.');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar produto: " . $e->getMessage());
    if (!$is_modal) header('Location: products.php');
    exit('Erro de banco de dados.');
}

// Funções de formatação para esta página
function getCategoryIcon($category) { $icons = ['CPU' => 'fa-microchip', 'RAM' => 'fa-memory', 'SSD' => 'fa-hdd', 'HDD' => 'fa-hdd', 'GPU' => 'fa-tv', 'Motherboard' => 'fa-microchip', 'PSU' => 'fa-plug', 'Case' => 'fa-cube', 'Cable' => 'fa-ethernet', 'Monitor' => 'fa-desktop', 'Keyboard' => 'fa-keyboard', 'Mouse' => 'fa-mouse', 'Network' => 'fa-network-wired', 'Other' => 'fa-box-open']; return $icons[$category] ?? 'fa-box'; }
function getStatusText($status) { $texts = ['available' => 'Disponível', 'in_use' => 'Em Uso', 'defective' => 'Defeituoso', 'maintenance' => 'Manutenção']; return $texts[$status] ?? ucfirst($status); }
function getStatusBadgeClass($status) { $classes = ['available' => 'bg-success', 'in_use' => 'bg-info text-dark', 'defective' => 'bg-danger', 'maintenance' => 'bg-warning text-dark']; return $classes[$status] ?? 'bg-secondary'; }
function getQuantityBadgeClass($quantity, $min_quantity) { if ($quantity <= 0) return 'bg-danger'; if ($quantity <= $min_quantity) return 'bg-warning text-dark'; return 'bg-success'; }


if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2 text-primary-custom">
        <i class="fas <?php echo getCategoryIcon($product['category']); ?> me-2"></i>
        <?php echo htmlspecialchars($product['name']); ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="products.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        </div>
        <div class="btn-group">
             <button type="button" class="btn btn-sm btn-primary-custom" onclick="openActionModal('edit_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')">
                <i class="fas fa-edit me-1"></i> Editar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-info-circle me-2"></i> Informações do Produto</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary-custom">Nome do Produto</h6><p class="mb-3"><strong><?php echo htmlspecialchars($product['name']); ?></strong></p>
                        <h6 class="text-primary-custom">Categoria</h6><p class="mb-3"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category']); ?></span></p>
                        <?php if (!empty($product['manufacturer'])): ?><h6 class="text-primary-custom">Fabricante</h6><p class="mb-3"><?php echo htmlspecialchars($product['manufacturer']); ?></p><?php endif; ?>
                        <?php if (!empty($product['model'])): ?><h6 class="text-primary-custom">Modelo</h6><p class="mb-3"><?php echo htmlspecialchars($product['model']); ?></p><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary-custom">Quantidade em Estoque</h6>
                        <p class="mb-3"><span class="badge <?php echo getQuantityBadgeClass($product['quantity'], $product['min_quantity']); ?> fs-6"><?php echo number_format($product['quantity']); ?> unidades</span></p>
                        
                        <h6 class="text-primary-custom">Preço</h6>
                        <p class="mb-3">
                            <?php if ($product['price'] > 0): ?>
                                <strong class="text-success">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </p>

                        <h6 class="text-primary-custom">Status</h6><p class="mb-3"><span class="badge <?php echo getStatusBadgeClass($product['status']); ?>"><?php echo getStatusText($product['status']); ?></span></p>
                        
                        <h6 class="text-primary-custom">Níveis de Estoque</h6>
                        <p class="mb-3">
                            <small class="text-muted">Mínimo: <?php echo $product['min_quantity']; ?> | Máximo: <?php echo $product['max_quantity']; ?></small>
                        </p>
                    </div>
                </div>
                <?php if (!empty($product['description'])): ?><hr><h6 class="text-primary-custom">Descrição</h6><p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p><?php endif; ?>
                 <?php if (!empty($product['notes'])): ?><hr><h6 class="text-primary-custom">Observações</h6><p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($product['notes'])); ?></p><?php endif; ?>
            </div>
             <div class="card-footer bg-light">
                <small class="text-muted">Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?> | Última atualização: <?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?></small>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-shield-alt me-2"></i> Garantia</div>
            <div class="card-body">
                <?php 
                    $has_warranty = $product['has_warranty'] ?? 0;
                    if ($has_warranty): 
                        $warranty_provider = $product['warranty_provider'] ?? 'Não informado';
                        $warranty_period = $product['warranty_period_value'] ?? 'Não definido';
                        $warranty_unit = $product['warranty_period_unit'] ?? '';
                ?>
                    <p class="mb-3">
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Com Garantia</span>
                    </p>
                    <?php if (!empty($warranty_provider)): ?>
                        <p class="mb-3"><i class="fas fa-building me-1"></i> <strong><?php echo htmlspecialchars($warranty_provider); ?></strong></p>
                    <?php endif; ?>
                    
                    <?php if ($warranty_period && $warranty_unit): ?>
                        <p class="mb-3"><i class="fas fa-calendar me-1"></i> <?php echo $warranty_period; ?> <?php echo htmlspecialchars($warranty_unit); ?></p>
                    <?php endif; ?>
                    
                    <a href="warranties.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-external-link-alt me-1"></i>Ver Todas as Garantias</a>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-times-circle me-1"></i>Sem garantia registrada
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-image me-2"></i> Imagem do Produto</div>
            <div class="card-body text-center">
                <?php if (!empty($product['image'])): ?>
                    <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid rounded mb-3" style="max-height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div class="py-4">
                        <i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-5x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma imagem cadastrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-qrcode me-2"></i> Códigos e Localização</div>
            <div class="card-body">
                <?php if (!empty($product['serial_number'])): ?><h6 class="text-primary-custom">Nº de Série</h6><p class="font-monospace text-muted"><?php echo htmlspecialchars($product['serial_number']); ?></p><?php endif; ?>
                <?php if (!empty($product['barcode'])): ?><h6 class="text-primary-custom">Código de Barras</h6><p class="font-monospace text-muted"><?php echo htmlspecialchars($product['barcode']); ?></p><?php endif; ?>
                <?php if (!empty($product['qr_code'])): ?><h6 class="text-primary-custom">Código QR</h6><p class="font-monospace text-muted"><?php echo htmlspecialchars($product['qr_code']); ?></p><?php endif; ?>
                <?php if (!empty($product['location'])): ?><hr><h6 class="text-primary-custom">Localização</h6><p class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($product['location']); ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_modal) {
    include '../../includes/footer.php';
}
?>