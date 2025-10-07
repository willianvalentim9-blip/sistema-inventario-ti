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
    
    // Renderizadores
    'barcode-lib/src/Renderers/RendererInterface.php',
    'barcode-lib/src/Renderers/PngRenderer.php',

    // Gerador principal que será usado
    'barcode-lib/src/BarcodeGeneratorPNG.php',

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
        
        // Cria uma imagem de erro para feedback visual imediato no navegador
        if (!headers_sent()) {
            header('Content-Type: image/png');
        }
        $image = imagecreate(800, 60);
        if ($image) {
            imagecolorallocate($image, 255, 235, 238); // Fundo rosa
            $textColor = imagecolorallocate($image, 185, 28, 28); // Texto vermelho
            imagestring($image, 3, 10, 5, "ERRO NO SERVIDOR: Arquivo de biblioteca ausente.", $textColor);
            imagestring($image, 3, 10, 25, "Caminho: " . basename(dirname($fullPath)) . "/" . basename($fullPath), $textColor);
            imagepng($image);
            imagedestroy($image);
        }
        exit;
    }
}