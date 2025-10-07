<?php
// ========================================
// API PARA ATUALIZAÇÃO DO AVATAR (VERSÃO CORRIGIDA)
// ========================================
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$user_id = $_SESSION['user_id'];
$pdo = getConnection();
$upload_dir = __DIR__ . '/uploads/avatars/';

try {
    // Busca os dados atuais do usuário
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $current_avatar = $user['avatar'] ?? null;

    // --- AÇÃO DE REMOVER O AVATAR ---
    if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
        
        if (!empty($current_avatar) && file_exists($upload_dir . $current_avatar)) {
            @unlink($upload_dir . $current_avatar);
        }

        $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $_SESSION['user_avatar'] = null;
        $response = ['success' => true, 'message' => 'Foto de perfil removida com sucesso!'];

    // --- AÇÃO DE FAZER UPLOAD DE UM NOVO AVATAR ---
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['avatar'];

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('O arquivo é muito grande (máximo 5MB).');
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido (apenas JPG, PNG, GIF, WebP).');
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!empty($current_avatar) && file_exists($upload_dir . $current_avatar)) {
            @unlink($upload_dir . $current_avatar);
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;

        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$new_filename, $user_id]);

            $_SESSION['user_avatar'] = $new_filename;
            $response = ['success' => true, 'message' => 'Foto de perfil atualizada com sucesso!'];
        } else {
            throw new Exception('Falha ao mover o arquivo para o destino.');
        }
    } else {
        throw new Exception('Nenhuma ação válida (remover ou upload) foi solicitada.');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("Erro em update_avatar.php: " . $e->getMessage());
}

echo json_encode($response);
exit();