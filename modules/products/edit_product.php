<?php
// ========================================
// PÁGINA DE EDIÇÃO DE PRODUTO (CORRIGIDO PARA REDIRECIONAR)
// ========================================
require_once '../../config.php';
requireLogin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Produto';
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

    // IMPORTANTE: Guarda o estado anterior de has_warranty para decidir redirecionamento
    $had_warranty_before = isset($product['has_warranty']) && $product['has_warranty'] == 1;

    if (!$product) {
        if (!$is_modal) header('Location: products.php');
        exit('Produto não encontrado.');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar produto para edição: " . $e->getMessage());
    if (!$is_modal) header('Location: products.php');
    exit('Erro de banco de dados.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==================================================================
    // ⭐️ AJUSTE 1 (PHP): MODIFICADO PARA REDIRECIONAR
    // ==================================================================

    $old_data = $product;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '') ?: null;
    $barcode = trim($_POST['barcode'] ?? '') ?: null;
    $qr_code = trim($_POST['qr_code'] ?? '') ?: null;
    $min_quantity = intval(preg_replace('/[^0-9]/', '', $_POST['min_quantity'] ?? '0'));
    $max_quantity = intval(preg_replace('/[^0-9]/', '', $_POST['max_quantity'] ?? '0'));
    $price = !empty($_POST['price']) ? floatval(str_replace(',', '.', str_replace('.', '', $_POST['price']))) : null;
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    $image_to_save = trim($_POST['uploaded_image'] ?? $product['image']);
    
    // Campos de garantia
    $has_warranty = isset($_POST['has_warranty']) ? 1 : 0;
    $warranty_provider = trim($_POST['edit_warranty_provider'] ?? '');
    $warranty_period_value = !empty($_POST['edit_warranty_period_value']) ? intval($_POST['edit_warranty_period_value']) : null;
    $warranty_period_unit = trim($_POST['edit_warranty_period_unit'] ?? '');
    $warranty_start_date = !empty($_POST['edit_warranty_start_date']) ? trim($_POST['edit_warranty_start_date']) : null;
    $warranty_end_date = !empty($_POST['edit_warranty_end_date']) ? trim($_POST['edit_warranty_end_date']) : null;
    $invoice_number = trim($_POST['edit_invoice_number'] ?? '');
    $warranty_notes = trim($_POST['edit_warranty_notes'] ?? '');
    
    $current_stock_quantity = $product['quantity']; 

    // Validações
    if (empty($name) || empty($category)) {
        $_SESSION['flash_message'] = 'Nome e Categoria são obrigatórios.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }

    if ($min_quantity < 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade mínima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }

    if ($max_quantity < 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade máxima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }

    if ($min_quantity == 1 && $max_quantity == 1) {
        $_SESSION['flash_message'] = '⚠️ AVISO: A quantidade mínima e máxima não podem ser ambas 1. Defina valores diferentes para o controle de estoque.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }

    if ($min_quantity > $max_quantity && $max_quantity > 0) {
        $_SESSION['flash_message'] = 'A quantidade mínima (' . $min_quantity . ') não pode ser maior que a quantidade máxima (' . $max_quantity . ').';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }

    if ($max_quantity > 0 && $current_stock_quantity > $max_quantity) {
        $_SESSION['flash_message'] = 'A quantidade máxima (' . $max_quantity . ') não pode ser menor que a quantidade atual em estoque (' . $current_stock_quantity . '). O produto está no limite máximo de estoque.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        if (is_null($serial_number) && is_null($barcode) && is_null($qr_code)) {
            $qr_code = 'PROD-' . strtoupper(uniqid());
        }

        $stmt = $pdo->prepare("
            UPDATE products SET 
                name = ?, description = ?, image = ?, category = ?, manufacturer = ?, 
                model = ?, serial_number = ?, barcode = ?, qr_code = ?, 
                min_quantity = ?, max_quantity = ?, price = ?, location = ?, status = ?,
                has_warranty = ?, warranty_provider = ?, warranty_period_value = ?,
                warranty_period_unit = ?, warranty_start_date = ?, warranty_end_date = ?,
                invoice_number = ?, warranty_notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $description, $image_to_save, $category, $manufacturer, $model, 
            $serial_number, $barcode, $qr_code, $min_quantity, $max_quantity, 
            $price, $location, $status,
            $has_warranty, $has_warranty ? $warranty_provider : null, $has_warranty ? $warranty_period_value : null,
            $has_warranty ? $warranty_period_unit : null, $has_warranty ? $warranty_start_date : null, $has_warranty ? $warranty_end_date : null,
            $has_warranty ? $invoice_number : null, $has_warranty ? $warranty_notes : null,
            $product_id
        ]);
        $new_data_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
        $new_data_stmt->execute([$product_id]);
        $new_data = $new_data_stmt->fetch();

        // ⭐ NOVO: Apenas registra log se houver alterações reais
        $data_changed = hasDataChanged($old_data, $new_data);
        if ($data_changed) {
            logAdminActivity($_SESSION["user_id"], "UPDATE", "products", $product_id, $old_data, $new_data);
        }

        $pdo->commit();
        
        // ⭐ NOVO: Apenas mostra mensagem se houve alterações
        if ($data_changed) {
            $_SESSION['flash_message'] = 'Produto atualizado com sucesso!';
            $_SESSION['flash_type'] = 'success';
        }

        // LÓGICA INTELIGENTE DE REDIRECIONAMENTO:
        // Se NÃO tinha garantia antes e AGORA foi marcado has_warranty = 1, vai para ../warranties/warranties.php
        // Caso contrário, volta para products.php

        if (!$had_warranty_before && $has_warranty == 1) {
            // Novo cadastro de garantia - vai para a página de garantias
            header('Location: ../warranties/warranties.php');
            exit();
        }

        // Atualização normal ou desmarcou garantia - volta para produtos
        header('Location: products.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        $_SESSION['flash_message'] = 'Erro de banco de dados: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit();
    }
}

// ==================================================================
// FIM DO BLOCO PHP
// ==================================================================

// Se houver uma mensagem de erro/sucesso da submissão anterior, exibe aqui
$form_message = '';
if (isset($_SESSION['flash_message'])) {
    $alert_type = $_SESSION['flash_type'] === 'success' ? 'success' : 'danger';
    $icon = $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    $form_message = '
    <div class="alert alert-'.$alert_type.' fade show" role="alert" id="mainAlert">
        <i class="fas '.$icon.' me-2"></i>' . htmlspecialchars($_SESSION['flash_message']) . '
    </div>
    <script>
        // Auto-desaparece após 5 segundos
        setTimeout(() => {
            const alert = document.getElementById("mainAlert");
            if (alert) {
                alert.style.transition = "opacity 0.5s ease-out";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}


if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<!-- Link CSS para Media Upload -->
<link rel="stylesheet" href="../../CSS/media-upload.css">

<?php if ($is_modal): ?>
<!-- Script necessário para modal (quando header não é carregado) -->
<script src="../../js/media-upload.js"></script>
<?php endif; ?>

<form method="POST" action="edit_product.php?id=<?php echo $product['id']; ?>&modal=true" enctype="multipart/form-data" id="editProductForm">
    
    <div id="edit-error-message-product" class="mb-3">
        <?php echo $form_message; ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label form-label-custom"><i class="fas fa-tag me-1"></i>Nome do Produto *</label>
                <input type="text" class="form-control form-control-custom" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label form-label-custom"><i class="fas fa-folder me-1"></i>Categoria *</label>
                <select class="form-select form-control-custom" id="category" name="category" required>
                    <option value="">Selecione uma categoria</option>
                    <option value="CPU" <?php echo ($product['category'] ?? '') === 'CPU' ? 'selected' : ''; ?>>CPU/Processador</option>
                    <option value="RAM" <?php echo ($product['category'] ?? '') === 'RAM' ? 'selected' : ''; ?>>Memória RAM</option>
                    <option value="SSD" <?php echo ($product['category'] ?? '') === 'SSD' ? 'selected' : ''; ?>>SSD</option>
                    <option value="HDD" <?php echo ($product['category'] ?? '') === 'HDD' ? 'selected' : ''; ?>>HD/HDD</option>
                    <option value="GPU" <?php echo ($product['category'] ?? '') === 'GPU' ? 'selected' : ''; ?>>Placa de Vídeo</option>
                    <option value="Motherboard" <?php echo ($product['category'] ?? '') === 'Motherboard' ? 'selected' : ''; ?>>Placa Mãe</option>
                    <option value="PSU" <?php echo ($product['category'] ?? '') === 'PSU' ? 'selected' : ''; ?>>Fonte</option>
                    <option value="Case" <?php echo ($product['category'] ?? '') === 'Case' ? 'selected' : ''; ?>>Gabinete</option>
                    <option value="Cable" <?php echo ($product['category'] ?? '') === 'Cable' ? 'selected' : ''; ?>>Cabo</option>
                    <option value="Monitor" <?php echo ($product['category'] ?? '') === 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                    <option value="Keyboard" <?php echo ($product['category'] ?? '') === 'Keyboard' ? 'selected' : ''; ?>>Teclado</option>
                    <option value="Mouse" <?php echo ($product['category'] ?? '') === 'Mouse' ? 'selected' : ''; ?>>Mouse</option>
                    <option value="Network" <?php echo ($product['category'] ?? '') === 'Network' ? 'selected' : ''; ?>>Rede</option>
                    <option value="Other" <?php echo ($product['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Outros</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="manufacturer" class="form-label form-label-custom"><i class="fas fa-industry me-1"></i>Fabricante</label>
                <input type="text" class="form-control form-control-custom" id="manufacturer" name="manufacturer" value="<?php echo htmlspecialchars($product['manufacturer']); ?>">
            </div>
            <div class="mb-3">
                <label for="model" class="form-label form-label-custom"><i class="fas fa-barcode me-1"></i>Modelo</label>
                <input type="text" class="form-control form-control-custom" id="model" name="model" value="<?php echo htmlspecialchars($product['model']); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label form-label-custom"><i class="fas fa-align-left me-1"></i>Descrição</label>
                <textarea class="form-control form-control-custom" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label form-label-custom"><i class="fas fa-camera me-1"></i>Imagem do Produto</label>
                <div class="media-upload-container" id="product-upload-container">
                    <div class="media-upload-placeholder" style="<?php echo !empty($product['image']) ? 'display: none;' : ''; ?>">
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
                    <div class="media-upload-preview" style="<?php echo empty($product['image']) ? 'display: none;' : ''; ?>">
                        <img class="media-upload-preview-image" src="<?php echo !empty($product['image']) ? '../../uploads/products/' . htmlspecialchars($product['image']) : ''; ?>" alt="Preview">
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
                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($product['image']); ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="serial_number" class="form-label form-label-custom"><i class="fas fa-hashtag me-1"></i>Nº de Série</label>
                <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($product['serial_number']); ?>">
            </div>
            <div class="mb-3">
                <label for="barcode" class="form-label form-label-custom"><i class="fas fa-barcode me-1"></i>Código de Barras</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-custom" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" data-bs-toggle="tooltip" title="Gerar código automaticamente">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="barcode" title="Escanear código de barras">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="qr_code" class="form-label form-label-custom"><i class="fas fa-qrcode me-1"></i>Código QR</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code" value="<?php echo htmlspecialchars($product['qr_code']); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" data-bs-toggle="tooltip" title="Gerar código QR automaticamente">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="qr_code" title="Escanear QR code">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="min_quantity" class="form-label form-label-custom"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Qtd. Mínima *</label>
                <input type="number" class="form-control form-control-custom" id="min_quantity" name="min_quantity" value="<?php echo htmlspecialchars($product['min_quantity']); ?>" min="1" placeholder="Mínimo: 1" required>
                <div class="form-text" id="min-quantity-help">Quantidade mínima para alerta de estoque baixo (mínimo: 1)</div>
                <div class="invalid-feedback" id="min-quantity-error" style="display: none;">
                    ⚠️ Valor mínimo necessário para salvar: 1
                </div>
            </div>
            <div class="mb-3">
                <label for="max_quantity" class="form-label form-label-custom"><i class="fas fa-chart-line text-info me-1"></i>Qtd. Máxima *</label>
                <input type="number" class="form-control form-control-custom" id="max_quantity" name="max_quantity" value="<?php echo htmlspecialchars($product['max_quantity']); ?>" min="1" placeholder="Mínimo: 1" required>
                <div class="form-text" id="max-quantity-help">Quantidade máxima permitida no estoque (mínimo: 1)</div>
                <div class="invalid-feedback" id="max-quantity-error" style="display: none;">
                    ⚠️ Valor mínimo necessário para salvar: 1
                </div>
                <div class="invalid-feedback" id="max-quantity-stock-error" style="display: none;">
                    ⚠️ A quantidade máxima não pode ser menor que o estoque atual (<?php echo $product['quantity']; ?> unidades)
                </div>
                <input type="hidden" id="current_stock_quantity" value="<?php echo $product['quantity']; ?>">
            </div>
            <div class="mb-3">
                <label for="price" class="form-label form-label-custom"><i class="fas fa-dollar-sign me-1"></i>Preço (R$)</label>
                <input type="text" class="form-control form-control-custom" id="price" name="price" value="<?php echo number_format($product['price'], 2, ',', '.'); ?>">
            </div>
            <div class="mb-3">
                <label for="location" class="form-label form-label-custom"><i class="fas fa-map-marker-alt me-1"></i>Localização</label>
                <input type="text" class="form-control form-control-custom" id="location" name="location" value="<?php echo htmlspecialchars($product['location']); ?>">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label form-label-custom"><i class="fas fa-flag me-1"></i>Status</label>
                <select class="form-select form-control-custom" id="status" name="status">
                    <option value="available" <?php echo ($product['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Disponível</option>
                    <option value="in_use" <?php echo ($product['status'] ?? '') === 'in_use' ? 'selected' : ''; ?>>Em Uso</option>
                    <option value="defective" <?php echo ($product['status'] ?? '') === 'defective' ? 'selected' : ''; ?>>Defeituoso</option>
                    <option value="maintenance" <?php echo ($product['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                    <option value="discontinued" <?php echo ($product['status'] ?? '') === 'discontinued' ? 'selected' : ''; ?>>Descontinuado</option>
                </select>
            </div>

            <!-- BOTÃO E CHECKBOX DE GARANTIA -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="product_has_warranty" 
                               name="has_warranty" value="1" <?php echo ($product['has_warranty'] ?? 0) ? 'checked' : ''; ?> 
                               style="width: 3em; height: 1.5em;">
                        <label class="form-check-label" for="product_has_warranty">
                            <strong>Com Garantia</strong>
                        </label>
                    </div>
                    <a href="../warranties/warranties.php" class="btn btn-sm btn-outline-info" id="editProductWarrantyBtn" 
                       <?php echo ($product['has_warranty'] ?? 0) ? '' : 'style="display: none;"'; ?>>
                        <i class="fas fa-shield-alt me-1"></i>Editar Garantia
                    </a>
                </div>
                
                <!-- CAMPOS OCULTOS DE GARANTIA -->
                <input type="hidden" id="edit_warranty_provider" name="edit_warranty_provider" value="<?php echo htmlspecialchars($product['warranty_provider'] ?? ''); ?>">
                <input type="hidden" id="edit_invoice_number" name="edit_invoice_number" value="<?php echo htmlspecialchars($product['invoice_number'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_start_date" name="edit_warranty_start_date" value="<?php echo htmlspecialchars($product['warranty_start_date'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_period_value" name="edit_warranty_period_value" value="<?php echo htmlspecialchars($product['warranty_period_value'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_period_unit" name="edit_warranty_period_unit" value="<?php echo htmlspecialchars($product['warranty_period_unit'] ?? 'months'); ?>">
                <input type="hidden" id="edit_warranty_end_date" name="edit_warranty_end_date" value="<?php echo htmlspecialchars($product['warranty_end_date'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_notes" name="edit_warranty_notes" value="<?php echo htmlspecialchars($product['warranty_notes'] ?? ''); ?>">

                <!-- RESUMO VISUAL -->
                <div id="warranty-summary-edit" class="alert alert-light border border-info p-2 mt-2 <?php echo ($product['has_warranty'] ?? 0) ? '' : 'd-none'; ?>">
                    <small class="text-muted d-block mb-1"><i class="fas fa-shield-alt me-1 text-info"></i>Garantia:</small>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark" id="summary-provider-edit"><?php echo htmlspecialchars($product['warranty_provider'] ?? 'Não informado'); ?></span>
                        <span class="badge bg-light text-dark" id="summary-period-edit"><?php echo htmlspecialchars(($product['warranty_period_value'] ?? '-') . ' ' . ($product['warranty_period_unit'] ?? 'meses')); ?></span>
                        <span class="badge bg-light text-dark" id="summary-end-edit"><?php echo ($product['warranty_end_date'] ?? false) ? date('d/m/Y', strtotime($product['warranty_end_date'])) : 'Data não definida'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end border-top pt-3 mt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="save-edit-product-btn"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
    </div>
</form>

<?php 
if (!$is_modal) { 
    // Include modal apenas quando não estiver em modo modal
    // Senão o modal será editado em ../warranties/warranties.php
    $GLOBALS['is_inside_product_form'] = true;
    include '../../includes/warranty_modal_edit_inline.php'; 
    include '../../includes/footer.php'; 
} 
?>

<script>
// ========================================
// FUNÇÃO DE INICIALIZAÇÃO (pode ser chamada múltiplas vezes)
// ========================================
function initEditProductValidation() {
    console.log('🔧 Inicializando validação do produto...');

    const hasWarrantyCheckbox = document.getElementById('product_has_warranty');
    const editWarrantyBtn = document.getElementById('editProductWarrantyBtn');
    const warrantySummaryEdit = document.getElementById('warranty-summary-edit');
    const saveEditProductBtn = document.getElementById('save-edit-product-btn');
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

        console.log('📊 Validando Produto:', { minQty, maxQty });

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
        if (saveEditProductBtn) {
            saveEditProductBtn.disabled = !isValid;
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

    // Checkbox de garantia
    if (hasWarrantyCheckbox) {
        hasWarrantyCheckbox.addEventListener('change', function() {
            // Obter dados dos campos ocultos
            const provider = document.getElementById('edit_warranty_provider')?.value || 'Não informado';
            const periodValue = document.getElementById('edit_warranty_period_value')?.value || '-';
            const periodUnit = document.getElementById('edit_warranty_period_unit')?.value || 'months';
            const endDate = document.getElementById('edit_warranty_end_date')?.value;
            
            // Mapear unidade de período para português
            const unitLabels = {
                'days': 'dias',
                'months': 'meses',
                'years': 'anos'
            };
            const unitLabel = unitLabels[periodUnit] || periodUnit;
            
            // Formatar data final
            let endDateFormatted = 'Data não definida';
            if (endDate) {
                try {
                    endDateFormatted = new Date(endDate + 'T00:00:00').toLocaleDateString('pt-BR');
                } catch (e) {
                    endDateFormatted = endDate;
                }
            }
            
            if (this.checked) {
                // Atualizar resumo com dados dos hidden inputs
                const summaryProvider = document.getElementById('summary-provider-edit');
                const summaryPeriod = document.getElementById('summary-period-edit');
                const summaryEnd = document.getElementById('summary-end-edit');
                
                if (summaryProvider) summaryProvider.textContent = provider;
                if (summaryPeriod) summaryPeriod.textContent = periodValue + ' ' + unitLabel;
                if (summaryEnd) summaryEnd.textContent = endDateFormatted;
                
                // Mostrar elementos
                if (editWarrantyBtn) editWarrantyBtn.style.display = 'inline-block';
                if (warrantySummaryEdit) warrantySummaryEdit.classList.remove('d-none');
            } else {
                // Esconder elementos
                if (editWarrantyBtn) editWarrantyBtn.style.display = 'none';
                if (warrantySummaryEdit) warrantySummaryEdit.classList.add('d-none');
            }
        });
    }

    // Validar no carregamento
    setTimeout(() => {
        validateQuantities();
        console.log('✅ Validação inicial do produto executada');
    }, 200);

    // Prevenir submissão se inválido
    const editProductForm = document.getElementById('editProductForm');
    if (editProductForm) {
        editProductForm.addEventListener('submit', function(e) {
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
            submitBtnElem.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
            submitBtnElem.disabled = true;

            setTimeout(() => {
                submitBtnElem.innerHTML = originalText;
                submitBtnElem.disabled = false;
            }, 5000);
        });
    }

    console.log('✅ Validação do produto inicializada com sucesso');
}

// ========================================
// EXECUTAR NO DOM READY E NO LOAD DO MODAL
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado, iniciando validação...');
    initEditProductValidation();
});

// Se o formulário já existir (modal carregado dinamicamente)
if (document.getElementById('editProductForm')) {
    console.log('🔲 Modal detectado, inicializando imediatamente...');
    initEditProductValidation();
}

// Funções de Gerar Código (continuam iguais)
function generateBarcode() {
    const timestamp = Date.now();
    const barcode = '789' + timestamp.toString().substring(timestamp.toString().length - 10);
    document.getElementById('barcode').value = barcode;
    if (typeof showAlert === 'function') {
        showAlert('Código de barras gerado.', 'info');
    }
}

function generateQRCode() {
    const qrCode = 'PROD-' + Date.now();
    document.getElementById('qr_code').value = qrCode;
    if (typeof showAlert === 'function') {
        showAlert('Código QR gerado.', 'info');
    }
}
</script>

<script>
// ========================================
// INICIALIZAÇÃO DO MEDIA UPLOAD (COMPATÍVEL COM MODAL)
// ========================================
(function() {
    console.log('🚀 Iniciando script de inicialização do upload de produto...');

    // Se já existe um manager, limpa antes de criar novo
    if (window.productUploadManager && typeof window.productUploadManager.cleanup === 'function') {
        console.log('🧹 Limpando manager anterior...');
        window.productUploadManager.cleanup();
        window.productUploadManager = null;
    }

    let attempts = 0;
    const maxAttempts = 50; // 5 segundos máximo

    function checkAndInit() {
        attempts++;
        console.log(`🔍 Tentativa ${attempts}/${maxAttempts} de inicializar Media Upload Manager`);

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
        const container = document.getElementById('product-upload-container');
        if (!container) {
            console.warn('⏳ Container #product-upload-container ainda não existe no DOM');
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
            window.productUploadManager = new MediaUploadManager({
                containerId: 'product-upload-container',
                fileInputId: 'media-file-input',
                cameraInputId: 'media-camera-input',
                hiddenInputId: 'uploaded_image',
                itemType: 'product'
            });

            console.log('✅ Product Upload Manager inicializado com sucesso!');

            // Verifica se os listeners foram adicionados
            setTimeout(() => {
                const manager = window.productUploadManager;
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
            console.error('❌ Erro ao inicializar Media Upload Manager:', error);
            console.error('Stack:', error.stack);
        }
    }

    // Inicia a verificação imediatamente
    checkAndInit();
})();

</script>