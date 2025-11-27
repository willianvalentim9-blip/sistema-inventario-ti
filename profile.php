<?php
// ========================================
// PÁGINA DE PERFIL DO USUÁRIO (VERSÃO CORRIGIDA)
// ========================================
require_once 'config.php';
requireLogin();

$page_title = 'Meu Perfil';
$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Erro ao carregar dados do usuário.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- ATUALIZAR INFORMAÇÕES DO PERFIL ---
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $theme = trim($_POST['theme']);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, theme = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $theme, $user_id]);
            
            $_SESSION['username'] = $username;
            $success_message = "Perfil atualizado com sucesso!";
            
            // Recarrega os dados do usuário para exibir as informações atualizadas
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

        } catch (PDOException $e) {
            $error_message = ($e->errorInfo[1] == 1062) ? "Nome de usuário ou e-mail já está em uso." : "Erro ao atualizar o perfil.";
        }
    }

    // --- ALTERAR SENHA ---
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    $success_message = "Senha alterada com sucesso!";
                } else {
                    $error_message = "A nova senha deve ter pelo menos 6 caracteres.";
                }
            } else {
                $error_message = "A nova senha e a confirmação não correspondem.";
            }
        } else {
            $error_message = "A senha atual está incorreta.";
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-user-circle me-2"></i>Meu Perfil</h1>
</div>

<?php if ($error_message): ?>
<div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>
<?php if ($success_message): ?>
<div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card card-custom text-center">
            <div class="card-body">
                
                <div class="profile-avatar-container mb-3">
                    <?php
                    $avatar_path = 'uploads/avatars/' . ($user['avatar'] ?? '');
                    if (!empty($user['avatar']) && file_exists($avatar_path)):
                    ?>
                        <img src="<?php echo htmlspecialchars($avatar_path) . '?v=' . time(); ?>" 
                             alt="Avatar do Usuário" 
                             class="img-thumbnail rounded-circle" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="default-avatar-icon">
                            <i class="fas fa-user-circle text-secondary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="card-title"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-info'; ?>">
                    <?php echo ucfirst($user['role']); ?>
                </span>

                <div class="mt-3">
                    <input type="file" class="d-none" id="avatar-input" accept="image/png, image/jpeg, image/gif">
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('avatar-input').click();">
                        <i class="fas fa-upload me-1"></i> Alterar Foto
                    </button>
                    
                    <?php if (!empty($user['avatar'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="remove-avatar-btn">
                        <i class="fas fa-trash me-1"></i> Remover Foto
                    </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#edit-profile">Editar Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#change-password">Alterar Senha</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="edit-profile">
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                           
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="change-password">
                        <div class="row justify-content-center">
                            <div class="col-lg-10 col-xl-8">
                                <form action="profile.php" method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Senha Atual</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility(this, 'current_password')"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nova Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility(this, 'new_password')"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility(this, 'confirm_password')"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-key me-1"></i> Alterar Senha</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Adicione este estilo para o ícone padrão */
.default-avatar-icon {
    width: 150px;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    margin-left: auto;
    margin-right: auto;
}
.default-avatar-icon i {
    font-size: 80px; /* Tamanho do ícone */
}
</style>

<script>
function togglePasswordVisibility(button, inputId) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Lógica para upload e remoção automática do avatar
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar-input');
    const removeBtn = document.getElementById('remove-avatar-btn');

    // Função para fazer o upload via AJAX
    const uploadAvatar = (file) => {
        const formData = new FormData();
        formData.append('avatar', file);
        
        showAlert('Enviando nova foto...', 'info', 0);

        fetch('update_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.message || 'Erro ao enviar a imagem.', 'error');
            }
        })
        .catch(error => {
            showAlert('Erro de comunicação. Tente novamente.', 'error');
            console.error('Error:', error);
        });
    };

    // Função para remover via AJAX
    const removeAvatar = () => {
        showConfirmation({
            title: 'Remover Foto',
            message: 'Tem certeza que deseja remover sua foto de perfil?',
            type: 'danger',
            confirmText: 'Sim, Remover',
            onConfirm: () => {
                const formData = new FormData();
                formData.append('remove_avatar', '1');

                showAlert('Removendo foto...', 'info', 0);

                fetch('update_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showAlert(data.message || 'Erro ao remover a imagem.', 'error');
                    }
                })
                .catch(error => {
                    showAlert('Erro de comunicação. Tente novamente.', 'error');
                    console.error('Error:', error);
                });
            }
        });
    };

    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                uploadAvatar(this.files[0]);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', removeAvatar);
    }
});
</script>

<?php include 'includes/footer.php'; ?>