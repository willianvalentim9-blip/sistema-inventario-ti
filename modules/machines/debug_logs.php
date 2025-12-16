<?php
// Página temporária para debug de logs
require_once '../../config.php';
requireLogin();

$page_title = 'Debug Logs';

// Força exibição de erros na tela para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Testa error_log
error_log("🧪 TESTE: Este é um log de teste gerado em " . date('Y-m-d H:i:s'));

// Informações sobre configuração de logs
$error_log_path = ini_get('error_log');
$log_errors = ini_get('log_errors');

?>
<?php include '../../includes/header.php'; ?>

<div class="container mt-4">
    <h1>🔍 Debug: Configuração de Logs</h1>

    <div class="card mt-3">
        <div class="card-header bg-primary text-white">
            <h5>Configuração do PHP</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <td><strong>error_log:</strong></td>
                    <td><?php echo htmlspecialchars($error_log_path ?: 'não configurado (usa syslog ou STDERR)'); ?></td>
                </tr>
                <tr>
                    <td><strong>log_errors:</strong></td>
                    <td><?php echo $log_errors ? 'Ativo' : 'Desativado'; ?></td>
                </tr>
                <tr>
                    <td><strong>display_errors:</strong></td>
                    <td><?php echo ini_get('display_errors') ? 'Ativo' : 'Desativado'; ?></td>
                </tr>
                <tr>
                    <td><strong>error_reporting:</strong></td>
                    <td><?php echo error_reporting(); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <h5>Locais Comuns de Logs no XAMPP</h5>
        </div>
        <div class="card-body">
            <?php
            $possible_logs = [
                'c:\xampp\apache\logs\error.log',
                'c:\xampp\php\logs\php_error_log',
                'c:\xampp\logs\php_error_log',
                'c:\xampp\apache\logs\php_error_log',
                'c:\tmp\php_errors.log',
                'c:\windows\temp\php_errors.log'
            ];

            echo '<table class="table table-sm">';
            echo '<tr><th>Arquivo</th><th>Status</th><th>Tamanho</th><th>Última Modificação</th></tr>';

            foreach ($possible_logs as $log) {
                $exists = file_exists($log);
                $readable = $exists && is_readable($log);

                echo '<tr>';
                echo '<td><code>' . htmlspecialchars($log) . '</code></td>';

                if ($exists) {
                    if ($readable) {
                        $size = filesize($log);
                        $mtime = filemtime($log);
                        echo '<td><span class="badge bg-success">Existe e legível</span></td>';
                        echo '<td>' . number_format($size) . ' bytes</td>';
                        echo '<td>' . date('Y-m-d H:i:s', $mtime) . '</td>';
                    } else {
                        echo '<td><span class="badge bg-warning">Existe mas não é legível</span></td>';
                        echo '<td>-</td><td>-</td>';
                    }
                } else {
                    echo '<td><span class="badge bg-secondary">Não existe</span></td>';
                    echo '<td>-</td><td>-</td>';
                }

                echo '</tr>';
            }

            echo '</table>';
            ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-success text-white">
            <h5>Teste de Log Gerado</h5>
        </div>
        <div class="card-body">
            <p>Um log de teste foi gerado com a mensagem: <code>🧪 TESTE: Este é um log de teste...</code></p>
            <p>Verifique nos arquivos acima se o log apareceu.</p>
        </div>
    </div>

    <div class="mt-3">
        <a href="add_machine.php" class="btn btn-primary">← Voltar para Adicionar Máquina</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
