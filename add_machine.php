<?php
// ========================================
// PÁGINA DE ADICIONAR MÁQUINA PRONTA (VERSÃO CORRIGIDA COM LAYOUT ORIGINAL)
// ========================================

// Inclui o arquivo de configuração
require_once 'config.php';

// Verifica se o usuário está logado
requireLogin();

// Define variáveis para o template
$page_title = 'Adicionar Máquina Pronta';

// Variáveis para controle de mensagens e valores do formulário
$error_message = '';
$success_message = '';
$name = ''; 
$description = ''; 
$specifications = ''; 
$processor = ''; 
$memory = ''; 
$storage = ''; 
$graphics = ''; 
$motherboard = ''; 
$power_supply = ''; 
$case_type = ''; 
$serial_number = ''; 
$barcode = ''; 
$qr_code = ''; 
$quantity = 1; // Valor padrão para o novo campo
$sale_price = ''; 
$cost_price = ''; 
$windows_10_compatible = 0; 
$windows_11_compatible = 0; 
$status = 'available'; 
$location = ''; 
$notes = ''; 
$uploaded_image = '';


// ========================================
// PROCESSAMENTO DO FORMULÁRIO
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $specifications = trim($_POST['specifications'] ?? '');
    $processor = trim($_POST['processor'] ?? '');
    $memory = trim($_POST['memory'] ?? '');
    $storage = trim($_POST['storage'] ?? '');
    $graphics = trim($_POST['graphics'] ?? '');
    $motherboard = trim($_POST['motherboard'] ?? '');
    $power_supply = trim($_POST['power_supply'] ?? '');
    $case_type = trim($_POST['case_type'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1); // NOVO CAMPO
    $sale_price = floatval($_POST['sale_price'] ?? 0);
    $cost_price = floatval($_POST['cost_price'] ?? 0);
    $windows_10_compatible = isset($_POST['windows_10_compatible']) ? 1 : 0;
    $windows_11_compatible = isset($_POST['windows_11_compatible']) ? 1 : 0;
    $status = $_POST['status'] ?? 'available';
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $uploaded_image = trim($_POST['uploaded_image'] ?? '');
    
    // Validação básica
    if (empty($name)) {
        $error_message = 'O nome da máquina é obrigatório.';
    } elseif ($quantity <= 0) {
        $error_message = 'A quantidade deve ser de pelo menos 1.';
    } elseif ($sale_price < 0) {
        $error_message = 'O preço de venda não pode ser negativo.';
    } elseif ($cost_price < 0) {
        $error_message = 'O preço de custo não pode ser negativo.';
    } else {
        try {
            $pdo = getConnection();
            
            // Verifica duplicidades
            if (!empty($barcode)) {
                $stmt = $pdo->prepare("SELECT id FROM ready_machines WHERE barcode = ?");
                $stmt->execute([$barcode]);
                if ($stmt->fetch()) $error_message = 'Já existe uma máquina com este código de barras.';
            }
            if (empty($error_message) && !empty($qr_code)) {
                $stmt = $pdo->prepare("SELECT id FROM ready_machines WHERE qr_code = ?");
                $stmt->execute([$qr_code]);
                if ($stmt->fetch()) $error_message = 'Já existe uma máquina com este código QR.';
            }
            if (empty($error_message) && !empty($serial_number)) {
                $stmt = $pdo->prepare("SELECT id FROM ready_machines WHERE serial_number = ?");
                $stmt->execute([$serial_number]);
                if ($stmt->fetch()) $error_message = 'Já existe uma máquina com este número de série.';
            }
            
            // Se não há erros, insere a máquina
            if (empty($error_message)) {
                $stmt = $pdo->prepare("
                    INSERT INTO ready_machines (
                        name, description, image, specifications, processor, memory, storage, 
                        graphics, motherboard, power_supply, case_type, serial_number, 
                        barcode, qr_code, quantity, sale_price, cost_price, windows_10_compatible, 
                        windows_11_compatible, status, location, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $name, $description, $uploaded_image ?: null, $specifications, $processor, 
                    $memory, $storage, $graphics, $motherboard, $power_supply, $case_type,
                    $serial_number ?: null, $barcode ?: null, $qr_code ?: null, $quantity,
                    $sale_price ?: null, $cost_price ?: null, $windows_10_compatible,
                    $windows_11_compatible, $status, $location ?: null, $notes ?: null
                ]);
                
                $machine_id = $pdo->lastInsertId();
                logAdminActivity($_SESSION["user_id"], 'CREATE_MACHINE', 'ready_machines', $machine_id);
                
                header("Location: ready_machines.php");
                exit();
            }
            
        } catch (PDOException $e) {
            $error_message = 'Erro ao adicionar máquina: ' . $e->getMessage();
            error_log("Erro ao adicionar máquina: " . $e->getMessage());
        }
    }
}

$code_from_scanner = $_GET['code'] ?? '';
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-desktop me-2"></i>Adicionar Máquina Pronta</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="ready_machines.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar para Máquinas</a>
        </div>
        <div class="btn-group">
            <a href="scanner.php" class="btn btn-sm btn-secondary-custom"><i class="fas fa-qrcode me-1"></i>Scanner</a>
        </div>
    </div>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-custom" role="alert"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="card card-custom">
    <div class="card-header card-header-custom"><i class="fas fa-desktop me-2"></i>Informações da Máquina Pronta</div>
    <div class="card-body">
        <form method="POST" action="" id="add-machine-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h5>
                    <div class="mb-3">
                        <label for="name" class="form-label form-label-custom">Nome da Máquina *</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name" placeholder="Ex: PC Gamer i7 RTX 3060" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label form-label-custom">Descrição</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="3" placeholder="Descrição geral da máquina..."><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="machine_image" class="form-label form-label-custom">Imagem da Máquina</label>
                        <div class="upload-area border rounded p-4 text-center" id="upload-area" style="cursor: pointer; border-style: dashed !important;">
                            <input type="file" class="form-control form-control-custom" id="machine_image" name="machine_image" accept="image/*" style="display: none;">
                            <div class="upload-placeholder" id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-2">Clique aqui ou arraste uma imagem</p>
                                <small class="text-muted">JPG, PNG, GIF ou WebP (máx. 5MB)</small>
                            </div>
                            <div class="upload-preview" id="upload-preview" style="display: none;">
                                <img id="preview-image" src="" alt="Preview" class="img-thumbnail mb-2" style="max-width: 200px;">
                                <div><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()"><i class="fas fa-trash me-1"></i>Remover Imagem</button></div>
                            </div>
                        </div>
                        <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($uploaded_image); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="specifications" class="form-label form-label-custom">Especificações Gerais</label>
                        <textarea class="form-control form-control-custom" id="specifications" name="specifications" rows="4" placeholder="Especificações técnicas detalhadas..."><?php echo htmlspecialchars($specifications); ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-microchip me-2"></i>Componentes</h5>
                    <div class="mb-3"><label for="processor" class="form-label form-label-custom">Processador</label><input type="text" class="form-control form-control-custom" id="processor" name="processor" placeholder="Ex: Intel i7-12700K" value="<?php echo htmlspecialchars($processor); ?>"></div>
                    <div class="mb-3"><label for="memory" class="form-label form-label-custom">Memória RAM</label><input type="text" class="form-control form-control-custom" id="memory" name="memory" placeholder="Ex: 16GB DDR4 3200MHz" value="<?php echo htmlspecialchars($memory); ?>"></div>
                    <div class="mb-3"><label for="storage" class="form-label form-label-custom">Armazenamento</label><input type="text" class="form-control form-control-custom" id="storage" name="storage" placeholder="Ex: SSD 500GB + HDD 1TB" value="<?php echo htmlspecialchars($storage); ?>"></div>
                    <div class="mb-3"><label for="graphics" class="form-label form-label-custom">Placa de Vídeo</label><input type="text" class="form-control form-control-custom" id="graphics" name="graphics" placeholder="Ex: RTX 3060 12GB" value="<?php echo htmlspecialchars($graphics); ?>"></div>
                    <div class="mb-3"><label for="motherboard" class="form-label form-label-custom">Placa Mãe</label><input type="text" class="form-control form-control-custom" id="motherboard" name="motherboard" placeholder="Ex: ASUS B550M-A" value="<?php echo htmlspecialchars($motherboard); ?>"></div>
                    <div class="mb-3"><label for="power_supply" class="form-label form-label-custom">Fonte</label><input type="text" class="form-control form-control-custom" id="power_supply" name="power_supply" placeholder="Ex: 650W 80+ Bronze" value="<?php echo htmlspecialchars($power_supply); ?>"></div>
                    <div class="mb-3"><label for="case_type" class="form-label form-label-custom">Gabinete</label><input type="text" class="form-control form-control-custom" id="case_type" name="case_type" placeholder="Ex: Mid Tower RGB" value="<?php echo htmlspecialchars($case_type); ?>"></div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-qrcode me-2"></i>Códigos e Identificação</h5>
                    <div class="mb-3">
                        <label for="serial_number" class="form-label form-label-custom">Número de Série</label>
                        <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number" placeholder="Ex: PC123456789" value="<?php echo htmlspecialchars($serial_number); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label form-label-custom"><i class="fas fa-cubes me-1"></i>Quantidade em Estoque *</label>
                        <input type="number" class="form-control form-control-custom" id="quantity" name="quantity" min="1" value="<?php echo htmlspecialchars($quantity); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="barcode" class="form-label form-label-custom">Código de Barras</label>
                        <div class="input-group"><input type="text" class="form-control form-control-custom" id="barcode" name="barcode" placeholder="Ex: 1234567890123" value="<?php echo htmlspecialchars($barcode ?: $code_from_scanner); ?>"><button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" data-bs-toggle="tooltip" title="Gerar código"><i class="fas fa-magic"></i></button></div>
                    </div>
                    <div class="mb-3">
                        <label for="qr_code" class="form-label form-label-custom">Código QR</label>
                        <div class="input-group"><input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code" placeholder="Ex: QR123456789" value="<?php echo htmlspecialchars($qr_code); ?>"><button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" data-bs-toggle="tooltip" title="Gerar código QR"><i class="fas fa-magic"></i></button></div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label form-label-custom">Localização</label>
                        <input type="text" class="form-control form-control-custom" id="location" name="location" placeholder="Ex: Showroom A, Bancada 1" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-dollar-sign me-2"></i>Preços e Compatibilidade</h5>
                    <div class="mb-3">
                        <label for="sale_price" class="form-label form-label-custom">Preço de Venda (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="sale_price" name="sale_price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($sale_price); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cost_price" class="form-label form-label-custom">Preço de Custo (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="cost_price" name="cost_price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($cost_price); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Compatibilidade Windows</label>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="windows_10_compatible" name="windows_10_compatible" value="1" <?php if($windows_10_compatible) echo 'checked'; ?>><label class="form-check-label" for="windows_10_compatible">Compatível com Windows 10</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="windows_11_compatible" name="windows_11_compatible" value="1" <?php if($windows_11_compatible) echo 'checked'; ?>><label class="form-check-label" for="windows_11_compatible">Compatível com Windows 11</label></div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label form-label-custom">Status</label>
                        <select class="form-select form-control-custom" id="status" name="status">
                            <option value="available" <?php if($status === 'available') echo 'selected'; ?>>Disponível para Venda</option>
                            <option value="sold" <?php if($status === 'sold') echo 'selected'; ?>>Vendida</option>
                            <option value="reserved" <?php if($status === 'reserved') echo 'selected'; ?>>Reservada</option>
                            <option value="maintenance" <?php if($status === 'maintenance') echo 'selected'; ?>>Em Manutenção</option>
                            <option value="testing" <?php if($status === 'testing') echo 'selected'; ?>>Em Teste</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label form-label-custom">Observações</label>
                        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="3" placeholder="Observações adicionais..."><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-between">
                <div><button type="button" class="btn btn-outline-secondary me-2" onclick="resetForm()"><i class="fas fa-undo me-1"></i>Limpar Formulário</button></div>
                <div><a href="ready_machines.php" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Cancelar</a><button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i>Salvar Máquina</button></div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('machine_image');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const uploadedImageInput = document.getElementById('uploaded_image');
    
    uploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function() { if (this.files.length > 0) handleFileUpload(this.files[0]); });
    
    function handleFileUpload(file) {
        if (file.size > 5 * 1024 * 1024) { showAlert('Arquivo muito grande (máx 5MB).', 'warning'); return; }
        const reader = new FileReader();
        reader.onload = e => {
            previewImage.src = e.target.result;
            uploadPlaceholder.style.display = 'none';
            uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', 'machine');
        
        fetch('upload_image.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                uploadedImageInput.value = data.filename;
                showAlert('Imagem enviada com sucesso!', 'success');
            } else {
                showAlert('Erro no upload: ' + data.message, 'danger');
                removeImage();
            }
        })
        .catch(() => { showAlert('Erro de comunicação no upload.', 'danger'); removeImage(); });
    }
});

function removeImage() {
    document.getElementById('upload-placeholder').style.display = 'block';
    document.getElementById('upload-preview').style.display = 'none';
    document.getElementById('preview-image').src = '';
    document.getElementById('uploaded_image').value = '';
    document.getElementById('machine_image').value = '';
}

function generateBarcode() {
    const timestamp = Date.now();
    const barcode = '789' + timestamp.toString().substring(timestamp.toString().length - 9);
    document.getElementById('barcode').value = barcode;
    showAlert('Código de barras gerado.', 'info');
}

function generateQRCode() {
    const qrCode = 'MACHINE-' + Date.now();
    document.getElementById('qr_code').value = qrCode;
    showAlert('Código QR gerado.', 'info');
}

function resetForm() {
    if (confirm('Tem certeza que deseja limpar todos os campos?')) {
        document.getElementById('add-machine-form').reset();
        removeImage();
        showAlert('Formulário limpo.', 'info');
    }
}
</script>

<?php include 'includes/footer.php'; ?>