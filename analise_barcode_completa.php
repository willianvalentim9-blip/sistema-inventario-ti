<?php
/**
 * ANÁLISE COMPLETA DO SISTEMA DE BARCODE
 * Identifica e corrige todos os problemas
 */

echo "=== ANÁLISE COMPLETA DO SISTEMA DE BARCODE ===\n\n";

$issues = [];
$fixes = [];

// ============================================
// 1. VERIFICAR ARQUIVO barcode_print.php
// ============================================
echo "1. ANALISANDO barcode_print.php...\n";

$file = __DIR__ . '/barcode_print.php';
$content = file_get_contents($file);

// Problema 1: JavaScript ainda usa new Image() ao invés de document.createElement('img')
if (strpos($content, 'const img = new Image()') !== false) {
    $issues[] = "barcode_print.php linha ~240: Usa 'new Image()' que não funciona com SVG";
    $fixes[] = "Substituir 'new Image()' por 'document.createElement(\"img\")'";
}

// Problema 2: URL relativa pode não funcionar em impressão
if (strpos($content, 'const apiUrl = `generate_barcode_image.php') !== false) {
    $issues[] = "barcode_print.php: URL relativa 'generate_barcode_image.php' pode falhar";
    $fixes[] = "Usar URL absoluta com window.location.protocol + '//' + window.location.host";
}

// Problema 3: CSS print incompleto
if (strpos($content, '@media print {') !== false) {
    if (strpos($content, '#barcode-container svg') === false) {
        $issues[] = "barcode_print.php: CSS print não inclui regras para SVG";
        $fixes[] = "Adicionar '#barcode-container svg' ao CSS print";
    }
    if (strpos($content, 'print-color-adjust: exact') === false) {
        $issues[] = "barcode_print.php: CSS print sem 'print-color-adjust: exact'";
        $fixes[] = "Adicionar 'print-color-adjust: exact' para preservar SVG em cores";
    }
}

echo "  ✓ Problemas encontrados: " . count($issues) . "\n\n";

// ============================================
// 2. VERIFICAR diagnostico.php
// ============================================
echo "2. ANALISANDO diagnostico.php...\n";

$file = __DIR__ . '/diagnostico.php';
$content = file_get_contents($file);

// Problema 4: Ainda tenta usar BarcodeGeneratorPNG (requer GD)
if (strpos($content, 'BarcodeGeneratorPNG') !== false) {
    $issues[] = "diagnostico.php: Tenta usar BarcodeGeneratorPNG que requer GD library";
    $fixes[] = "Mudar para BarcodeGeneratorSVG que não requer GD";
}

// Problema 5: Verifica se GD está instalado (desnecessário com SVG)
if (strpos($content, '$gd_ok = extension_loaded') !== false) {
    $issues[] = "diagnostico.php: Verifica GD como requisito (não é mais necessário)";
    $fixes[] = "Remover verificação de GD, agora usando SVG";
}

echo "  ✓ Problemas encontrados: " . count($issues) . "\n\n";

// ============================================
// 3. VERIFICAR barcode-loader.php
// ============================================
echo "3. ANALISANDO barcode-loader.php...\n";

$file = __DIR__ . '/barcode-loader.php';
$content = file_get_contents($file);

// Problema 6: Não carrega helpers necessários
if (strpos($content, 'ColorHelper.php') === false) {
    $issues[] = "barcode-loader.php: Não carrega ColorHelper.php";
    $fixes[] = "Adicionar 'barcode-lib/src/Helpers/ColorHelper.php' ao carregador";
}

if (strpos($content, 'SvgRenderer.php') === false) {
    $issues[] = "barcode-loader.php: Não carrega SvgRenderer.php";
    $fixes[] = "Adicionar 'barcode-lib/src/Renderers/SvgRenderer.php' ao carregador";
}

if (strpos($content, 'BarcodeGeneratorSVG.php') === false) {
    $issues[] = "barcode-loader.php: Não carrega BarcodeGeneratorSVG.php";
    $fixes[] = "Adicionar 'barcode-lib/src/BarcodeGeneratorSVG.php' ao carregador";
}

// Problema 7: Bloco de erro tenta usar imagecreate (requer GD)
if (strpos($content, 'imagecreate(800, 60)') !== false) {
    $issues[] = "barcode-loader.php: Bloco de erro usa imagecreate que requer GD";
    $fixes[] = "Substituir bloco de erro por SVG que funciona sem GD";
}

echo "  ✓ Problemas encontrados: " . count($issues) . "\n\n";

// ============================================
// 4. VERIFICAR generate_barcode_image.php
// ============================================
echo "4. ANALISANDO generate_barcode_image.php...\n";

$file = __DIR__ . '/generate_barcode_image.php';
$content = file_get_contents($file);

// Problema 8: Verifica se está usando SVG
if (strpos($content, 'BarcodeGeneratorSVG') === false) {
    $issues[] = "generate_barcode_image.php: Não está usando BarcodeGeneratorSVG";
    $fixes[] = "Mudar para BarcodeGeneratorSVG";
}

if (strpos($content, 'image/svg+xml') === false) {
    $issues[] = "generate_barcode_image.php: Header não retorna image/svg+xml";
    $fixes[] = "Mudar header para 'Content-Type: image/svg+xml; charset=utf-8'";
}

echo "  ✓ Problemas encontrados: " . count($issues) . "\n\n";

// ============================================
// RESUMO
// ============================================
echo "\n=== RESUMO TOTAL ===\n";
echo "Total de problemas: " . count($issues) . "\n\n";

foreach ($issues as $index => $issue) {
    echo ($index + 1) . ". " . $issue . "\n";
    echo "   FIX: " . $fixes[$index] . "\n\n";
}

// ============================================
// TESTE DE FUNCIONALIDADE
// ============================================
echo "=== TESTE DE FUNCIONALIDADE ===\n\n";

try {
    require_once __DIR__ . '/barcode-loader.php';
    
    echo "✓ Biblioteca carregada com sucesso\n";
    
    // Teste 1: SVG Generator
    try {
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $svg = $generator->getBarcode('TESTE123', 'C128', 2, 60);
        echo "✓ BarcodeGeneratorSVG funciona (" . strlen($svg) . " bytes)\n";
    } catch (Exception $e) {
        echo "✗ BarcodeGeneratorSVG FALHOU: " . $e->getMessage() . "\n";
    }
    
    // Teste 2: PNG Generator (com fallback)
    try {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $png = $generator->getBarcode('TESTE123', 'C128', 2, 60);
        echo "✓ BarcodeGeneratorPNG funciona (" . strlen($png) . " bytes)\n";
    } catch (Exception $e) {
        echo "⚠ BarcodeGeneratorPNG falhou (esperado se GD não instalada): " . $e->getMessage() . "\n";
    }
    
    // Teste 3: Tipos de barcode
    $tipos = ['C128', 'C39', 'EAN13', 'UPCA'];
    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
    $tipos_ok = 0;
    foreach ($tipos as $tipo) {
        try {
            $codigo = ($tipo === 'EAN13') ? '123456789012' : 'TEST';
            $svg = $generator->getBarcode($codigo, $tipo, 2, 60);
            if (strlen($svg) > 500) $tipos_ok++;
        } catch (Exception $e) {}
    }
    echo "✓ Tipos de barcode suportados: $tipos_ok/4\n";
    
} catch (Exception $e) {
    echo "✗ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n";
?>
