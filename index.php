<?php
// ========================================
// ARQUIVO INDEX PRINCIPAL DO SISTEMA
// ========================================
// Este arquivo redireciona para o dashboard ou login

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
    // Usuário logado - redireciona para o dashboard
    header("Location: public/dashboard.php");
} else {
    // Usuário não logado - redireciona para o login
    header("Location: modules/auth/login.php");
}
exit();
?>
