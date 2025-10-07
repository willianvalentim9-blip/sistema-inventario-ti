<?php
// ========================================
// SCRIPT DE DIAGNÓSTICO DO SISTEMA (VERSÃO COM CARREGADOR CENTRAL)
// ========================================
header('Content-Type: text/html; charset=utf-8');

// --- Funções de verificação ---
function check($description, $status, $message_ok, $message_fail) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($description) . '</td>';
    if ($status) {
        echo '<td><span class="badge bg-success">OK</span></td>';
        echo '<td>' . htmlspecialchars($message_ok) . '</td>';
    } else {
        echo '<td><span class="badge bg-danger">FALHA</span></td>';
        echo '<td>' . $message_fail . '</td>';
    }
    echo '</tr>';
    return $status;
}

$all_ok = true;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico do Sistema de Código de Barras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 800px; margin-top: 2rem; }
        .card-header { font-weight: bold; }
        .badge { font-size: 0.9em; }
        td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                Relatório de Diagnóstico do Servidor
            </div>
            <div class="card-body">
                <p>Este relatório verifica se o seu ambiente de servidor atende aos requisitos para gerar códigos de barras.</p>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Verificação</th>
                            <th>Status</th>
                            <th>Detalhes e Solução</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 1. Versão do PHP
                        $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
                        if (!check('Versão do PHP', $php_version_ok, 'Versão ' . PHP_VERSION . ' é compatível.', 'Sua versão do PHP é ' . PHP_VERSION . '. É necessário PHP 7.4 ou superior.')) $all_ok = false;

                        // 2. Extensão GD
                        $gd_ok = extension_loaded('gd') && function_exists('imagecreate');
                        if (!check('Extensão GD', $gd_ok, 'A extensão GD está instalada e ativa.', '<b>A extensão GD não está ativa!</b> Para corrigir, abra seu arquivo <code>php.ini</code>, procure a linha <code>;extension=gd</code> e remova o <code>;</code> do início. Depois, <b>reinicie o seu servidor Apache</b>.')) $all_ok = false;

                        // 3. Carregador da Biblioteca
                        $loader_path = __DIR__ . '/barcode-loader.php';
                        $loader_ok = file_exists($loader_path);
                        if (!check('Carregador da Biblioteca', $loader_ok, 'O arquivo barcode-loader.php foi encontrado.', '<b>Arquivo não encontrado:</b> <code>' . htmlspecialchars($loader_path) . '</code>. Certifique-se de que o arquivo <code>barcode-loader.php</code> que criamos anteriormente existe na pasta <code>Sistema/</code>.')) $all_ok = false;
                        
                        // 4. Teste de Geração de Imagem
                        $image_test_ok = false;
                        $error_message = '';
                        if ($gd_ok && $loader_ok) {
                            try {
                                // **CORREÇÃO**: Usa o carregador centralizado
                                require_once $loader_path;

                                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                $barcodeImage = $generator->getBarcode('12345', 'C128', 2, 30);
                                
                                if ($barcodeImage && strlen($barcodeImage) > 100) {
                                    $image_test_ok = true;
                                } else {
                                    $error_message = 'A função de geração retornou dados inválidos.';
                                }
                            } catch (Throwable $e) {
                                $error_message = 'Erro ao tentar gerar o código de barras: ' . $e->getMessage();
                            }
                        } else {
                            $error_message = 'Teste não executado devido a falhas anteriores.';
                        }
                        if (!check('Teste de Geração', $image_test_ok, 'A biblioteca conseguiu gerar uma imagem de código de barras com sucesso.', '<b>Falha ao gerar a imagem.</b> Erro: ' . htmlspecialchars($error_message))) $all_ok = false;
                        ?>
                    </tbody>
                </table>

                <hr>

                <div id="summary">
                    <?php if ($all_ok): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading">Tudo Certo!</h4>
                            <p class="mb-0">Seu sistema parece estar configurado corretamente para gerar códigos de barras.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Problemas Encontrados!</h4>
                            <p class="mb-0">O diagnóstico encontrou um ou mais problemas. Por favor, siga as instruções na coluna "Detalhes e Solução" para corrigir o item com status de <strong>FALHA</strong>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>