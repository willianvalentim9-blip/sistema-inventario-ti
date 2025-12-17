<?php
// ========================================
// API PARA ATUALIZAÇÃO DO AVATAR (VERSÃO CORRIGIDA)
// ========================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../../config.php';
requireLogin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

/**
 * Redimensiona e otimiza uma imagem de avatar
 * Tamanho final: 150x150px para exibição em perfil
 */
function resizeAndOptimizeAvatar($source_path, $destination_path, $target_size = 150) {
    if (!extension_loaded('gd')) {
        throw new Exception('Extensão GD não está habilitada no PHP.');
    }

    if (!file_exists($source_path)) {
        throw new Exception('Arquivo de origem não encontrado.');
    }

    // Detectar tipo de imagem
    $imageinfo = getimagesize($source_path);
    if ($imageinfo === false) {
        throw new Exception('Não foi possível ler a imagem.');
    }

    $image_type = $imageinfo[2]; // IMAGETYPE_*
    
    // Carregar imagem de acordo com seu tipo
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            throw new Exception('Tipo de imagem não suportado.');
    }

    if (!$source_image) {
        throw new Exception('Erro ao carregar a imagem.');
    }

    $width = imagesx($source_image);
    $height = imagesy($source_image);

    // Criar imagem quadrada (cropar para o maior tamanho que cabe)
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;

    // Criar imagem final com tamanho de 150x150
    $final_image = imagecreatetruecolor($target_size, $target_size);

    // Manter transparência se PNG
    if ($image_type == IMAGETYPE_PNG) {
        imagealphablending($final_image, false);
        imagesavealpha($final_image, true);
        $transparent = imagecolorallocatealpha($final_image, 255, 255, 255, 127);
        imagefill($final_image, 0, 0, $transparent);
    }

    // Redimensionar a imagem
    imagecopyresampled(
        $final_image,
        $source_image,
        0, 0,
        $x, $y,
        $target_size, $target_size,
        $size, $size
    );

    // Salvar como JPG otimizado (melhor compatibilidade e tamanho)
    if (!imagejpeg($final_image, $destination_path, 85)) {
        imagedestroy($source_image);
        imagedestroy($final_image);
        throw new Exception('Erro ao salvar a imagem processada.');
    }

    // Limpar memória
    imagedestroy($source_image);
    imagedestroy($final_image);

    return true;
}

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];
$user_id = $_SESSION['user_id'];
$pdo = getConnection();
$upload_dir = __DIR__ . '/../../uploads/avatars/';

try {
    // Busca os dados atuais do usuário
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $current_avatar = $user['avatar'] ?? null;

    // --- AÇÃO DE REMOVER O AVATAR ---
    if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
        
        if (!empty($current_avatar)) {
            $avatar_file = $upload_dir . $current_avatar;
            if (file_exists($avatar_file)) {
                if (!@unlink($avatar_file)) {
                    throw new Exception('Não foi possível deletar o arquivo da imagem.');
                }
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
            $result = $stmt->execute([$user_id]);
            
            if (!$result) {
                throw new Exception('Erro ao atualizar banco de dados.');
            }
            
            $_SESSION['user_avatar'] = '';
            $response = ['success' => true, 'message' => 'Foto de perfil removida com sucesso!'];
        } catch (Exception $e) {
            throw new Exception('Erro ao remover foto: ' . $e->getMessage());
        }

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

        // Salvar com extensão JPG para compatibilidade
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.jpg';
        $new_filepath = $upload_dir . $new_filename;

        // Redimensionar e processar imagem
        try {
            resizeAndOptimizeAvatar($file['tmp_name'], $new_filepath);
        } catch (Exception $e) {
            throw new Exception('Erro ao processar imagem: ' . $e->getMessage());
        }

        if (file_exists($new_filepath)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$new_filename, $user_id]);

            $_SESSION['user_avatar'] = $new_filename;
            $response = ['success' => true, 'message' => 'Foto de perfil atualizada com sucesso!'];
        } else {
            throw new Exception('Falha ao processar e salvar a imagem.');
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