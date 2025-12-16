<?php
// ========================================
// DIAGNÓSTICO: SISTEMA E SERVIDOR
// ========================================

function check_item($description, $status, $message_ok, $message_fail) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($description) . '</td>';
    if ($status) {
        echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span></td>';
        echo '<td>' . $message_ok . '</td>';
    } else {
        echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>FALHA</span></td>';
        echo '<td class="text-danger">' . $message_fail . '</td>';
    }
    echo '</tr>';
    return $status;
}

$all_ok = true;
?>

<h4 class="mb-3"><i class="fas fa-server me-2"></i>Verificações do Ambiente PHP</h4>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th width="30%">Verificação</th>
            <th width="15%">Status</th>
            <th>Detalhes</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // 1. Versão do PHP
        $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
        if (!check_item(
            'Versão do PHP',
            $php_version_ok,
            'Versão ' . PHP_VERSION . ' é compatível',
            'Versão ' . PHP_VERSION . ' - Requer PHP 7.4 ou superior'
        )) $all_ok = false;

        // 2. Extensão PDO
        $pdo_ok = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        if (!check_item(
            'Extensão PDO MySQL',
            $pdo_ok,
            'PDO MySQL está ativo',
            'PDO MySQL não encontrado - Necessário para conexão com banco de dados'
        )) $all_ok = false;

        // 3. Extensão GD
        $gd_ok = extension_loaded('gd');
        check_item(
            'Extensão GD',
            $gd_ok,
            'GD ativo - Permite manipulação de imagens',
            'GD não ativo - Descomente extension=gd no php.ini (opcional para SVG)'
        );

        // 4. Extensão FileInfo
        $fileinfo_ok = extension_loaded('fileinfo');
        check_item(
            'Extensão FileInfo',
            $fileinfo_ok,
            'FileInfo ativo - Validação MIME funcional',
            'FileInfo não ativo - Descomente extension=fileinfo no php.ini'
        );

        // 5. Extensão MBString
        $mbstring_ok = extension_loaded('mbstring');
        check_item(
            'Extensão MBString',
            $mbstring_ok,
            'MBString ativo - Suporte a caracteres UTF-8',
            'MBString não ativo - Descomente extension=mbstring no php.ini'
        );

        // 6. Session
        $session_ok = function_exists('session_start');
        if (!check_item(
            'Suporte a Sessões',
            $session_ok,
            'Sessões PHP funcionando corretamente',
            'Sessões não disponíveis'
        )) $all_ok = false;

        // 7. Upload Max Size
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $upload_ok = true;
        check_item(
            'Tamanho Máximo de Upload',
            $upload_ok,
            "upload_max_filesize: $upload_max | post_max_size: $post_max",
            'Configuração não adequada'
        );

        // 8. Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_ok = true;
        check_item(
            'Limite de Memória PHP',
            $memory_ok,
            "Limite atual: $memory_limit",
            'Memória insuficiente'
        );

        // 9. Error Reporting
        $error_reporting = ini_get('display_errors');
        $display_errors_ok = true;
        check_item(
            'Exibição de Erros',
            $display_errors_ok,
            $error_reporting ? 'Ativo (modo desenvolvimento)' : 'Desativado (modo produção)',
            'Configuração inadequada'
        );

        // 10. Timezone
        $timezone = date_default_timezone_get();
        $timezone_ok = !empty($timezone);
        check_item(
            'Timezone',
            $timezone_ok,
            "Timezone: $timezone",
            'Timezone não configurado'
        );
        ?>
    </tbody>
</table>

<?php if ($all_ok): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Ambiente OK!</strong> Todas as verificações críticas passaram com sucesso.
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Atenção!</strong> Alguns recursos críticos estão ausentes. Verifique os itens marcados em vermelho.
    </div>
<?php endif; ?>

<h4 class="mt-4 mb-3"><i class="fas fa-info-circle me-2"></i>Informações do Servidor</h4>

<table class="table table-sm table-bordered">
    <tr>
        <td width="30%"><strong>Sistema Operacional</strong></td>
        <td><?php echo PHP_OS; ?></td>
    </tr>
    <tr>
        <td><strong>Servidor Web</strong></td>
        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></td>
    </tr>
    <tr>
        <td><strong>Versão PHP</strong></td>
        <td><?php echo phpversion(); ?></td>
    </tr>
    <tr>
        <td><strong>Pasta do Sistema</strong></td>
        <td><code><?php echo realpath(__DIR__ . '/../..'); ?></code></td>
    </tr>
    <tr>
        <td><strong>Pasta de Diagnóstico</strong></td>
        <td><code><?php echo __DIR__; ?></code></td>
    </tr>
</table>
