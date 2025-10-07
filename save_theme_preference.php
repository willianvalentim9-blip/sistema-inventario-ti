<?php
require_once 'config.php';

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Lê os dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['theme'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tema não especificado']);
    exit;
}

$theme = $input['theme'];
$user_id = $_SESSION['user_id'];

// Valida o tema
if (!in_array($theme, ["light", "dark_blue"])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tema inválido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Salva a preferência de tema do usuário
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute(["user_theme_{$user_id}", $theme]);
    
    echo json_encode(['success' => true, 'message' => 'Preferência de tema salva']);
    
} catch (PDOException $e) {
    error_log("Erro ao salvar preferência de tema: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

