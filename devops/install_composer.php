<?php
/**
 * Script para instalar Composer e dependências
 * Execute no terminal: php install_composer.php
 */

echo "=== Instalando Composer e Dependências ===\n\n";

// 1. Verificar se composer.phar existe
$composer_path = __DIR__ . '/composer.phar';
if (!file_exists($composer_path)) {
    echo "[1/3] Baixando Composer...\n";
    $composer_url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    
    // Usar file_get_contents ou curl
    if (ini_get('allow_url_fopen')) {
        $data = @file_get_contents($composer_url);
        
        if ($data) {
            file_put_contents($composer_path, $data);
            chmod($composer_path, 0755);
            echo "✓ Composer baixado com sucesso!\n\n";
        } else {
            echo "✗ Erro ao baixar Composer via file_get_contents\n";
            exit(1);
        }
    } else if (function_exists('curl_init')) {
        $ch = curl_init($composer_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        
        if ($data) {
            file_put_contents($composer_path, $data);
            chmod($composer_path, 0755);
            echo "✓ Composer baixado com sucesso!\n\n";
        } else {
            echo "✗ Erro ao baixar Composer via curl\n";
            exit(1);
        }
    } else {
        echo "✗ Nenhum método disponível. Baixe manualmente de: https://getcomposer.org\n";
        exit(1);
    }
} else {
    echo "[1/3] Composer já existe\n\n";
}

// 2. Executar composer install
echo "[2/3] Executando composer install...\n";
$output = [];
$return_var = 0;
exec("php " . escapeshellarg($composer_path) . " install 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "✓ Composer install executado com sucesso!\n\n";
    foreach ($output as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ Erro ao executar composer install\n";
    foreach ($output as $line) {
        echo "  " . $line . "\n";
    }
    exit(1);
}

// 3. Verificar se a biblioteca foi instalada
echo "\n[3/3] Verificando instalação...\n";
$vendor_file = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor_file)) {
    require_once $vendor_file;
    if (class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
        echo "✓ Biblioteca Picqer instalada com sucesso!\n\n";
        echo "=== SUCESSO ===\n";
        echo "Agora você pode usar o barcode_print.php normalmente.\n";
        exit(0);
    } else {
        echo "✗ Classe BarcodeGeneratorSVG não encontrada\n";
        exit(1);
    }
} else {
    echo "✗ vendor/autoload.php não encontrado\n";
    exit(1);
}
?>
