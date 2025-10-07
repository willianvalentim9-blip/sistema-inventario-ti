<?php
// ========================================
// PÁGINA DE REDEFINIÇÃO DE SENHA (MANTENDO SENHA SIMPLES)
// ========================================
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';
$token = trim($_GET['token'] ?? '');
$is_token_valid = false;
$user = null;

if (empty($token)) {
    $error_message = 'Token de redefinição inválido ou ausente.';
} else {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $is_token_valid = true;
        } else {
            $error_message = 'Token inválido ou expirado. Por favor, solicite um novo link de redefinição.';
        }
    } catch (Exception $e) {
        $error_message = 'Ocorreu um erro no servidor. Tente novamente mais tarde.';
        error_log("Erro em reset_password.php: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || strlen($new_password) < 6) {
        $error_message = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'As senhas não coincidem.';
    } else {
        try {
            // Salva a nova senha como texto simples, conforme solicitado
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $stmt->execute([$new_password, $user['id']]);
            
            $success_message = 'Sua senha foi redefinida com sucesso! Agora você pode fazer o login.';
            $is_token_valid = false;
        } catch (Exception $e) {
            $error_message = 'Ocorreu um erro ao redefinir sua senha. Tente novamente.';
            error_log("Erro ao salvar nova senha: " . $e->getMessage());
        }
    }
}

$page_title = 'Redefinir Senha';
$hide_sidebar = true;
include 'includes/header.php';
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="w-100" style="max-width: 450px;">
        <div class="card card-custom shadow-lg">
            <div class="card-header card-header-custom text-center">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Redefinir Senha</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <div class="d-grid">
                        <a href="login.php" class="btn btn-primary-custom">Ir para o Login</a>
                    </div>
                <?php elseif ($is_token_valid): ?>
                    <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="new_password" class="form-label form-label-custom">Nova Senha</label>
                            <input type="password" class="form-control form-control-custom" id="new_password" name="new_password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label form-label-custom">Confirmar Nova Senha</label>
                            <input type="password" class="form-control form-control-custom" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-save me-2"></i>
                                Redefinir Senha
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-outline-secondary">Solicitar Novo Link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>