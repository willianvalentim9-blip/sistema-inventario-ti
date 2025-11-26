<?php
// ========================================
// PÁGINA DE EDIÇÃO DE PRODUTO
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
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];

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
    
    if (empty($name) || empty($category)) {
        $response['message'] = 'Nome e Categoria são obrigatórios.';
        echo json_encode($response);
        exit;
    } elseif ($max_quantity > 0 && $product['quantity'] > $max_quantity) {
        $response['message'] = 'A quantidade máxima (' . $max_quantity . ') não pode ser menor que a quantidade atual em estoque (' . $product['quantity'] . ').';
        echo json_encode($response);
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
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $description, $image_to_save, $category, $manufacturer, $model, 
            $serial_number, $barcode, $qr_code, $min_quantity, $max_quantity, 
            $price, $location, $status, $product_id
        ]);
        
        $new_data_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $new_data_stmt->execute([$product_id]);
        $new_data = $new_data_stmt->fetch();

        logAdminActivity($_SESSION["user_id"], "UPDATE", "products", $product_id, $old_data, $new_data);

        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Produto atualizado com sucesso!';
        
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'success';

        echo json_encode($response);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        $response['message'] = 'Erro de banco de dados: ' . $e->getMessage();
        echo json_encode($response);
        exit();
    }
}

// Se não for uma requisição modal (carregada via AJAX), inclui o cabeçalho completo da página
if (!$is_modal) {
    include 'includes/header.php';
}
?>

<form method="POST" action="edit_product.php?id=<?php echo $product['id']; ?>" enctype="multipart/form-data" id="editProductForm">
    <div id="edit-error-message-product" class="mb-3"></div>
    <div class="row">
        <div class="col-md-6">
            <h5 class="text-primary-custom mb-3"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h5>
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
                <div class="upload-area border rounded p-4 text-center" id="product-upload-area" style="cursor: pointer; min-height: 150px; display: flex; align-items: center; justify-content: center;">
                    <input type="file" class="form-control" id="product_image_input" accept="image/*" style="display: none;">
                    <div id="product-placeholder" style="<?php echo !empty($product['image']) ? 'display: none;' : ''; ?>">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-2">Clique aqui ou arraste uma imagem</p>
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
            <h5 class="text-primary-custom mb-3"><i class="fas fa-qrcode me-2"></i>Códigos e Estoque</h5>
            <div class="mb-3">
                <label for="serial_number" class="form-label form-label-custom"><i class="fas fa-hashtag me-1"></i>Nº de Série</label>
                <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($product['serial_number'] ?? ''); ?>">
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
                <input type="number" class="form-control form-control-custom" id="min_quantity" name="min_quantity" value="<?php echo htmlspecialchars($product['min_quantity']); ?>" min="0">
            </div>
            <div class="mb-3">
                <label for="max_quantity" class="form-label form-label-custom"><i class="fas fa-chart-line text-info me-1"></i>Qtd. Máxima</label>
                <input type="number" class="form-control form-control-custom" id="max_quantity" name="max_quantity" value="<?php echo htmlspecialchars($product['max_quantity']); ?>" min="0">
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
                </select>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end border-top pt-3 mt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
    </div>
</form>

<?php 
// Se o script for carregado como uma página completa, inclui o footer.
// Se for carregado em um modal, o footer da página principal já fará o trabalho.
if (!$is_modal) { 
    include 'includes/footer.php'; 
} 
?>

<script>
// A lógica de controle do modal do scanner foi removida daqui, pois agora é global.
// A função `window.setScannedCode` no footer.php cuidará de tudo.

document.addEventListener('DOMContentLoaded', function() {
    
    // Script para upload de imagem (permanece igual)
    if (typeof setupImageUpload === 'function') {
        setupImageUpload({
            formId: 'editProductForm',
            fileInputId: 'product_image_input',
            hiddenInputId: 'uploaded_image',
            previewImageId: 'product-preview-image',
            placeholderId: 'product-placeholder',
            previewContainerId: 'product-preview-container',
            removeBtnId: 'product-remove-btn',
            uploadAreaId: 'product-upload-area',
            itemType: 'product',
            itemId: <?php echo $product_id; ?>
        });
    }

    // Script de submissão do formulário via AJAX (permanece igual)
    const editForm = document.getElementById('editProductForm');
    if(editForm) {
        editForm.addEventListener('submit', function(e) {
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
                    const modalInstance = bootstrap.Modal.getInstance(this.closest('.modal'));
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    // Recarrega a página para mostrar a mensagem flash e atualizar os dados
                    location.reload(); 
                } else {
                    document.getElementById('edit-error-message-product').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                document.getElementById('edit-error-message-product').innerHTML = `<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>`;
                submitButton.innerHTML = originalButtonHtml;
                submitButton.disabled = false;
            });
        });
    }
});

// Funções para gerar códigos (permanecem iguais)
function generateBarcode() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 1000);
    const barcode = timestamp.toString() + random.toString().padStart(3, '0');
    document.getElementById('barcode').value = barcode.substring(0, 13);
    if (typeof showAlert === 'function') {
        showAlert('Código de barras gerado automaticamente.', 'info');
    }
}

function generateQRCode() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 10000);
    const qrCode = 'QR' + timestamp.toString() + random.toString().padStart(4, '0');
    document.getElementById('qr_code').value = qrCode;
    if (typeof showAlert === 'function') {
        showAlert('Código QR gerado automaticamente.', 'info');
    }
}
</script>