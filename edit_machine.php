<?php
// ========================================
// PAGINA DE EDICAO DE MAQUINA (VERSAO FINAL COM UPLOAD CORRIGIDO)
// ========================================
require_once 'config.php';
requireLogin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Maquina';
$machine_id = intval($_GET["id"] ?? 0);
$GLOBALS['is_inside_machine_form'] = true;

if ($machine_id <= 0) {
    if (!$is_modal) header('Location: ready_machines.php');
    exit('ID de máquina inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ?");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();
    if (!$machine) {
        if (!$is_modal) header('Location: ready_machines.php');
        exit('Máquina não encontrada.');
    }
} catch (PDOException $e) {
    if (!$is_modal) header('Location: ready_machines.php');
    exit('Erro de banco de dados.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $processor = trim($_POST["processor"] ?? "");
    $memory = trim($_POST["memory"] ?? "");
    $storage = trim($_POST["storage"] ?? "");
    $graphics = trim($_POST["graphics"] ?? "");
    $barcode = trim($_POST["barcode"] ?? "") ?: null;
    $qr_code = trim($_POST["qr_code"] ?? "") ?: null;
    $sale_price = floatval(str_replace(",", ".", $_POST["sale_price"] ?? 0));
    $status = trim($_POST["status"] ?? "");
    $windows_10_compatible = isset($_POST["windows_10_compatible"]) ? 1 : 0;
    $windows_11_compatible = isset($_POST["windows_11_compatible"]) ? 1 : 0;
    $has_warranty = isset($_POST["has_warranty"]) ? 1 : 0;
    $warranty_provider = trim($_POST["machine_warranty_provider"] ?? '');
    $warranty_period_value = !empty($_POST["machine_warranty_period_value"]) ? intval($_POST["machine_warranty_period_value"]) : null;
    $warranty_period_unit = trim($_POST["machine_warranty_period_unit"] ?? '');
    $image_to_save = trim($_POST["uploaded_image"] ?? $machine["image"]);
    
    if (empty($name)) {
        $response['message'] = 'O nome da maquina é obrigatório.';
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_machine.php?id=' . $machine_id . '&modal=true');
        exit();
    } elseif ($has_warranty && empty($warranty_provider)) {
        $_SESSION['flash_message'] = 'Fornecedor de Garantia é obrigatório quando a máquina tem garantia.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_machine.php?id=' . $machine_id . '&modal=true');
        exit();
    } elseif ($has_warranty && empty($warranty_period_value)) {
        $_SESSION['flash_message'] = 'Duração da Garantia é obrigatória quando a máquina tem garantia.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_machine.php?id=' . $machine_id . '&modal=true');
        exit();
    } 
    
    try {
        $stmt = $pdo->prepare("UPDATE ready_machines SET name = ?, description = ?, image = ?, processor = ?, memory = ?, storage = ?, graphics = ?, barcode = ?, qr_code = ?, sale_price = ?, status = ?, windows_10_compatible = ?, windows_11_compatible = ?, has_warranty = ?, warranty_provider = ?, warranty_period_value = ?, warranty_period_unit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $description, $image_to_save, $processor, $memory, $storage, $graphics, $barcode, $qr_code, $sale_price, $status, $windows_10_compatible, $windows_11_compatible, $has_warranty, $has_warranty ? $warranty_provider : null, $has_warranty ? $warranty_period_value : null, $has_warranty ? $warranty_period_unit : null, $machine_id]);
        
        $_SESSION['flash_message'] = 'Maquina atualizada com sucesso!';
        $_SESSION['flash_type'] = 'success';
        
        // Se for requisição modal, redirecionar para warranties.php
        if ($is_modal) {
            header('Location: warranties.php');
            exit();
        }
        
        header('Location: ready_machines.php');
        exit();

    } catch (PDOException $e) {
        $_SESSION['flash_message'] = 'Erro de banco de dados: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_machine.php?id=' . $machine_id . '&modal=true');
        exit();
    }
}

if (!$is_modal) {
    include 'includes/header.php';
}

// Se houver uma mensagem de erro/sucesso da submissao anterior, exibe aqui
$form_message = '';
if (isset($_SESSION['flash_message'])) {
    $alert_type = $_SESSION['flash_type'] === 'success' ? 'success' : 'danger';
    $form_message = '<div class="alert alert-'. $alert_type . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

$image_path = 'uploads/machines/' . htmlspecialchars($machine['image'] ?? '');
$image_exists = !empty($machine['image']) && file_exists($image_path);

?>

<form method="POST" action="edit_machine.php?id=<?php echo $machine['id']; ?>&modal=true" enctype="multipart/form-data" id="editMachineForm">
    
    <div id="edit-error-message-machine" class="mb-3">
        <?php echo $form_message; ?>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="name" class="form-label">Nome da Máquina *</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($machine['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="barcode" class="form-label">Código de Barras</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($machine['barcode']); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()"><i class="fas fa-magic"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label for="qr_code" class="form-label">Código QR</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="qr_code" name="qr_code" value="<?php echo htmlspecialchars($machine['qr_code']); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()"><i class="fas fa-magic"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($machine["description"]); ?></textarea>
            </div>
        </div>
        <div class="col-md-4">
             <div class="mb-3">
                <label for="sale_price" class="form-label">Preço de Venda (R$)</label>
                <input type="text" class="form-control" id="sale_price" name="sale_price" value="<?php echo number_format($machine['sale_price'], 2, ',', '.'); ?>">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="available" <?php if ($machine['status'] === 'available') echo 'selected'; ?>>Disponível</option>
                    <option value="reserved" <?php if ($machine['status'] === 'reserved') echo 'selected'; ?>>Reservada</option>
                    <option value="sold" <?php if ($machine['status'] === 'sold') echo 'selected'; ?>>Vendida/Esgotada</option>
                </select>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="windows_10_compatible" name="windows_10_compatible" value="1" <?php if ($machine['windows_10_compatible']) echo 'checked'; ?>>
                    <label class="form-check-label" for="windows_10_compatible">Compatível com Windows 10</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="windows_11_compatible" name="windows_11_compatible" value="1" <?php if ($machine['windows_11_compatible']) echo 'checked'; ?>>
                    <label class="form-check-label" for="windows_11_compatible">Compatível com Windows 11</label>
                </div>
            </div>
            <!-- BOTÃO E CHECKBOX DE GARANTIA -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="machine_has_warranty" 
                               name="has_warranty" value="1" <?php echo ($machine['has_warranty'] ?? 0) ? 'checked' : ''; ?> 
                               style="width: 3em; height: 1.5em;">
                        <label class="form-check-label" for="machine_has_warranty">
                            <strong>Com Garantia</strong>
                        </label>
                    </div>
                    <a href="warranties.php" class="btn btn-sm btn-outline-info" id="editMachineWarrantyBtn" 
                       <?php echo ($machine['has_warranty'] ?? 0) ? '' : 'style="display: none;"'; ?>>
                        <i class="fas fa-shield-alt me-1"></i>Editar Garantia
                    </a>
                </div>
                
                <!-- CAMPOS OCULTOS DE GARANTIA -->
                <input type="hidden" id="edit_warranty_provider" name="machine_warranty_provider" value="<?php echo htmlspecialchars($machine['warranty_provider'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_period_value" name="machine_warranty_period_value" value="<?php echo htmlspecialchars($machine['warranty_period_value'] ?? ''); ?>">
                <input type="hidden" id="edit_warranty_period_unit" name="machine_warranty_period_unit" value="<?php echo htmlspecialchars($machine['warranty_period_unit'] ?? 'months'); ?>">

                <!-- RESUMO VISUAL -->
                <div id="warranty-summary-machine" class="alert alert-light border border-info p-2 mt-2 <?php echo ($machine['has_warranty'] ?? 0) ? '' : 'd-none'; ?>">
                    <small class="text-muted d-block mb-1"><i class="fas fa-shield-alt me-1 text-info"></i>Garantia:</small>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark" id="summary-provider-machine"><?php echo htmlspecialchars($machine['warranty_provider'] ?? 'Nao informado'); ?></span>
                        <span class="badge bg-light text-dark" id="summary-period-machine"><?php echo htmlspecialchars(($machine['warranty_period_value'] ?? '-') . ' ' . ($machine['warranty_period_unit'] ?? 'meses')); ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label form-label-custom"><i class="fas fa-camera me-1"></i>Imagem</label>
                <div class="upload-area border rounded p-3 text-center" id="machine-upload-area">
                    <input type="file" class="form-control" id="machine_image_input" accept="image/*" style="display: none;">
                    <div class="upload-placeholder" id="machine-placeholder" style="cursor: pointer; <?php echo $image_exists ? 'display: none;' : ''; ?>">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2 small">Clique ou arraste</p>
                    </div>
                    <div id="machine-add-btn-container" class="text-center" style="display: none;">
                        <button type="button" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus me-1"></i> Adicionar</button>
                    </div>
                    <div class="upload-preview" id="machine-preview-container" style="<?php echo !$image_exists ? 'display: none;' : ''; ?>">
                        <img id="machine-preview-image" src="<?php echo $image_exists ? $image_path : ''; ?>" alt="Preview" class="img-thumbnail mb-2" style="max-width: 150px;">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="machine-remove-btn"><i class="fas fa-trash me-1"></i>Remover</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($machine["image"]); ?>">
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end border-top pt-3 mt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
    </div>
</form>

<script>
// SCRIPTS PARA EDIT MACHINE
document.addEventListener('DOMContentLoaded', function() {
    const hasWarrantyCheckbox = document.getElementById('machine_has_warranty');
    const editWarrantyBtn = document.getElementById('editMachineWarrantyBtn');
    const warrantySummaryEdit = document.getElementById('warranty-summary-machine');

    if (hasWarrantyCheckbox) {
        hasWarrantyCheckbox.addEventListener('change', function() {
            if (this.checked) {
                if (editWarrantyBtn) {
                    editWarrantyBtn.style.display = 'inline-block';
                }
                warrantySummaryEdit.classList.remove('d-none');
            } else {
                if (editWarrantyBtn) {
                    editWarrantyBtn.style.display = 'none';
                }
                warrantySummaryEdit.classList.add('d-none');
            }
        });
    }

    // A funcao setupImageUpload do seu custom.js original sera chamada para este formulario.
    setupImageUpload({
        formId: 'editMachineForm',
        fileInputId: 'machine_image_input',
        hiddenInputId: 'uploaded_image',
        previewImageId: 'machine-preview-image',
        placeholderId: 'machine-placeholder',
        previewContainerId: 'machine-preview-container',
        addBtnContainerId: 'machine-add-btn-container',
        removeBtnId: 'machine-remove-btn',
        uploadAreaId: 'machine-upload-area',
        itemType: 'machine',
        itemId: <?php echo $machine_id; ?>
    });
});

// Funcoes de Gerar Codigo (continuam iguais)
function generateBarcode() {
    const timestamp = Date.now();
    const barcode = '789' + timestamp.toString().substring(timestamp.toString().length - 9);
    document.getElementById('barcode').value = barcode;
    if (typeof showAlert === 'function') {
        showAlert('Codigo de barras gerado.', 'info');
    }
}

function generateQRCode() {
    const qrCode = 'MACHINE-' + Date.now();
    document.getElementById('qr_code').value = qrCode;
    if (typeof showAlert === 'function') {
        showAlert('Codigo QR gerado.', 'info');
        }
    }
</script>

<?php 
// Modal de garantia removido - agora abre diretamente em warranties.php
// include 'includes/warranty_modal_edit_machine.php'; 

if (!$is_modal) { 
    include 'includes/footer.php'; 
} 
?>