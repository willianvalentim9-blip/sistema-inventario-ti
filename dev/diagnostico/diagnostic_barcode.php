<?php
/**
 * DIAGNÓSTICO COMPLETO DO SISTEMA DE CÓDIGO DE BARRAS
 * Atualizado para verificar arquivos em modules/barcode/
 */
require_once 'config.php';

$diagnostics = [];
$baseDir = __DIR__ . '/modules/barcode/';

// 1. Verificar biblioteca de barcode
$barcode_lib_files = [
    'barcode-lib/src/Exceptions/BarcodeException.php',
    'barcode-lib/src/Barcode.php',
    'barcode-lib/src/BarcodeBar.php',
    'barcode-lib/src/BarcodeGenerator.php',
    'barcode-lib/src/Renderers/RendererInterface.php',
    'barcode-lib/src/Renderers/SvgRenderer.php',
    'barcode-lib/src/BarcodeGeneratorSVG.php',
    'barcode-lib/src/Types/TypeCode128.php',
];

echo "<h2>📋 DIAGNÓSTICO BARCODE - " . date('Y-m-d H:i:s') . "</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";

echo "\n=== 1. ARQUIVOS DA BIBLIOTECA (modules/barcode/) ===\n";
foreach ($barcode_lib_files as $file) {
    $full_path = $baseDir . $file;
    $exists = file_exists($full_path) ? '✅ OK' : '❌ FALTA';
    echo sprintf("%-50s %s\n", $file, $exists);
}

echo "\n=== 2. ARQUIVOS PRINCIPAIS (modules/barcode/) ===\n";
$main_files = [
    'barcode-loader.php',
    'generate_barcode_image.php',
    'barcode_print.php',
    'scanner_modal.php',
];

foreach ($main_files as $file) {
    $full_path = $baseDir . $file;
    $exists = file_exists($full_path) ? '✅ OK' : '❌ FALTA';
    echo sprintf("%-50s %s\n", $file, $exists);
}

echo "\n=== 3. ARQUIVOS DE COMPATIBILIDADE (raiz) ===\n";
$redirect_files = [
    'barcode_print.php',
    'generate_barcode_image.php',
    'scanner_modal.php',
];

foreach ($redirect_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path) ? '✅ OK (redirect)' : '❌ FALTA';
    echo sprintf("%-50s %s\n", $file, $exists);
}

echo "\n=== 4. VERIFICAR CARREGAMENTO DA BIBLIOTECA ===\n";
try {
    if (file_exists($baseDir . 'barcode-loader.php')) {
        ob_start();
        require_once $baseDir . 'barcode-loader.php';
        ob_end_clean();
        echo "✅ Biblioteca carregada com sucesso\n";
    } else {
        echo "❌ barcode-loader.php não encontrado em modules/barcode/\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar: " . $e->getMessage() . "\n";
}

echo "\n=== 5. TESTAR GERAÇÃO DE BARCODE ===\n";
try {
    if (class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $svg = $generator->getBarcode('TEST123', 'C128');
        if (strpos($svg, '<svg') !== false) {
            echo "✅ BarcodeGeneratorSVG funcionando\n";
            echo "✅ SVG gerado com sucesso (" . strlen($svg) . " bytes)\n";
        } else {
            echo "❌ SVG gerado mas conteúdo inválido\n";
        }
    } else {
        echo "❌ Classe BarcodeGeneratorSVG não encontrada\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao gerar barcode: " . $e->getMessage() . "\n";
}

echo "\n=== 6. ARQUIVOS CSS E JS ===\n";
$css_files = ['css/bootstrap.min.css', 'css/custom.css', 'css/themes.css'];
$js_files = ['js/bootstrap.bundle.min.js', 'js/custom.js'];

foreach ($css_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file) ? '✅' : '❌';
    echo "CSS: $exists $file\n";
}
foreach ($js_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file) ? '✅' : '❌';
    echo "JS:  $exists $file\n";
}

echo "\n=== 7. BIBLIOTECAS CDN (Online) ===\n";
echo "✅ Font Awesome 6.4.0 (para ícones)\n";
echo "✅ Html5-qrcode 2.3.8 (para QR code)\n";
echo "✅ Quagga 0.12.1 (para código de barras)\n";
echo "✅ Bootstrap 5.3.0 (para UI)\n";

echo "\n=== 8. VERIFICAR PATHS EM barcode_print.php ===\n";
if (file_exists($baseDir . 'barcode_print.php')) {
    $content = file_get_contents($baseDir . 'barcode_print.php');

    if (strpos($content, 'generate_barcode_image.php') !== false) {
        echo "✅ Referência a generate_barcode_image.php encontrada\n";
    } else {
        echo "❌ Referência a generate_barcode_image.php NÃO encontrada\n";
    }

    if (strpos($content, "../../includes/header.php") !== false) {
        echo "✅ Referência correta a ../../includes/header.php encontrada\n";
    } else {
        echo "⚠️ Path para header.php pode estar incorreto\n";
    }

    if (strpos($content, "../../includes/footer.php") !== false) {
        echo "✅ Referência correta a ../../includes/footer.php encontrada\n";
    } else {
        echo "⚠️ Path para footer.php pode estar incorreto\n";
    }

    if (strpos($content, "../../config.php") !== false) {
        echo "✅ Referência correta a ../../config.php encontrada\n";
    } else {
        echo "⚠️ Path para config.php pode estar incorreto\n";
    }
}

echo "\n=== 9. VERIFICAR PATHS EM scanner_modal.php ===\n";
if (file_exists($baseDir . 'scanner_modal.php')) {
    $content = file_get_contents($baseDir . 'scanner_modal.php');

    if (strpos($content, "require_once '../../config.php'") !== false) {
        echo "✅ Config.php carregado com path correto\n";
    } else {
        echo "⚠️ Path para config.php pode estar incorreto\n";
    }

    if (strpos($content, 'Html5Qrcode') !== false) {
        echo "✅ Html5Qrcode referenciado\n";
    }

    if (strpos($content, 'Quagga') !== false) {
        echo "✅ Quagga referenciado\n";
    }

    if (strpos($content, "../../css/bootstrap.min.css") !== false) {
        echo "✅ Bootstrap CSS com path correto\n";
    } else {
        echo "⚠️ Path para bootstrap pode estar incorreto\n";
    }
}

echo "\n=== 10. INTEGRAÇÃO COM FOOTER ===\n";
if (file_exists(__DIR__ . '/includes/footer.php')) {
    $footer = file_get_contents(__DIR__ . '/includes/footer.php');

    if (strpos($footer, 'modules/barcode/scanner_modal.php') !== false) {
        echo "✅ Footer aponta corretamente para modules/barcode/scanner_modal.php\n";
    } else {
        echo "❌ Footer NÃO aponta para modules/barcode/scanner_modal.php\n";
    }

    if (strpos($footer, 'setScannedCode') !== false) {
        echo "✅ Função setScannedCode encontrada no footer\n";
    } else {
        echo "⚠️ Função setScannedCode NÃO encontrada no footer\n";
    }
}

echo "\n=== 11. INTEGRAÇÃO COM HEADER ===\n";
if (file_exists(__DIR__ . '/includes/header.php')) {
    $header = file_get_contents(__DIR__ . '/includes/header.php');

    if (strpos($header, 'bootstrap.min.css') !== false) {
        echo "✅ Bootstrap CSS carregado\n";
    }

    if (strpos($header, 'font-awesome') !== false) {
        echo "✅ Font Awesome carregado\n";
    }

    if (strpos($header, 'custom.css') !== false) {
        echo "✅ Custom CSS carregado\n";
    }
}

echo "\n=== 12. VERIFICAR REFERÊNCIAS NAS PÁGINAS PRINCIPAIS ===\n";
$pages_to_check = [
    'products.php' => 'modules/barcode/barcode_print.php',
    'ready_machines.php' => 'modules/barcode/barcode_print.php',
    'warehouse.php' => 'modules/barcode/barcode_print.php',
];

foreach ($pages_to_check as $page => $expected_path) {
    if (file_exists(__DIR__ . '/' . $page)) {
        $content = file_get_contents(__DIR__ . '/' . $page);
        if (strpos($content, $expected_path) !== false) {
            echo "✅ $page aponta corretamente para $expected_path\n";
        } else {
            echo "❌ $page NÃO aponta para $expected_path\n";
        }
    } else {
        echo "⚠️ $page não encontrado\n";
    }
}

echo "\n=== 13. VERIFICAR ESTRUTURA DE DIRETÓRIOS ===\n";
$required_dirs = [
    'modules/barcode/',
    'modules/barcode/barcode-lib/',
    'modules/barcode/barcode-lib/src/',
];

foreach ($required_dirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir) ? '✅ OK' : '❌ FALTA';
    echo sprintf("%-50s %s\n", $dir, $exists);
}

echo "\n=== RESUMO ===\n";
echo "✅ Se todos os itens acima estão com ✅, o sistema de barcode está 100% funcional.\n";
echo "❌ Se há itens com ❌, corrija os caminhos ou arquivos faltantes.\n";
echo "⚠️ Itens com ⚠️ são avisos que podem não impactar o funcionamento.\n";

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Teste a impressão de barcode acessando modules/barcode/barcode_print.php?code=TEST123\n";
echo "2. Teste o scanner clicando no botão de scanner em qualquer página\n";
echo "3. Teste a impressão direta de produtos, máquinas ou warehouse\n";

echo "</pre>";
?>
