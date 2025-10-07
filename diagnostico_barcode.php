<?php

ini_set("display_errors", "On");
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Geração de Código de Barras</h1>";
echo "<p>Este script irá verificar as configurações essenciais para a geração de códigos de barras.</p>";

// 1. Verificar versão do PHP
echo "<h2>1. Versão do PHP</h2>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";

// 2. Verificar extensão GD
echo "<h2>2. Extensão GD</h2>";
if (extension_loaded('gd')) {
    echo "<p style=\"color: green;\">✓ Extensão GD está habilitada.</p>";

    // Tentar criar uma imagem simples para testar a GD
    if (function_exists('imagecreate')) {
        echo "<p style=\"color: green;\">✓ Função imagecreate() está disponível.</p>";
        $img = imagecreate(100, 50);
        if ($img) {
            imagecolorallocate($img, 255, 255, 255);
            imagestring($img, 5, 10, 15, 'GD Test', imagecolorallocate($img, 0, 0, 0));
            
            $testImagePath = __DIR__ . '/uploads/gd_test.png';
            if (imagepng($img, $testImagePath)) {
                echo "<p style=\"color: green;\">✓ Imagem de teste GD criada com sucesso em: " . $testImagePath . "</p>";
                echo "<img src=\"uploads/gd_test.png\" alt=\"GD Test Image\" /><br>";
            } else {
                echo "<p style=\"color: red;\">✗ Falha ao salvar imagem de teste GD. Verifique as permissões de escrita no diretório 'uploads'.</p>";
            }
            imagedestroy($img);
        } else {
            echo "<p style=\"color: red;\">✗ Falha ao criar imagem com GD. Pode haver um problema de memória ou configuração.</p>";
        }
    } else {
        echo "<p style=\"color: red;\">✗ Função imagecreate() NÃO está disponível, apesar da extensão GD estar habilitada. Isso é incomum.</p>";
    }
} else {
    echo "<p style=\"color: red;\">✗ Extensão GD NÃO está habilitada. Por favor, verifique seu php.ini e reinicie o Apache.</p>";
}

// 3. Verificar permissões de escrita no diretório de uploads
echo "<h2>3. Permissões de Diretório</h2>";
$uploadDir = __DIR__ . '/uploads/code/';
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style=\"color: green;\">✓ Diretório '" . $uploadDir . "' criado com sucesso.</p>";
    } else {
        echo "<p style=\"color: red;\">✗ Falha ao criar o diretório '" . $uploadDir . "'. Verifique as permissões da pasta 'uploads'.</p>";
    }
}

if (is_writable($uploadDir)) {
    echo "<p style=\"color: green;\">✓ Diretório '" . $uploadDir . "' tem permissão de escrita.</p>";
    $testFile = $uploadDir . 'permission_test.txt';
    if (file_put_contents($testFile, 'Teste de escrita de arquivo.')) {
        echo "<p style=\"color: green;\">✓ Arquivo de teste escrito com sucesso em: " . $testFile . "</p>";
        unlink($testFile); // Limpa o arquivo de teste
        echo "<p style=\"color: green;\">✓ Arquivo de teste removido.</p>";
    } else {
        echo "<p style=\"color: red;\">✗ Falha ao escrever arquivo no diretório '" . $uploadDir . "'.</p>";
    }
} else {
    echo "<p style=\"color: red;\">✗ Diretório '" . $uploadDir . "' NÃO tem permissão de escrita. Por favor, ajuste as permissões.</p>";
}

// 4. Testar carregamento da biblioteca Picqer/Barcode e geração
echo "<h2>4. Teste da Biblioteca Picqer/Barcode</h2>";

try {
    require_once __DIR__ . '/barcode-loader.php';

    if (class_exists('\Picqer\Barcode\BarcodeGeneratorPNG')) {
        echo "<p style=\"color: green;\">✓ Classe BarcodeGeneratorPNG da biblioteca Picqer/Barcode carregada com sucesso.</p>";
        
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $code = '123456789012'; // Exemplo de código
        $type = 'EAN13';
        $widthFactor = 2;
        $height = 60;

        $barcodeImage = $generator->getBarcode($code, $type, $widthFactor, $height);

        $barcodeTestPath = __DIR__ . '/uploads/code/barcode_test.png';
        if (file_put_contents($barcodeTestPath, $barcodeImage)) {
            echo "<p style=\"color: green;\">✓ Código de barras de teste gerado e salvo com sucesso em: " . $barcodeTestPath . "</p>";
            echo "<img src=\"uploads/code/barcode_test.png\" alt=\"Barcode Test Image\" /><br>";
        } else {
            echo "<p style=\"color: red;\">✗ Falha ao salvar o código de barras de teste. Verifique as permissões de escrita em 'uploads/code/'.</p>";
        }

    } else {
        echo "<p style=\"color: red;\">✗ Classe BarcodeGeneratorPNG NÃO encontrada. Verifique se 'barcode-loader.php' está correto e se os arquivos da biblioteca estão no lugar certo.</p>";
    }
} catch (Exception $e) {
    echo "<p style=\"color: red;\">✗ Erro ao testar a biblioteca Picqer/Barcode: " . $e->getMessage() . "</p>";
    echo "<p>Detalhes: " . $e->getFile() . " na linha " . $e->getLine() . "</p>";
}

echo "<h2>5. Conclusão</h2>";
echo "<p>Analise os resultados acima para identificar o ponto de falha. Se todos os itens estiverem com '✓', o problema pode estar em outro lugar na sua aplicação ou na forma como você está chamando o gerador de código de barras.</p>";

?>
