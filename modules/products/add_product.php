<?php

require_once '../../config.php';
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

    // Novos campos de garantia
    $has_warranty = isset($_POST['has_warranty']) ? 1 : 0;
    $warranty_provider = trim($_POST['warranty_provider'] ?? '');
    $warranty_period_value = !empty($_POST['warranty_period_value']) ? intval($_POST['warranty_period_value']) : null;
    $warranty_period_unit = trim($_POST['warranty_period_unit'] ?? '');
    $warranty_start_date = !empty($_POST['warranty_start_date']) ? trim($_POST['warranty_start_date']) : null;
    $warranty_end_date = !empty($_POST['warranty_end_date']) ? trim($_POST['warranty_end_date']) : null;
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $warranty_notes = trim($_POST['warranty_notes'] ?? '');
    
    if (empty($name)) {
        $error_message = 'O nome do produto é obrigatório.';
    } elseif (empty($category)) {
        $error_message = 'A categoria do produto é obrigatória.';
    } elseif ($min_quantity < 1) {
        $error_message = '⚠️ AVISO: A quantidade mínima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
    } elseif ($max_quantity < 1) {
        $error_message = '⚠️ AVISO: A quantidade máxima deve ser no mínimo 1. Defina um valor válido para o controle de estoque.';
    } elseif ($min_quantity == 1 && $max_quantity == 1) {
        $error_message = '⚠️ AVISO: A quantidade mínima e máxima não podem ser ambas 1. Defina valores diferentes para o controle de estoque.';
    } elseif ($quantity < 0) {
        $error_message = 'A quantidade não pode ser negativa.';
    } elseif ($price < 0) {
        $error_message = 'O preço não pode ser negativo.';
    } elseif ($min_quantity > $max_quantity && $max_quantity > 0) {
        $error_message = 'A quantidade mínima (' . $min_quantity . ') não pode ser maior que a quantidade máxima (' . $max_quantity . ').';
    } elseif ($max_quantity > 0 && $quantity > $max_quantity) {
        $error_message = 'A quantidade inicial (' . $quantity . ') não pode ser maior que a quantidade máxima permitida (' . $max_quantity . '). O produto está no limite máximo de estoque.';
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
                // Pegar total de produtos para contador
                $countStmt = $pdo->query("SELECT COUNT(*) + 1 as next_id FROM products");
                $nextCount = $countStmt->fetch()['next_id'];

                // Código da categoria
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

                $dateCode = date('Ymd'); // 20250105
                $timeCode = date('His');  // 143052

                // GERAR NÚMERO DE SÉRIE se vazio
                // Padrão: SN-{CATEGORIA}-{CONTADOR}-{DATA}
                if (empty($serial_number)) {
                    $serial_number = sprintf('SN-%s-%05d-%s', $categoryCode, $nextCount, $dateCode);

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkSN = $pdo->prepare("SELECT id FROM products WHERE serial_number = ?");
                        $checkSN->execute([$serial_number]);
                        if (!$checkSN->fetch()) {
                            break;
                        }
                        $serial_number = sprintf('SN-%s-%05d-%s-%02d', $categoryCode, $nextCount, $dateCode, $attempts);
                        $attempts++;
                    }
                }

                // GERAR CÓDIGO DE BARRAS se vazio
                // Padrão: {CONTADOR_8_DIGITOS}{TIMESTAMP_4_DIGITOS} = 12 dígitos (EAN-12)
                if (empty($barcode)) {
                    $barcode = sprintf('%08d%04d', $nextCount, substr($timeCode, -4));

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkBC = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
                        $checkBC->execute([$barcode]);
                        if (!$checkBC->fetch()) {
                            break;
                        }
                        $barcode = sprintf('%08d%04d', $nextCount, substr($timeCode, -4) + $attempts);
                        $attempts++;
                    }
                }

                // GERAR QR CODE se vazio
                // Padrão: QR-{CATEGORIA}-{CONTADOR}-{TIMESTAMP}
                if (empty($qr_code)) {
                    $qr_code = sprintf('QR-%s-%05d-%s', $categoryCode, $nextCount, $timeCode);

                    // Verificar duplicatas
                    $attempts = 0;
                    while ($attempts < 10) {
                        $checkQR = $pdo->prepare("SELECT id FROM products WHERE qr_code = ?");
                        $checkQR->execute([$qr_code]);
                        if (!$checkQR->fetch()) {
                            break;
                        }
                        $qr_code = sprintf('QR-%s-%05d-%s-%02d', $categoryCode, $nextCount, $timeCode, $attempts);
                        $attempts++;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        name, description, image, category, manufacturer, model, 
                        serial_number, barcode, qr_code, quantity, min_quantity, max_quantity, 
                        price, location, status, has_warranty, warranty_provider, 
                        warranty_period_value, warranty_period_unit, warranty_start_date, 
                        warranty_end_date, invoice_number, warranty_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $name, $description, $uploaded_image ?: null, $category, $manufacturer, $model,
                    $serial_number ?: null, $barcode ?: null, $qr_code ?: null,
                    $quantity, $min_quantity, $max_quantity, $price ?: null, $location ?: null, $status,
                    $has_warranty, $has_warranty ? $warranty_provider : null, $has_warranty ? $warranty_period_value : null,
                    $has_warranty ? $warranty_period_unit : null, $has_warranty ? $warranty_start_date : null,
                    $has_warranty ? $warranty_end_date : null, $has_warranty ? $invoice_number : null, $has_warranty ? $warranty_notes : null
                ]);
                
                $product_id = $pdo->lastInsertId();

                logAdminActivity($_SESSION["user_id"], "CREATE", "products", $product_id);

                // Registrar criação de garantia no histórico se o produto tem garantia
                if ($has_warranty) {
                    $warranty_data = [
                        'warranty_provider' => $warranty_provider ?? '',
                        'warranty_start_date' => $warranty_start_date ?? null,
                        'warranty_end_date' => $warranty_end_date ?? null,
                        'warranty_period_value' => $warranty_period_value ?? null,
                        'warranty_period_unit' => $warranty_period_unit ?? null,
                        'warranty_notes' => $warranty_notes ?? ''
                    ];
                    registerWarrantyHistory($pdo, $product_id, 'CREATE', [], $warranty_data, $_SESSION["user_id"], 'product');
                }

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

<?php include '../../includes/header.php'; ?>

<!-- Link CSS para Media Upload -->
<link rel="stylesheet" href="../../CSS/media-upload.css">

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
                <form method="POST" action="add_product.php" id="add-product-form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
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
                                    <!-- Componentes PC -->
                                    <optgroup label="Componentes de PC">
                                        <option value="CPU" <?php echo ($category ?? '') === 'CPU' ? 'selected' : ''; ?>>CPU/Processador</option>
                                        <option value="RAM" <?php echo ($category ?? '') === 'RAM' ? 'selected' : ''; ?>>Memória RAM</option>
                                        <option value="SSD" <?php echo ($category ?? '') === 'SSD' ? 'selected' : ''; ?>>SSD</option>
                                        <option value="NVMe" <?php echo ($category ?? '') === 'NVMe' ? 'selected' : ''; ?>>NVMe M.2</option>
                                        <option value="HDD" <?php echo ($category ?? '') === 'HDD' ? 'selected' : ''; ?>>HD/HDD</option>
                                        <option value="GPU" <?php echo ($category ?? '') === 'GPU' ? 'selected' : ''; ?>>Placa de Vídeo/GPU</option>
                                        <option value="Motherboard" <?php echo ($category ?? '') === 'Motherboard' ? 'selected' : ''; ?>>Placa Mãe</option>
                                        <option value="PSU" <?php echo ($category ?? '') === 'PSU' ? 'selected' : ''; ?>>Fonte/PSU</option>
                                        <option value="Case" <?php echo ($category ?? '') === 'Case' ? 'selected' : ''; ?>>Gabinete/Case</option>
                                        <option value="Cooler" <?php echo ($category ?? '') === 'Cooler' ? 'selected' : ''; ?>>Cooler/Ventilador</option>
                                        <option value="Memoria-Cache" <?php echo ($category ?? '') === 'Memoria-Cache' ? 'selected' : ''; ?>>Cache/Buffer</option>
                                    </optgroup>
                                    <!-- Periféricos -->
                                    <optgroup label="Periféricos &amp; Entrada">
                                        <option value="Monitor" <?php echo ($category ?? '') === 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                                        <option value="Keyboard" <?php echo ($category ?? '') === 'Keyboard' ? 'selected' : ''; ?>>Teclado</option>
                                        <option value="Mouse" <?php echo ($category ?? '') === 'Mouse' ? 'selected' : ''; ?>>Mouse</option>
                                        <option value="Headset" <?php echo ($category ?? '') === 'Headset' ? 'selected' : ''; ?>>Headset/Fone</option>
                                        <option value="Webcam" <?php echo ($category ?? '') === 'Webcam' ? 'selected' : ''; ?>>Webcam</option>
                                        <option value="Scanner" <?php echo ($category ?? '') === 'Scanner' ? 'selected' : ''; ?>>Scanner</option>
                                        <option value="Impressora" <?php echo ($category ?? '') === 'Impressora' ? 'selected' : ''; ?>>Impressora</option>
                                    </optgroup>
                                    <!-- Rede -->
                                    <optgroup label="Rede &amp; Conectividade">
                                        <option value="Network-Card" <?php echo ($category ?? '') === 'Network-Card' ? 'selected' : ''; ?>>Placa de Rede</option>
                                        <option value="Adaptador-USB" <?php echo ($category ?? '') === 'Adaptador-USB' ? 'selected' : ''; ?>>Adaptador USB</option>
                                        <option value="Adaptador-Video" <?php echo ($category ?? '') === 'Adaptador-Video' ? 'selected' : ''; ?>>Adaptador Vídeo</option>
                                        <option value="Hub-USB" <?php echo ($category ?? '') === 'Hub-USB' ? 'selected' : ''; ?>>Hub USB</option>
                                        <option value="Docking-Station" <?php echo ($category ?? '') === 'Docking-Station' ? 'selected' : ''; ?>>Docking Station</option>
                                    </optgroup>
                                    <!-- Cabos e Acessórios -->
                                    <optgroup label="Cabos &amp; Acessórios">
                                        <option value="Cabo-HDMI" <?php echo ($category ?? '') === 'Cabo-HDMI' ? 'selected' : ''; ?>>Cabo HDMI</option>
                                        <option value="Cabo-USB" <?php echo ($category ?? '') === 'Cabo-USB' ? 'selected' : ''; ?>>Cabo USB</option>
                                        <option value="Cabo-DisplayPort" <?php echo ($category ?? '') === 'Cabo-DisplayPort' ? 'selected' : ''; ?>>Cabo DisplayPort</option>
                                        <option value="Cabo-RJ45" <?php echo ($category ?? '') === 'Cabo-RJ45' ? 'selected' : ''; ?>>Cabo RJ45/Rede</option>
                                        <option value="Cabo-Energia" <?php echo ($category ?? '') === 'Cabo-Energia' ? 'selected' : ''; ?>>Cabo Energia</option>
                                        <option value="Adaptador-Energia" <?php echo ($category ?? '') === 'Adaptador-Energia' ? 'selected' : ''; ?>>Adaptador/Carregador</option>
                                        <option value="Bateria" <?php echo ($category ?? '') === 'Bateria' ? 'selected' : ''; ?>>Bateria</option>
                                        <option value="Suporte-Fixacao" <?php echo ($category ?? '') === 'Suporte-Fixacao' ? 'selected' : ''; ?>>Suporte/Fixação</option>
                                        <option value="Painel-Frontal" <?php echo ($category ?? '') === 'Painel-Frontal' ? 'selected' : ''; ?>>Painel Frontal</option>
                                    </optgroup>
                                    <!-- Manutenção e Limpeza -->
                                    <optgroup label="Manutenção &amp; Ferramentas">
                                        <option value="Pasta-Termica" <?php echo ($category ?? '') === 'Pasta-Termica' ? 'selected' : ''; ?>>Pasta Térmica</option>
                                        <option value="Spray-Limpeza" <?php echo ($category ?? '') === 'Spray-Limpeza' ? 'selected' : ''; ?>>Spray Limpeza</option>
                                        <option value="Ferramenta" <?php echo ($category ?? '') === 'Ferramenta' ? 'selected' : ''; ?>>Ferramenta</option>
                                        <option value="Kit-Limpeza" <?php echo ($category ?? '') === 'Kit-Limpeza' ? 'selected' : ''; ?>>Kit Limpeza</option>
                                    </optgroup>
                                    <!-- Armazenamento Externo -->
                                    <optgroup label="Armazenamento Externo">
                                        <option value="HD-Externo" <?php echo ($category ?? '') === 'HD-Externo' ? 'selected' : ''; ?>>HD Externo</option>
                                        <option value="SSD-Externo" <?php echo ($category ?? '') === 'SSD-Externo' ? 'selected' : ''; ?>>SSD Externo</option>
                                        <option value="Pendrive" <?php echo ($category ?? '') === 'Pendrive' ? 'selected' : ''; ?>>Pendrive/USB</option>
                                        <option value="Cartao-Memoria" <?php echo ($category ?? '') === 'Cartao-Memoria' ? 'selected' : ''; ?>>Cartão de Memória</option>
                                    </optgroup>
                                    <!-- Outros -->
                                    <option value="Outros" <?php echo ($category ?? '') === 'Outros' ? 'selected' : ''; ?>>Outros</option>
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
                                <label class="form-label form-label-custom">
                                    <i class="fas fa-camera me-1"></i>
                                    Imagem do Produto
                                </label>
                                <div class="media-upload-container" id="product-upload-container">
                                    <div class="media-upload-placeholder">
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
                                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($uploaded_image ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
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
                                    <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="barcode">
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
                                     <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="qr_code">
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
                                    Quantidade Mínima *
                                </label>
                                <input type="number" class="form-control form-control-custom" id="min_quantity" name="min_quantity" min="1" value="<?php echo htmlspecialchars($min_quantity ?? 5); ?>" placeholder="Mínimo: 1" required>
                                <div class="form-text" id="min-quantity-help">Quantidade mínima para alerta de estoque baixo (mínimo: 1)</div>
                                <div class="invalid-feedback" id="min-quantity-error" style="display: none;">
                                    ⚠️ Valor mínimo necessário para salvar: 1
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="max_quantity" class="form-label form-label-custom">
                                    <i class="fas fa-chart-line me-1 text-info"></i>
                                    Quantidade Máxima *
                                </label>
                                <input type="number" class="form-control form-control-custom" id="max_quantity" name="max_quantity" min="1" value="<?php echo htmlspecialchars($max_quantity ?? 100); ?>" placeholder="Mínimo: 1" required>
                                <div class="form-text" id="max-quantity-help">Quantidade máxima permitida no estoque (mínimo: 1)</div>
                                <div class="invalid-feedback" id="max-quantity-error" style="display: none;">
                                    ⚠️ Valor mínimo necessário para salvar: 1
                                </div>
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
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="has_warranty" name="has_warranty" value="1">
                                <label class="form-check-label" for="has_warranty">
                                    <strong>Produto possui garantia?</strong>
                                </label>
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Marque se o produto possui garantia. Você poderá gerenciar todos os detalhes após salvar o produto.
                                </small>
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
                                    <button type="submit" class="btn btn-primary-custom" id="save-product-btn" disabled>
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

<?php include '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // REFERÊNCIAS
    const hasWarrantyCheckbox = document.getElementById('has_warranty');

    // ========================================
    // VALIDAÇÃO DE QUANTIDADE MÍNIMA E MÁXIMA
    // ========================================
    const saveProductBtn = document.getElementById('save-product-btn');
    const minQuantityInput = document.getElementById('min_quantity');
    const maxQuantityInput = document.getElementById('max_quantity');
    const quantityInput = document.getElementById('quantity');
    const minQuantityError = document.getElementById('min-quantity-error');
    const maxQuantityError = document.getElementById('max-quantity-error');

    function validateQuantities() {
        const minQty = parseInt(minQuantityInput.value) || 0;
        const maxQty = parseInt(maxQuantityInput.value) || 0;
        const currentQty = parseInt(quantityInput?.value) || 0;
        let isValid = true;

        // Validar quantidade mínima
        if (minQty < 1) {
            minQuantityInput.classList.add('is-invalid');
            minQuantityError.textContent = '⚠️ Valor mínimo necessário para salvar: 1';
            minQuantityError.style.display = 'block';
            isValid = false;
        } else {
            minQuantityInput.classList.remove('is-invalid');
            minQuantityError.style.display = 'none';
        }

        // Validar quantidade máxima
        if (maxQty < 1) {
            maxQuantityInput.classList.add('is-invalid');
            maxQuantityError.textContent = '⚠️ Valor mínimo necessário para salvar: 1';
            maxQuantityError.style.display = 'block';
            isValid = false;
        } else {
            maxQuantityInput.classList.remove('is-invalid');
            maxQuantityError.style.display = 'none';
        }

        // VALIDAÇÃO: min e max não podem ser ambos 1
        if (minQty === 1 && maxQty === 1) {
            minQuantityInput.classList.add('is-invalid');
            maxQuantityInput.classList.add('is-invalid');
            if (minQuantityError) {
                minQuantityError.textContent = '⚠️ Min e max não podem ser ambas 1';
                minQuantityError.style.display = 'block';
            }
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

        // VALIDAÇÃO: quantidade atual não pode ser maior que máxima
        if (maxQty > 0 && currentQty > maxQty) {
            maxQuantityInput.classList.add('is-invalid');
            if (maxQuantityError) {
                maxQuantityError.textContent = `⚠️ A quantidade inicial (${currentQty}) não pode ser maior que a máxima (${maxQty})`;
                maxQuantityError.style.display = 'block';
            }
            isValid = false;
        }

        // Habilitar/desabilitar botão salvar
        if (saveProductBtn) {
            saveProductBtn.disabled = !isValid;
        }

        return isValid;
    }

    // Validar em tempo real
    if (minQuantityInput) {
        minQuantityInput.addEventListener('input', validateQuantities);
        minQuantityInput.addEventListener('change', validateQuantities);
    }
    if (maxQuantityInput) {
        maxQuantityInput.addEventListener('input', validateQuantities);
        maxQuantityInput.addEventListener('change', validateQuantities);
    }
    if (quantityInput) {
        quantityInput.addEventListener('input', validateQuantities);
        quantityInput.addEventListener('change', validateQuantities);
    }

    // Validar no carregamento da página
    setTimeout(() => {
        validateQuantities();
    }, 100);

    // Prevenir submissão se inválido
    const addProductForm = document.getElementById('add-product-form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            const currentMinQty = parseInt(minQuantityInput.value) || 0;
            const currentMaxQty = parseInt(maxQuantityInput.value) || 0;
            const currentQty = parseInt(quantityInput?.value) || 0;

            if (!validateQuantities()) {
                e.preventDefault();

                // Verificar qual é o erro específico
                if (currentMinQty === 1 && currentMaxQty === 1) {
                    alert('⚠️ AVISO: A quantidade mínima e máxima não podem ser ambas 1.\n\nDefina valores diferentes para o controle de estoque.');
                } else if (currentMinQty > currentMaxQty && currentMaxQty > 0) {
                    alert(`⚠️ AVISO: A quantidade mínima (${currentMinQty}) não pode ser maior que a máxima (${currentMaxQty}).\n\nDefina valores válidos.`);
                } else if (currentMaxQty > 0 && currentQty > currentMaxQty) {
                    alert(`⚠️ AVISO: A quantidade inicial (${currentQty}) não pode ser maior que a máxima (${currentMaxQty}).\n\nO produto está no limite máximo de estoque.`);
                } else {
                    alert('⚠️ AVISO: As quantidades mínima e máxima devem ser no mínimo 1.\n\nDefina valores válidos para o controle de estoque antes de salvar.');
                }

                // Scroll para o primeiro campo inválido
                if (minQuantityInput.classList.contains('is-invalid')) {
                    minQuantityInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (maxQuantityInput.classList.contains('is-invalid')) {
                    maxQuantityInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                return false;
            }
        });
    }


});
</script>

<script src="../../js/media-upload.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // VALIDAÇÃO DO FORMULÁRIO
    // ========================================
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

    // ========================================
    // LÓGICA DA GARANTIA
    // ========================================
    const warrantyStartDateInput = document.getElementById('warranty_start_date');
    const warrantyPeriodValueInput = document.getElementById('warranty_period_value');
    const warrantyPeriodUnitInput = document.getElementById('warranty_period_unit');
    const warrantyEndDateInput = document.getElementById('warranty_end_date');

    // Função para calcular a data final da garantia
    function calculateWarrantyEndDate() {
        if (!warrantyStartDateInput || !warrantyEndDateInput) return;

        const startDate = warrantyStartDateInput.value;
        const periodValue = parseInt(warrantyPeriodValueInput.value);
        const periodUnit = warrantyPeriodUnitInput.value;

        if (startDate && periodValue > 0) {
            const date = new Date(startDate + 'T00:00:00');

            if (periodUnit === 'days') {
                date.setDate(date.getDate() + periodValue);
            } else if (periodUnit === 'months') {
                date.setMonth(date.getMonth() + periodValue);
            } else if (periodUnit === 'years') {
                date.setFullYear(date.getFullYear() + periodValue);
            }

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            warrantyEndDateInput.value = `${year}-${month}-${day}`;
        }
    }

    // Adiciona listeners para recalcular a data final
    if (warrantyStartDateInput) {
        warrantyStartDateInput.addEventListener('change', calculateWarrantyEndDate);
        warrantyPeriodValueInput.addEventListener('input', calculateWarrantyEndDate);
        warrantyPeriodUnitInput.addEventListener('change', calculateWarrantyEndDate);
    }

    // ========================================
    // SUGESTÕES DE FABRICANTE POR CATEGORIA
    // ========================================
    const categorySelect = document.getElementById('category');
    const manufacturerField = document.getElementById('manufacturer');

    if (categorySelect && manufacturerField) {
        categorySelect.addEventListener('change', function() {
            const category = this.value;
            const suggestions = {
                'CPU': 'Intel, AMD',
                'RAM': 'Kingston, Corsair, G.Skill',
                'SSD': 'Kingston, Samsung, WD',
                'HDD': 'Seagate, WD, Toshiba',
                'GPU': 'NVIDIA, AMD, ASUS',
                'Motherboard': 'ASUS, MSI, Gigabyte',
                'PSU': 'Corsair, EVGA, Seasonic',
                'Monitor': 'LG, Samsung, ASUS'
            };
            if (suggestions[category]) {
                manufacturerField.setAttribute('placeholder', 'Ex: ' + suggestions[category]);
            }
        });
    }
    // ========================================
    // FUNÇÃO: LIMPAR FORMULÁRIO
    // ========================================
    window.resetForm = function() {
        if (confirm('Deseja limpar todos os campos do formulário?')) {
            document.getElementById('add-product-form').reset();
            if (hasWarrantyCheckbox) {
                hasWarrantyCheckbox.checked = false;
            }
            showAlert('Formulário limpo!', 'info');
        }
    };
});

function removeImage() {
    document.getElementById('uploaded_image').value = '';
    const container = document.getElementById('product-upload-container');
    if (container) {
        const manager = window.productUploadManager;
        if (manager) {
            manager.removeImage();
        }
    }
}