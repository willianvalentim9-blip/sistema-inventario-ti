<?php
// ========================================
// PÁGINA DE VISUALIZAÇÃO DE MÁQUINA PRONTA (VERSÃO CORRIGIDA E FINAL)
// ========================================
require_once '../../config.php';
requireLogin();

// Verifica se a página está sendo carregada em um modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

$page_title = 'Visualizar Máquina Pronta';
$machine_id = intval($_GET['id'] ?? 0);

if ($machine_id <= 0) {
    if (!$is_modal) header('Location: ready_machines.php');
    exit('ID de máquina inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();
    if (!$machine) {
        if (!$is_modal) header('Location: ready_machines.php');
        exit('Máquina não encontrada.');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar máquina: " . $e->getMessage());
    if (!$is_modal) header('Location: ready_machines.php');
    exit('Erro de banco de dados.');
}

// Funções de ajuda
function getMachineStatusText($status) { $texts = ['available' => 'Disponível para Venda', 'sold' => 'Vendida', 'reserved' => 'Reservada', 'maintenance' => 'Em Manutenção', 'testing' => 'Em Teste']; return $texts[$status] ?? ucfirst($status); }
function getMachineStatusBadgeClass($status) { $classes = ['available' => 'bg-success', 'sold' => 'bg-secondary', 'reserved' => 'bg-warning text-dark', 'maintenance' => 'bg-info text-dark', 'testing' => 'bg-primary']; return $classes[$status] ?? 'bg-secondary'; }

if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-desktop me-2"></i> <?php echo htmlspecialchars($machine['name']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="ready_machines.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        </div>
        <div class="btn-group">
            <button type="button" onclick="openActionModal('edit_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Editar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" class="btn btn-sm btn-primary-custom"><i class="fas fa-edit me-1"></i> Editar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-info-circle me-2"></i> Informações da Máquina</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary-custom">Status</h6>
                        <p class="mb-3"><span class="badge <?php echo getMachineStatusBadgeClass($machine['status']); ?>"><?php echo getMachineStatusText($machine['status']); ?></span></p>
                        <?php if ($machine['sale_price']): ?><h6 class="text-primary-custom">Preço de Venda</h6><p class="mb-3"><strong class="text-success fs-5">R$ <?php echo number_format($machine['sale_price'], 2, ',', '.'); ?></strong></p><?php endif; ?>
                        <h6 class="text-primary-custom">Data de Cadastro</h6><p class="mb-3"><i class="fas fa-calendar me-1"></i> <?php echo date('d/m/Y H:i', strtotime($machine['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($machine['serial_number'])): ?><h6 class="text-primary-custom">Número de Série</h6><p class="mb-3 font-monospace"><?php echo htmlspecialchars($machine['serial_number']); ?></p><?php endif; ?>
                         <h6 class="text-primary-custom">Compatibilidade Windows</h6>
                        <p class="mb-3">
                            <?php if ($machine['windows_10_compatible']): ?><span class="badge bg-info text-dark me-1"><i class="fab fa-windows me-1"></i>Windows 10</span><?php endif; ?>
                            <?php if ($machine['windows_11_compatible']): ?><span class="badge bg-primary"><i class="fab fa-windows me-1"></i>Windows 11</span><?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($machine['description'])): ?><hr><h6 class="text-primary-custom">Descrição</h6><p class="mb-0"><?php echo nl2br(htmlspecialchars($machine['description'])); ?></p><?php endif; ?>
            </div>
        </div>
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-microchip me-2"></i> Componentes</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?php if (!empty($machine['processor'])): ?><div class="mb-3"><h6 class="text-primary-custom"><i class="fas fa-microchip me-1"></i> Processador</h6><p><?php echo htmlspecialchars($machine['processor']); ?></p></div><?php endif; ?>
                        <?php if (!empty($machine['memory'])): ?><div class="mb-3"><h6 class="text-primary-custom"><i class="fas fa-memory me-1"></i> Memória RAM</h6><p><?php echo htmlspecialchars($machine['memory']); ?></p></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($machine['storage'])): ?><div class="mb-3"><h6 class="text-primary-custom"><i class="fas fa-hdd me-1"></i> Armazenamento</h6><p><?php echo htmlspecialchars($machine['storage']); ?></p></div><?php endif; ?>
                        <?php if (!empty($machine['graphics'])): ?><div class="mb-3"><h6 class="text-primary-custom"><i class="fas fa-tv me-1"></i> Placa de Vídeo</h6><p><?php echo htmlspecialchars($machine['graphics']); ?></p></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-shield-alt me-2"></i> Garantia</div>
            <div class="card-body">
                <?php 
                    $has_warranty = $machine['has_warranty'] ?? 0;
                    if ($has_warranty): 
                        $warranty_provider = $machine['warranty_provider'] ?? 'Não informado';
                        $warranty_period = $machine['warranty_period_value'] ?? 'Não definido';
                        $warranty_unit = $machine['warranty_period_unit'] ?? '';
                ?>
                    <p class="mb-3">
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Com Garantia</span>
                    </p>
                    <?php if (!empty($warranty_provider)): ?>
                        <h6 class="text-primary-custom">Fornecedor</h6>
                        <p class="mb-3"><i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($warranty_provider); ?></p>
                    <?php endif; ?>
                    <?php if ($warranty_period && $warranty_unit): ?>
                        <h6 class="text-primary-custom">Período</h6>
                        <p class="mb-3"><i class="fas fa-calendar me-1"></i> <?php echo $warranty_period; ?> <?php echo $warranty_unit; ?></p>
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
            <div class="card-header card-header-custom"><i class="fas fa-image me-2"></i> Imagem da Máquina</div>
            <div class="card-body text-center">
                <?php if (!empty($machine['image']) && file_exists('uploads/machines/' . $machine['image'])): ?>
                    <img src="../../uploads/machines/<?php echo htmlspecialchars($machine["image"]); ?>" alt="<?php echo htmlspecialchars($machine["name"]); ?>" class="img-fluid rounded mb-3" style="max-height: 200px; object-fit: cover;">
                    <p class="file-name-text"><?php echo htmlspecialchars($machine["image"]); ?></p>
                <?php else: ?>
                    <div class="py-4">
                        <i class="fas fa-desktop fa-5x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma imagem cadastrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_modal) {
    include '../../includes/footer.php';
}
?>