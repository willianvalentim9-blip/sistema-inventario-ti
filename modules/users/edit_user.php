<?php
require_once '../../config.php';
requireAdmin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    if (!$is_modal) header('Location: users.php');
    exit('ID de usuário inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, role, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        if (!$is_modal) header('Location: users.php');
        exit('Usuário não encontrado.');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar usuário para edição: " . $e->getMessage());
    if (!$is_modal) header('Location: users.php');
    exit('Erro de banco de dados.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];

    $edit_user_id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $full_name = trim($_POST['full_name'] ?? '');

    if (empty($username) || empty($email) || empty($role)) {
        $response['message'] = 'Nome de usuário, email e função são obrigatórios.';
        echo json_encode($response);
        exit;
    }

    if (!in_array($role, ['admin', 'user'])) {
        $response['message'] = 'Função inválida.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $edit_user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Nome de usuário já está em uso.');
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email já está em uso.');
        }

        $stmt = $pdo->prepare("
            UPDATE users SET
                username = ?,
                email = ?,
                role = ?,
                full_name = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $role, $full_name, $edit_user_id]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Usuário atualizado com sucesso!';
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'success';

        echo json_encode($response);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        $response['message'] = $e->getMessage();
        echo json_encode($response);
        exit();
    }
}
?>

<form id="editUserForm" method="POST" action="edit_user.php?id=<?php echo $user['id']; ?>&modal=true">
    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
    <div id="edit-error-message-user" class="mb-3"></div>

    <div class="mb-3">
        <label for="edit_full_name" class="form-label">Nome Completo</label>
        <input type="text" class="form-control" id="edit_full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
    </div>
    <div class="mb-3">
        <label for="edit_username" class="form-label">Nome de Usuário *</label>
        <input type="text" class="form-control" id="edit_username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="edit_email" class="form-label">Email *</label>
        <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="edit_role" class="form-label">Função *</label>
        <select class="form-select" id="edit_role" name="role" required <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
            <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Usuário</option>
            <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
        </select>
        <?php if ($user['id'] == $_SESSION['user_id']): ?>
            <small class="form-text text-muted">Você não pode alterar sua própria função.</small>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-end border-top pt-3 mt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
    </div>
</form>

<script>
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonHtml = submitButton.innerHTML;

        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitButton.disabled = true;

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message) });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                modal.hide();
                location.reload();
            } else {
                document.getElementById('edit-error-message-user').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                submitButton.innerHTML = originalButtonHtml;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            document.getElementById('edit-error-message-user').innerHTML = `<div class="alert alert-danger">Erro: ${error.message}</div>`;
            submitButton.innerHTML = originalButtonHtml;
            submitButton.disabled = false;
        });
    });
</script>