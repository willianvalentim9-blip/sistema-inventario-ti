<?php
require_once 'config.php';
echo "<h1>Iniciando migração de senhas...</h1>";

try {
    $pdo = getConnection();
    // Seleciona apenas usuários cuja senha NÃO parece ser um hash
    $stmt = $pdo->query("SELECT id, password FROM users WHERE password NOT LIKE '$2y$%'");
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "<p>Nenhuma senha em texto plano encontrada para migrar. O sistema já está atualizado.</p>";
        echo "<p><a href='login.php'>Voltar para o Login</a></p>";
        exit;
    }

    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $count = 0;

    foreach ($users as $user) {
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $update_stmt->execute([$hashed_password, $user['id']]);
        $count++;
    }

    echo "<p style='color: green; font-size: 1.2em;'><b>SUCESSO!</b> {$count} senha(s) foram migradas para o formato criptografado.</p>";
    echo "<p><a href='login.php'>Clique aqui para fazer login com suas senhas antigas (ex: user / user123)</a>.</p>";
    echo "<p style='color: red; font-weight: bold;'>Por segurança, delete este arquivo (migrate_passwords.php) do servidor agora.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro durante a migração: " . $e->getMessage() . "</p>";
}
?>