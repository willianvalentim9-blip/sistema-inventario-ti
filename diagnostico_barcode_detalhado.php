<?php
/**
 * DIAGNÓSTICO DETALHADO DO SISTEMA DE BARCODE
 */

$baseDir = __DIR__ . '/';

// Lista de arquivos que devem existir
$filesToLoad = [
    'barcode-lib/src/Exceptions/BarcodeException.php',
    'barcode-lib/src/Exceptions/InvalidCharacterException.php',
    'barcode-lib/src/Exceptions/InvalidCheckDigitException.php',
    'barcode-lib/src/Exceptions/InvalidFormatException.php',
    'barcode-lib/src/Exceptions/InvalidLengthException.php',
    'barcode-lib/src/Exceptions/InvalidOptionException.php',
    'barcode-lib/src/Exceptions/UnknownColorException.php',
    'barcode-lib/src/Exceptions/UnknownTypeException.php',
    'barcode-lib/src/Barcode.php',
    'barcode-lib/src/BarcodeBar.php',
    'barcode-lib/src/BarcodeGenerator.php',
    'barcode-lib/src/Renderers/RendererInterface.php',
    'barcode-lib/src/Renderers/PngRenderer.php',
    'barcode-lib/src/BarcodeGeneratorPNG.php',
    'barcode-lib/src/Types/TypeInterface.php',
    'barcode-lib/src/Types/TypeEanUpcBase.php',
    'barcode-lib/src/Types/TypeCode128.php',
    'barcode-lib/src/Types/TypeCode39.php',
    'barcode-lib/src/Types/TypeCode39Checksum.php',
    'barcode-lib/src/Types/TypeCode39Extended.php',
    'barcode-lib/src/Types/TypeCode39ExtendedChecksum.php',
    'barcode-lib/src/Types/TypeEan13.php',
    'barcode-lib/src/Types/TypeUpcA.php',
    'barcode-lib/src/Types/TypeCodabar.php',
    'barcode-lib/src/Types/TypeInterleaved25Checksum.php',
    'barcode-lib/src/Types/TypeInterleaved25.php',
    'barcode-lib/src/Types/TypePharmacode.php'
];

echo "=== DIAGNÓSTICO DE BARCODE ===\n\n";

$missing = [];
$found = [];

foreach ($filesToLoad as $file) {
    $fullPath = $baseDir . $file;
    if (file_exists($fullPath)) {
        $found[] = $file;
        echo "✓ $file\n";
    } else {
        $missing[] = $file;
        echo "✗ FALTANDO: $file\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "Encontrados: " . count($found) . "\n";
echo "Faltando: " . count($missing) . "\n";

if (!empty($missing)) {
    echo "\nARQUIVOS FALTANDO:\n";
    foreach ($missing as $file) {
        echo "  - $file\n";
    }
}

echo "\n=== TENTANDO CARREGAR BIBLIOTECA ===\n";

try {
    require_once $baseDir . 'barcode-loader.php';
    echo "✓ Biblioteca carregada com sucesso!\n";
    
    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
    echo "✓ Gerador de SVG instanciado com sucesso!\n";
    
    $barcode = $generator->getBarcode('TESTE123', 'C128', 2, 60);
    echo "✓ Barcode gerado com sucesso!\n";
    echo "  Tamanho da imagem: " . strlen($barcode) . " bytes\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}

echo "\nDiagnóstico finalizado.\n";
?>
