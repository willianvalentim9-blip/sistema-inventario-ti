<?php
require_once '../../config.php';
requireAdmin();

$user_id = intval($_REQUEST['id'] ?? 0); 

if ($user_id <= 0) {
    exit('ID de usuário inválido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Ocorreu um erro.'];
    
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($new_password)) {
        $response['message'] = 'A nova senha é obrigatória.';
    } elseif (strlen($new_password) < 6) {
        $response['message'] = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $user_id]);
            
            logAdminActivity($_SESSION["user_id"], "CHANGE_PASSWORD", "users", $user_id);

            $response['success'] = true;
            $response['message'] = 'Senha do usuário alterada com sucesso!';
            $_SESSION['flash_message'] = $response['message'];
            $_SESSION['flash_type'] = 'success';
        } catch (PDOException $e) {
            http_response_code(500);
            $response['message'] = 'Erro ao alterar senha: ' . $e->getMessage();
            error_log("Erro ao alterar senha do usuário: " . $e->getMessage());
        }
    }
    echo json_encode($response);
    exit;
}

// HTML part for GET request
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        exit('Usuário não encontrado.');
    }
} catch (PDOException $e) {
    exit('Erro de banco de dados.');
}

?>
<form id="changePasswordForm" method="POST" action="change_password_user.php?id=<?php echo $user_id; ?>">
    <div id="change-password-error-message" class="mb-3"></div>
    <p>Alterando senha para o usuário: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
    <div class="mb-3">
        <label for="new_password" class="form-label">Nova Senha *</label>
        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
        <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
    </div>
    <div class="d-flex justify-content-end border-top pt-3 mt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i> Alterar Senha</button>
    </div>
</form>

<script>
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton.innerHTML;

    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Alterando...';
    submitButton.disabled = true;

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
            modal.hide();
            location.reload(); 
        } else {
            document.getElementById('change-password-error-message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            submitButton.innerHTML = originalButtonHtml;
            submitButton.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('change-password-error-message').innerHTML = `<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>`;
        submitButton.innerHTML = originalButtonHtml;
        submitButton.disabled = false;
    });
});
</script>