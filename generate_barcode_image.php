<?php
/**
 * GERADOR DE CÓDIGO DE BARRAS (VERSÃO FINAL COM CARREGADOR CENTRAL)
 */

// Define o diretório base
$baseDir = __DIR__ . '/';

// Carrega as configurações do sistema
require_once $baseDir . 'config.php';

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

    // Inicializa o gerador de código de barras para o formato PNG
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    
    // Gera a imagem do código de barras
    $barcodeImage = $generator->getBarcode($code, $type, $widthFactor, $height);

    // Envia a imagem para o navegador
    header('Content-Type: image/png');
    echo $barcodeImage;

} catch (Exception $e) {
    // Se ocorrer um erro, cria e exibe uma imagem com a mensagem de erro
    http_response_code(500);
    header('Content-Type: image/png');
    $image = imagecreate(450, 40);
    if ($image) {
        $bg = imagecolorallocate($image, 255, 235, 238); // Fundo rosa
        $textColor = imagecolorallocate($image, 185, 28, 28); // Texto vermelho
        imagestring($image, 3, 10, 5, "Erro ao gerar o codigo:", $textColor);
        imagestring($image, 2, 10, 20, $e->getMessage(), $textColor);
        imagepng($image);
        imagedestroy($image);
    }
}
exit();
?>