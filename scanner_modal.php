<<?php
// ========================================
// PÁGINA DE SCANNER DEDICADA PARA MODAL (GERADO A PARTIR DO SCANNER HÍBRIDO)
// ========================================

require_once 'config.php';

// Apenas usuários logados podem acessar
if (!isLoggedIn()) {
    http_response_code(403); // Forbidden
    exit('Acesso não autorizado.');
}

// Este arquivo sempre se comportará como um modal.
$hide_sidebar = true;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos para preencher o modal e focar na câmera */
        body, html {
            overflow: hidden;
            height: 100%;
            background-color: #000;
            margin: 0;
            padding: 0;
        }
        #scanner-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        #scanner-video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        #scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #scanner-frame {
            border: 3px solid rgba(255, 0, 0, 0.7);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            width: 80vw; /* 80% da largura da janela */
            height: 25vh; /* 25% da altura da janela */
        }
         /* Controles flutuantes na parte inferior */
        .scanner-controls {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            background-color: rgba(0,0,0,0.4);
            border-radius: 10px;
            padding: 5px;
        }
    </style>
</head>
<body>

<div id="scanner-container">
    <video id="scanner-video" autoplay muted playsinline></video>
    <div id="scanner-overlay">
        <div id="scanner-frame"></div>
    </div>
    <div class="scanner-controls">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-light" id="switch-camera-btn" disabled title="Trocar Câmera">
                <i class="fas fa-sync-alt"></i>
            </button>
            <input type="radio" class="btn-check" name="scan-mode" id="mode-barcode" autocomplete="off" checked>
            <label class="btn btn-sm btn-outline-light" for="mode-barcode" title="Código de Barras"><i class="fas fa-barcode"></i></label>

            <input type="radio" class="btn-check" name="scan-mode" id="mode-qrcode" autocomplete="off">
            <label class="btn btn-sm btn-outline-light" for="mode-qrcode" title="QR Code"><i class="fas fa-qrcode"></i></label>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>

<script>
// VARIÁVEIS GLOBAIS
let html5QrCode = null;
let quaggaRunning = false;
let cameraStream = null;
let availableCameras = [];
let currentCameraId = null;
let currentScanMode = "barcode";
const targetInputId = new URLSearchParams(window.location.search).get('target');

// INICIALIZAÇÃO
document.addEventListener("DOMContentLoaded", function() {
    setupEventListeners();
    initializeCameraDevices().then(() => {
        startCamera(); // Inicia a câmera assim que os dispositivos estiverem prontos
    });
    updateScanFrame("barcode"); // Configura a mira inicial
});

function setupEventListeners() {
    document.getElementById("switch-camera-btn").addEventListener("click", switchCamera);
    document.getElementById("mode-barcode").addEventListener("change", () => updateScanMode("barcode"));
    document.getElementById("mode-qrcode").addEventListener("change", () => updateScanMode("qrcode"));
}

async function initializeCameraDevices() {
    try {
        const cameras = await Html5Qrcode.getCameras();
        if (cameras && cameras.length) {
            availableCameras = cameras;
            // Prioriza a câmera traseira
            const rearCamera = cameras.find(camera => camera.label.toLowerCase().includes('back') || camera.label.toLowerCase().includes('traseira'));
            currentCameraId = rearCamera ? rearCamera.id : cameras[0].id;
            
            if (cameras.length > 1) {
                document.getElementById("switch-camera-btn").disabled = false;
            }
        }
    } catch (err) {
        console.error("Erro ao listar câmeras:", err);
        if (window.parent.showAlert) {
            window.parent.showAlert("Erro ao acessar câmeras. Verifique as permissões.", "danger");
        }
    }
}

function updateScanMode(mode) {
    currentScanMode = mode;
    updateScanFrame(mode);
    // Reinicia o scanner para usar o novo modo
    if (cameraStream) {
        stopScanning();
        setTimeout(startScanning, 200);
    }
}

function updateScanFrame(mode) {
    const frame = document.getElementById("scanner-frame");
    if (mode === 'barcode') {
        frame.style.width = '80vw';
        frame.style.height = '25vh';
        frame.style.border = '3px solid rgba(255, 0, 0, 0.7)';
    } else { // qrcode
        frame.style.width = '60vw';
        frame.style.height = '60vw';
        frame.style.maxWidth = '250px';
        frame.style.maxHeight = '250px';
        frame.style.border = '3px solid #0d6efd';
    }
}

// CONTROLE DA CÂMERA
async function startCamera() {
    if (!currentCameraId) {
        if (window.parent.showAlert) window.parent.showAlert("Nenhuma câmera encontrada.", "warning");
        return;
    }
    try {
        const videoElement = document.getElementById("scanner-video");
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: currentCameraId } }
        });
        videoElement.srcObject = cameraStream;
        await videoElement.play();
        startScanning();
    } catch (err) {
        console.error("Erro ao iniciar câmera:", err);
        if (window.parent.showAlert) window.parent.showAlert("Não foi possível iniciar a câmera.", "danger");
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    stopScanning();
}

async function switchCamera() {
    if (availableCameras.length <= 1) return;
    stopCamera();
    
    const currentIndex = availableCameras.findIndex(cam => cam.id === currentCameraId);
    currentCameraId = availableCameras[(currentIndex + 1) % availableCameras.length].id;
    
    await startCamera();
}

// CONTROLE DO SCANNING
async function startScanning() {
    if (!cameraStream) return;

    stopScanning(); // Garante que não haja instâncias rodando

    if (currentScanMode === "qrcode") {
        html5QrCode = new Html5Qrcode("scanner-video");
        try {
            await html5QrCode.start(
                currentCameraId, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => handleScanSuccess(decodedText),
                (errorMessage) => { /* Ignora erros de "não encontrado" */ }
            );
        } catch(err) {
            console.error("Erro ao iniciar Html5Qrcode:", err);
        }
    } else { // barcode
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector("#scanner-video"),
                constraints: { deviceId: currentCameraId, facingMode: "environment" },
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error("Erro ao iniciar QuaggaJS:", err);
                return;
            }
            Quagga.start();
            quaggaRunning = true;
        });

        Quagga.onDetected(result => {
            if (result && result.codeResult && result.codeResult.code) {
                handleScanSuccess(result.codeResult.code);
            }
        });
    }
}

async function stopScanning() {
    if (html5QrCode && html5QrCode.isScanning) {
        try { await html5QrCode.stop(); } catch (err) { /* Ignora */ }
    }
    if (quaggaRunning) {
        Quagga.stop();
        quaggaRunning = false;
    }
}

let lastScannedCode = "";
function handleScanSuccess(decodedText) {
    if (decodedText === lastScannedCode) return; // Evita scans duplicados
    lastScannedCode = decodedText;

    playBeep();
    stopCamera(); 
    
    if (targetInputId && window.parent && typeof window.parent.setScannedCode === 'function') {
        window.parent.setScannedCode(decodedText, targetInputId);
    }
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
</script>

</body>
</html>