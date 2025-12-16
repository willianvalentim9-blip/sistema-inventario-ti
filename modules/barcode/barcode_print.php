<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Imprimir Código de Barras';
$product_id = $_GET['product_id'] ?? null;
$machine_id = $_GET['machine_id'] ?? null;
$warehouse_id = $_GET['warehouse_id'] ?? null;
$code = $_GET['code'] ?? null;
$item = null;
$item_type = '';

if ($product_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $item = $stmt->fetch();
        if ($item) {
            $code = $item['barcode'] ?: $item['serial_number'] ?: ('PROD-' . $item['id']);
            $item_type = $item['category'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar produto: " . $e->getMessage());
    }
} elseif ($machine_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ?");
        $stmt->execute([$machine_id]);
        $item = $stmt->fetch();
        if ($item) {
            $code = $item['barcode'] ?: $item['serial_number'] ?: ('MACH-' . $item['id']);
            $item_type = 'Máquina Pronta';
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar máquina: " . $e->getMessage());
    }
} elseif ($warehouse_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ?");
        $stmt->execute([$warehouse_id]);
        $item = $stmt->fetch();
        if ($item) {
            $code = $item['barcode'] ?: $item['serial_number'] ?: ('WH-' . $item['id']);
            $item_type = $item['category'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar item do armazém: " . $e->getMessage());
    }
}


if (empty($code)) {
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header("Location: " . $redirect_url);
    exit();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2 text-primary-custom"><i class="fas fa-print me-2"></i>Imprimir Código de Barras</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        <button type="button" class="btn btn-sm btn-primary-custom" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimir</button>
    </div>
</div>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8">
            <div class="card card-custom">
                <div class="card-header card-header-custom text-white"><i class="fas fa-barcode me-2"></i>Etiqueta de Identificação</div>
                <div class="card-body text-center p-4" id="printable-area">
                    <?php if ($item): ?>
                        <div class="mb-4">
                            <h4 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($item_type . ' | ' . ($item['model'] ?? '')); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div id="barcode-container" class="mb-3 d-flex justify-content-center align-items-center" style="min-height: 80px;">
                        </div>

                    <div class="barcode-info">
                        <p class="font-monospace h5" id="barcode-text"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 no-print">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8">
            <div class="card card-custom">
                <div class="card-header card-header-custom"><i class="fas fa-cogs me-2"></i>Configurações da Etiqueta</div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="barcode-type" class="form-label form-label-custom">Tipo de Código</label>
                            <select class="form-select form-control-custom" id="barcode-type" onchange="updateBarcode()">
                                <option value="C128" selected>Code 128 (Padrão)</option>
                                <option value="C39">Code 39</option>
                                <option value="EAN13">EAN-13</option>
                                <option value="UPCA">UPC-A</option>
                                <option value="CODABAR">Codabar</option>
                                <option value="I25">Interleaved 2 of 5</option>
                                <option value="PHARMA">Pharmacode</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="barcode-width" class="form-label form-label-custom">Largura da Barra (1-5):</label>
                            <input type="range" class="form-range" id="barcode-width" value="3" min="1" max="5" oninput="updateBarcode()">
                        </div>
                        <div class="col-md-4">
                            <label for="barcode-height" class="form-label form-label-custom">Altura (px):</label>
                            <input type="range" class="form-range" id="barcode-height" value="60" min="20" max="150" oninput="updateBarcode()">
                        </div>
                    </div>
                    
                    <div id="compatibility-error" class="alert alert-danger small mt-3 d-none">
                        </div>
                    <div class="alert alert-warning small mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Atenção:</strong> Alguns tipos de código exigem um formato específico. O sistema tentará ajustar o código para um formato válido.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Esconde TUDO do body */
    body * {
        visibility: hidden !important;
    }

    /* Mostra APENAS #printable-area e seus elementos internos */
    #printable-area,
    #printable-area * {
        visibility: visible !important;
    }

    /* Posiciona #printable-area no topo da página */
    #printable-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        text-align: center !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
    }

    /* Remove fundo e margens do body */
    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Garante que imagens/SVG do código de barras sejam impressas */
    #printable-area img,
    #printable-area svg {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        max-width: 100% !important;
        height: auto !important;
    }

    /* Garante que o texto do código seja impresso */
    #printable-area .barcode-info,
    #printable-area #barcode-text {
        visibility: visible !important;
        display: block !important;
        margin-top: 10px !important;
        font-size: 18px !important;
        font-weight: bold !important;
        letter-spacing: 3px !important;
        color: #000 !important;
    }
}

/* Estilos para tela e impressão */
.barcode-info p {
    letter-spacing: 2px;
    font-weight: 600;
    margin-top: 15px;
}
</style>

<script>
// --- FUNÇÕES DE VALIDAÇÃO DE CHECKSUM ---
function calculateUpcAChecksum(code) {
    if (!/^[0-9]{11}$/.test(code)) return null;
    let sum = 0;
    for (let i = 0; i < 11; i++) {
        sum += parseInt(code.charAt(i), 10) * (i % 2 === 0 ? 3 : 1);
    }
    return (10 - (sum % 10)) % 10;
}

function calculateEan13Checksum(code) {
    if (!/^[0-9]{12}$/.test(code)) return null;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(code.charAt(i), 10) * (i % 2 === 0 ? 1 : 3);
    }
    return (10 - (sum % 10)) % 10;
}


function updateBarcode() {
    const container = document.getElementById('barcode-container');
    const barcodeText = document.getElementById('barcode-text');
    const errorContainer = document.getElementById('compatibility-error');
    
    errorContainer.classList.add('d-none');
    container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div>';
    
    const width = document.getElementById('barcode-width').value;
    const height = document.getElementById('barcode-height').value;
    const type = document.getElementById('barcode-type').value;
    let code = "<?php echo urlencode($code); ?>";
    const originalCode = code;
    
    const typeValidations = {
        'EAN13': {
            regex: /^[0-9]{12,13}$/,
            message: 'O tipo EAN-13 requer 12 ou 13 dígitos numéricos.',
            sanitizer: (c) => c.replace(/[^0-9]/g, '').substring(0, 12)
        },
        'UPCA': {
            regex: /^[0-9]{11,12}$/,
            message: 'O tipo UPC-A requer 11 ou 12 dígitos numéricos.',
            sanitizer: (c) => c.replace(/[^0-9]/g, '').substring(0, 11)
        },
        'PHARMA': {
            regex: /^[0-9]+$/,
            message: 'O tipo Pharmacode só aceita códigos numéricos.',
            sanitizer: (c) => c.replace(/[^0-9]/g, '')
        },
        'I25': {
            regex: /^[0-9]+$/,
            message: 'O tipo Interleaved 2 of 5 só aceita códigos numéricos. Se a quantidade de dígitos for ímpar, um zero será adicionado no início.',
            sanitizer: (c) => c.replace(/[^0-9]/g, '')
        },
        'CODABAR': {
            regex: /^[0-9\-\$\:\/\.\+]+$/,
            message: 'O tipo Codabar só aceita números e os símbolos: - $ : / . +',
            sanitizer: (c) => c.replace(/[^0-9\-\$\:\/\.\+]/g, '')
        },
        'C39': {
            regex: /^[A-Z0-9\-\.\ \$\/\+\%]+$/,
            message: 'O tipo Code 39 só aceita letras maiúsculas, números e os símbolos: - . $ / + %',
            sanitizer: (c) => c.toUpperCase().replace(/[^A-Z0-9\-\.\ \$\/\+\%]/g, '')
        }
    };
    
    if (typeValidations[type]) {
        const validation = typeValidations[type];
        if (!validation.regex.test(originalCode.toUpperCase())) {
            showCompatibilityError(validation.message);
        }
        code = validation.sanitizer(originalCode);
    }
    
    if (type === 'UPCA' && /^[0-9]{12}$/.test(originalCode)) {
        const correctChecksum = calculateUpcAChecksum(originalCode.substring(0, 11));
        const providedChecksum = parseInt(originalCode.charAt(11), 10);
        if (correctChecksum !== providedChecksum) {
            showCompatibilityError(`Dígito verificador incorreto. O esperado era <strong>${correctChecksum}</strong>, mas o código termina com <strong>${providedChecksum}</strong>.`);
        }
    } else if (type === 'EAN13' && /^[0-9]{13}$/.test(originalCode)) {
        const correctChecksum = calculateEan13Checksum(originalCode.substring(0, 12));
        const providedChecksum = parseInt(originalCode.charAt(12), 10);
        if (correctChecksum !== providedChecksum) {
            showCompatibilityError(`Dígito verificador incorreto. O esperado era <strong>${correctChecksum}</strong>, mas o código termina com <strong>${providedChecksum}</strong>.`);
        }
    }
    
    barcodeText.textContent = code || originalCode;

    // Construir URL relativa para o novo local (modules/barcode/)
    // Esta página está em modules/barcode/, então generate_barcode_image.php também está aqui
    const apiUrl = `generate_barcode_image.php?code=${encodeURIComponent(code)}&type=${type}&width=${width}&height=${height}`;
    
    // Usar document.createElement em vez de new Image() para suportar SVG
    const img = document.createElement('img');
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.display = 'block';
    img.crossOrigin = 'anonymous';
    
    img.onload = () => { 
        container.innerHTML = ''; 
        container.appendChild(img); 
    };
    img.onerror = () => { 
        barcodeText.textContent = originalCode;
        container.innerHTML = `<div class="alert alert-danger p-2 small"><strong>Erro ao gerar:</strong> O código <strong>'${originalCode}'</strong> não pôde ser convertido para o formato <strong>${type}</strong>. Verifique se o formato é compatível.</div>`; 
        console.error(`Falha ao carregar a imagem da URL: ${apiUrl}`);
    };
    img.src = apiUrl;
}

function showCompatibilityError(message) {
    const errorContainer = document.getElementById('compatibility-error');
    errorContainer.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i><strong>Incompatibilidade de formato:</strong> ${message}`;
    errorContainer.classList.remove('d-none');
}

document.addEventListener('DOMContentLoaded', updateBarcode);
</script>

<?php include '../../includes/footer.php'; ?>
