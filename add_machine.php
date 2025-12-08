<?php
// ========================================
// PÁGINA DE ADICIONAR MÁQUINA PRONTA (VERSÃO CORRIGIDA COM LAYOUT ORIGINAL)
// ========================================

// Inclui o arquivo de configuração
require_once 'config.php';
require_once 'includes/machine_components_functions.php';

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
    $has_warranty = isset($_POST['machine_has_warranty']) ? 1 : 0;
    $warranty_provider = trim($_POST['machine_warranty_provider'] ?? '');
    $warranty_period_value = !empty($_POST['machine_warranty_period_value']) ? intval($_POST['machine_warranty_period_value']) : null;
    $warranty_period_unit = trim($_POST['machine_warranty_period_unit'] ?? '');
    $machine_components_json = trim($_POST['machine_components'] ?? '{}'); // NOVO - Componentes selecionados
    
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
                // Pegar total de máquinas para contador
                $countStmt = $pdo->query("SELECT COUNT(*) + 1 as next_id FROM ready_machines");
                $nextCount = $countStmt->fetch()['next_id'];

                $dateCode = date('Ymd'); // 20250105
                $timeCode = date('His');  // 143052

                // GERAR NÚMERO DE SÉRIE se vazio
                // Padrão: MAC-{CONTADOR}-{DATA}
                if (empty($serial_number)) {
                    $serial_number = sprintf('MAC-%05d-%s', $nextCount, $dateCode);

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkSN = $pdo->prepare("SELECT id FROM ready_machines WHERE serial_number = ?");
                        $checkSN->execute([$serial_number]);
                        if (!$checkSN->fetch()) {
                            break;
                        }
                        $serial_number = sprintf('MAC-%05d-%s-%02d', $nextCount, $dateCode, $attempts);
                        $attempts++;
                    }
                }

                // GERAR CÓDIGO DE BARRAS se vazio
                // Padrão: {CONTADOR_8_DIGITOS}{TIMESTAMP_4_DIGITOS}
                if (empty($barcode)) {
                    $barcode = sprintf('%08d%04d', $nextCount, substr($timeCode, -4));

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkBC = $pdo->prepare("SELECT id FROM ready_machines WHERE barcode = ?");
                        $checkBC->execute([$barcode]);
                        if (!$checkBC->fetch()) {
                            break;
                        }
                        $barcode = sprintf('%08d%04d', $nextCount, substr($timeCode, -4) + $attempts);
                        $attempts++;
                    }
                }

                // GERAR QR CODE se vazio
                // Padrão: QR-MAC-{CONTADOR}-{TIMESTAMP}
                if (empty($qr_code)) {
                    $qr_code = sprintf('QR-MAC-%05d-%s', $nextCount, $timeCode);

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkQR = $pdo->prepare("SELECT id FROM ready_machines WHERE qr_code = ?");
                        $checkQR->execute([$qr_code]);
                        if (!$checkQR->fetch()) {
                            break;
                        }
                        $qr_code = sprintf('QR-MAC-%05d-%s-%02d', $nextCount, $timeCode, $attempts);
                        $attempts++;
                    }
                }

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
                
                // Salva os componentes da máquina se houver
                if (!empty($machine_components_json) && $machine_components_json !== '{}') {
                    try {
                        saveMachineComponents($pdo, $machine_id, $machine_components_json);
                        
                        // Deduz o stock dos produtos utilizados
                        deductProductsStock($pdo, $machine_id, 1);
                        
                        logAdminActivity($_SESSION["user_id"], 'LINK_MACHINE_COMPONENTS', 'machine_products', $machine_id, 
                            'Machine ' . $machine_id . ' linked with components');
                    } catch (Exception $e) {
                        // Registra erro mas não impede a criação da máquina
                        error_log("Erro ao salvar componentes da máquina: " . $e->getMessage());
                        logAdminActivity($_SESSION["user_id"], 'LINK_MACHINE_COMPONENTS_ERROR', 'machine_products', $machine_id, 
                            'Error: ' . $e->getMessage());
                    }
                }
                
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

<!-- Link CSS para Media Upload -->
<link rel="stylesheet" href="CSS/media-upload.css">
<!-- Link CSS para Modal de Componentes -->
<link rel="stylesheet" href="CSS/machine-components-modal.css">

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
            <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
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
                        <label for="specifications" class="form-label form-label-custom">Especificações Gerais</label>
                        <textarea class="form-control form-control-custom" id="specifications" name="specifications" rows="4" placeholder="Especificações técnicas detalhadas..."><?php echo htmlspecialchars($specifications); ?></textarea>
                    </div>
                </div>

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
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="barcode" name="barcode" placeholder="Ex: 1234567890123" value="<?php echo htmlspecialchars($barcode ?: $code_from_scanner); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" data-bs-toggle="tooltip" title="Gerar código automaticamente">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="barcode">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="qr_code" class="form-label form-label-custom">Código QR</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code" placeholder="Ex: QR123456789" value="<?php echo htmlspecialchars($qr_code); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" data-bs-toggle="tooltip" title="Gerar código QR automaticamente">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="qr_code">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label form-label-custom">Localização</label>
                        <input type="text" class="form-control form-control-custom" id="location" name="location" placeholder="Ex: Showroom A, Bancada 1" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- SEÇÃO 2: PREÇOS E STATUS -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-dollar-sign me-2"></i>Preços e Controle</h5>
                    <div class="mb-3">
                        <label for="sale_price" class="form-label form-label-custom">Preço de Venda (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="sale_price" name="sale_price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($sale_price); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cost_price" class="form-label form-label-custom">Preço de Custo (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="cost_price" name="cost_price" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($cost_price); ?>">
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
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-sticky-note me-2"></i>Observações</h5>
                    <div class="mb-3">
                        <label for="notes" class="form-label form-label-custom">Anotações Gerais</label>
                        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="7" placeholder="Observações adicionais sobre a máquina..."><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- SEÇÃO 3: IMAGEM -->
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-camera me-2"></i>Imagem da Máquina</h5>
                    <div class="mb-3">
                        <div class="media-upload-container" id="machine-upload-container">
                            <div class="media-upload-placeholder">
                                <div class="media-upload-placeholder-content">
                                    <span class="media-upload-placeholder-icon">
                                        <i class="fas fa-desktop"></i>
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
                            <div class="media-upload-preview" style="display: none;">
                                <img class="media-upload-preview-image" src="" alt="Preview">
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
                        <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($uploaded_image); ?>">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- SEÇÃO 4: COMPONENTES -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-microchip me-2"></i>Componentes de Hardware</h5>

                    <div class="mb-3">
                        <label for="processor" class="form-label form-label-custom">Processador</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="processor" name="processor" placeholder="Ex: Intel i7-12700K" value="<?php echo htmlspecialchars($processor); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="CPU" data-component-name="Processador" data-category="CPU">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="memory" class="form-label form-label-custom">Memória RAM</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="memory" name="memory" placeholder="Ex: 16GB DDR4 3200MHz" value="<?php echo htmlspecialchars($memory); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="RAM" data-component-name="Memória RAM" data-category="RAM">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="storage" class="form-label form-label-custom">Armazenamento</label>
                        <div class="input-group">
                            <textarea class="form-control form-control-custom" id="storage" name="storage" placeholder="Ex: SSD 500GB + HDD 1TB" style="height: auto; min-height: 60px; resize: vertical; font-size: 0.9rem;" readonly><?php echo htmlspecialchars($storage); ?></textarea>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="HDD" data-component-name="Armazenamento" data-category="HDD,SSD,NVMe">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Adicione múltiplos itens de armazenamento</small>
                    </div>

                    <div class="mb-3">
                        <label for="graphics" class="form-label form-label-custom">Placa de Vídeo</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="graphics" name="graphics" placeholder="Ex: RTX 3060 12GB" value="<?php echo htmlspecialchars($graphics); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="GPU" data-component-name="Placa de Vídeo" data-category="GPU">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3">&nbsp;</h5>

                    <div class="mb-3">
                        <label for="motherboard" class="form-label form-label-custom">Placa Mãe</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="motherboard" name="motherboard" placeholder="Ex: ASUS B550M-A" value="<?php echo htmlspecialchars($motherboard); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="Motherboard" data-component-name="Placa Mãe" data-category="Motherboard">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="power_supply" class="form-label form-label-custom">Fonte de Alimentação</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="power_supply" name="power_supply" placeholder="Ex: 650W 80+ Bronze" value="<?php echo htmlspecialchars($power_supply); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="PSU" data-component-name="Fonte" data-category="PSU">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="case_type" class="form-label form-label-custom">Gabinete</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="case_type" name="case_type" placeholder="Ex: Mid Tower RGB" value="<?php echo htmlspecialchars($case_type); ?>" readonly>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="Case" data-component-name="Gabinete" data-category="Case">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4">

            <!-- RESUMO DE COMPONENTES -->
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-puzzle-piece me-2"></i>Resumo de Componentes do Estoque</h5>
                    <div id="components-selection-summary">
                        <p class="text-muted"><i class="fas fa-info-circle me-1"></i>Clique no botão <strong>+</strong> ao lado de cada componente para adicionar itens do seu estoque.</p>
                    </div>
                    <input type="hidden" id="machine-components-json" name="machine_components" value="{}">
                </div>
            </div>

            <hr class="my-4">

            <!-- SEÇÃO 5: SISTEMA OPERACIONAL E GARANTIA -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-laptop-code me-2"></i>Sistema Operacional</h5>

                    <div class="mb-3">
                        <label for="operating_system" class="form-label form-label-custom">Sistema Operacional Instalado</label>
                        <select class="form-select form-control-custom" id="operating_system" name="operating_system">
                            <option value="">Sem Sistema Operacional</option>
                            <optgroup label="Windows">
                                <option value="Windows 11 Pro">Windows 11 Pro</option>
                                <option value="Windows 11 Home">Windows 11 Home</option>
                                <option value="Windows 10 Pro">Windows 10 Pro</option>
                                <option value="Windows 10 Home">Windows 10 Home</option>
                                <option value="Windows Server 2022">Windows Server 2022</option>
                                <option value="Windows Server 2019">Windows Server 2019</option>
                            </optgroup>
                            <optgroup label="Linux">
                                <option value="Ubuntu 24.04 LTS">Ubuntu 24.04 LTS</option>
                                <option value="Ubuntu 22.04 LTS">Ubuntu 22.04 LTS</option>
                                <option value="Ubuntu 20.04 LTS">Ubuntu 20.04 LTS</option>
                                <option value="Debian 12">Debian 12</option>
                                <option value="Debian 11">Debian 11</option>
                                <option value="Fedora Workstation">Fedora Workstation</option>
                                <option value="Linux Mint">Linux Mint</option>
                                <option value="Pop!_OS">Pop!_OS</option>
                                <option value="Arch Linux">Arch Linux</option>
                                <option value="CentOS Stream">CentOS Stream</option>
                                <option value="Red Hat Enterprise Linux">Red Hat Enterprise Linux</option>
                            </optgroup>
                            <optgroup label="macOS">
                                <option value="macOS Sonoma">macOS Sonoma</option>
                                <option value="macOS Ventura">macOS Ventura</option>
                                <option value="macOS Monterey">macOS Monterey</option>
                            </optgroup>
                            <optgroup label="Outro">
                                <option value="FreeBSD">FreeBSD</option>
                                <option value="Chrome OS">Chrome OS</option>
                                <option value="Outro">Outro</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Compatibilidade Windows</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="windows_10_compatible" name="windows_10_compatible" value="1" <?php if($windows_10_compatible) echo 'checked'; ?>>
                            <label class="form-check-label" for="windows_10_compatible">Compatível com Windows 10</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="windows_11_compatible" name="windows_11_compatible" value="1" <?php if($windows_11_compatible) echo 'checked'; ?>>
                            <label class="form-check-label" for="windows_11_compatible">Compatível com Windows 11</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-shield-alt me-2"></i>Garantia</h5>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="machine_has_warranty" name="machine_has_warranty" value="1">
                        <label class="form-check-label" for="machine_has_warranty">
                            <strong>Máquina possui garantia?</strong>
                        </label>
                    </div>

                    <div id="warranty-fields-add" style="display: none;">
                        <div class="mb-3">
                            <label for="machine_warranty_provider" class="form-label form-label-custom">
                                <i class="fas fa-building me-1"></i>Fornecedor da Garantia
                            </label>
                            <input type="text" class="form-control form-control-custom"
                                   id="machine_warranty_provider"
                                   name="machine_warranty_provider"
                                   placeholder="Ex: Samsung, LG, Autorizado">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="machine_warranty_period_value" class="form-label form-label-custom">
                                    <i class="fas fa-hourglass-half me-1"></i>Duração
                                </label>
                                <input type="number" class="form-control form-control-custom"
                                       id="machine_warranty_period_value"
                                       name="machine_warranty_period_value"
                                       min="1" placeholder="Ex: 12">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="machine_warranty_period_unit" class="form-label form-label-custom">
                                    <i class="fas fa-calendar-alt me-1"></i>Unidade
                                </label>
                                <select class="form-select form-control-custom"
                                        id="machine_warranty_period_unit"
                                        name="machine_warranty_period_unit">
                                    <option value="days">Dias</option>
                                    <option value="months" selected>Meses</option>
                                    <option value="years">Anos</option>
                                </select>
                            </div>
                        </div>
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

<script src="js/media-upload.js"></script>
<script src="js/machine-components-integration.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hasWarrantyCheckbox = document.getElementById('machine_has_warranty');
    const warrantyFieldsRow = document.getElementById('warranty-fields-add');
    const addMachineForm = document.getElementById('add-machine-form');

    // Warranty checkbox handler
    if (hasWarrantyCheckbox) {
        hasWarrantyCheckbox.addEventListener('change', function() {
            if (this.checked) {
                warrantyFieldsRow.style.display = '';
            } else {
                warrantyFieldsRow.style.display = 'none';
            }
        });
    }
    
    // Form submission handler com validação de stock
    if (addMachineForm) {
        addMachineForm.addEventListener('submit', function(e) {
            // Obtém os componentes selecionados
            const componentsJson = document.getElementById('machine-components-json');
            if (!componentsJson) return; // Continue com submit normal se não houver componentes
            
            const selectedComponents = JSON.parse(componentsJson.value || '{}');
            
            // Se há componentes selecionados, mostra confirmação de stock
            if (Object.keys(selectedComponents).length > 0) {
                e.preventDefault();
                showStockConfirmationModal(selectedComponents, function(confirmed) {
                    if (confirmed) {
                        addMachineForm.submit();
                    }
                });
            }
        });
    }
});

function showStockConfirmationModal(components, callback) {
    let componentsHtml = '';
    const componentsList = [];
    
    for (const [type, product] of Object.entries(components)) {
        componentsList.push({type, ...product});
    }
    
    componentsHtml = componentsList.map(item => `
        <div class="alert alert-warning alert-sm mb-2" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>${item.type}:</strong> ${item.productName}
        </div>
    `).join('');
    
    const modalHtml = `
        <div class="modal fade" id="stockConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="stockConfirmationTitle">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="stockConfirmationTitle">
                            <i class="fas fa-box me-2"></i>Confirmar Dedução de Estoque
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            <strong>Você está vinculando os seguintes componentes a esta máquina:</strong>
                        </p>
                        <div class="components-list mb-3">
                            ${componentsHtml}
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Atenção:</strong> Estes itens serão <strong>DEDUZIDOS</strong> do seu estoque quando a máquina for salva.
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmStockDeduction" required>
                            <label class="form-check-label" for="confirmStockDeduction">
                                Eu confirmo a dedução destes itens do estoque
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmStockBtn" disabled>
                            <i class="fas fa-check me-1"></i>Confirmar e Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Salva posição de scroll
    const scrollPosition = window.scrollY;
    
    // Remove modal anterior se existir
    const oldModal = document.getElementById('stockConfirmationModal');
    if (oldModal) oldModal.remove();
    
    // Adiciona novo modal ao body
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = modalHtml;
    document.body.appendChild(tempDiv.firstElementChild);
    
    // Configura handlers
    const modal = new bootstrap.Modal(document.getElementById('stockConfirmationModal'), {
        keyboard: true,
        backdrop: true,
        scroll: true
    });
    const confirmCheckbox = document.getElementById('confirmStockDeduction');
    const confirmBtn = document.getElementById('confirmStockBtn');
    
    confirmCheckbox.addEventListener('change', function() {
        confirmBtn.disabled = !this.checked;
    });
    
    confirmBtn.addEventListener('click', function() {
        modal.hide();
        callback(true);
    });
    
    // Listener para quando modal fecha
    document.getElementById('stockConfirmationModal').addEventListener('hidden.bs.modal', function() {
        window.scrollTo(0, scrollPosition);
        callback(false);
    }, { once: true });
    
    modal.show();
    
    // Restaura scroll após modal abrir
    setTimeout(() => {
        window.scrollTo(0, scrollPosition);
    }, 100);
}

function removeImage() {
    document.getElementById('uploaded_image').value = '';
    const container = document.getElementById('machine-upload-container');
    if (container) {
        const manager = window.machineUploadManager;
        if (manager) {
            manager.removeImage();
        }
    }
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