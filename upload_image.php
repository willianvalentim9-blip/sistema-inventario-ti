<?php
// ========================================
// SISTEMA DE UPLOAD DE IMAGENS (VERSÃO COM REDIMENSIONAMENTO AUTOMÁTICO)
// ========================================
// Este arquivo gerencia o upload, redimensiona e salva as imagens para produtos e máquinas

// Define o cabeçalho como JSON desde o início
header('Content-Type: application/json');

require_once 'config.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.', 'filename' => ''];

/**
 * Função para redimensionar uma imagem mantendo a proporção.
 *
 * @param string $source_path Caminho da imagem original.
 * @param string $destination_path Caminho para salvar a nova imagem.
 * @param int $max_width Largura máxima permitida.
 * @param int $max_height Altura máxima permitida.
 * @param int $quality Qualidade da imagem JPEG/WEBP (0 a 100).
 * @return bool Retorna true em caso de sucesso, false em caso de falha.
 */
function resizeImage($source_path, $destination_path, $max_width = 1024, $max_height = 1024, $quality = 85) {
    list($width, $height, $type) = getimagesize($source_path);
    if (!$width || !$height) {
        return false;
    }

    $ratio = $width / $height;

    if ($width > $max_width || $height > $max_height) {
        if (($max_width / $max_height) > $ratio) {
            $new_width = $max_height * $ratio;
            $new_height = $max_height;
        } else {
            $new_height = $max_width / $ratio;
            $new_width = $max_width;
        }
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Carrega a imagem original com base no tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case IMAGETYPE_WEBP:
             $source_image = imagecreatefromwebp($source_path);
             imagealphablending($new_image, false);
             imagesavealpha($new_image, true);
            break;
        default:
            return false;
    }

    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Salva a nova imagem
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($new_image, $destination_path, $quality);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($new_image, $destination_path);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($new_image, $destination_path, 9); // Compressão máxima para PNG
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($new_image, $destination_path, $quality);
            break;
    }

    imagedestroy($source_image);
    imagedestroy($new_image);

    return $success;
}


try {
    // Validação de segurança
    if (!isLoggedIn()) {
        throw new Exception('Usuário não autenticado. Faça o login novamente.');
    }

    // Validações do arquivo
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo enviado ou erro no upload.');
    }
    
    $file = $_FILES['image'];
    $itemType = $_POST['type'] ?? 'product'; // 'product' ou 'machine'
    
    // Define o diretório de destino com base no tipo
    $upload_dir = __DIR__ . '/uploads/' . ($itemType === 'machine' ? 'machines/' : 'products/');

    // Validações de segurança do arquivo
    $max_file_size = 10 * 1024 * 1024; // 10MB
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['size'] > $max_file_size) {
        throw new Exception('Arquivo muito grande. Máximo de 10MB.');
    }
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Tipo de arquivo não permitido.');
    }
    if (getimagesize($file['tmp_name']) === false) {
        throw new Exception('O arquivo enviado não é uma imagem válida.');
    }

    // Cria o diretório se ele não existir
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Falha ao criar o diretório de uploads. Verifique as permissões da pasta no servidor.');
        }
    }
    
    // Gera um nome de arquivo único e seguro
    $filename = uniqid('img_', true) . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // **NOVO: Redimensiona a imagem antes de salvar**
    if (!resizeImage($file['tmp_name'], $filepath)) {
        // Se o redimensionamento falhar, tenta mover o arquivo original como fallback
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("Falha ao mover o arquivo: " . $file['tmp_name'] . " para " . $filepath);
            throw new Exception('Erro fatal ao salvar o arquivo no servidor. Verifique as permissões da pasta `uploads/`.');
        }
    }
    
    // Sucesso
    $response['success'] = true;
    $response['message'] = 'Imagem enviada com sucesso!';
    $response['filename'] = $filename;

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("Erro no upload de imagem: " . $e->getMessage());
}

// Envia a resposta final em JSON
echo json_encode($response);
exit();