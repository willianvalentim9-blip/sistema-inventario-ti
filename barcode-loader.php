<?php
/**
 * CARREGADOR CENTRAL DA BIBLIOTECA DE CÓDIGO DE BARRAS (VERSÃO FINAL E COMPLETA)
 * Este arquivo contém todos os 'require_once' necessários para a biblioteca funcionar.
 */

$baseDir = __DIR__ . '/';

// Estrutura de arquivos para garantir a ordem de carregamento correta
$filesToLoad = [
    // Exceções primeiro
    'barcode-lib/src/Exceptions/BarcodeException.php',
    'barcode-lib/src/Exceptions/InvalidCharacterException.php',
    'barcode-lib/src/Exceptions/InvalidCheckDigitException.php',
    'barcode-lib/src/Exceptions/InvalidFormatException.php',
    'barcode-lib/src/Exceptions/InvalidLengthException.php',
    'barcode-lib/src/Exceptions/InvalidOptionException.php',
    'barcode-lib/src/Exceptions/UnknownColorException.php',
    'barcode-lib/src/Exceptions/UnknownTypeException.php',

    // Classes base e helpers
    'barcode-lib/src/Barcode.php',
    'barcode-lib/src/BarcodeBar.php',
    'barcode-lib/src/BarcodeGenerator.php',
    'barcode-lib/src/Helpers/ColorHelper.php',
    'barcode-lib/src/Helpers/StringHelpers.php',
    'barcode-lib/src/Helpers/BinarySequenceConverter.php',
    
    // Renderizadores
    'barcode-lib/src/Renderers/RendererInterface.php',
    'barcode-lib/src/Renderers/PngRenderer.php',
    'barcode-lib/src/Renderers/SvgRenderer.php',

    // Geradores
    'barcode-lib/src/BarcodeGeneratorPNG.php',
    'barcode-lib/src/BarcodeGeneratorSVG.php',

    // Interface e tipos base
    'barcode-lib/src/Types/TypeInterface.php',
    'barcode-lib/src/Types/TypeEanUpcBase.php',
    
    // Tipos específicos
    'barcode-lib/src/Types/TypeCode128.php',
    'barcode-lib/src/Types/TypeCode39.php', // Base do Code39
    'barcode-lib/src/Types/TypeCode39Checksum.php',
    'barcode-lib/src/Types/TypeCode39Extended.php',
    'barcode-lib/src/Types/TypeCode39ExtendedChecksum.php',
    'barcode-lib/src/Types/TypeEan13.php',
    'barcode-lib/src/Types/TypeUpcA.php',
    'barcode-lib/src/Types/TypeCodabar.php',
    'barcode-lib/src/Types/TypeInterleaved25Checksum.php', // Base para I25
    'barcode-lib/src/Types/TypeInterleaved25.php',
    'barcode-lib/src/Types/TypePharmacode.php'
];

foreach ($filesToLoad as $file) {
    $fullPath = $baseDir . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
    } else {
        // Gera um erro visível se um arquivo da biblioteca não for encontrado.
        $errorMessage = "ERRO FATAL: Arquivo de biblioteca de codigo de barras nao encontrado: " . htmlspecialchars($fullPath);
        error_log($errorMessage);
        
        // Retorna erro em SVG (funciona sem GD)
        if (!headers_sent()) {
            header('Content-Type: image/svg+xml; charset=utf-8');
        }
        $fileName = basename(dirname($fullPath)) . "/" . basename($fullPath);
        echo <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="60" viewBox="0 0 800 60">
    <rect width="100%" height="100%" fill="#ffe6e6"/>
    <text x="10" y="20" font-family="Arial" font-size="14" fill="#c41c1c">
        ERRO NO SERVIDOR: Arquivo de biblioteca ausente.
    </text>
    <text x="10" y="45" font-family="Arial" font-size="12" fill="#c41c1c">
        $fileName
    </text>
</svg>
SVG;
        exit;
    }
}