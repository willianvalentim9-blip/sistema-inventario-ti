<?php
// ========================================
// PÁGINA DE CONFIGURAÇÕES DO SISTEMA (VERSÃO CORRIGIDA)
// ========================================
require_once 'config.php';
requireAdmin();

$page_title = 'Configurações do Sistema';

// Carrega configurações atuais do banco
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $settings = array_merge([
        'system_name' => 'Sistema de Estoque TI',
        'company_name' => 'Sua Empresa',
        'company_slogan' => 'Controle de estoque de TI',
        'company_logo' => ''
    ], $db_settings);
} catch (PDOException $e) {
    $settings = [];
    $_SESSION['flash_message'] = 'Erro ao carregar configurações.';
    $_SESSION['flash_type'] = 'danger';
}

// Processa o formulário para salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        try {
            $pdo->beginTransaction();
            $settings_to_save = ['system_name', 'company_name', 'company_slogan'];
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($settings_to_save as $key) {
                if (isset($_POST[$key])) {
                    $stmt->execute([$key, trim($_POST[$key])]);
                }
            }
            $pdo->commit();
            logAdminActivity($_SESSION["user_id"], "UPDATE_SETTINGS", "system_settings");
            $_SESSION['flash_message'] = 'Configurações salvas com sucesso!';
            $_SESSION['flash_type'] = 'success';
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_message'] = 'Erro ao salvar configurações: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: settings.php");
        exit();
    }

    if ($action === 'backup_database') {
        try {
            $pdo = getConnection();
            
            // Cria arquivo de backup com timestamp
            $backup_dir = __DIR__ . '/backups';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';
            
            // Executa mysqldump via PowerShell
            $db_host = DB_HOST;
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            $db_name = DB_NAME;
            
            // Comando PowerShell para executar mysqldump
            $command = "Get-Content -LiteralPath 'NONE' | & 'C:\\xampp\\mysql\\bin\\mysqldump.exe' -h " . escapeshellarg($db_host) . " -u " . escapeshellarg($db_user) . " -p" . escapeshellarg($db_pass) . " " . escapeshellarg($db_name);
            
            // Alternativa: usar função PHP para fazer backup
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql_content = "-- Backup do banco de dados: " . DB_NAME . "\n";
            $sql_content .= "-- Data: " . date('Y-m-d H:i:s') . "\n";
            $sql_content .= "-- Host: " . DB_HOST . "\n\n";
            
            // Para cada tabela, faz dump
            foreach ($tables as $table) {
                $sql_content .= "\n-- Estrutura da tabela: $table\n";
                $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
                
                // Get CREATE TABLE
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $create_table = $stmt->fetch(PDO::FETCH_NUM)[1];
                $sql_content .= $create_table . ";\n";
                
                // Get dados
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $sql_content .= "\nINSERT INTO `$table` VALUES\n";
                    $values = [];
                    foreach ($rows as $row) {
                        $row_values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $row_values[] = 'NULL';
                            } else {
                                $row_values[] = $pdo->quote($value);
                            }
                        }
                        $values[] = '(' . implode(',', $row_values) . ')';
                    }
                    $sql_content .= implode(",\n", $values) . ";\n";
                }
            }
            
            // Salva arquivo
            file_put_contents($backup_file, $sql_content);
            
            logAdminActivity($_SESSION["user_id"], "BACKUP_DATABASE_SUCCESS", "backup: " . basename($backup_file));
            $_SESSION['flash_message'] = 'Backup realizado com sucesso! Arquivo: ' . basename($backup_file);
            $_SESSION['flash_type'] = 'success';
            
        } catch (Exception $e) {
            logAdminActivity($_SESSION["user_id"], "BACKUP_DATABASE_ERROR", "erro: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Erro ao fazer backup: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: settings.php");
        exit();
    }
}

include 'includes/header.php';

// Exibe mensagens flash que podem ter sido definidas no processamento do POST
if (isset($_SESSION["flash_message"])) {
    echo '<div class="alert alert-' . htmlspecialchars($_SESSION["flash_type"] ?? 'info') . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION["flash_message"]) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION["flash_message"]);
    unset($_SESSION["flash_type"]);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-cogs me-2"></i>Configurações do Sistema</h1>
</div>
    
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="settings.php" id="settings-form">
             <input type="hidden" name="action" value="save_settings">
            <div class="card card-custom mb-4">
                <div class="card-header card-header-custom"><i class="fas fa-info-circle me-2"></i>Informações Gerais</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="system_name" class="form-label">Nome do Sistema</label>
                            <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo htmlspecialchars($settings['system_name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Nome da Empresa</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="company_slogan" class="form-label">Slogan da Empresa</label>
                        <input type="text" class="form-control" id="company_slogan" name="company_slogan" value="<?php echo htmlspecialchars($settings['company_slogan']); ?>">
                    </div>
                </div>
                 <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-image me-2"></i>Logo da Empresa</div>
            <div class="card-body text-center">
                <?php $logo_path = $settings['company_logo'] ?? ''; ?>
                <div id="logo-display-area" class="mb-3">
                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                        <img src="<?php echo $logo_path . '?v=' . time(); ?>" alt="Logo da Empresa" id="logo-preview" class="img-thumbnail" style="max-height: 80px;">
                    <?php else: ?>
                        <div class="text-muted p-3 border rounded" id="logo-placeholder">
                            <i class="fas fa-building fa-3x"></i>
                            <p class="mb-0 mt-2">Sem logo</p>
                        </div>
                        <img src="" alt="Logo Preview" id="logo-preview" class="img-thumbnail d-none" style="max-height: 80px;">
                    <?php endif; ?>
                </div>
                
                <input type="file" class="d-none" id="logo-input" accept="image/png, image/jpeg, image/gif, image/webp">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('logo-input').click();"><i class="fas fa-upload me-1"></i> Alterar</button>
                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="remove-logo-btn"><i class="fas fa-trash me-1"></i> Remover</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-tools me-2"></i>Manutenção do Sistema</div>
            <div class="card-body">
                <p class="text-muted small">Realize tarefas de manutenção para otimizar o sistema.</p>
                <div class="d-grid gap-2">
                    <button type="submit" form="backupForm" class="btn btn-success"><i class="fas fa-database me-2"></i>Fazer Backup Agora</button>
                </div>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom"><i class="fas fa-clipboard-list me-2"></i>Diagnóstico e Logs</div>
            <div class="card-body">
                <p class="text-muted small">Acesse os registros e ferramentas de diagnóstico.</p>
                <div class="d-grid gap-2">
                    <a href="modules/logs/admin_logs.php" class="btn btn-outline-secondary"><i class="fas fa-shield-alt me-2"></i>Logs de Administrador</a>
                    <a href="dev/diagnostico/" class="btn btn-outline-info" target="_blank"><i class="fas fa-stethoscope me-2"></i>Central de Diagnósticos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulário de backup (oculto) -->
<form method="POST" action="settings.php" id="backupForm" class="d-none">
    <input type="hidden" name="action" value="backup_database">
</form>

<?php 
// O FOOTER É INCLUÍDO AQUI, CARREGANDO TODOS OS SCRIPTS GLOBAIS
include 'includes/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logo-input');
    const removeBtn = document.getElementById('remove-logo-btn');

    if (logoInput) {
        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('company_logo', this.files[0]);
                
                showAlert('Enviando nova logo...', 'info');
                
                fetch('update_logo.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { 
                            showAlert(data.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else { 
                            showAlert(data.message || 'Erro desconhecido.', 'danger'); 
                        }
                    })
                    .catch(() => showAlert('Erro de comunicação com o servidor.', 'danger'));
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            // A função showConfirmation agora existe e será chamada corretamente
            showConfirmation({
                title: 'Remover Logo', 
                message: 'Deseja realmente remover a logo da empresa?', 
                type: 'danger',
                confirmText: 'Sim, Remover',
                onConfirm: () => {
                    const formData = new FormData();
                    formData.append('remove_logo', '1');
                    
                    showAlert('Removendo logo...', 'info');
                    
                    fetch('update_logo.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) { 
                                showAlert(data.message, 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else { 
                                showAlert(data.message || 'Erro desconhecido.', 'danger'); 
                            }
                        })
                        .catch(() => showAlert('Erro de comunicação com o servidor.', 'danger'));
                }
            });
        });
    }
});
</script>