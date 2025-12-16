<?php
// ========================================
// PÁGINA DE LOGOUT DO SISTEMA (VERSÃO ATUALIZADA COM LOG DE ADMIN)
// ========================================
// Esta página encerra a sessão do usuário e redireciona para o login

// Inclui o arquivo de configuração
require_once '../../config.php';

// ========================================
// PROCESSO DE LOGOUT
// ========================================

// Registra o logout no log de administrador (para auditoria)
if (isLoggedIn()) {
    logAdminActivity(
        $_SESSION['user_id'],
        'LOGOUT',
        'users',
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    );
}

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se existir um cookie de sessão, remove ele
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Inicia uma nova sessão para mostrar mensagem de logout
session_start();
$_SESSION['logout_message'] = 'Você foi desconectado com sucesso.';

// Redireciona para a página de login
header("Location: login.php");
exit();
?>