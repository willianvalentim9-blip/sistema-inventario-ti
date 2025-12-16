<?php
// ========================================
// DIAGNÓSTICO: CÓDIGO DE BARRAS
// ========================================

echo '<h4 class="mb-3"><i class="fas fa-barcode me-2"></i>Verificação de Geração de Códigos</h4>';

// Verificar biblioteca (novo local em modules/barcode/)
$loader_path = realpath(__DIR__ . '/../../modules/barcode/barcode-loader.php');
$lib_path = realpath(__DIR__ . '/../../modules/barcode/barcode-lib');
$lib_simple_path = realpath(__DIR__ . '/../../modules/barcode/barcode-lib-simple.php');
$generate_api = realpath(__DIR__ . '/../../modules/barcode/generate_barcode_image.php');
$barcode_print = realpath(__DIR__ . '/../../modules/barcode/barcode_print.php');

echo '<div class="alert alert-info mb-4">';
echo '<strong><i class="fas fa-info-circle me-2"></i>Informação:</strong> ';
echo 'Verificando arquivos em: <code>/modules/barcode/</code>';
echo '</div>';

echo '<h5 class="mt-4 mb-3"><i class="fas fa-file-check me-2"></i>Etapa 1: Verificação de Arquivos</h5>';

echo '<table class="table table-bordered">';
echo '<thead class="table-light"><tr><th width="30%">Arquivo</th><th>Status</th><th>Caminho</th></tr></thead>';
echo '<tbody>';

// 1. Verificar loader
echo '<tr>';
echo '<td><strong>barcode-loader.php</strong></td>';
if ($loader_path && file_exists($loader_path)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>EXISTE</span></td>';
    echo '<td><small>' . htmlspecialchars($loader_path) . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO EXISTE</span></td>';
    echo '<td><small class="text-danger">Esperado: ' . htmlspecialchars(__DIR__ . '/../../modules/barcode/barcode-loader.php') . '</small></td>';
}
echo '</tr>';

// 2. Verificar biblioteca barcode-lib (OPCIONAL - temos fallback)
echo '<tr>';
echo '<td><strong>barcode-lib/ (opcional)</strong></td>';
if ($lib_path && is_dir($lib_path)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>EXISTE</span></td>';
    echo '<td><small>' . htmlspecialchars($lib_path) . '</small></td>';
} else {
    echo '<td><span class="badge bg-warning"><i class="fas fa-info-circle me-1"></i>NÃO ENCONTRADA</span></td>';
    echo '<td><small class="text-warning">OK - Usando fallback barcode-lib-simple.php</small></td>';
}
echo '</tr>';

// 3. Verificar biblioteca simplificada (FALLBACK - IMPORTANTE)
echo '<tr>';
echo '<td><strong>barcode-lib-simple.php (fallback)</strong></td>';
if ($lib_simple_path && file_exists($lib_simple_path)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>EXISTE</span></td>';
    echo '<td><small>' . htmlspecialchars($lib_simple_path) . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO EXISTE</span></td>';
    echo '<td><small class="text-danger">CRÍTICO - Necessário para funcionamento</small></td>';
}
echo '</tr>';

// 4. Verificar API de geração
echo '<tr>';
echo '<td><strong>generate_barcode_image.php</strong></td>';
if ($generate_api && file_exists($generate_api)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>EXISTE</span></td>';
    echo '<td><small>' . htmlspecialchars($generate_api) . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO EXISTE</span></td>';
    echo '<td><small class="text-danger">Esperado: ' . htmlspecialchars(__DIR__ . '/../../modules/barcode/generate_barcode_image.php') . '</small></td>';
}
echo '</tr>';

// 5. Verificar página de impressão
echo '<tr>';
echo '<td><strong>barcode_print.php</strong></td>';
if ($barcode_print && file_exists($barcode_print)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>EXISTE</span></td>';
    echo '<td><small>' . htmlspecialchars($barcode_print) . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>NÃO EXISTE</span></td>';
    echo '<td><small class="text-danger">Esperado: ' . htmlspecialchars(__DIR__ . '/../../modules/barcode/barcode_print.php') . '</small></td>';
}
echo '</tr>';

// 6. Verificar GD
$gd_ok = extension_loaded('gd');
echo '<tr>';
echo '<td><strong>Extensão GD (PNG)</strong></td>';
if ($gd_ok) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>ATIVA</span></td>';
    echo '<td><small class="text-muted">Suporte a imagens PNG disponível</small></td>';
} else {
    echo '<td><span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>INATIVA</span></td>';
    echo '<td><small class="text-muted">SVG funciona normalmente (recomendado)</small></td>';
}
echo '</tr>';

echo '</tbody></table>';

echo '<h5 class="mt-4 mb-3"><i class="fas fa-cogs me-2"></i>Etapa 2: Diagnóstico da Biblioteca</h5>';

echo '<table class="table table-bordered">';
echo '<thead class="table-light"><tr><th width="30%">Componente</th><th>Status</th></tr></thead>';
echo '<tbody>';

// Verificar qual biblioteca foi carregada
echo '<tr>';
echo '<td><strong>Método de Carregamento</strong></td>';
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    echo '<td><span class="badge bg-info">Composer (vendor/)</span></td>';
} elseif ($lib_path && is_dir($lib_path)) {
    echo '<td><span class="badge bg-info">barcode-lib/ local</span></td>';
} elseif ($lib_simple_path && file_exists($lib_simple_path)) {
    echo '<td><span class="badge bg-warning">barcode-lib-simple.php (fallback)</span></td>';
} else {
    echo '<td><span class="badge bg-danger">NENHUM disponível</span></td>';
}
echo '</tr>';

echo '</tbody></table>';

// Teste de geração
if (file_exists($loader_path)) {
    echo '<h5 class="mt-4 mb-3"><i class="fas fa-vial me-2"></i>Etapa 3: Teste de Geração</h5>';

    try {
        // Limpar qualquer erro anterior
        if (function_exists('error_clear_last')) {
            error_clear_last();
        }
        
        // Carregar o arquivo e capturar erros
        ob_start();
        $load_result = require_once $loader_path;
        ob_end_clean();

        // Verificar se a classe foi carregada
        if (!class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
            throw new Exception('Classe BarcodeGeneratorSVG não foi carregada após require_once');
        }

        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode_svg = $generator->getBarcode('123456789', 'C128');

        echo '<div class="card border-success">';
        echo '<div class="card-body">';
        echo '<h6 class="card-title text-success">✓ Código de Barras Gerado com Sucesso</h6>';
        // Mostrar amostra do SVG
        echo '<pre style="background:#f5f5f5; padding:10px; border-radius:5px; overflow-x:auto; max-height:200px; font-size:11px;">';
        echo htmlspecialchars(substr($barcode_svg, 0, 200)) . "...\n\n";
        echo 'Tamanho total: ' . strlen($barcode_svg) . ' bytes</pre>';
        echo '</div></div>';

        echo '<div class="alert alert-success mt-3">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>✓ FUNCIONANDO!</strong> ';
        echo 'Sistema de barcode está operacional. Você pode usar <code>barcode_print.php</code> para gerar códigos de barras.';
        echo '</div>';

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<i class="fas fa-times-circle me-2"></i>';
        echo '<strong>✗ ERRO ao Testar Geração:</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
        
        // Debug info
        echo '<div class="card border-warning mt-3">';
        echo '<div class="card-header bg-warning text-dark"><strong>Informações de Debug</strong></div>';
        echo '<div class="card-body small">';
        echo '<strong>Carregamento:</strong><br>';
        echo '- Arquivo loader: ' . htmlspecialchars($loader_path) . '<br>';
        echo '- Existe: ' . (file_exists($loader_path) ? 'SIM' : 'NÃO') . '<br>';
        echo '- Classe carregada: ' . (class_exists('Picqer\Barcode\BarcodeGeneratorSVG') ? 'SIM' : 'NÃO') . '<br><br>';
        echo '<strong>Alternativas:</strong><br>';
        echo '1. barcode-lib/: ' . ($lib_path && is_dir($lib_path) ? 'ENCONTRADA' : 'não encontrada') . '<br>';
        echo '2. barcode-lib-simple.php: ' . ($lib_simple_path && file_exists($lib_simple_path) ? 'ENCONTRADA' : 'não encontrada') . '<br>';
        echo '3. composer (vendor/): ' . (file_exists(__DIR__ . '/../../vendor/autoload.php') ? 'ENCONTRADO' : 'não encontrado') . '<br>';
        echo '</div></div>';
    }
} else {
    echo '<div class="alert alert-danger mt-3">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>Não foi possível testar:</strong> Arquivo barcode-loader.php não encontrado!';
    echo '</div>';
}
?>
