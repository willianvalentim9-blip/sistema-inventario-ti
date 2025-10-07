<?php
// ========================================
// PÁGINA DE EDIÇÃO DE MÁQUINA (VERSÃO FINAL COM UPLOAD CORRIGIDO)
// ========================================
require_once 'config.php';
requireLogin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Máquina';
$machine_id = intval($_GET["id"] ?? 0);

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
    $image_to_save = trim($_POST["uploaded_image"] ?? $machine["image"]);
    
    if (empty($name)) {
        $response['message'] = 'O nome da máquina é obrigatório.';
        echo json_encode($response);
        exit;
    } 
    
    try {
        $stmt = $pdo->prepare("UPDATE ready_machines SET name = ?, description = ?, image = ?, processor = ?, memory = ?, storage = ?, graphics = ?, barcode = ?, qr_code = ?, sale_price = ?, status = ?, windows_10_compatible = ?, windows_11_compatible = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $description, $image_to_save, $processor, $memory, $storage, $graphics, $barcode, $qr_code, $sale_price, $status, $windows_10_compatible, $windows_11_compatible, $machine_id]);
        
        $response['success'] = true;
        $response['message'] = 'Máquina atualizada com sucesso!';
        
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'success';
        
        echo json_encode($response);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Erro de banco de dados: ' . $e->getMessage();
        echo json_encode($response);
        exit();
    }
}

if (!$is_modal) {
    include 'includes/header.php';
}

$image_path = 'uploads/machines/' . htmlspecialchars($machine['image'] ?? '');
$image_exists = !empty($machine['image']) && file_exists($image_path);

?>

<form method="POST" action="edit_machine.php?id=<?php echo $machine['id']; ?>&modal=true" enctype="multipart/form-data" id="editMachineForm">
    <div id="edit-error-message-machine" class="mb-3"></div>
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
    // A função setupImageUpload do seu custom.js original será chamada para este formulário.
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

    document.getElementById('editMachineForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonHtml = submitButton.innerHTML;

        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitButton.disabled = true;

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                modal.hide();
                location.reload(); 
            } else {
                document.getElementById('edit-error-message-machine').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                submitButton.innerHTML = originalButtonHtml;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            document.getElementById('edit-error-message-machine').innerHTML = `<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>`;
            submitButton.innerHTML = originalButtonHtml;
            submitButton.disabled = false;
        });
    });

    function generateBarcode() {
        const timestamp = Date.now();
        const barcode = '789' + timestamp.toString().substring(timestamp.toString().length - 9);
        document.getElementById('barcode').value = barcode;
        if (typeof showAlert === 'function') {
            showAlert('Código de barras gerado.', 'info');
        }
    }

    function generateQRCode() {
        const qrCode = 'MACHINE-' + Date.now();
        document.getElementById('qr_code').value = qrCode;
        if (typeof showAlert === 'function') {
            showAlert('Código QR gerado.', 'info');
        }
    }
</script>