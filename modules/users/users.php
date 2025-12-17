<?php
// ========================================
// PÁGINA DE GERENCIAMENTO DE USUÁRIOS (VERSÃO COMPLETA E CORRIGIDA)
// ========================================
// Esta página permite visualizar, adicionar, editar e excluir usuários do sistema

// Inclui o arquivo de configuração
require_once '../../config.php';

// Verifica se o usuário está logado e é admin
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/dashboard.php');
    exit;
}

// Define variáveis para o template
$page_title = 'Gerenciar Usuários';

// ========================================
// PROCESSAMENTO DE AÇÕES (ADICIONAR USUÁRIO)
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        // Adicionar novo usuário
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $full_name = trim($_POST['full_name'] ?? '');
        
        // Validação
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos.';
            $_SESSION['flash_type'] = 'danger';
        } elseif (strlen($password) < 6) {
            $_SESSION['flash_message'] = 'A senha deve ter pelo menos 6 caracteres.';
            $_SESSION['flash_type'] = 'danger';
        } elseif (!in_array($role, ['admin', 'user', 'administrativo'])) {
            $_SESSION['flash_message'] = 'Função inválida.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                $pdo = getConnection();
                
                // Verifica se username já existe
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['flash_message'] = 'Nome de usuário já existe.';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    // Verifica se email já existe
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $_SESSION['flash_message'] = 'Email já está em uso.';
                        $_SESSION['flash_type'] = 'danger';
                    } else {
                        // Insere o novo usuário com senha simples
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, email, password, role, full_name, is_deleted) 
                            VALUES (?, ?, ?, ?, ?, FALSE)
                        ");
                        $stmt->execute([$username, $email, $password, $role, $full_name]);
                        $_SESSION['flash_message'] = 'Usuário adicionado com sucesso!';
                        $_SESSION['flash_type'] = 'success';
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Erro ao adicionar usuário: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
                error_log("Erro ao adicionar usuário: " . $e->getMessage());
            }
        }
        // Redireciona para a mesma página para mostrar a notificação e limpar o POST
        header("Location: ./users.php");
        exit();
    }
}

// ========================================
// BUSCA USUÁRIOS
// ========================================
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construção da query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Adiciona filtro para excluir usuários deletados (soft delete)
if (empty($where_clause)) {
    $where_clause = 'WHERE (is_deleted = FALSE OR is_deleted IS NULL)';
} else {
    $where_clause .= ' AND (is_deleted = FALSE OR is_deleted IS NULL)';
}

try {
    $pdo = getConnection();
    
    // Conta total de usuários
    $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_users / $per_page);
    
    // Busca usuários da página atual
    $sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Estatísticas
    $stats_stmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
            COUNT(CASE WHEN role = 'administrativo' THEN 1 END) as administrativos,
            COUNT(CASE WHEN role = 'user' THEN 1 END) as regular_users,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_users
        FROM users
        WHERE (is_deleted = FALSE OR is_deleted IS NULL)
    ");
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar usuários: " . $e->getMessage());
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'admins' => 0, 'administrativos' => 0, 'regular_users' => 0, 'active_users' => 0];
}
?>

<?php include '../../includes/header.php'; ?>

<?php
// Bloco para exibir mensagens de feedback (sucesso/erro)
if (isset($_SESSION["flash_message"])) {
    $alert_type = $_SESSION["flash_type"] ?? 'info';
    $icon = $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    echo 
    '<div class="alert alert-' . htmlspecialchars($alert_type) . ' alert-dismissible fade show" role="alert">
        <i class="fas ' . $icon . ' me-2"></i>
        ' . htmlspecialchars($_SESSION["flash_message"]) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION["flash_message"]);
    unset($_SESSION["flash_type"]);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-users me-2"></i>
        Gerenciar Usuários
        <span class="badge badge-custom-secondary ms-2"><?php echo number_format($total_users); ?></span>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-1"></i>
                Novo Usuário
            </button>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom text-center">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-primary-custom mb-2"></i>
                <h5 class="card-title"><?php echo number_format($stats['total']); ?></h5>
                <p class="card-text text-muted">Total de Usuários</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom text-center">
            <div class="card-body">
                <i class="fas fa-user-shield fa-2x text-warning mb-2"></i>
                <h5 class="card-title"><?php echo number_format($stats['admins']); ?></h5>
                <p class="card-text text-muted">Administradores</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom text-center">
            <div class="card-body">
                <i class="fas fa-user-cog fa-2x text-purple mb-2"></i>
                <h5 class="card-title"><?php echo number_format($stats['administrativos'] ?? 0); ?></h5>
                <p class="card-text text-muted">Administrativos</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom text-center">
            <div class="card-body">
                <i class="fas fa-user fa-2x text-info mb-2"></i>
                <h5 class="card-title"><?php echo number_format($stats['regular_users']); ?></h5>
                <p class="card-text text-muted">Usuários</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom text-center">
            <div class="card-body">
                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                <h5 class="card-title"><?php echo number_format($stats['active_users']); ?></h5>
                <p class="card-text text-muted">Ativos (30 dias)</p>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label form-label-custom">
                    <i class="fas fa-search me-1"></i>
                    Buscar Usuário
                </label>
                <input type="text" 
                       class="form-control form-control-custom" 
                       id="search" 
                       name="search" 
                       placeholder="Nome, email ou usuário..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="role" class="form-label form-label-custom">
                    <i class="fas fa-user-tag me-1"></i>
                    Função
                </label>
                <select class="form-select form-control-custom" id="role" name="role">
                    <option value="">Todas</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="administrativo" <?php echo $role_filter === 'administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Usuário</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-search me-1"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($search) || !empty($role_filter)): ?>
            <div class="mt-3">
                <a href="./users.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>
                    Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Função</th>
                            <th>Último Acesso</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <i class="fas fa-user-circle fa-2x text-primary-custom"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                            <?php if (!empty($user['full_name'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-info';
                                    $role_label = 'Usuário';

                                    if ($user['role'] === 'admin') {
                                        $badge_class = 'bg-warning text-dark';
                                        $role_label = 'Administrador';
                                    } elseif ($user['role'] === 'administrativo') {
                                        $badge_class = 'bg-success text-white';
                                        $role_label = 'Administrativo';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $role_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['last_login']):
                                        echo "<small>" . date('d/m/Y H:i', strtotime($user['last_login'])) . "</small>";
                                    else:
                                        echo "<small class=\"text-muted\">Nunca</small>";
                                    endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-primary" 
                                                onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-info" 
                                                onclick="showUserHistory(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"
                                                data-bs-toggle="tooltip" 
                                                title="Histórico">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <?php if ($user["id"] != $_SESSION["user_id"]): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="openDeleteModal('user', <?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-warning" 
                                                    onclick="changePassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Alterar Senha">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum usuário encontrado</h5>
                <p>Use o botão "Novo Usuário" para adicionar o primeiro.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="./users.php">
                    <input type="hidden" name="action" value="add_user">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_role" class="form-label">Função</label>
                        <select class="form-select" id="add_role" name="role">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                            <option value="administrativo">Administrativo</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Histórico do Usuário -->
<div class="modal fade" id="userHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>Histórico do Usuário: <span id="historyUserName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent" class="table-responsive">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Detalhes da Alteração -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Alteração</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Valores Anteriores</h6>
                        <pre id="oldValues" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><small>Carregando...</small></pre>
                    </div>
                    <div class="col-md-6">
                        <h6>Valores Novos</h6>
                        <pre id="newValues" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><small>Carregando...</small></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Função para exibir histórico do usuário
function showUserHistory(userId, userName) {
    document.getElementById('historyUserName').textContent = userName;
    const historyModal = new bootstrap.Modal(document.getElementById('userHistoryModal'));
    historyModal.show();
    
    // Carrega o histórico via AJAX
    fetch(`../../api/get_user_history.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs && data.logs.length > 0) {
                let html = `
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-clock me-2"></i>Data/Hora</th>
                                <th><i class="fas fa-cogs me-2"></i>Ação</th>
                                <th><i class="fas fa-table me-2"></i>Tabela</th>
                                <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                <th><i class="fas fa-info-circle me-2"></i>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.logs.forEach(log => {
                    const timestamp = new Date(log.timestamp).toLocaleString('pt-BR');
                    
                    // Cores por ação
                    const actionColors = {
                        'LOGIN': 'success',
                        'LOGOUT': 'warning',
                        'CREATE': 'info',
                        'UPDATE': 'primary',
                        'DELETE': 'danger',
                        'RESTORE': 'success',
                        'UPDATE_WARRANTY': 'warning',
                        'UPDATE_MACHINE': 'primary',
                        'UPDATE_WAREHOUSE': 'primary'
                    };
                    const color = actionColors[log.action] || 'secondary';
                    
                    // Ícones por ação
                    const actionIcons = {
                        'LOGIN': 'fa-sign-in-alt',
                        'LOGOUT': 'fa-sign-out-alt',
                        'CREATE': 'fa-plus-circle',
                        'UPDATE': 'fa-edit',
                        'DELETE': 'fa-trash',
                        'RESTORE': 'fa-undo',
                        'UPDATE_WARRANTY': 'fa-certificate',
                        'UPDATE_MACHINE': 'fa-edit',
                        'UPDATE_WAREHOUSE': 'fa-edit'
                    };
                    const icon = actionIcons[log.action] || 'fa-cog';
                    
                    const actionLabels = {
                        'LOGIN': 'Login',
                        'LOGOUT': 'Logout',
                        'CREATE': 'Criado',
                        'UPDATE': 'Atualizado',
                        'DELETE': 'Deletado',
                        'RESTORE': 'Restaurado',
                        'UPDATE_WARRANTY': 'Garantia Atualizada',
                        'UPDATE_MACHINE': 'Máquina Atualizada',
                        'UPDATE_WAREHOUSE': 'Armazém Atualizado'
                    };
                    const label = actionLabels[log.action] || log.action;
                    
                    html += `
                        <tr>
                            <td class="fw-500"><small>${timestamp}</small></td>
                            <td>
                                <span class="badge bg-${color}">
                                    <i class="fas ${icon} me-1"></i>${label}
                                </span>
                            </td>
                            <td><small class="text-muted">${log.table_name || '-'}</small></td>
                            <td><small>${log.record_id || '-'}</small></td>
                            <td>
                                ${log.old_values || log.new_values ? `
                                    <button class="btn btn-xs btn-outline-secondary" onclick="showLogDetails('${btoa(log.old_values || '')}', '${btoa(log.new_values || '')}')">
                                        <i class="fas fa-eye me-1"></i>Ver
                                    </button>
                                ` : '<small class="text-muted">-</small>'}
                            </td>
                        </tr>
                    `;
                });
                
                html += `</tbody></table>`;
                document.getElementById('historyContent').innerHTML = html;
            } else {
                document.getElementById('historyContent').innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhuma ação registrada para este usuário.</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('historyContent').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Erro ao carregar histórico.</div>';
        });
}

// Função para exibir detalhes da alteração
function showLogDetails(oldValuesB64, newValuesB64) {
    const oldValues = atob(oldValuesB64);
    const newValues = atob(newValuesB64);
    
    try {
        const oldObj = JSON.parse(oldValues || '{}');
        const newObj = JSON.parse(newValues || '{}');
        
        document.getElementById('oldValues').textContent = JSON.stringify(oldObj, null, 2);
        document.getElementById('newValues').textContent = JSON.stringify(newObj, null, 2);
    } catch (e) {
        document.getElementById('oldValues').textContent = oldValues || 'N/A';
        document.getElementById('newValues').textContent = newValues || 'N/A';
    }
    
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    detailsModal.show();
}

function editUser(userId, userName) {
    const title = `Editar Usuário: ${userName}`;
    openActionModal(`edit_user.php?id=${userId}&modal=true`, title);
}

function changePassword(userId, userName) {
    const title = `Alterar Senha de: ${userName}`;
    openActionModal(`change_password_user.php?id=${userId}&modal=true`, title);
}
</script>
