<?php
require_once 'config.php';

function reset_admin_user($username, $password, $email) {
    $pdo = getConnection();
    
    // Deleta o usuário admin antigo, se existir
    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt_delete->execute([$username]);
    
    // **CORREÇÃO**: Criptografa a senha antes de inserir
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insere o novo usuário admin com a senha criptografada
    $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
    $stmt_insert->execute([$username, $hashed_password, $email]);
}

try {
    // A senha aqui é 'admin123', que será criptografada antes de salvar
    reset_admin_user('admin', 'admin123', 'admin@example.com');
    echo "<h1>Usuário 'admin' resetado com sucesso!</h1>";
    echo "<p>A senha foi definida como: <strong>admin123</strong></p>";
    echo "<p>O sistema a salvou de forma segura e criptografada.</p>";
    echo "<p><a href='login.php'>Clique aqui para ir para a página de login.</a></p>";
} catch (Exception $e) {
    echo "<h1>Erro ao resetar o usuário administrador:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>