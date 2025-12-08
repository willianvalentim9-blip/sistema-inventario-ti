<?php
// ========================================
// DIAGNÓSTICO: CÓDIGO DE BARRAS
// ========================================

echo '<h4 class="mb-3"><i class="fas fa-barcode me-2"></i>Verificação de Geração de Códigos</h4>';

// Verificar biblioteca
$loader_path = realpath(__DIR__ . '/../barcode-loader.php');
$lib_path = realpath(__DIR__ . '/../barcode-lib');

echo '<table class="table table-bordered">';
echo '<thead class="table-light"><tr><th width="30%">Item</th><th>Status</th></tr></thead>';
echo '<tbody>';

// 1. Verificar loader
echo '<tr>';
echo '<td><strong>Arquivo barcode-loader.php</strong></td>';
if (file_exists($loader_path)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Encontrado</span><br>';
    echo '<small class="text-muted">' . $loader_path . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Não encontrado</span></td>';
}
echo '</tr>';

// 2. Verificar biblioteca
echo '<tr>';
echo '<td><strong>Pasta barcode-lib</strong></td>';
if (is_dir($lib_path)) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Encontrada</span><br>';
    echo '<small class="text-muted">' . $lib_path . '</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Não encontrada</span></td>';
}
echo '</tr>';

// 3. Verificar GD
$gd_ok = extension_loaded('gd');
echo '<tr>';
echo '<td><strong>Extensão GD (PNG)</strong></td>';
if ($gd_ok) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativa</span><br>';
    echo '<small class="text-muted">Suporte a imagens PNG disponível</small></td>';
} else {
    echo '<td><span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>Inativa</span><br>';
    echo '<small class="text-muted">SVG ainda funciona</small></td>';
}
echo '</tr>';

echo '</tbody></table>';

// Teste de geração
if (file_exists($loader_path)) {
    echo '<h5 class="mt-4 mb-3"><i class="fas fa-vial me-2"></i>Teste de Geração</h5>';

    try {
        require_once $loader_path;

        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode_svg = $generator->getBarcode('123456789', $generator::TYPE_CODE_128);

        echo '<div class="card">';
        echo '<div class="card-body text-center">';
        echo '<h6>Código de Barras Teste</h6>';
        echo '<div class="mb-2">' . $barcode_svg . '</div>';
        echo '<p class="mb-0"><small class="text-muted">Código: 123456789 | Tipo: CODE_128</small></p>';
        echo '</div></div>';

        echo '<div class="alert alert-success mt-3">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>Sucesso!</strong> A geração de códigos de barras está funcionando corretamente.';
        echo '</div>';

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<i class="fas fa-times-circle me-2"></i>';
        echo '<strong>Erro na Geração:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-warning mt-3">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>Não foi possível testar:</strong> Arquivo barcode-loader.php não encontrado.';
    echo '</div>';
}
?>
