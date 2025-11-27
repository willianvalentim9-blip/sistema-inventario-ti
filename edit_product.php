<?php
// ========================================
// PÁGINA DE EDIÇÃO DE PRODUTO (CORRIGIDO PARA REDIRECIONAR)
// ========================================
require_once 'config.php';
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
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
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
        // Se der erro, mostramos a mensagem de erro no formulário
        $_SESSION['flash_message'] = 'Nome e Categoria são obrigatórios.';
        $_SESSION['flash_type'] = 'danger';
        // Recarrega a página de edição para mostrar o erro
        header('Location: edit_product.php?id=' . $product_id . '&modal=true');
        exit;
    } 
    
    if ($max_quantity > 0 && $current_stock_quantity > $max_quantity) {
        $_SESSION['flash_message'] = 'A quantidade máxima (' . $max_quantity . ') não pode ser menor que a quantidade atual em estoque (' . $current_stock_quantity . ').';
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
        
        $new_data_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $new_data_stmt->execute([$product_id]);
        $new_data = $new_data_stmt->fetch();

        logAdminActivity($_SESSION["user_id"], "UPDATE", "products", $product_id, $old_data, $new_data);

        $pdo->commit();
        
        // Seta a mensagem de sucesso
        $_SESSION['flash_message'] = 'Produto atualizado com sucesso!';
        $_SESSION['flash_type'] = 'success';
        
        // Redireciona de volta para a lista de produtos
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
    $form_message = '<div class="alert alert-'. $alert_type . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}


if (!$is_modal) {
    include 'includes/header.php';
}
?>

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
                <div class="upload-area border rounded p-4 text-center" id="product-upload-area" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                    <input type="file" class="form-control" id="product_image_input" accept="image/*" style="display: none;">
                    
                    <div id="product-placeholder" style="cursor: pointer; <?php echo !empty($product['image']) ? 'display: none;' : ''; ?>">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-2">Clique aqui ou arraste uma imagem</p>
                    </div>

                    <div id="product-add-btn-container" class="text-center" style="display: none;">
                        <button type="button" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adicionar Imagem
                        </button>
                    </div>

                    <div id="product-preview-container" style="<?php echo empty($product['image']) ? 'display: none;' : ''; ?>">
                        <img id="product-preview-image" src="<?php echo !empty($product["image"]) ? 'uploads/products/' . htmlspecialchars($product["image"]) : ''; ?>" alt="Preview" class="img-thumbnail mb-2" style="max-width: 200px;">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="product-remove-btn"><i class="fas fa-trash me-1"></i>Remover Imagem</button>
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
                <label for="min_quantity" class="form-label form-label-custom"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Qtd. Mínima</label>
                <input type="number" class="form-control" id="min_quantity" name="min_quantity" value="<?php echo htmlspecialchars($product['min_quantity']); ?>" min="0">
            </div>
             <div class="mb-3">
                <label for="max_quantity" class="form-label form-label-custom"><i class="fas fa-chart-line text-info me-1"></i>Qtd. Máxima</label>
                <input type="number" class="form-control" id="max_quantity" name="max_quantity" value="<?php echo htmlspecialchars($product['max_quantity']); ?>" min="0">
            </div>
            <div class="mb-3">
                <label for="price" class="form-label form-label-custom"><i class="fas fa-dollar-sign me-1"></i>Preço (R$)</label>
                <input type="text" class="form-control" id="price" name="price" value="<?php echo number_format($product['price'], 2, ',', '.'); ?>">
            </div>
            <div class="mb-3">
                <label for="location" class="form-label form-label-custom"><i class="fas fa-map-marker-alt me-1"></i>Localização</label>
                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($product['location']); ?>">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label form-label-custom"><i class="fas fa-flag me-1"></i>Status</label>
                <select class="form-select" id="status" name="status">
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
                    <button type="button" class="btn btn-sm btn-outline-info" id="editProductWarrantyBtn" 
                            <?php echo ($product['has_warranty'] ?? 0) ? '' : 'style="display: none;"'; ?> 
                            data-bs-toggle="modal" data-bs-target="#warrantyModalProductEdit">
                        <i class="fas fa-shield-alt me-1"></i>Editar Garantia
                    </button>
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
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
    </div>
</form>

<?php 
if (!$is_modal) { 
    $GLOBALS['is_inside_product_form'] = true;
    include 'includes/warranty_modal_edit_inline.php'; 
    include 'includes/footer.php'; 
} 
?>

<script>
// SCRIPTS PARA EDIT PRODUCT
document.addEventListener('DOMContentLoaded', function() {
    const hasWarrantyCheckbox = document.getElementById('product_has_warranty');
    const editWarrantyBtn = document.getElementById('editProductWarrantyBtn');
    const warrantySummaryEdit = document.getElementById('warranty-summary-edit');

    if (hasWarrantyCheckbox) {
        hasWarrantyCheckbox.addEventListener('change', function() {
            if (this.checked) {
                editWarrantyBtn.style.display = 'inline-block';
                warrantySummaryEdit.classList.remove('d-none');
            } else {
                editWarrantyBtn.style.display = 'none';
                warrantySummaryEdit.classList.add('d-none');
            }
        });
    }

    // Modal de garantia para edição
    const warrantyModalEdit = document.getElementById('warrantyModalProductEdit');
    if (warrantyModalEdit) {
        warrantyModalEdit.addEventListener('show.bs.modal', function() {
            // Carrega dados do formulário para o modal
            const fields = ['warranty_provider', 'invoice_number', 'warranty_start_date', 'warranty_period_value', 'warranty_period_unit', 'warranty_end_date', 'warranty_notes'];
            
            fields.forEach(field => {
                const editField = document.getElementById('edit_' + field);
                const modalField = document.getElementById('product_' + field);
                
                if (editField && modalField) {
                    modalField.value = editField.value;
                }
            });

            const modal = document.getElementById('warrantyModalProduct');
            if (modal) {
                const toggle = document.getElementById('product_has_warranty');
                if (toggle) {
                    toggle.checked = hasWarrantyCheckbox.checked;
                }
            }
        });
    }

    // Sincronizar dados ao fechar o modal
    if (warrantyModalEdit) {
        warrantyModalEdit.addEventListener('hidden.bs.modal', function() {
            const fields = ['warranty_provider', 'invoice_number', 'warranty_start_date', 'warranty_period_value', 'warranty_period_unit', 'warranty_end_date', 'warranty_notes'];
            
            fields.forEach(field => {
                const editField = document.getElementById('edit_' + field);
                const modalField = document.getElementById('product_' + field);
                
                if (editField && modalField) {
                    editField.value = modalField.value;
                }
            });

            // Atualizar resumo
            updateWarrantySummaryEdit();
        });
    }

    window.updateWarrantySummaryEdit = function() {
        const provider = document.getElementById('edit_warranty_provider').value;
        const periodValue = document.getElementById('edit_warranty_period_value').value;
        const periodUnit = document.getElementById('edit_warranty_period_unit').value;
        const endDate = document.getElementById('edit_warranty_end_date').value;

        if (provider) {
            document.getElementById('summary-provider-edit').textContent = provider;
        }
        if (periodValue && periodUnit) {
            document.getElementById('summary-period-edit').textContent = `${periodValue} ${periodUnit}`;
        }
        if (endDate) {
            const dateObj = new Date(endDate);
            document.getElementById('summary-end-edit').textContent = dateObj.toLocaleDateString('pt-BR');
        }
    };
});
</script>

<script>
// ==================================================================
// ⭐️ AJUSTE 2 (JS): REMOVIDO O 'addEventListener' DE SUBMIT
// ==================================================================

// O script de 'submit' (fetch) foi removido.
// O formulário agora será enviado da forma tradicional (HTML),
// permitindo que o PHP faça o redirecionamento.

// Scripts que *NÃO* são de submit (como upload de imagem e gerador de código)
// devem permanecer.

document.addEventListener('DOMContentLoaded', function() {

    // Setup do Upload de Imagem (continua igual)
    if (typeof setupImageUpload === 'function') {
        setupImageUpload({
            formId: 'editProductForm',
            fileInputId: 'product_image_input',
            hiddenInputId: 'uploaded_image',
            previewImageId: 'product-preview-image',
            placeholderId: 'product-placeholder',
            previewContainerId: 'product-preview-container',
            addBtnContainerId: 'product-add-btn-container', 
            removeBtnId: 'product-remove-btn',
            uploadAreaId: 'product-upload-area',
            itemType: 'product',
            itemId: <?php echo $product_id; ?>
        });
    }

    // O 'submit' listener FOI REMOVIDO DAQUI
});

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