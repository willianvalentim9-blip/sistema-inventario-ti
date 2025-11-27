<?php
/**
 * DIAGNÓSTICO DE MODAL
 * 
 * Testa se:
 * 1. Modal HTML está correto
 * 2. Edit warranty está funcionando
 * 3. JSON é retornado corretamente
 */

require_once 'config.php';
requireLogin();

$product_id = $_GET['id'] ?? 1;

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Modal</title>
    <link href="CSS/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">🧪 Diagnóstico de Modal</h5>
        </div>
        <div class="card-body">
            <h6>1️⃣ Teste de HTML do Modal</h6>
            <button class="btn btn-primary mb-3" onclick="testModalHTML()">
                <i class="fas fa-check-circle"></i> Verificar HTML
            </button>
            
            <div id="htmlTest"></div>
            
            <hr>
            
            <h6>2️⃣ Teste de Carregamento (AJAX)</h6>
            <button class="btn btn-info mb-3" onclick="testAjaxLoad()">
                <i class="fas fa-cloud-download-alt"></i> Carregar Conteúdo
            </button>
            
            <div id="ajaxTest"></div>
            
            <hr>
            
            <h6>3️⃣ Teste de Modal Completo</h6>
            <button class="btn btn-success mb-3" onclick="testModalComplete()">
                <i class="fas fa-play-circle"></i> Abrir Modal
            </button>
            
            <div id="completeTest"></div>
            
            <hr>
            
            <h6>4️⃣ Console de Logs</h6>
            <div id="console" style="background: #222; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; max-height: 300px; overflow-y: auto;">
                <p style="margin: 0;">Logs aparecem aqui...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal para teste -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testModalBody">
                Carregando...
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
// Redirect logs to console div
const consoleDiv = document.getElementById('console');
const originalLog = console.log;
const originalError = console.error;

function addLog(message, type = 'log') {
    const timestamp = new Date().toLocaleTimeString();
    const color = type === 'error' ? '#ff6b6b' : '#0f0';
    const prefix = type === 'error' ? '❌' : '✓';
    
    const line = document.createElement('p');
    line.style.margin = '5px 0';
    line.style.color = color;
    line.innerHTML = `[${timestamp}] ${prefix} ${message}`;
    consoleDiv.appendChild(line);
    consoleDiv.scrollTop = consoleDiv.scrollHeight;
    
    originalLog(message);
}

function testModalHTML() {
    addLog('Testando HTML do modal...', 'log');
    
    const modal = document.getElementById('testModal');
    const modalBody = document.getElementById('testModalBody');
    
    if (!modal) {
        addLog('❌ Modal HTML não encontrado!', 'error');
        document.getElementById('htmlTest').innerHTML = '<div class="alert alert-danger">Modal não encontrado!</div>';
        return;
    }
    
    if (!modalBody) {
        addLog('❌ Modal body não encontrado!', 'error');
        document.getElementById('htmlTest').innerHTML = '<div class="alert alert-danger">Modal body não encontrado!</div>';
        return;
    }
    
    addLog('✅ Modal HTML estrutura OK', 'log');
    addLog(`Modal ID: ${modal.id}`, 'log');
    addLog(`Modal Body ID: ${modalBody.id}`, 'log');
    addLog(`aria-hidden: ${modal.getAttribute('aria-hidden')}`, 'log');
    
    document.getElementById('htmlTest').innerHTML = `
        <div class="alert alert-success">
            <strong>✅ HTML OK!</strong><br>
            Modal ID: <code>${modal.id}</code><br>
            Body ID: <code>${modalBody.id}</code>
        </div>
    `;
}

function testAjaxLoad() {
    addLog('Testando carregamento AJAX...', 'log');
    
    // Testa primeiro com arquivo de teste simplificado
    const testUrl = 'test_edit_warranty_modal.php?id=<?php echo $product_id; ?>&modal=true';
    const prodUrl = 'edit_warranty.php?id=<?php echo $product_id; ?>&modal=true';
    
    addLog(`Tentando URL de teste: ${testUrl}`, 'log');
    
    fetch(testUrl)
        .then(response => {
            addLog(`Status (teste): ${response.status}`, 'log');
            if (!response.ok) {
                addLog(`❌ Teste falhou, tentando produção...`, 'log');
                return fetch(prodUrl).then(r => ({response: r, isTest: false}));
            }
            return response.text().then(html => ({html, status: response.status, isTest: true}));
        })
        .then(result => {
            if (result.response) {
                // Veio da segunda tentativa (produção)
                const response = result.response;
                addLog(`Status (prod): ${response.status}`, 'log');
                return response.text().then(html => ({html, status: response.status, isTest: false}));
            }
            return result;
        })
        .then(({html, status, isTest}) => {
            const source = isTest ? 'TESTE' : 'PRODUÇÃO';
            
            addLog(`${html.length === 0 ? '❌' : '✅'} Resposta [${source}]: ${html.length} caracteres`, 'log');
            
            if (html.length === 0) {
                addLog('❌ HTML vazio!', 'error');
                document.getElementById('ajaxTest').innerHTML = '<div class="alert alert-danger">❌ HTML vazio recebido!</div>';
                return;
            }
            
            if (html.includes('editWarrantyForm')) {
                addLog('✅ Formulário de garantia detectado', 'log');
            } else {
                addLog('⚠️ Formulário NÃO detectado', 'log');
            }
            
            if (html.includes('<script')) {
                addLog('✅ Scripts detectados no HTML', 'log');
            }
            
            // Mostra preview do HTML
            const preview = html.substring(0, 300).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            document.getElementById('ajaxTest').innerHTML = `
                <div class="alert alert-success">
                    <strong>✅ AJAX OK! [${source}]</strong><br>
                    <small>${html.length} caracteres recebidos</small><br><br>
                    <code>${preview}...</code>
                </div>
            `;
        })
        .catch(error => {
            addLog(`❌ Erro AJAX: ${error.message}`, 'error');
            document.getElementById('ajaxTest').innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Erro!</strong><br>
                    ${error.message}
                </div>
            `;
        });
}

function testModalComplete() {
    addLog('Abrindo modal de teste...', 'log');
    
    const modal = document.getElementById('testModal');
    const modalBody = document.getElementById('testModalBody');
    
    if (!modal || !modalBody) {
        addLog('❌ Modal não encontrado!', 'error');
        return;
    }
    
    // Cria a instância
    const instance = bootstrap.Modal.getOrCreateInstance(modal);
    
    // Define conteúdo
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    
    // Remove aria-hidden
    modal.removeAttribute('aria-hidden');
    
    // Abre
    instance.show();
    addLog('✅ Modal aberto', 'log');
    
    // Carrega conteúdo - tenta arquivo de teste primeiro
    const testUrl = 'test_edit_warranty_modal.php?id=<?php echo $product_id; ?>&modal=true';
    const prodUrl = 'edit_warranty.php?id=<?php echo $product_id; ?>&modal=true';
    
    addLog(`Carregando de: ${testUrl}`, 'log');
    
    fetch(testUrl)
        .then(response => {
            addLog(`Resposta (teste): ${response.status}`, 'log');
            if (response.status === 404) {
                addLog('Arquivo de teste não encontrado, tentando produção...', 'log');
                return fetch(prodUrl);
            }
            if (!response.ok) throw new Error('Erro HTTP');
            return response;
        })
        .then(response => {
            addLog(`Status final: ${response.status}`, 'log');
            if (!response.ok) throw new Error('Erro HTTP');
            return response.text();
        })
        .then(html => {
            if (!html || html.length === 0) {
                throw new Error('HTML vazio!');
            }
            
            addLog(`✅ Inserindo ${html.length} caracteres...`, 'log');
            modalBody.innerHTML = html;
            addLog('✅ Conteúdo inserido', 'log');
            
            // Executa scripts
            const scripts = modalBody.querySelectorAll('script');
            addLog(`📜 ${scripts.length} scripts encontrados`, 'log');
            
            Array.from(scripts).forEach((oldScript, index) => {
                try {
                    addLog(`🔄 Executando script ${index + 1}/${scripts.length}...`, 'log');
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                } catch (e) {
                    addLog(`❌ Erro ao executar script: ${e.message}`, 'error');
                }
            });
            
            addLog('✅ Modal carregado com sucesso!', 'log');
            document.getElementById('completeTest').innerHTML = '<div class="alert alert-success">✅ Modal carregado com sucesso!</div>';
        })
        .catch(error => {
            addLog(`❌ Erro: ${error.message}`, 'error');
            document.getElementById('completeTest').innerHTML = `<div class="alert alert-danger">Erro: ${error.message}</div>`;
        });
}

// Log inicial
addLog('Página de diagnóstico carregada', 'log');
addLog('Clique nos botões para testar', 'log');
</script>
</body>
</html>
