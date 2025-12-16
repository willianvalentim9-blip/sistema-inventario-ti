<?php
/**
 * CARREGADOR CENTRAL DA BIBLIOTECA DE CÓDIGO DE BARRAS
 * Usa autoloader PSR-4 para carregar a biblioteca completa
 * Localização: modules/barcode/barcode-loader.php
 */

// 1. Tentar carregar via Composer (vendor)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    if (class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
        // Sucesso! Picqer foi carregado via Composer
        return;
    }
}

// 2. Carregar biblioteca barcode-lib local usando autoloader PSR-4
$baseDir = __DIR__ . '/barcode-lib/src/';

if (is_dir(__DIR__ . '/barcode-lib')) {
    // Registrar autoloader PSR-4 para Picqer\Barcode
    spl_autoload_register(function ($class) use ($baseDir) {
        // Apenas processar classes do namespace Picqer\Barcode
        $prefix = 'Picqer\\Barcode\\';

        // Verificar se a classe usa o namespace correto
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return; // Não é nossa classe, deixar outro autoloader tentar
        }

        // Remover o prefix do namespace
        $relativeClass = substr($class, strlen($prefix));

        // Converter namespace para caminho de arquivo
        // Picqer\Barcode\BarcodeGeneratorSVG -> BarcodeGeneratorSVG.php
        // Picqer\Barcode\Types\TypeCode128 -> Types/TypeCode128.php
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        // Se o arquivo existe, carregar
        if (file_exists($file)) {
            require_once $file;
        }
    });

    // Verificar se o autoloader funciona
    if (class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
        // Sucesso! Autoloader PSR-4 carregou a biblioteca completa
        return;
    }
}

// 3. Fallback: carregar biblioteca simplificada (apenas se tudo mais falhar)
if (file_exists(__DIR__ . '/barcode-lib-simple.php')) {
    require_once __DIR__ . '/barcode-lib-simple.php';
}

if (!class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
    throw new Exception(
        'Nenhuma biblioteca de barcode encontrada! Tentativas: ' .
        '1) composer (vendor/) - NÃO ENCONTRADO, ' .
        '2) barcode-lib/ com autoloader - FALHOU, ' .
        '3) barcode-lib-simple.php - FALHOU'
    );
}
