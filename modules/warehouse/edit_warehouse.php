<?php
// ========================================
// PÁGINA DE EDIÇÃO DE ITEM DO ARMAZÉM
// ========================================
require_once '../../config.php';
requireLogin();

// Verificação de acesso - apenas administrativos e admin
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'administrativo' && $_SESSION['user_role'] !== 'admin')) {
    $_SESSION['flash_message'] = 'Acesso negado! Apenas usuários administrativos podem acessar o armazém.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Item do Armazém';
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
    error_log("Erro ao buscar item do armazém para edição: " . $e->getMessage());
    if (!$is_modal) header('Location: warehouse.php');
    exit('Erro de banco de dados.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_data = $warehouse;
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '') ?: null;
    $barcode = trim($_POST['barcode'] ?? '') ?: null;
    $qr_code = trim($_POST['qr_code'] ?? '') ?: null;
    $sku = trim($_POST['sku'] ?? '') ?: null;
    $min_quantity = intval(preg_replace('/[^0-9]/', '', $_POST['min_quantity'] ?? '5'));
    $max_quantity = intval(preg_replace('/[^0-9]/', '', $_POST['max_quantity'] ?? '100'));
    $price = !empty($_POST['price']) ? floatval(str_replace(',', '.', str_replace('.', '', $_POST['price']))) : null;
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    $image_to_save = trim($_POST['uploaded_image'] ?? $warehouse['image']);
    $notes = trim($_POST['notes'] ?? '');

    // Campos de garantia
    $has_warranty = isset($_POST['has_warranty']) ? 1 : 0;
    $warranty_provider = trim($_POST['warranty_provider'] ?? '');
    $invoice_number = trim($_POST['invoice_number'] ?? '') ?: null;
    $warranty_period_value = !empty($_POST['warranty_period_value']) ? intval($_POST['warranty_period_value']) : null;
    $warranty_period_unit = trim($_POST['warranty_period_unit'] ?? '');
    $warranty_start_date = !empty($_POST['warranty_start_date']) ? trim($_POST['warranty_start_date']) : null;
    $warranty_end_date = !empty($_POST['warranty_end_date']) ? trim($_POST['warranty_end_date']) : null;
    $warranty_notes = trim($_POST['warranty_notes'] ?? '') ?: null;

    $current_quantity = $warehouse['quantity'];

    // Validações
    if (empty($name) || empty($category)) {
        $_SESSION['flash_message'] = 'Nome e Categoria são obrigatórios.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    if ($min_quantity < 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade mínima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    if ($max_quantity < 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade máxima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    if ($min_quantity == 1 && $max_quantity == 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade mínima e máxima não podem ser ambas 1. Defina valores diferentes para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    if ($min_quantity > $max_quantity && $max_quantity > 0) {
        $_SESSION['flash_message'] = 'A quantidade mínima (' . $min_quantity . ') não pode ser maior que a quantidade máxima (' . $max_quantity . ').';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    if ($max_quantity > 0 && $current_quantity > $max_quantity) {
        $_SESSION['flash_message'] = 'A quantidade máxima (' . $max_quantity . ') não pode ser menor que a quantidade atual em estoque (' . $current_quantity . '). O item está no limite máximo de estoque.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE warehouse SET
                name = ?, description = ?, image = ?, category = ?, manufacturer = ?,
                model = ?, serial_number = ?, barcode = ?, qr_code = ?, sku = ?,
                min_quantity = ?, max_quantity = ?, price = ?, location = ?, status = ?,
                notes = ?, has_warranty = ?, warranty_provider = ?, invoice_number = ?,
                warranty_period_value = ?, warranty_period_unit = ?, warranty_start_date = ?,
                warranty_end_date = ?, warranty_notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $name, $description, $image_to_save, $category, $manufacturer, $model,
            $serial_number, $barcode, $qr_code, $sku, $min_quantity, $max_quantity,
            $price, $location, $status, $notes,
            $has_warranty, $has_warranty ? $warranty_provider : null, $has_warranty ? $invoice_number : null,
            $has_warranty ? $warranty_period_value : null, $has_warranty ? $warranty_period_unit : null,
            $has_warranty ? $warranty_start_date : null, $has_warranty ? $warranty_end_date : null,
            $has_warranty ? $warranty_notes : null,
            $warehouse_id
        ]);

        $new_data_stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
        $new_data_stmt->execute([$warehouse_id]);
        $new_data = $new_data_stmt->fetch();

        logAdminActivity($_SESSION["user_id"], "UPDATE", "warehouse", $warehouse_id, $old_data, $new_data);

        // Histórico de garantia para warehouse
        if (function_exists('registerWarrantyHistory')) {
            registerWarrantyHistory($pdo, $warehouse_id, 'UPDATE', $old_data, $new_data, $_SESSION["user_id"], 'warehouse');
        }

        $pdo->commit();

        $_SESSION['flash_message'] = 'Item do armazém atualizado com sucesso!';
        $_SESSION['flash_type'] = 'success';

        header('Location: warehouse.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        $_SESSION['flash_message'] = 'Erro de banco de dados: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warehouse.php?id=' . $warehouse_id . '&modal=true');
        exit();
    }
}

if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<!-- Link CSS para Media Upload -->
<link rel="stylesheet" href="CSS/media-upload.css">

<?php if ($is_modal): ?>
<!-- Script necessário para modal (quando header não é carregado) -->
<script src="js/media-upload.js"></script>
<?php endif; ?>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-edit me-2"></i>
        Editar Item do Armazém
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="warehouse.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-warehouse me-2"></i>
                Editar Informações do Item
            </div>
            <div class="card-body">
                <form method="POST" action="edit_warehouse.php?id=<?php echo $warehouse['id']; ?>&modal=true" enctype="multipart/form-data" id="edit-warehouse-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label form-label-custom">
                                    <i class="fas fa-tag me-1"></i>
                                    Nome do Item *
                                </label>
                                <input type="text" class="form-control form-control-custom" id="name" name="name" placeholder="Ex: Servidor Dell PowerEdge R740" value="<?php echo htmlspecialchars($warehouse['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label form-label-custom">
                                    <i class="fas fa-folder me-1"></i>
                                    Categoria *
                                </label>
                                <select class="form-select form-control-custom" id="category" name="category" required>
                                    <option value="">Selecione uma categoria</option>
                                    <!-- Categorias de Servidor/Infraestrutura -->
                                    <optgroup label="Servidor &amp; Infraestrutura">
                                        <option value="Servidor" <?php echo ($warehouse['category'] ?? '') === 'Servidor' ? 'selected' : ''; ?>>Servidor</option>
                                        <option value="Switch" <?php echo ($warehouse['category'] ?? '') === 'Switch' ? 'selected' : ''; ?>>Switch</option>
                                        <option value="No-Break" <?php echo ($warehouse['category'] ?? '') === 'No-Break' ? 'selected' : ''; ?>>No-Break/UPS</option>
                                        <option value="Roteador" <?php echo ($warehouse['category'] ?? '') === 'Roteador' ? 'selected' : ''; ?>>Roteador</option>
                                        <option value="Firewall" <?php echo ($warehouse['category'] ?? '') === 'Firewall' ? 'selected' : ''; ?>>Firewall</option>
                                        <option value="Storage" <?php echo ($warehouse['category'] ?? '') === 'Storage' ? 'selected' : ''; ?>>Storage/NAS</option>
                                    </optgroup>
                                    <!-- Componentes PC -->
                                    <optgroup label="Componentes de PC">
                                        <option value="CPU" <?php echo ($warehouse['category'] ?? '') === 'CPU' ? 'selected' : ''; ?>>CPU/Processador</option>
                                        <option value="RAM" <?php echo ($warehouse['category'] ?? '') === 'RAM' ? 'selected' : ''; ?>>Memória RAM</option>
                                        <option value="SSD" <?php echo ($warehouse['category'] ?? '') === 'SSD' ? 'selected' : ''; ?>>SSD</option>
                                        <option value="HDD" <?php echo ($warehouse['category'] ?? '') === 'HDD' ? 'selected' : ''; ?>>HD/HDD</option>
                                        <option value="GPU" <?php echo ($warehouse['category'] ?? '') === 'GPU' ? 'selected' : ''; ?>>Placa de Vídeo</option>
                                        <option value="Motherboard" <?php echo ($warehouse['category'] ?? '') === 'Motherboard' ? 'selected' : ''; ?>>Placa Mãe</option>
                                        <option value="PSU" <?php echo ($warehouse['category'] ?? '') === 'PSU' ? 'selected' : ''; ?>>Fonte</option>
                                        <option value="Case" <?php echo ($warehouse['category'] ?? '') === 'Case' ? 'selected' : ''; ?>>Gabinete</option>
                                    </optgroup>
                                    <!-- Periféricos -->
                                    <optgroup label="Periféricos">
                                        <option value="Monitor" <?php echo ($warehouse['category'] ?? '') === 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                                        <option value="Keyboard" <?php echo ($warehouse['category'] ?? '') === 'Keyboard' ? 'selected' : ''; ?>>Teclado</option>
                                        <option value="Mouse" <?php echo ($warehouse['category'] ?? '') === 'Mouse' ? 'selected' : ''; ?>>Mouse</option>
                                        <option value="Network" <?php echo ($warehouse['category'] ?? '') === 'Network' ? 'selected' : ''; ?>>Rede</option>
                                    </optgroup>
                                    <!-- Acessórios e Ferramentas -->
                                    <optgroup label="Acessórios &amp; Ferramentas">
                                        <option value="Rack" <?php echo ($warehouse['category'] ?? '') === 'Rack' ? 'selected' : ''; ?>>Rack</option>
                                        <option value="Cable" <?php echo ($warehouse['category'] ?? '') === 'Cable' ? 'selected' : ''; ?>>Cabo</option>
                                        <option value="Ferramenta" <?php echo ($warehouse['category'] ?? '') === 'Ferramenta' ? 'selected' : ''; ?>>Ferramenta</option>
                                        <option value="Acessório" <?php echo ($warehouse['category'] ?? '') === 'Acessório' ? 'selected' : ''; ?>>Acessório</option>
                                        <option value="Equipamento" <?php echo ($warehouse['category'] ?? '') === 'Equipamento' ? 'selected' : ''; ?>>Equipamento</option>
                                    </optgroup>
                                    <!-- Outros -->
                                    <option value="Other" <?php echo ($warehouse['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Outros</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="manufacturer" class="form-label form-label-custom">
                                    <i class="fas fa-industry me-1"></i>
                                    Fabricante
                                </label>
                                <input type="text" class="form-control form-control-custom" id="manufacturer" name="manufacturer" placeholder="Ex: Dell, Cisco, HP" value="<?php echo htmlspecialchars($warehouse['manufacturer'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="model" class="form-label form-label-custom">
                                    <i class="fas fa-barcode me-1"></i>
                                    Modelo
                                </label>
                                <input type="text" class="form-control form-control-custom" id="model" name="model" placeholder="Ex: PowerEdge R740" value="<?php echo htmlspecialchars($warehouse['model'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label form-label-custom">
                                    <i class="fas fa-align-left me-1"></i>
                                    Descrição
                                </label>
                                <textarea class="form-control form-control-custom" id="description" name="description" rows="3" placeholder="Descrição detalhada do item..."><?php echo htmlspecialchars($warehouse['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label form-label-custom"><i class="fas fa-camera me-1"></i>Imagem do Item</label>
                                <div class="media-upload-container" id="warehouse-upload-container">
                                    <div class="media-upload-placeholder" style="<?php echo !empty($warehouse['image']) ? 'display: none;' : ''; ?>">
                                        <div class="media-upload-placeholder-content">
                                            <span class="media-upload-placeholder-icon">
                                                <i class="fas fa-image"></i>
                                            </span>
                                            <div class="media-upload-placeholder-title">Adicionar Imagem</div>
                                            <div class="media-upload-placeholder-subtitle">Escolha uma fonte</div>
                                            <div class="media-upload-actions">
                                                <button type="button" class="media-upload-btn" data-action="gallery">
                                                    <i class="fas fa-images"></i>
                                                    Galeria
                                                </button>
                                                <button type="button" class="media-upload-btn" data-action="camera">
                                                    <i class="fas fa-camera"></i>
                                                    Câmera
                                                </button>
                                            </div>
                                            <div class="media-upload-drag-hint">
                                                <i class="fas fa-hand-point-up"></i>
                                                Ou arraste aqui
                                            </div>
                                        </div>
                                    </div>
                                    <div class="media-upload-preview" style="<?php echo empty($warehouse['image']) ? 'display: none;' : ''; ?>">
                                        <img class="media-upload-preview-image" src="<?php echo !empty($warehouse['image']) ? 'uploads/warehouse/' . htmlspecialchars($warehouse['image']) : ''; ?>" alt="Preview">
                                        <div class="media-upload-preview-overlay">
                                            <button type="button" class="media-upload-action-btn" data-action="change" title="Trocar imagem">
                                                <i class="fas fa-camera"></i>
                                            </button>
                                            <button type="button" class="media-upload-action-btn danger" data-action="remove" title="Remover imagem">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($warehouse['image']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label form-label-custom">
                                    <i class="fas fa-hashtag me-1"></i>
                                    Número de Série
                                </label>
                                <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number" placeholder="Ex: SN123456789" value="<?php echo htmlspecialchars($warehouse['serial_number'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="sku" class="form-label form-label-custom">
                                    <i class="fas fa-box me-1"></i>
                                    SKU
                                </label>
                                <input type="text" class="form-control form-control-custom" id="sku" name="sku" placeholder="Ex: WH-SKU-001" value="<?php echo htmlspecialchars($warehouse['sku'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="barcode" class="form-label form-label-custom">
                                    <i class="fas fa-barcode me-1"></i>
                                    Código de Barras
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-custom" id="barcode" name="barcode" placeholder="Ex: 1234567890123" value="<?php echo htmlspecialchars($warehouse['barcode'] ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" data-bs-toggle="tooltip" title="Gerar código automaticamente">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="qr_code" class="form-label form-label-custom">
                                    <i class="fas fa-qrcode me-1"></i>
                                    Código QR
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code" placeholder="Ex: QR123456789" value="<?php echo htmlspecialchars($warehouse['qr_code'] ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" data-bs-toggle="tooltip" title="Gerar código QR automaticamente">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="min_quantity" class="form-label form-label-custom">
                                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                    Qtd. Mínima *
                                </label>
                                <input type="number" class="form-control form-control-custom" id="min_quantity" name="min_quantity" min="1" value="<?php echo htmlspecialchars($warehouse['min_quantity'] ?? 5); ?>" placeholder="Mínimo: 1" required>
                                <div class="form-text" id="min-quantity-help">Quantidade mínima para alerta de estoque baixo (mínimo: 1)</div>
                                <div class="invalid-feedback" id="min-quantity-error" style="display: none;">
                                    ⚠️ Valor mínimo necessário para salvar: 1
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="max_quantity" class="form-label form-label-custom">
                                    <i class="fas fa-chart-line me-1 text-info"></i>
                                    Qtd. Máxima *
                                </label>
                                <input type="number" class="form-control form-control-custom" id="max_quantity" name="max_quantity" min="1" value="<?php echo htmlspecialchars($warehouse['max_quantity'] ?? 100); ?>" placeholder="Mínimo: 1" required>
                                <div class="form-text" id="max-quantity-help">Quantidade máxima permitida no estoque (mínimo: 1)</div>
                                <div class="invalid-feedback" id="max-quantity-error" style="display: none;">
                                    ⚠️ Valor mínimo necessário para salvar: 1
                                </div>
                                <div class="invalid-feedback" id="max-quantity-stock-error" style="display: none;">
                                    ⚠️ A quantidade máxima não pode ser menor que o estoque atual (<?php echo $warehouse['quantity']; ?> unidades)
                                </div>
                                <input type="hidden" id="current_stock_quantity" value="<?php echo $warehouse['quantity']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label form-label-custom">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    Preço (R$)
                                </label>
                                <input type="text" class="form-control form-control-custom" id="price" name="price" placeholder="0,00" value="<?php echo !empty($warehouse['price']) ? number_format($warehouse['price'], 2, ',', '.') : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label form-label-custom">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    Localização
                                </label>
                                <input type="text" class="form-control form-control-custom" id="location" name="location" placeholder="Ex: Prateleira A1, Corredor 3" value="<?php echo htmlspecialchars($warehouse['location'] ?? ''); ?>">
                                <div class="form-text">Localização física no armazém</div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label form-label-custom">
                                    <i class="fas fa-flag me-1"></i>
                                    Status
                                </label>
                                <select class="form-select form-control-custom" id="status" name="status">
                                    <option value="available" <?php echo ($warehouse['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Disponível</option>
                                    <option value="unavailable" <?php echo ($warehouse['status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Indisponível</option>
                                    <option value="reserved" <?php echo ($warehouse['status'] ?? '') === 'reserved' ? 'selected' : ''; ?>>Reservado</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label form-label-custom">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    Observações
                                </label>
                                <textarea class="form-control form-control-custom" id="notes" name="notes" rows="3" placeholder="Observações adicionais..."><?php echo htmlspecialchars($warehouse['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <!-- BOTÃO DE GARANTIA (PADRÃO PRODUTO) -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="warehouse_has_warranty"
                                               name="has_warranty" value="1" <?php echo ($warehouse['has_warranty'] ?? 0) ? 'checked' : ''; ?>
                                               style="width: 3em; height: 1.5em;">
                                        <label class="form-check-label" for="warehouse_has_warranty">
                                            <strong>Com Garantia</strong>
                                        </label>
                                    </div>
                                    <a href="../warranties/warranties.php" class="btn btn-sm btn-outline-info" id="editWarehouseWarrantyBtn"
                                       <?php echo ($warehouse['has_warranty'] ?? 0) ? '' : 'style="display: none;"'; ?>>
                                        <i class="fas fa-shield-alt me-1"></i>Editar Garantia
                                    </a>
                                </div>

                                <!-- CAMPOS OCULTOS PARA GARANTIA -->
                                <input type="hidden" id="warranty_provider" name="warranty_provider" value="<?php echo htmlspecialchars($warehouse['warranty_provider'] ?? ''); ?>">
                                <input type="hidden" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($warehouse['invoice_number'] ?? ''); ?>">
                                <input type="hidden" id="warranty_period_value" name="warranty_period_value" value="<?php echo htmlspecialchars($warehouse['warranty_period_value'] ?? ''); ?>">
                                <input type="hidden" id="warranty_period_unit" name="warranty_period_unit" value="<?php echo htmlspecialchars($warehouse['warranty_period_unit'] ?? 'months'); ?>">
                                <input type="hidden" id="warranty_start_date" name="warranty_start_date" value="<?php echo htmlspecialchars($warehouse['warranty_start_date'] ?? ''); ?>">
                                <input type="hidden" id="warranty_end_date" name="warranty_end_date" value="<?php echo htmlspecialchars($warehouse['warranty_end_date'] ?? ''); ?>">
                                <input type="hidden" id="warranty_notes" name="warranty_notes" value="<?php echo htmlspecialchars($warehouse['warranty_notes'] ?? ''); ?>">

                                <!-- RESUMO VISUAL -->
                                <div id="warranty-summary-warehouse" class="alert alert-light border border-info p-2 mt-2 <?php echo ($warehouse['has_warranty'] ?? 0) ? '' : 'd-none'; ?>">
                                    <small class="text-muted d-block mb-1"><i class="fas fa-shield-alt me-1 text-info"></i>Garantia:</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-light text-dark" id="summary-provider-warehouse"><?php echo htmlspecialchars($warehouse['warranty_provider'] ?? 'Não informado'); ?></span>
                                        <span class="badge bg-light text-dark" id="summary-period-warehouse"><?php echo htmlspecialchars(($warehouse['warranty_period_value'] ?? '-') . ' ' . ($warehouse['warranty_period_unit'] ?? 'meses')); ?></span>
                                        <span class="badge bg-light text-dark" id="summary-end-warehouse"><?php echo !empty($warehouse['warranty_end_date']) ? date('d/m/Y', strtotime($warehouse['warranty_end_date'])) : 'Sem data'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <hr class="my-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary me-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-1"></i>
                                        Limpar Formulário
                                    </button>
                                </div>
                                <div>
                                    <a href="warehouse.php" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary-custom" id="save-edit-warehouse-btn">
                                        <i class="fas fa-save me-1"></i>
                                        Atualizar Item
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_modal) {
    // Include modal apenas quando não estiver em modo modal
    $GLOBALS['is_inside_warehouse_form'] = true;
    include 'includes/warranty_modal_edit_warehouse.php';
    include '../../includes/footer.php';
}
?>

<script>
// ========================================
// FUNÇÃO PARA ATUALIZAR RESUMO DE GARANTIA (chamada pelo modal)
// ========================================
function updateWarrantySummaryWarehouse() {
    const provider = document.getElementById('warehouse_warranty_provider')?.value || 'Não informado';
    const periodValue = document.getElementById('warehouse_warranty_period_value')?.value || '-';
    const periodUnit = document.getElementById('warehouse_warranty_period_unit')?.value || 'months';
    const endDate = document.getElementById('warehouse_warranty_end_date')?.value;

    const unitLabels = {
        'days': 'dias',
        'months': 'meses',
        'years': 'anos'
    };
    const unitLabel = unitLabels[periodUnit] || periodUnit;

    let endDateFormatted = 'Sem data';
    if (endDate) {
        const date = new Date(endDate + 'T00:00:00');
        endDateFormatted = date.toLocaleDateString('pt-BR');
    }

    const summaryProvider = document.getElementById('summary-provider-warehouse');
    const summaryPeriod = document.getElementById('summary-period-warehouse');
    const summaryEnd = document.getElementById('summary-end-warehouse');

    if (summaryProvider) summaryProvider.textContent = provider;
    if (summaryPeriod) summaryPeriod.textContent = periodValue + ' ' + unitLabel;
    if (summaryEnd) summaryEnd.textContent = endDateFormatted;
}

document.addEventListener('DOMContentLoaded', function() {
    const hasWarrantyCheckbox = document.getElementById('warehouse_has_warranty');
    const editWarrantyBtn = document.getElementById('editWarehouseWarrantyBtn');
    const warrantySummary = document.getElementById('warranty-summary-warehouse');

    // ========================================
    // ABRIR MODAL AO CLICAR NO BOTÃO
    // ========================================
    if (editWarrantyBtn) {
        editWarrantyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = document.getElementById('warrantyModalWarehouseEdit');
            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    }

    if (hasWarrantyCheckbox) {
        hasWarrantyCheckbox.addEventListener('change', function() {
            if (this.checked) {
                updateWarrantySummaryWarehouse();
                if (editWarrantyBtn) editWarrantyBtn.style.display = 'inline-block';
                if (warrantySummary) warrantySummary.classList.remove('d-none');
            } else {
                if (editWarrantyBtn) editWarrantyBtn.style.display = 'none';
                if (warrantySummary) warrantySummary.classList.add('d-none');
            }
        });
    }
});

// ========================================
// FUNÇÃO DE INICIALIZAÇÃO DE VALIDAÇÃO (copiada do produto)
// ========================================
function initEditWarehouseValidation() {
    console.log('🔧 Inicializando validação do warehouse...');

    const hasWarrantyCheckbox = document.getElementById('has_warranty');
    const saveEditWarehouseBtn = document.getElementById('save-edit-warehouse-btn');
    const minQuantityInput = document.getElementById('min_quantity');
    const maxQuantityInput = document.getElementById('max_quantity');
    const minQuantityError = document.getElementById('min-quantity-error');
    const maxQuantityError = document.getElementById('max-quantity-error');
    const maxQuantityStockError = document.getElementById('max-quantity-stock-error');
    const currentStockInput = document.getElementById('current_stock_quantity');

    if (!minQuantityInput || !maxQuantityInput) {
        console.warn('⚠️ Campos de quantidade não encontrados');
        return;
    }

    // ========================================
    // VALIDAÇÃO DE QUANTIDADE MÍNIMA E MÁXIMA
    // ========================================
    function validateQuantities() {
        const minQty = parseInt(minQuantityInput.value) || 0;
        const maxQty = parseInt(maxQuantityInput.value) || 0;
        let isValid = true;

        console.log('📊 Validando Warehouse:', { minQty, maxQty });

        // Validar quantidade mínima
        if (minQty < 1) {
            minQuantityInput.classList.add('is-invalid');
            if (minQuantityError) {
                minQuantityError.textContent = '⚠️ Valor mínimo necessário para salvar: 1';
                minQuantityError.style.display = 'block';
            }
            isValid = false;
        } else {
            minQuantityInput.classList.remove('is-invalid');
            if (minQuantityError) {
                minQuantityError.textContent = '⚠️ Valor mínimo necessário para salvar: 1';
                minQuantityError.style.display = 'none';
            }
        }

        // Validar quantidade máxima
        if (maxQty < 1) {
            maxQuantityInput.classList.add('is-invalid');
            if (maxQuantityError) maxQuantityError.style.display = 'block';
            isValid = false;
        } else {
            maxQuantityInput.classList.remove('is-invalid');
            if (maxQuantityError) maxQuantityError.style.display = 'none';
        }

        // VALIDAÇÃO: min e max não podem ser ambos 1
        if (minQty === 1 && maxQty === 1) {
            minQuantityInput.classList.add('is-invalid');
            maxQuantityInput.classList.add('is-invalid');
            isValid = false;
        }

        // VALIDAÇÃO: min não pode ser maior que max
        if (minQty > maxQty && maxQty > 0) {
            minQuantityInput.classList.add('is-invalid');
            maxQuantityInput.classList.add('is-invalid');
            if (minQuantityError) {
                minQuantityError.textContent = `⚠️ A quantidade mínima (${minQty}) não pode ser maior que a máxima (${maxQty})`;
                minQuantityError.style.display = 'block';
            }
            isValid = false;
        }

        // Habilitar/desabilitar botão salvar
        if (saveEditWarehouseBtn) {
            saveEditWarehouseBtn.disabled = !isValid;
            console.log('🔘 Botão Salvar:', isValid ? '✅ HABILITADO' : '❌ DESABILITADO');
        }

        return isValid;
    }

    // Adicionar listeners aos campos de quantidade
    minQuantityInput.addEventListener('input', validateQuantities);
    minQuantityInput.addEventListener('change', validateQuantities);
    minQuantityInput.addEventListener('keyup', validateQuantities);

    maxQuantityInput.addEventListener('input', validateQuantities);
    maxQuantityInput.addEventListener('change', validateQuantities);
    maxQuantityInput.addEventListener('keyup', validateQuantities);

    // Validar no carregamento
    setTimeout(() => {
        validateQuantities();
        console.log('✅ Validação inicial do warehouse executada');
    }, 200);

    // Prevenir submissão se inválido
    const editWarehouseForm = document.getElementById('edit-warehouse-form');
    if (editWarehouseForm) {
        editWarehouseForm.addEventListener('submit', function(e) {
            // Revalidar antes de submeter
            const currentMinQty = parseInt(document.getElementById('min_quantity').value) || 0;
            const currentMaxQty = parseInt(document.getElementById('max_quantity').value) || 0;

            if (currentMinQty < 1 || currentMaxQty < 1) {
                e.preventDefault();
                alert('⚠️ AVISO: As quantidades mínima e máxima devem ser no mínimo 1.\n\nDefina valores válidos para o controle de estoque antes de salvar.');

                // Scroll para o primeiro campo inválido
                if (currentMinQty < 1) {
                    document.getElementById('min_quantity').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (currentMaxQty < 1) {
                    document.getElementById('max_quantity').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                return false;
            }

            // NOVA VALIDAÇÃO: min e max não podem ser ambos 1
            if (currentMinQty === 1 && currentMaxQty === 1) {
                e.preventDefault();
                alert('⚠️ AVISO: A quantidade mínima e máxima não podem ser ambas 1.\n\nDefina valores diferentes para o controle de estoque.');
                document.getElementById('min_quantity').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const submitBtnElem = this.querySelector('button[type="submit"]');
            const originalText = submitBtnElem.innerHTML;
            submitBtnElem.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Atualizando...';
            submitBtnElem.disabled = true;

            setTimeout(() => {
                submitBtnElem.innerHTML = originalText;
                submitBtnElem.disabled = false;
            }, 5000);
        });
    }

    console.log('✅ Validação do warehouse inicializada com sucesso');
}

// ========================================
// EXECUTAR NO DOM READY E NO LOAD DO MODAL
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado, iniciando validação...');
    initEditWarehouseValidation();
});

// Se o formulário já existir (modal carregado dinamicamente)
if (document.getElementById('edit-warehouse-form')) {
    console.log('🔲 Modal detectado, inicializando imediatamente...');
    initEditWarehouseValidation();
}

function generateBarcode() {
    const category = document.getElementById('category').value;
    const categoryCode = category.substring(0, 3).toUpperCase();
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 9999) + 1000;
    document.getElementById('barcode').value = `WH-${categoryCode}-${timestamp}-${random}`;
}

function generateQRCode() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 9999) + 1000;
    document.getElementById('qr_code').value = `QR-WH-${timestamp}-${random}`;
}

function resetForm() {
    if (confirm('Tem certeza que deseja limpar todos os campos?')) {
        document.getElementById('edit-warehouse-form').reset();
        alert('Formulário limpo!');
    }
}
</script>

<script>
// ========================================
// INICIALIZAÇÃO DO MEDIA UPLOAD (COMPATÍVEL COM MODAL)
// ========================================
(function() {
    console.log('🚀 Iniciando script de inicialização do upload de armazém...');

    // Se já existe um manager, limpa antes de criar novo
    if (window.warehouseUploadManager && typeof window.warehouseUploadManager.cleanup === 'function') {
        console.log('🧹 Limpando manager anterior...');
        window.warehouseUploadManager.cleanup();
        window.warehouseUploadManager = null;
    }

    let attempts = 0;
    const maxAttempts = 50; // 5 segundos máximo

    function checkAndInit() {
        attempts++;
        console.log(`🔍 Tentativa ${attempts}/${maxAttempts} de inicializar Warehouse Upload Manager`);

        // Verifica se MediaUploadManager está disponível
        if (typeof MediaUploadManager === 'undefined') {
            console.warn('⏳ MediaUploadManager ainda não está disponível');
            if (attempts < maxAttempts) {
                setTimeout(checkAndInit, 100);
            } else {
                console.error('❌ MediaUploadManager não foi carregado após ' + maxAttempts + ' tentativas');
            }
            return;
        }

        // Verifica se o container existe
        const container = document.getElementById('warehouse-upload-container');
        if (!container) {
            console.warn('⏳ Container #warehouse-upload-container ainda não existe no DOM');
            if (attempts < maxAttempts) {
                setTimeout(checkAndInit, 100);
            } else {
                console.error('❌ Container não encontrado após ' + maxAttempts + ' tentativas');
            }
            return;
        }

        console.log('✅ MediaUploadManager disponível, container encontrado');

        // Verifica se os botões existem
        const galleryBtn = container.querySelector('[data-action="gallery"]');
        const cameraBtn = container.querySelector('[data-action="camera"]');
        const removeBtn = container.querySelector('[data-action="remove"]');
        const changeBtn = container.querySelector('[data-action="change"]');

        console.log('🔍 Botões encontrados:', {
            gallery: !!galleryBtn,
            camera: !!cameraBtn,
            remove: !!removeBtn,
            change: !!changeBtn
        });

        // Tenta criar o manager
        try {
            window.warehouseUploadManager = new MediaUploadManager({
                containerId: 'warehouse-upload-container',
                fileInputId: 'media-file-input',
                cameraInputId: 'media-camera-input',
                hiddenInputId: 'uploaded_image',
                itemType: 'warehouse'
            });

            console.log('✅ Warehouse Upload Manager inicializado com sucesso!');

            // Verifica se os listeners foram adicionados
            setTimeout(() => {
                const manager = window.warehouseUploadManager;
                console.log('🔍 Verificando manager:', {
                    galeryBtn: !!manager.galeryBtn,
                    cameraBtn: !!manager.cameraBtn,
                    removeBtn: !!manager.removeBtn,
                    changeBtn: !!manager.changeBtn,
                    fileInput: !!manager.fileInput,
                    cameraInput: !!manager.cameraInput
                });
            }, 200);

        } catch (error) {
            console.error('❌ Erro ao inicializar Warehouse Upload Manager:', error);
            console.error('Stack:', error.stack);
        }
    }

    // Inicia a verificação imediatamente
    checkAndInit();
})();
</script>
