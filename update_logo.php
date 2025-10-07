<?php
// ========================================
// API PARA ATUALIZAÇÃO DA LOGO (UPLOAD E REMOÇÃO)
// ========================================
require_once 'config.php';
requireAdmin(); // Apenas admins podem alterar a logo

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$pdo = getConnection();
$upload_dir = __DIR__ . '/assets/img/';

try {
    // Busca a logo atual
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'company_logo'");
    $stmt->execute();
    $current_logo_path = $stmt->fetchColumn();

    // --- AÇÃO DE REMOVER A LOGO ---
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
        
        // Remove o arquivo físico se ele existir
        if (!empty($current_logo_path) && file_exists(__DIR__ . '/' . $current_logo_path)) {
            @unlink(__DIR__ . '/' . $current_logo_path);
        }

        // Atualiza o banco de dados para remover a referência
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'company_logo'");
        $stmt->execute(['']);
        
        logAdminActivity($_SESSION['user_id'], 'REMOVE_LOGO', 'system_settings');
        $response = ['success' => true, 'message' => 'Logo da empresa removida com sucesso!'];

    // --- AÇÃO DE FAZER UPLOAD DE UMA NOVA LOGO ---
    } elseif (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['company_logo'];

        // Validações de segurança
        if ($file['size'] > 5 * 1024 * 1024) { // Limite de 5MB
            throw new Exception('O arquivo é muito grande (máximo 5MB).');
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido (apenas JPG, PNG, GIF, WebP).');
        }

        // Garante que o diretório de uploads exista
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Apaga a logo antiga se existir
        if (!empty($current_logo_path) && file_exists(__DIR__ . '/' . $current_logo_path)) {
            @unlink(__DIR__ . '/' . $current_logo_path);
        }

        // Cria um nome de arquivo novo e seguro
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'logo_' . time() . '.' . $file_ext;
        $new_filepath_relative = 'assets/img/' . $new_filename;
        $new_filepath_absolute = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $new_filepath_absolute)) {
            // Atualiza o banco de dados
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('company_logo', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$new_filepath_relative]);
            
            logAdminActivity($_SESSION['user_id'], 'UPDATE_LOGO', 'system_settings');
            $response = ['success' => true, 'message' => 'Logo da empresa atualizada com sucesso!'];
        } else {
            throw new Exception('Falha ao mover o arquivo para o destino.');
        }
    } else {
        throw new Exception('Nenhuma ação válida (remover ou upload) foi solicitada.');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("Erro em update_logo.php: " . $e->getMessage());
}

echo json_encode($response);
exit();