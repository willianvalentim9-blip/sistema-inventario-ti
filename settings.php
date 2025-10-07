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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
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

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-cogs me-2"></i>Configurações do Sistema</h1>
</div>
    
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="settings.php">
             <input type="hidden" name="save_settings" value="1">
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
    </div>
</div>

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