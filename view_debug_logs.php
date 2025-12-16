<?php
// Página para visualizar logs de debug
require_once 'config.php';
requireLogin();

$page_title = 'Debug Logs - Componentes';

$debug_log_file = __DIR__ . '/debug_components.log';
$log_exists = file_exists($debug_log_file);
$log_content = '';
$log_size = 0;
$log_mtime = null;

if ($log_exists) {
    $log_size = filesize($debug_log_file);
    $log_mtime = filemtime($debug_log_file);
    $log_content = file_get_contents($debug_log_file);
}

// Ação de limpar logs
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($debug_log_file, '');
    header('Location: view_debug_logs.php');
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>🔍 Debug Logs - Componentes de Máquinas</h1>
        <div>
            <a href="view_debug_logs.php" class="btn btn-primary">
                <i class="fas fa-sync"></i> Recarregar
            </a>
            <?php if ($log_exists && $log_size > 0): ?>
            <a href="view_debug_logs.php?clear=1" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja limpar os logs?')">
                <i class="fas fa-trash"></i> Limpar Logs
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($log_exists): ?>
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <strong>Informações do Arquivo</strong>
        </div>
        <div class="card-body">
            <p><strong>Caminho:</strong> <code><?php echo htmlspecialchars($debug_log_file); ?></code></p>
            <p><strong>Tamanho:</strong> <?php echo number_format($log_size); ?> bytes</p>
            <p><strong>Última Modificação:</strong> <?php echo $log_mtime ? date('Y-m-d H:i:s', $log_mtime) : 'N/A'; ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Conteúdo do Log</strong>
        </div>
        <div class="card-body" style="background: #1e1e1e; color: #d4d4d4;">
            <?php if (!empty($log_content)): ?>
                <pre style="margin: 0; white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 12px;"><?php echo htmlspecialchars($log_content); ?></pre>
            <?php else: ?>
                <p class="text-muted">O arquivo de log está vazio.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Arquivo de log não existe ainda.</strong>
        <p>O arquivo <code><?php echo htmlspecialchars($debug_log_file); ?></code> será criado automaticamente quando você adicionar uma máquina com componentes.</p>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="modules/machines/add_machine.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Máquina (Gerar Logs)
        </a>
        <a href="modules/machines/ready_machines.php" class="btn btn-secondary">
            <i class="fas fa-desktop"></i> Ver Máquinas
        </a>
        <a href="modules/machines/debug_logs.php" class="btn btn-info">
            <i class="fas fa-cog"></i> Ver Configuração PHP
        </a>
    </div>
</div>

<script>
// Auto-reload a cada 5 segundos
setTimeout(function() {
    window.location.reload();
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>
