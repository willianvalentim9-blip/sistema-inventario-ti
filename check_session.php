<?php
// ========================================
// VERIFICADOR DE SESSÃO VIA AJAX
// ========================================
// Este arquivo verifica se a sessão do usuário ainda está ativa

// Inclui o arquivo de configuração
require_once 'config.php';

// Define o cabeçalho como JSON
header('Content-Type: application/json');

// ========================================
// VERIFICAÇÃO DA SESSÃO
// ========================================

$response = array();

try {
    // Verifica se o usuário está logado
    if (isLoggedIn()) {
        // Verifica se a sessão não expirou (opcional - 8 horas)
        $login_time = $_SESSION['login_time'] ?? 0;
        $current_time = time();
        $session_duration = 8 * 60 * 60; // 8 horas em segundos
        
        if (($current_time - $login_time) > $session_duration) {
            // Sessão expirou
            session_destroy();
            $response['logged_in'] = false;
            $response['message'] = 'Sessão expirada';
        } else {
            // Sessão ainda válida
            $response['logged_in'] = true;
            $response['user_id'] = $_SESSION['user_id'];
            $response['username'] = $_SESSION['username'];
            $response['user_role'] = $_SESSION['user_role'];
            $response['message'] = 'Sessão ativa';
        }
    } else {
        // Usuário não está logado
        $response['logged_in'] = false;
        $response['message'] = 'Usuário não logado';
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    // Erro na verificação
    $response['success'] = false;
    $response['logged_in'] = false;
    $response['message'] = 'Erro na verificação de sessão';
    error_log("Erro na verificação de sessão: " . $e->getMessage());
}

// Retorna a resposta em JSON
echo json_encode($response);
exit();
?>

