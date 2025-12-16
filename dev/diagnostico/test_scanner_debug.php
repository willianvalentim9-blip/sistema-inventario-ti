<?php
require_once 'config.php';
requireLogin();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico do Scanner</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-card { margin-bottom: 15px; }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-stethoscope"></i> Diagnóstico do Scanner</h2>
        <hr>

        <!-- Teste 1: Arquivos existem? -->
        <div class="card test-card">
            <div class="card-header bg-primary text-white">
                1️⃣ Verificar Arquivos do Scanner
            </div>
            <div class="card-body">
                <?php
                $scanner_files = [
                    'scanner_modal.php' => 'Scanner Modal',
                    'scanner.php' => 'Scanner Standalone',
                ];

                foreach ($scanner_files as $file => $desc) {
                    $exists = file_exists(__DIR__ . '/' . $file);
                    $class = $exists ? 'status-ok' : 'status-error';
                    $icon = $exists ? 'fa-check-circle' : 'fa-times-circle';
                    echo "<div class='$class'><i class='fas $icon'></i> $desc ($file): " . ($exists ? 'OK' : 'NÃO ENCONTRADO') . "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Teste 2: Modal HTML existe? -->
        <div class="card test-card">
            <div class="card-header bg-primary text-white">
                2️⃣ Verificar Modal HTML no Footer
            </div>
            <div class="card-body">
                <?php
                $footer_content = file_get_contents(__DIR__ . '/../../includes/footer.php');
                $has_modal = strpos($footer_content, 'id="scannerModal"') !== false;
                $has_iframe = strpos($footer_content, 'id="scannerIframe"') !== false;
                $has_js = strpos($footer_content, 'setScannedCode') !== false;

                echo "<div class='" . ($has_modal ? 'status-ok' : 'status-error') . "'>";
                echo "<i class='fas " . ($has_modal ? 'fa-check-circle' : 'fa-times-circle') . "'></i> ";
                echo "Modal #scannerModal: " . ($has_modal ? 'ENCONTRADO' : 'NÃO ENCONTRADO');
                echo "</div>";

                echo "<div class='" . ($has_iframe ? 'status-ok' : 'status-error') . "'>";
                echo "<i class='fas " . ($has_iframe ? 'fa-check-circle' : 'fa-times-circle') . "'></i> ";
                echo "Iframe #scannerIframe: " . ($has_iframe ? 'ENCONTRADO' : 'NÃO ENCONTRADO');
                echo "</div>";

                echo "<div class='" . ($has_js ? 'status-ok' : 'status-error') . "'>";
                echo "<i class='fas " . ($has_js ? 'fa-check-circle' : 'fa-times-circle') . "'></i> ";
                echo "Função setScannedCode(): " . ($has_js ? 'ENCONTRADA' : 'NÃO ENCONTRADA');
                echo "</div>";
                ?>
            </div>
        </div>

        <!-- Teste 3: Conexão HTTPS -->
        <div class="card test-card">
            <div class="card-header bg-warning text-dark">
                3️⃣ Verificar Protocolo (HTTPS)
            </div>
            <div class="card-body">
                <?php
                $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                $is_localhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']);

                if ($is_https) {
                    echo "<div class='status-ok'><i class='fas fa-check-circle'></i> Conexão HTTPS: SIM ✅</div>";
                } elseif ($is_localhost) {
                    echo "<div class='status-warning'><i class='fas fa-exclamation-triangle'></i> Conexão HTTP (localhost): OK para teste local ⚠️</div>";
                } else {
                    echo "<div class='status-error'><i class='fas fa-times-circle'></i> Conexão HTTP: CÂMERA NÃO FUNCIONARÁ ❌</div>";
                    echo "<div class='alert alert-danger mt-2'>";
                    echo "<strong>PROBLEMA CRÍTICO:</strong> Navegadores modernos exigem HTTPS para acessar a câmera.<br>";
                    echo "Configure um certificado SSL ou use localhost para testes.";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Teste 4: Bibliotecas JavaScript -->
        <div class="card test-card">
            <div class="card-header bg-info text-white">
                4️⃣ Testar Carregamento das Bibliotecas
            </div>
            <div class="card-body">
                <p>Testando carregamento de bibliotecas externas:</p>
                <div id="library-status">
                    <div><i class="fas fa-spinner fa-spin"></i> Testando...</div>
                </div>
            </div>
        </div>

        <!-- Teste 5: Permissões -->
        <div class="card test-card">
            <div class="card-header bg-success text-white">
                5️⃣ Testar Permissões da Câmera
            </div>
            <div class="card-body">
                <button class="btn btn-primary" id="test-camera-btn">
                    <i class="fas fa-camera"></i> Testar Acesso à Câmera
                </button>
                <div id="camera-test-result" class="mt-3"></div>
                <video id="test-video" autoplay muted playsinline style="max-width: 100%; max-height: 300px; margin-top: 15px; display: none;"></video>
            </div>
        </div>

        <!-- Teste 6: Abrir Scanner Modal -->
        <div class="card test-card">
            <div class="card-header bg-dark text-white">
                6️⃣ Testar Abertura do Scanner
            </div>
            <div class="card-body">
                <input type="text" class="form-control mb-2" id="test-input" placeholder="Código escaneado aparecerá aqui">
                <button class="btn btn-success" data-open-scanner data-target-input="test-input">
                    <i class="fas fa-qrcode"></i> Abrir Scanner Modal
                </button>
                <div class="alert alert-info mt-3">
                    Clique no botão acima para testar se o scanner abre corretamente.
                </div>
            </div>
        </div>

        <div class="alert alert-secondary">
            <h5><i class="fas fa-lightbulb"></i> Soluções Comuns:</h5>
            <ul>
                <li><strong>Câmera não abre:</strong> Verifique se você permitiu acesso à câmera no navegador</li>
                <li><strong>Erro de HTTPS:</strong> Use <code>https://</code> ou <code>localhost</code></li>
                <li><strong>Scripts não carregam:</strong> Verifique sua conexão com a internet</li>
                <li><strong>Iframe vazio:</strong> Verifique se scanner_modal.php está acessível</li>
            </ul>
        </div>

        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Teste de bibliotecas
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const libs = {
                'Html5Qrcode': typeof Html5Qrcode !== 'undefined',
                'Quagga': typeof Quagga !== 'undefined'
            };

            let html = '';
            for (let [name, loaded] of Object.entries(libs)) {
                const className = loaded ? 'status-ok' : 'status-error';
                const icon = loaded ? 'fa-check-circle' : 'fa-times-circle';
                html += `<div class="${className}"><i class="fas ${icon}"></i> ${name}: ${loaded ? 'CARREGADO ✅' : 'FALHOU ❌'}</div>`;
            }

            document.getElementById('library-status').innerHTML = html;
        }, 2000);

        // Teste de câmera
        document.getElementById('test-camera-btn').addEventListener('click', async function() {
            const resultDiv = document.getElementById('camera-test-result');
            const video = document.getElementById('test-video');

            try {
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Solicitando permissão...</div>';

                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = 'block';

                resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>SUCESSO!</strong> Câmera acessada com sucesso!</div>';

                // Para após 5 segundos
                setTimeout(() => {
                    stream.getTracks().forEach(track => track.stop());
                    video.style.display = 'none';
                    resultDiv.innerHTML += '<div class="alert alert-secondary">Câmera desligada automaticamente.</div>';
                }, 5000);

            } catch (error) {
                console.error('Erro ao acessar câmera:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> <strong>ERRO:</strong> ${error.message}<br>
                        <small>Nome do erro: ${error.name}</small>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Possíveis causas:</strong>
                        <ul>
                            <li>Permissão negada pelo usuário</li>
                            <li>Navegador bloqueou acesso à câmera</li>
                            <li>Câmera já está em uso por outro aplicativo</li>
                            <li>Conexão não é HTTPS (exceto localhost)</li>
                        </ul>
                    </div>
                `;
            }
        });
    });
    </script>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>
</body>
</html>
