<?php

require_once 'config.php';
requireLogin();

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    ob_start();
}

$page_title = 'Adicionar Produto';
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    ob_start(); 

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $min_quantity = intval($_POST['min_quantity'] ?? 5);
    $max_quantity = intval($_POST['max_quantity'] ?? 100);
    $price = floatval($_POST['price'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $uploaded_image = trim($_POST['uploaded_image'] ?? '');
    
    if (empty($name)) {
        $error_message = 'O nome do produto é obrigatório.';
    } elseif (empty($category)) {
        $error_message = 'A categoria do produto é obrigatória.';
    } elseif ($quantity < 0) {
        $error_message = 'A quantidade não pode ser negativa.';
    } elseif ($price < 0) {
        $error_message = 'O preço não pode ser negativo.';
    } elseif ($max_quantity > 0 && $quantity > $max_quantity) {
        $error_message = 'A quantidade inicial (' . $quantity . ') não pode ser maior que a quantidade máxima permitida (' . $max_quantity . ').';
    } else {
        try {
            $pdo = getConnection();
            
            if (!empty($barcode)) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
                $stmt->execute([$barcode]);
                if ($stmt->fetch()) {
                    $error_message = 'Já existe um produto com este código de barras.';
                }
            }
            
            if (empty($error_message) && !empty($qr_code)) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE qr_code = ?");
                $stmt->execute([$qr_code]);
                if ($stmt->fetch()) {
                    $error_message = 'Já existe um produto com este código QR.';
                }
            }
            
            if (empty($error_message) && !empty($serial_number)) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE serial_number = ?");
                $stmt->execute([$serial_number]);
                if ($stmt->fetch()) {
                    $error_message = 'Já existe um produto com este número de série.';
                }
            }
            
            if (empty($error_message)) {
                if (empty($serial_number) && empty($barcode)) {
                    $categoryCode = '';
                    switch (strtoupper($category)) {
                        case 'CPU': $categoryCode = 'CPU'; break;
                        case 'RAM': $categoryCode = 'RAM'; break;
                        case 'SSD': $categoryCode = 'SSD'; break;
                        case 'HDD': $categoryCode = 'HDD'; break;
                        case 'GPU': $categoryCode = 'GPU'; break;
                        case 'MOTHERBOARD': $categoryCode = 'MB'; break;
                        case 'PSU': $categoryCode = 'PSU'; break;
                        case 'CASE': $categoryCode = 'CASE'; break;
                        case 'MONITOR': $categoryCode = 'MON'; break;
                        case 'KEYBOARD': $categoryCode = 'KB'; break;
                        case 'MOUSE': $categoryCode = 'MS'; break;
                        case 'NETWORK': $categoryCode = 'NET'; break;
                        default: $categoryCode = 'GEN';
                    }
                    
                    $timestamp = time();
                    $randomNumber = rand(1000, 9999);
                    $generated_barcode = 'IT-' . $categoryCode . '-' . $timestamp . '-' . $randomNumber;
                    
                    $attempts = 0;
                    while ($attempts < 5) {
                        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
                        $check_stmt->execute([$generated_barcode]);
                        if (!$check_stmt->fetch()) {
                            break;
                        }
                        $randomNumber = rand(1000, 9999);
                        $generated_barcode = 'IT-' . $categoryCode . '-' . $timestamp . '-' . $randomNumber;
                        $attempts++;
                    }
                    
                    $barcode = $generated_barcode;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        name, description, image, category, manufacturer, model, 
                        serial_number, barcode, qr_code, quantity, min_quantity, max_quantity, price, 
                        location, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $name, $description, $uploaded_image ?: null, $category, $manufacturer, $model,
                    $serial_number ?: null, $barcode ?: null, $qr_code ?: null,
                    $quantity, $min_quantity, $max_quantity, $price ?: null, $location ?: null, $status
                ]);
                
                $product_id = $pdo->lastInsertId();

                logAdminActivity($_SESSION["user_id"], "CREATE", "products", $product_id);

                logProductMovement(
                    $product_id,
                    $_SESSION["user_id"],
                    "entrada",
                    $quantity,
                    0,
                    $quantity,
                    "Produto criado: " . $name
                );

                header("Location: products.php");
                exit();
            }
            
        } catch (PDOException $e) {
            $error_message = 'Erro ao adicionar produto: ' . $e->getMessage();
            error_log("Erro ao adicionar produto: " . $e->getMessage());
        }
    }

    $output = ob_get_clean(); 
    if (!empty($output)) {
        error_log("Saída inesperada em add_product.php: " . $output);
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") {
            echo json_encode(["success" => false, "message" => "Erro interno do servidor: Saída inesperada antes do JSON."]);
            exit;
        }
    }
}

$code_from_scanner = $_GET['code'] ?? '';
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-plus-circle me-2"></i>
        Adicionar Produto
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="products.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar para Produtos
            </a>
        </div>
    </div>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-custom" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-custom" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-box me-2"></i>
                Informações do Produto
            </div>
            <div class="card-body">
                <form method="POST" action="" id="add-product-form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary-custom mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Informações Básicas
                            </h5>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label form-label-custom">
                                    <i class="fas fa-tag me-1"></i>
                                    Nome do Produto *
                                </label>
                                <input type="text" class="form-control form-control-custom" id="name" name="name" placeholder="Ex: SSD Kingston 240GB" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label form-label-custom">
                                    <i class="fas fa-folder me-1"></i>
                                    Categoria *
                                </label>
                                <select class="form-select form-control-custom" id="category" name="category" required>
                                    <option value="">Selecione uma categoria</option>
                                    <option value="CPU" <?php echo ($category ?? '') === 'CPU' ? 'selected' : ''; ?>>CPU/Processador</option>
                                    <option value="RAM" <?php echo ($category ?? '') === 'RAM' ? 'selected' : ''; ?>>Memória RAM</option>
                                    <option value="SSD" <?php echo ($category ?? '') === 'SSD' ? 'selected' : ''; ?>>SSD</option>
                                    <option value="HDD" <?php echo ($category ?? '') === 'HDD' ? 'selected' : ''; ?>>HD/HDD</option>
                                    <option value="GPU" <?php echo ($category ?? '') === 'GPU' ? 'selected' : ''; ?>>Placa de Vídeo</option>
                                    <option value="Motherboard" <?php echo ($category ?? '') === 'Motherboard' ? 'selected' : ''; ?>>Placa Mãe</option>
                                    <option value="PSU" <?php echo ($category ?? '') === 'PSU' ? 'selected' : ''; ?>>Fonte</option>
                                    <option value="Case" <?php echo ($category ?? '') === 'Case' ? 'selected' : ''; ?>>Gabinete</option>
                                    <option value="Cable" <?php echo ($category ?? '') === 'Cable' ? 'selected' : ''; ?>>Cabo</option>
                                    <option value="Monitor" <?php echo ($category ?? '') === 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                                    <option value="Keyboard" <?php echo ($category ?? '') === 'Keyboard' ? 'selected' : ''; ?>>Teclado</option>
                                    <option value="Mouse" <?php echo ($category ?? '') === 'Mouse' ? 'selected' : ''; ?>>Mouse</option>
                                    <option value="Network" <?php echo ($category ?? '') === 'Network' ? 'selected' : ''; ?>>Rede</option>
                                    <option value="Other" <?php echo ($category ?? '') === 'Other' ? 'selected' : ''; ?>>Outros</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="manufacturer" class="form-label form-label-custom">
                                    <i class="fas fa-industry me-1"></i>
                                    Fabricante
                                </label>
                                <input type="text" class="form-control form-control-custom" id="manufacturer" name="manufacturer" placeholder="Ex: Kingston, Intel, AMD" value="<?php echo htmlspecialchars($manufacturer ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="model" class="form-label form-label-custom">
                                    <i class="fas fa-barcode me-1"></i>
                                    Modelo
                                </label>
                                <input type="text" class="form-control form-control-custom" id="model" name="model" placeholder="Ex: A400, i7-12700K" value="<?php echo htmlspecialchars($model ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label form-label-custom">
                                    <i class="fas fa-align-left me-1"></i>
                                    Descrição
                                </label>
                                <textarea class="form-control form-control-custom" id="description" name="description" rows="3" placeholder="Descrição detalhada do produto..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_image" class="form-label form-label-custom">
                                    <i class="fas fa-camera me-1"></i>
                                    Imagem do Produto
                                </label>
                                <div class="upload-area border rounded p-4 text-center" id="upload-area" style="cursor: pointer; border-style: dashed !important;">
                                    <input type="file" class="form-control form-control-custom" id="product_image" name="product_image" accept="image/*" style="display: none;">
                                    <div class="upload-placeholder" id="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-2">Clique aqui ou arraste uma imagem</p>
                                        <small class="text-muted">JPG, PNG, GIF ou WebP (máx. 5MB)</small>
                                    </div>
                                    <div class="upload-preview" id="upload-preview" style="display: none;">
                                        <img id="preview-image" src="" alt="Preview" class="img-thumbnail mb-2" style="max-width: 200px;">
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                                <i class="fas fa-trash me-1"></i>
                                                Remover Imagem
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($uploaded_image ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-primary-custom mb-3">
                                <i class="fas fa-qrcode me-2"></i>
                                Códigos e Identificação
                            </h5>
                            
                            <div class="mb-3">
                                <label for="serial_number" class="form-label form-label-custom">
                                    <i class="fas fa-hashtag me-1"></i>
                                    Número de Série
                                </label>
                                <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number" placeholder="Ex: SN123456789" value="<?php echo htmlspecialchars($serial_number ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="barcode" class="form-label form-label-custom">
                                    <i class="fas fa-barcode me-1"></i>
                                    Código de Barras
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-custom" id="barcode" name="barcode" placeholder="Ex: 1234567890123" value="<?php echo htmlspecialchars($barcode ?? $code_from_scanner); ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" data-bs-toggle="tooltip" title="Gerar código automaticamente">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#scannerModal" data-target-input="barcode">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="qr_code" class="form-label form-label-custom">
                                    <i class="fas fa-qrcode me-1"></i>
                                    Código QR
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code" placeholder="Ex: QR123456789" value="<?php echo htmlspecialchars($qr_code ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" data-bs-toggle="tooltip" title="Gerar código QR automaticamente">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                     <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#scannerModal" data-target-input="qr_code">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="quantity" class="form-label form-label-custom">
                                    <i class="fas fa-cubes me-1"></i>
                                    Quantidade em Estoque *
                                </label>
                                <input type="number" class="form-control form-control-custom" id="quantity" name="quantity" min="0" placeholder="0" value="<?php echo htmlspecialchars($quantity ?? 0); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="min_quantity" class="form-label form-label-custom">
                                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                    Quantidade Mínima
                                </label>
                                <input type="number" class="form-control form-control-custom" id="min_quantity" name="min_quantity" min="0" value="<?php echo htmlspecialchars($min_quantity ?? 5); ?>" placeholder="Alerta quando estoque baixo">
                                <div class="form-text">Quantidade mínima para alerta de estoque baixo</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="max_quantity" class="form-label form-label-custom">
                                    <i class="fas fa-chart-line me-1 text-info"></i>
                                    Quantidade Máxima
                                </label>
                                <input type="number" class="form-control form-control-custom" id="max_quantity" name="max_quantity" min="1" value="<?php echo htmlspecialchars($max_quantity ?? 100); ?>" placeholder="Limite máximo de estoque">
                                <div class="form-text">Quantidade máxima permitida no estoque</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label form-label-custom">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    Preço (R$)
                                </label>
                                <input type="number" class="form-control form-control-custom" id="price" name="price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($price ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label form-label-custom">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    Localização
                                </label>
                                <input type="text" class="form-control form-control-custom" id="location" name="location" placeholder="Ex: Prateleira A1, Gaveta 3" value="<?php echo htmlspecialchars($location ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label form-label-custom">
                                    <i class="fas fa-flag me-1"></i>
                                    Status
                                </label>
                                <select class="form-select form-control-custom" id="status" name="status">
                                    <option value="available" <?php echo ($status ?? 'available') === 'available' ? 'selected' : ''; ?>>Disponível</option>
                                    <option value="in_use" <?php echo ($status ?? '') === 'in_use' ? 'selected' : ''; ?>>Em Uso</option>
                                    <option value="defective" <?php echo ($status ?? '') === 'defective' ? 'selected' : ''; ?>>Defeituoso</option>
                                    <option value="maintenance" <?php echo ($status ?? '') === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                                </select>
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
                                    <a href="products.php" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-save me-1"></i>
                                        Salvar Produto
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

<div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scannerModalLabel"><i class="fas fa-barcode me-2"></i>Scanner de Código</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="scannerIframe" src="" style="width: 100%; height: 450px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scannerModal = new bootstrap.Modal(document.getElementById('scannerModal'));
    const scannerIframe = document.getElementById('scannerIframe');
    const scannerModalElement = document.getElementById('scannerModal');

    scannerModalElement.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const targetInputId = button.getAttribute('data-target-input');
        scannerIframe.src = `scanner_modal.php?target=${targetInputId}`;
    });

    scannerModalElement.addEventListener('hidden.bs.modal', function () {
        scannerIframe.src = '';
    });

    window.setScannedCode = function(code, targetId) {
        document.getElementById(targetId).value = code;
        scannerModal.hide();
    };
    
    // ... (restante do seu JavaScript original)
    
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('product_image');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const uploadedImageInput = document.getElementById('uploaded_image');
    
    uploadArea.addEventListener('click', function() { fileInput.click(); });
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.style.backgroundColor = '#f8f9fa';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.style.backgroundColor = '';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.style.backgroundColor = '';
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
    
    function handleFileUpload(file) {
        if (file.size > 5 * 1024 * 1024) {
            showAlert('Arquivo muito grande. Tamanho máximo: 5MB', 'warning');
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP', 'warning');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            uploadPlaceholder.style.display = 'none';
            uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', 'products');
        
        fetch('upload_image.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadedImageInput.value = data.filename;
                showAlert('Imagem enviada com sucesso!', 'success');
            } else {
                showAlert('Erro no upload: ' + data.message, 'danger');
                removeImage();
            }
        })
        .catch(error => {
            showAlert('Erro no upload da imagem.', 'danger');
            console.error('Error:', error);
            removeImage();
        });
    }
    
    const form = document.getElementById('add-product-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const category = document.getElementById('category').value;
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (!name) {
                e.preventDefault();
                showAlert('O nome do produto é obrigatório.', 'warning');
                document.getElementById('name').focus();
                return false;
            }
            if (!category) {
                e.preventDefault();
                showAlert('A categoria do produto é obrigatória.', 'warning');
                document.getElementById('category').focus();
                return false;
            }
            if (isNaN(quantity) || quantity < 0) {
                e.preventDefault();
                showAlert('A quantidade deve ser um número válido e não negativo.', 'warning');
                document.getElementById('quantity').focus();
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
});

function removeImage() {
    document.getElementById('upload-placeholder').style.display = 'block';
    document.getElementById('upload-preview').style.display = 'none';
    document.getElementById('preview-image').src = '';
    document.getElementById('uploaded_image').value = '';
    document.getElementById('product_image').value = '';
}

function generateBarcode() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 1000);
    const barcode = timestamp.toString() + random.toString().padStart(3, '0');
    document.getElementById('barcode').value = barcode.substring(0, 13);
    showAlert('Código de barras gerado automaticamente.', 'info');
}

function generateQRCode() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 10000);
    const qrCode = 'QR' + timestamp.toString() + random.toString().padStart(4, '0');
    document.getElementById('qr_code').value = qrCode;
    showAlert('Código QR gerado automaticamente.', 'info');
}

function resetForm() {
    if (confirm('Tem certeza que deseja limpar todos os campos?')) {
        document.getElementById('add-product-form').reset();
        removeImage();
        showAlert('Formulário limpo com sucesso.', 'info');
    }
}

document.getElementById('category').addEventListener('change', function() {
    const category = this.value;
    const manufacturerField = document.getElementById('manufacturer');
    const suggestions = {
        'CPU': 'Intel, AMD', 'RAM': 'Kingston, Corsair, G.Skill',
        'SSD': 'Kingston, Samsung, WD', 'HDD': 'Seagate, WD, Toshiba',
        'GPU': 'NVIDIA, AMD, ASUS', 'Motherboard': 'ASUS, MSI, Gigabyte',
        'PSU': 'Corsair, EVGA, Seasonic', 'Monitor': 'LG, Samsung, ASUS'
    };
    if (suggestions[category]) {
        manufacturerField.setAttribute('placeholder', 'Ex: ' + suggestions[category]);
    }
});
</script>

<?php 
if (empty($serial_number) && empty($barcode) && empty($qr_code)) {
    $qr_code = 'PROD-' . strtoupper(uniqid());
}
?>