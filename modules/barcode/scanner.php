<?php
// ========================================
// PÁGINA DE SCANNER QR/CÓDIGO DE BARRAS HÍBRIDO (VERSÃO COM AÇÕES NO HISTÓRICO E MODAL CORRIGIDO)
// ========================================

// Inclui o arquivo de configuração
require_once '../../config.php';

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

// Define variáveis para o template
$page_title = 'Scanner QR/Código de Barras';
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
if ($is_modal) {
    $hide_sidebar = true;
}
?>

<?php if (!$is_modal) include '../../includes/header.php'; ?>

<style>
    #scanner-container {
        position: relative;
        max-width: 600px;
        margin: auto;
        border-radius: 8px;
        overflow: hidden; 
        background: #000;
        aspect-ratio: 16 / 9;
    }
    #scanner-video, #quagga-canvas {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    #quagga-canvas.drawingBuffer {
        position: absolute;
        top: 0;
        left: 0;
    }
    #scanner-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 2;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    #scanner-frame {
        background-color: transparent !important;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        50% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }
</style>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-qrcode me-2"></i>
        Scanner QR/Código de Barras
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-camera-btn">
                <i class="fas fa-camera me-1"></i>
                <span id="camera-btn-text">Iniciar Câmera</span>
            </button>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-secondary-custom" id="switch-camera-btn" disabled>
                <i class="fas fa-sync-alt me-1"></i>
                Trocar Câmera
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-camera me-2"></i>
                Scanner de Códigos
            </div>
            <div class="card-body text-center">
                <div class="btn-group mb-3" role="group" aria-label="Modo de Scanner">
                    <input type="radio" class="btn-check" name="scan-mode" id="mode-barcode" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="mode-barcode"><i class="fas fa-barcode me-1"></i>Código de Barras</label>

                    <input type="radio" class="btn-check" name="scan-mode" id="mode-qrcode" autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode-qrcode"><i class="fas fa-qrcode me-1"></i>QR Code</label>
                </div>

                <div id="scanner-container" class="position-relative mb-3">
                    <video id="scanner-video" 
                           style="border-radius: 8px;"
                           autoplay 
                           muted 
                           playsinline>
                    </video>
                    
                    <div id="scanner-overlay">
                        <div id="scanner-frame" class="rounded">
                        </div>
                    </div>
                    
                    <canvas id="quagga-canvas" class="drawingBuffer" style="display: none;"></canvas>
                </div>
                
                <div id="scanner-status" class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Clique em "Iniciar Câmera" para começar a escanear códigos
                </div>
                
                <div id="scan-result" class="alert alert-success" role="alert" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Código detectado:</strong> <span id="scanned-code"></span>
                </div>
                
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary-custom" id="start-scan-btn" disabled>
                        <i class="fas fa-play me-1"></i>
                        Iniciar Scan
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="stop-scan-btn" disabled>
                        <i class="fas fa-stop me-1"></i>
                        Parar Scan
                    </button>
                    <button type="button" class="btn btn-outline-info" id="capture-manual-btn">
                        <i class="fas fa-camera me-1"></i>
                        Capturar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-cog me-2"></i>
                Configurações do Scanner
            </div>
            <div class="card-body">
                <div id="barcode-types-config">
                    <label class="form-label">Tipos de Código de Barras</label>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="code128" value="code_128_reader" checked>
                        <label class="form-check-label" for="code128">CODE 128</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="ean" value="ean_reader" checked>
                        <label class="form-check-label" for="ean">EAN-13</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="ean8" value="ean_8_reader">
                        <label class="form-check-label" for="ean8">EAN-8</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="code39" value="code_39_reader">
                        <label class="form-check-label" for="code39">CODE 39</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="upc" value="upc_reader">
                        <label class="form-check-label" for="upc">UPC-A</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-keyboard me-2"></i>
                Entrada Manual
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="manual-code" class="form-label form-label-custom">
                        Código Manual
                    </label>
                    <input type="text" 
                           class="form-control form-control-custom" 
                           id="manual-code" 
                           placeholder="Digite ou cole o código aqui">
                </div>
                <button type="button" class="btn btn-outline-primary w-100" id="process-manual-code-btn">
                    <i class="fas fa-search me-1"></i>
                    Buscar Código
                </button>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i>Histórico de Scans</span>
                <button class="btn btn-sm btn-outline-danger" id="clear-history-btn" title="Limpar histórico"><i class="fas fa-trash"></i></button>
            </div>
            <div class="card-body">
                <div id="scan-history" class="list-group list-group-flush">
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <p>Nenhum scan realizado ainda</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-bolt me-2"></i>
                Ações Rápidas
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success" id="add-product-btn" disabled>
                        <i class="fas fa-plus me-1"></i>
                        Adicionar Produto
                    </button>
                    <button type="button" class="btn btn-outline-info" id="add-machine-btn" disabled>
                        <i class="fas fa-desktop me-1"></i>
                        Adicionar Máquina
                    </button>
                    <button type="button" class="btn btn-outline-warning" id="search-code-btn" disabled>
                        <i class="fas fa-search me-1"></i>
                        Buscar no Estoque
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="searchResultModal" tabindex="-1" aria-labelledby="searchResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchResultModalLabel">
                    <i class="fas fa-search me-2"></i>
                    Resultado da Busca
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="search-result-content">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_modal) include '../../includes/footer.php'; ?>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>

<script>
// ========================================
// VARIÁVEIS GLOBAIS
// ========================================
let html5QrCode = null;
let quaggaRunning = false;
let cameraStream = null;
let availableCameras = [];
let currentCameraId = null;
let lastScannedCode = "";
let scanHistory = [];
let currentScanMode = "barcode";
const urlParams = new URLSearchParams(window.location.search);
const isModalMode = urlParams.get('modal') === 'true';
const targetInputId = urlParams.get('target');

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener("DOMContentLoaded", function() {
    loadScanHistory();
    setupEventListeners();
    initializeCameraDevices();
    document.getElementById("mode-barcode").checked = true;
    updateScanMode("barcode"); 
    if (isModalMode) {
        setTimeout(toggleCamera, 500);
    }
});

function setupEventListeners() {
    document.getElementById("toggle-camera-btn").addEventListener("click", toggleCamera);
    document.getElementById("switch-camera-btn").addEventListener("click", switchCamera);
    document.getElementById("start-scan-btn").addEventListener("click", startScanning);
    document.getElementById("stop-scan-btn").addEventListener("click", stopScanning);
    document.getElementById("capture-manual-btn").addEventListener("click", captureManual);
    document.getElementById("process-manual-code-btn").addEventListener("click", processManualCode);
    document.getElementById("add-product-btn").addEventListener("click", addProductWithCode);
    document.getElementById("add-machine-btn").addEventListener("click", addMachineWithCode);
    document.getElementById("search-code-btn").addEventListener("click", searchCode);
    document.getElementById("clear-history-btn").addEventListener("click", clearHistory); 

    document.getElementById("mode-barcode").addEventListener("change", () => updateScanMode("barcode"));
    document.getElementById("mode-qrcode").addEventListener("change", () => updateScanMode("qrcode"));

    document.getElementById("manual-code").addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            processManualCode();
        }
    });
}

async function initializeCameraDevices() {
    try {
        availableCameras = await Html5Qrcode.getCameras();
        if (availableCameras.length > 0) {
            const rearCamera = availableCameras.find(camera => camera.label.toLowerCase().includes('back') || camera.label.toLowerCase().includes('environment'));
            currentCameraId = rearCamera ? rearCamera.id : availableCameras[0].id; 
            
            document.getElementById("toggle-camera-btn").disabled = false;
            if (availableCameras.length > 1) {
                document.getElementById("switch-camera-btn").disabled = false;
            }
            updateScannerStatus(`${availableCameras.length} câmera(s) encontrada(s).`, "info");
        } else {
            updateScannerStatus("Nenhuma câmera encontrada.", "warning");
            document.getElementById("toggle-camera-btn").disabled = true;
        }
    } catch (err) {
        console.error("Erro ao listar câmeras: ", err);
        window.parent.showAlert("Erro ao acessar câmeras. Verifique as permissões.", "danger");
        document.getElementById("toggle-camera-btn").disabled = true;
    }
}

function updateScanMode(mode) {
    currentScanMode = mode;
    const scannerFrame = document.getElementById("scanner-frame");
    const barcodeTypesConfig = document.getElementById("barcode-types-config");
    
    if (mode === "barcode") {
        scannerFrame.style.width = "80%";
        scannerFrame.style.height = "150px";
        scannerFrame.style.borderRadius = "8px";
        scannerFrame.style.border = "3px solid rgba(255, 0, 0, 0.7)";
        barcodeTypesConfig.style.display = 'block';
    } else {
        scannerFrame.style.width = "250px";
        scannerFrame.style.height = "250px";
        scannerFrame.style.borderRadius = "8px";
        scannerFrame.style.border = "3px solid #0d6efd";
        barcodeTypesConfig.style.display = 'none';
    }

    if ((html5QrCode && html5QrCode.isScanning) || quaggaRunning) {
        stopScanning();
        setTimeout(() => startScanning(), 300);
    }
}

// ========================================
// CONTROLE DA CÂMERA
// ========================================
async function toggleCamera() {
    if (cameraStream) {
        stopCamera();
    } else {
        await startCamera();
    }
}

async function startCamera() {
    if (!currentCameraId) {
        window.parent.showAlert("Nenhuma câmera disponível para iniciar.", "warning");
        return;
    }

    try {
        const videoElement = document.getElementById("scanner-video");
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: currentCameraId } }
        });
        videoElement.srcObject = cameraStream;
        await videoElement.play();

        document.getElementById("camera-btn-text").textContent = "Parar Câmera";
        document.getElementById("start-scan-btn").disabled = false;
        document.getElementById("switch-camera-btn").disabled = availableCameras.length <= 1;
        document.getElementById("scanner-overlay").style.display = "flex";
        updateScannerStatus("Câmera iniciada. Clique em \"Iniciar Scan\" para começar.", "success");
        if(isModalMode) {
             startScanning();
        }

    } catch (err) {
        console.error("Erro ao iniciar câmera: ", err);
        window.parent.showAlert("Erro ao iniciar câmera. Verifique as permissões.", "danger");
        stopCamera();
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        document.getElementById("scanner-video").srcObject = null;
    }
    stopScanning(); 
    document.getElementById("camera-btn-text").textContent = "Iniciar Câmera";
    document.getElementById("start-scan-btn").disabled = true;
    document.getElementById("stop-scan-btn").disabled = true;
    document.getElementById("scanner-overlay").style.display = "none";
    updateScannerStatus("Câmera parada.", "info");
}

async function switchCamera() {
    if (availableCameras.length <= 1) {
        window.parent.showAlert("Apenas uma câmera disponível.", "info");
        return;
    }
    const wasScanning = (html5QrCode && html5QrCode.isScanning) || quaggaRunning;
    stopCamera();
    
    const currentIndex = availableCameras.findIndex(cam => cam.id === currentCameraId);
    const nextIndex = (currentIndex + 1) % availableCameras.length;
    currentCameraId = availableCameras[nextIndex].id;
    
    await startCamera();

    if (wasScanning) {
        startScanning();
    }
}

// ========================================
// CONTROLE DO SCANNING (HÍBRIDO)
// ========================================
async function startScanning() {
    if (!cameraStream) {
        window.parent.showAlert("Por favor, inicie a câmera primeiro.", "warning");
        return;
    }

    document.getElementById("start-scan-btn").disabled = true;
    document.getElementById("stop-scan-btn").disabled = false;
    updateScannerStatus("Escaneando... Posicione o código na mira.", "primary");

    if (currentScanMode === "qrcode") {
        html5QrCode = new Html5Qrcode("scanner-video");
        try {
            await html5QrCode.start(
                currentCameraId, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText, decodedResult) => handleScanSuccess(decodedText, "QR Code"),
                (errorMessage) => { /* ignora erros */ }
            );
        } catch(err) {
            console.error("Erro ao iniciar Html5Qrcode: ", err);
            stopScanning();
        }
    } else if (currentScanMode === "barcode") {
        const selectedReaders = Array.from(document.querySelectorAll('#barcode-types-config input:checked')).map(cb => cb.value);

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector("#scanner-video"),
                constraints: { deviceId: currentCameraId, facingMode: "environment" },
            },
            decoder: {
                readers: selectedReaders.length > 0 ? selectedReaders : ["code_128_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error("Erro ao iniciar QuaggaJS: ", err);
                stopScanning();
                return;
            }
            Quagga.start();
            quaggaRunning = true;
        });

        Quagga.onDetected(result => {
            if (result && result.codeResult && result.codeResult.code) {
                handleScanSuccess(result.codeResult.code, result.codeResult.format);
            }
        });
    }
}

async function stopScanning() {
    if (html5QrCode && html5QrCode.isScanning) {
        try { await html5QrCode.stop(); } catch (err) { console.error(err); }
    }
    if (quaggaRunning) {
        Quagga.stop();
        quaggaRunning = false;
    }

    document.getElementById("start-scan-btn").disabled = !cameraStream; 
    document.getElementById("stop-scan-btn").disabled = true;
    if(cameraStream) updateScannerStatus("Scanner parado.", "info");
}

function handleScanSuccess(decodedText, format) {
    if (decodedText === lastScannedCode) return;
    lastScannedCode = decodedText;

    playBeep();
    stopScanning(); 
    
    if (isModalMode && targetInputId && window.parent && typeof window.parent.setScannedCode === 'function') {
        window.parent.setScannedCode(decodedText, targetInputId);
        return;
    }

    document.getElementById("scanned-code").textContent = `${decodedText} (${format})`;
    document.getElementById("scan-result").style.display = "block";
    updateScannerStatus(`Código detectado: ${decodedText}`, "success");
    
    addToScanHistory(decodedText, format);

    ["add-product-btn", "add-machine-btn", "search-code-btn"].forEach(id => document.getElementById(id).disabled = false);
    document.getElementById("manual-code").value = decodedText;
}

// ========================================
// FUNÇÕES DE UTILIDADE E AÇÕES
// ========================================
function updateScannerStatus(message, type) {
    const statusElement = document.getElementById("scanner-status");
    statusElement.className = `alert alert-${type}`;
    statusElement.innerHTML = `<i class="fas fa-info-circle me-2"></i> ${message}`;
}

function playBeep() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
        oscillator.connect(audioContext.destination);
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch(e) {}
}

function addToScanHistory(code, format) {
    const timestamp = new Date().toLocaleTimeString('pt-BR');
    const entry = { code, format, timestamp };
    scanHistory.unshift(entry);
    if (scanHistory.length > 10) scanHistory.pop();
    localStorage.setItem("scanHistory", JSON.stringify(scanHistory));
    renderScanHistory();
}

function loadScanHistory() {
    const storedHistory = localStorage.getItem("scanHistory");
    if (storedHistory) {
        scanHistory = JSON.parse(storedHistory);
        renderScanHistory();
    }
}

function clearHistory() {
    if (confirm("Tem certeza que deseja limpar o histórico?")) {
        scanHistory = [];
        localStorage.removeItem("scanHistory");
        renderScanHistory();
        window.parent.showAlert("Histórico limpo.", "info");
    }
}

// ========================================
// *** FUNÇÃO ATUALIZADA ***
// ========================================
function renderScanHistory() {
    const historyContainer = document.getElementById("scan-history");
    historyContainer.innerHTML = "";
    if (scanHistory.length === 0) {
        historyContainer.innerHTML = `<div class="text-muted text-center py-3"><i class="fas fa-clock fa-2x mb-2"></i><p>Nenhum scan realizado</p></div>`;
        return;
    }

    scanHistory.forEach(entry => {
        const item = document.createElement("div");
        item.className = "list-group-item d-flex justify-content-between align-items-center";
        
        const content = document.createElement("div");
        content.innerHTML = `<strong>${entry.code}</strong><br><small class="text-muted">${entry.format} - ${entry.timestamp}</small>`;

        const actions = document.createElement("div");
        actions.className = "btn-group";

        const searchBtn = document.createElement("button");
        searchBtn.className = "btn btn-sm btn-outline-primary";
        searchBtn.title = "Buscar este código";
        searchBtn.innerHTML = `<i class="fas fa-search"></i>`;
        searchBtn.addEventListener("click", () => {
            // Ação direta: Define o código e chama a busca
            lastScannedCode = entry.code;
            searchCode();
        });

        const copyBtn = document.createElement("button");
        copyBtn.className = "btn btn-sm btn-outline-secondary";
        copyBtn.title = "Copiar código";
        copyBtn.innerHTML = `<i class="fas fa-copy"></i>`;
        copyBtn.addEventListener("click", () => {
            copyToClipboard(entry.code);
        });

        actions.appendChild(searchBtn);
        actions.appendChild(copyBtn);

        item.appendChild(content);
        item.appendChild(actions);
        historyContainer.appendChild(item);
    });
}

function copyToClipboard(text) {
    if (!navigator.clipboard) {
        window.parent.showAlert("A área de transferência não é compatível neste navegador.", "danger");
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        window.parent.showAlert(`Código "${text}" copiado!`, "success");
    }).catch(err => {
        console.error('Erro ao copiar texto: ', err);
        window.parent.showAlert("Falha ao copiar o código.", "danger");
    });
}

function captureManual() {
    window.parent.showAlert("A captura manual de imagem não está implementada nesta versão.", "info");
}

function processManualCode() {
    const manualCode = document.getElementById("manual-code").value.trim();
    if (manualCode) {
        handleScanSuccess(manualCode, "Manual");
    } else {
        window.parent.showAlert("Por favor, digite um código para buscar.", "warning");
    }
}

function addProductWithCode() {
    if (lastScannedCode) window.open(`../products/add_product.php?code=${encodeURIComponent(lastScannedCode)}`, '_blank');
}

function addMachineWithCode() {
    if (lastScannedCode) window.open(`../machines/add_machine.php?code=${encodeURIComponent(lastScannedCode)}`, '_blank');
}

// ========================================
// *** FUNÇÃO ATUALIZADA ***
// ========================================
async function searchCode() {
    if (!lastScannedCode) {
        window.parent.showAlert("Nenhum código para buscar.", "warning");
        return;
    }
    updateScannerStatus(`Buscando código ${lastScannedCode}...`, "info");
    const searchBtn = document.getElementById("search-code-btn");
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando...';

    try {
        const response = await fetch('../../modules/utilities/search_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: lastScannedCode })
        });
        const data = await response.json();
        
        // **Lógica Melhorada**
        if (data.success && data.results.length === 1) {
            // Se encontrou exatamente um item, abre o modal de detalhes (actionModal)
            const item = data.results[0];
            const title = `Resultado da Busca: ${item.name}`;
            // A função openActionModal está em custom.js, que é carregado no footer.php
            if (typeof openActionModal === 'function') {
                openActionModal(item.view_url, title);
            } else {
                console.error("Função openActionModal não encontrada.");
                alert("Item encontrado, mas o modal de detalhes não pôde ser aberto.");
            }
        } else {
            // Se encontrou múltiplos ou nenhum item, usa o modal de resultados padrão (searchResultModal)
            const content = document.getElementById("search-result-content");
            if (data.success && data.results.length > 1) {
                let html = '<h5>Múltiplos resultados encontrados:</h5>';
                html += '<div class="list-group">';
                data.results.forEach(item => {
                    // Adiciona um link para cada item que abre o modal de detalhes
                    html += `<a href="#" onclick="event.preventDefault(); openActionModal('${item.view_url}', 'Detalhes: ${item.name.replace(/'/g, "\\'")}')" class="list-group-item list-group-item-action">
                                <strong>${item.name}</strong> <span class="badge bg-info">${item.type}</span>
                            </a>`;
                });
                html += '</div>';
                content.innerHTML = html;
            } else {
                 content.innerHTML = `<div class="text-center p-4"><i class="fas fa-search fa-3x text-muted mb-3"></i><p>Nenhum item encontrado com o código <strong>${lastScannedCode}</strong>.</p></div>`;
            }
            const searchModal = new bootstrap.Modal(document.getElementById('searchResultModal'));
            searchModal.show();
        }

    } catch (error) {
        console.error("Erro na busca: ", error);
        window.parent.showAlert("Erro de comunicação ao buscar o código.", "danger");
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="fas fa-search me-1"></i>Buscar no Estoque';
    }
}
</script>

</body>
</html>