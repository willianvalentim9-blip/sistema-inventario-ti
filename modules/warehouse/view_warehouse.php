<?php
// ========================================
// PÁGINA DE VISUALIZAÇÃO DE ITEM DO ARMAZÉM (VERSÃO COMPLETA)
// ========================================
require_once '../../config.php';
requireLogin();

// Verifica se a página está sendo carregada em um modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

$page_title = 'Visualizar Item do Armazém';
$warehouse_id = intval($_GET['id'] ?? 0);

if ($warehouse_id <= 0) {
    if (!$is_modal) header('Location: warehouse.php');
    exit('ID de item inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$warehouse_id]);
    $warehouse = $stmt->fetch();
    if (!$warehouse) {
        if (!$is_modal) header('Location: warehouse.php');
        exit('Item do armazém não encontrado.');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar item do armazém: " . $e->getMessage());
    if (!$is_modal) header('Location: warehouse.php');
    exit('Erro de banco de dados.');
}

// Funções de formatação
function getCategoryIcon($category) {
    $icons = [
        'Servidor' => 'fa-server',
        'Switch' => 'fa-network-wired',
        'No-Break' => 'fa-battery-full',
        'Roteador' => 'fa-router',
        'Firewall' => 'fa-shield-alt',
        'Storage' => 'fa-database',
        'Rack' => 'fa-cube',
        'Cable' => 'fa-ethernet',
        'Ferramenta' => 'fa-wrench',
        'Acessório' => 'fa-puzzle-piece',
        'Equipamento' => 'fa-microchip',
        'Outro' => 'fa-box-open'
    ];
    return $icons[$category] ?? 'fa-box';
}

function getStatusText($status) {
    $texts = [
        'available' => 'Disponível',
        'unavailable' => 'Indisponível',
        'reserved' => 'Reservado'
    ];
    return $texts[$status] ?? ucfirst($status);
}

function getStatusBadgeClass($status) {
    $classes = [
        'available' => 'bg-success',
        'unavailable' => 'bg-danger',
        'reserved' => 'bg-warning text-dark'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

function getQuantityBadgeClass($quantity, $min_quantity) {
    if ($quantity <= 0) return 'bg-danger';
    if ($quantity <= $min_quantity) return 'bg-warning text-dark';
    return 'bg-success';
}

if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2 text-primary-custom">
        <i class="fas <?php echo getCategoryIcon($warehouse['category']); ?> me-2"></i>
        <?php echo htmlspecialchars($warehouse['name']); ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="warehouse.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary-custom" onclick="openActionModal('edit_warehouse.php?id=<?php echo $warehouse['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($warehouse['name'])); ?>')">
                <i class="fas fa-edit me-1"></i>
                Editar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Informações Gerais -->
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-info-circle me-2"></i>
                Informações do Item
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary-custom">Nome do Item</h6>
                        <p class="mb-3"><strong><?php echo htmlspecialchars($warehouse['name']); ?></strong></p>

                        <h6 class="text-primary-custom">Categoria</h6>
                        <p class="mb-3"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($warehouse['category']); ?></span></p>

                        <?php if (!empty($warehouse['manufacturer'])): ?>
                        <h6 class="text-primary-custom">Fabricante</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($warehouse['manufacturer']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($warehouse['model'])): ?>
                        <h6 class="text-primary-custom">Modelo</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($warehouse['model']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary-custom">Quantidade em Estoque</h6>
                        <p class="mb-3">
                            <span class="badge <?php echo getQuantityBadgeClass($warehouse['quantity'], $warehouse['min_quantity']); ?> fs-6">
                                <?php echo number_format($warehouse['quantity']); ?> unidades
                            </span>
                        </p>

                        <h6 class="text-primary-custom">Preço</h6>
                        <p class="mb-3">
                            <?php if ($warehouse['price'] > 0): ?>
                                <strong class="text-success">R$ <?php echo number_format($warehouse['price'], 2, ',', '.'); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </p>

                        <h6 class="text-primary-custom">Status</h6>
                        <p class="mb-3">
                            <span class="badge <?php echo getStatusBadgeClass($warehouse['status']); ?>">
                                <?php echo getStatusText($warehouse['status']); ?>
                            </span>
                        </p>

                        <h6 class="text-primary-custom">Níveis de Estoque</h6>
                        <p class="mb-3">
                            <small class="text-muted">
                                Mínimo: <?php echo $warehouse['min_quantity']; ?> | 
                                Máximo: <?php echo $warehouse['max_quantity']; ?>
                            </small>
                        </p>
                    </div>
                </div>

                <?php if (!empty($warehouse['description'])): ?>
                <hr>
                <h6 class="text-primary-custom">Descrição</h6>
                <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($warehouse['description'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($warehouse['notes'])): ?>
                <hr>
                <h6 class="text-primary-custom">Observações</h6>
                <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($warehouse['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Identificadores -->
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-barcode me-2"></i>
                Identificadores
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($warehouse['serial_number'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary-custom">Número de Série</h6>
                        <p class="font-monospace"><?php echo htmlspecialchars($warehouse['serial_number']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($warehouse['sku'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary-custom">SKU</h6>
                        <p class="font-monospace"><?php echo htmlspecialchars($warehouse['sku']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($warehouse['barcode'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary-custom">Código de Barras</h6>
                        <p class="font-monospace"><?php echo htmlspecialchars($warehouse['barcode']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($warehouse['qr_code'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary-custom">Código QR</h6>
                        <p class="font-monospace"><?php echo htmlspecialchars($warehouse['qr_code']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Localização -->
        <?php if (!empty($warehouse['location'])): ?>
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-map-marker-alt me-2"></i>
                Localização
            </div>
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($warehouse['location']); ?></strong></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Coluna Lateral -->
    <div class="col-lg-4">
        <!-- Imagem -->
        <?php if (!empty($warehouse['image'])): ?>
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-image me-2"></i>
                Imagem
            </div>
            <div class="card-body p-0">
                <img src="<?php echo htmlspecialchars($warehouse['image']); ?>" alt="<?php echo htmlspecialchars($warehouse['name']); ?>" class="img-fluid w-100" style="max-height: 300px; object-fit: contain;">
            </div>
        </div>
        <?php endif; ?>

        <!-- Informações de Garantia -->
        <?php if ($warehouse['has_warranty']): ?>
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-shield-alt me-2"></i>
                Informações de Garantia
            </div>
            <div class="card-body">
                <?php if (!empty($warehouse['warranty_provider'])): ?>
                <h6 class="text-primary-custom">Fornecedor</h6>
                <p class="mb-3"><?php echo htmlspecialchars($warehouse['warranty_provider']); ?></p>
                <?php endif; ?>

                <?php if (!empty($warehouse['warranty_start_date'])): ?>
                <h6 class="text-primary-custom">Data de Início</h6>
                <p class="mb-3">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo date('d/m/Y', strtotime($warehouse['warranty_start_date'])); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($warehouse['warranty_end_date'])): ?>
                <h6 class="text-primary-custom">Data de Término</h6>
                <p class="mb-3">
                    <i class="fas fa-calendar-check me-1"></i>
                    <?php echo date('d/m/Y', strtotime($warehouse['warranty_end_date'])); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($warehouse['warranty_period_value']) && !empty($warehouse['warranty_period_unit'])): ?>
                <h6 class="text-primary-custom">Período de Garantia</h6>
                <p class="mb-0">
                    <?php 
                    $unit_text = [
                        'days' => 'dias',
                        'months' => 'meses',
                        'years' => 'anos'
                    ];
                    echo $warehouse['warranty_period_value'] . ' ' . ($unit_text[$warehouse['warranty_period_unit']] ?? $warehouse['warranty_period_unit']);
                    ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informações do Sistema -->
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-clock me-2"></i>
                Informações do Sistema
            </div>
            <div class="card-body">
                <h6 class="text-primary-custom">Data de Criação</h6>
                <p class="mb-3">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo isset($warehouse['created_at']) ? date('d/m/Y H:i', strtotime($warehouse['created_at'])) : 'Não registrado'; ?>
                </p>

                <h6 class="text-primary-custom">Última Atualização</h6>
                <p class="mb-3">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo isset($warehouse['updated_at']) ? date('d/m/Y H:i', strtotime($warehouse['updated_at'])) : 'Não registrado'; ?>
                </p>

                <h6 class="text-primary-custom">Criado Por</h6>
                <p class="mb-0">
                    <?php 
                    if (!empty($warehouse['created_by'])) {
                        try {
                            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                            $stmt->execute([$warehouse['created_by']]);
                            $user = $stmt->fetch();
                            echo htmlspecialchars($user['full_name'] ?? 'Usuário deletado');
                        } catch (Exception $e) {
                            echo 'Erro ao buscar usuário';
                        }
                    } else {
                        echo 'Sistema';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Ações -->
        <?php if (!$is_modal && $_SESSION['user_role'] !== 'user'): ?>
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-cog me-2"></i>
                Ações
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-primary-custom w-100 mb-2" onclick="openActionModal('give_warehouse_stock_in.php?id=<?php echo $warehouse['id']; ?>&modal=true', 'Entrada: <?php echo htmlspecialchars(addslashes($warehouse['name'])); ?>')">
                    <i class="fas fa-plus me-1"></i>
                    Entrada (Stock In)
                </button>
                <button type="button" class="btn btn-sm btn-warning text-dark w-100 mb-2" onclick="openActionModal('give_warehouse_stock_out.php?id=<?php echo $warehouse['id']; ?>&modal=true', 'Saída: <?php echo htmlspecialchars(addslashes($warehouse['name'])); ?>')">
                    <i class="fas fa-minus me-1"></i>
                    Saída (Stock Out)
                </button>
                <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="openActionModal('warehouse_history_view.php?id=<?php echo $warehouse['id']; ?>&modal=true', 'Histórico: <?php echo htmlspecialchars(addslashes($warehouse['name'])); ?>')">
                    <i class="fas fa-history me-1"></i>
                    Histórico de Movimentações
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_modal): ?>
<?php include '../../includes/footer.php'; ?>
<?php endif; ?>
