<?php
/**
 * GERADOR DE CÓDIGO DE BARRAS (VERSÃO SVG - SEM GD)
 */

// Define o diretório base
$baseDir = __DIR__ . '/';

// Não requer conexão com banco de dados, pois apenas gera a imagem

try {
    // Carrega a biblioteca de código de barras de forma centralizada
    require_once $baseDir . 'barcode-loader.php';
    
    // Obtém os parâmetros da URL
    $code = $_GET['code'] ?? '';
    $type = $_GET['type'] ?? 'C128'; // Padrão para Code 128
    $widthFactor = intval($_GET['width'] ?? 2);
    $height = intval($_GET['height'] ?? 60);

    if (empty($code)) {
        throw new Exception('O código para gerar o barcode é obrigatório.');
    }

    // Usa SVG em vez de PNG (não requer GD)
    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
    
    // Gera a imagem do código de barras em SVG
    $barcodeSvg = $generator->getBarcode($code, $type, $widthFactor, $height);

    // Envia como SVG
    header('Content-Type: image/svg+xml; charset=utf-8');
    echo $barcodeSvg;

} catch (Exception $e) {
    // Se ocorrer um erro, retorna SVG com mensagem de erro
    http_response_code(500);
    header('Content-Type: image/svg+xml; charset=utf-8');
    $errorMessage = htmlspecialchars($e->getMessage());
    echo <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="80" viewBox="0 0 400 80">
    <rect width="100%" height="100%" fill="#ffe6e6"/>
    <text x="10" y="25" font-family="Arial" font-size="14" fill="#c41c1c">
        Erro ao gerar barcode:
    </text>
    <text x="10" y="50" font-family="Arial" font-size="12" fill="#c41c1c">
        $errorMessage
    </text>
</svg>
SVG;
}
exit();
?>
