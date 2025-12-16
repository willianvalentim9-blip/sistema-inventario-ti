<?php
// ========================================
// PÁGINA DE LOGIN DO SISTEMA (VERSÃO ATUALIZADA COM LOG DE ADMIN)
// ========================================
// Esta página permite que os usuários façam login e registra a atividade em admin_logs

// Inclui o arquivo de configuração
require_once '../../config.php';
require_once 'modules/logs/log_functions.php';

// Se o usuário já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Variáveis para controle de mensagens
$error_message = '';
$success_message = '';

// ========================================
// PROCESSAMENTO DO FORMULÁRIO DE LOGIN
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validação básica
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Conecta ao banco de dados
            $pdo = getConnection();
            
            // Busca o usuário no banco de dados
            $stmt = $pdo->prepare("SELECT id, username, password, email, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            // Verifica se o usuário existe e a senha está correta
            if ($user && $password === $user["password"]) { // Verificação simples de senha
                // Login bem-sucedido - cria a sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Atualiza o último acesso do usuário
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Registra o login no log de administradores
                logAdminActivity(
                    $user['id'],
                    'LOGIN',
                    'users',
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );
                
                // Redireciona para o dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error_message = 'Erro de conexão com o banco de dados.';
            error_log("Erro de login: " . $e->getMessage());
        }
    }
}

// Define variáveis para o template
$page_title = 'Login';
$hide_sidebar = true; // Não exibe sidebar na página de login
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 bg-primary-custom d-none d-lg-flex align-items-center justify-content-center">
            <div class="text-center text-white">
                <div class="mb-4">
                    <i class="fas fa-warehouse fa-5x mb-3"></i>
                    <h1 class="display-4 fw-bold"><?php echo SITE_NAME; ?></h1>
                    <h2 class="display-6 fw-bold text-white-50"><?php echo COMPANY_NAME; ?></h2>
                    <?php if (!empty(COMPANY_SLOGAN)): ?>
                        <p class="lead"><?php echo htmlspecialchars(COMPANY_SLOGAN); ?></p>
                    <?php else: ?>
                        <p class="lead">Sistema completo de controle de estoque de TI</p>
                    <?php endif; ?>
                </div>
                
                <div class="row text-center">
                    <div class="col-4">
                        <i class="fas fa-boxes fa-2x mb-2"></i>
                        <h5>Produtos</h5>
                        <p class="small">Controle completo de hardware e peças</p>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-qrcode fa-2x mb-2"></i>
                        <h5>QR Code</h5>
                        <p class="small">Leitura de códigos QR e barras</p>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-desktop fa-2x mb-2"></i>
                        <h5>Máquinas</h5>
                        <p class="small">Gestão de computadores prontos</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="card card-custom shadow-lg">
                    <div class="card-header card-header-custom text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Fazer Login
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-custom" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-custom" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="login-form">
                            <div class="mb-3">
                                <label for="username" class="form-label form-label-custom">
                                    <i class="fas fa-user me-1"></i>
                                    Usuário ou Email
                                </label>
                                <input type="text" 
                                       class="form-control form-control-custom" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Digite seu usuário ou email"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label form-label-custom">
                                    <i class="fas fa-lock me-1"></i>
                                    Senha
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-custom" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Digite sua senha"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="toggle-password"
                                            data-bs-toggle="tooltip" 
                                            title="Mostrar/Ocultar senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember_me" 
                                       name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Lembrar-me neste dispositivo
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-custom btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Entrar
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Esqueceu sua senha? 
                                <a href="forgot_password.php" class="text-decoration-none">
                                    Clique aqui
                                </a>
                            </small>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Usuários de Demonstração:
                            </h6>
                            <small class="text-muted">
                                <strong>Admin:</strong> admin / admin123<br>
                                <strong>Usuário:</strong> user / user123
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <?php echo SITE_NAME; ?> v1.0 - 
                        Sistema de Controle de Estoque de TI
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // TOGGLE DE VISUALIZAÇÃO DA SENHA
    // ========================================
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Alterna o ícone
            const icon = this.querySelector('i');
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    }
    
    // ========================================
    // VALIDAÇÃO DO FORMULÁRIO
    // ========================================
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            // Validação básica
            if (!username || !password) {
                e.preventDefault();
                showAlert('Por favor, preencha todos os campos.', 'warning');
                return false;
            }
            
            // Validação de comprimento mínimo
            if (password.length < 6) {
                e.preventDefault();
                showAlert('A senha deve ter pelo menos 6 caracteres.', 'warning');
                return false;
            }
            
            // Mostra loading no botão
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
            submitBtn.disabled = true;
            
            // Restaura o botão após 5 segundos (caso haja erro)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
    
    // ========================================
    // FOCO AUTOMÁTICO NO PRIMEIRO CAMPO
    // ========================================
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.focus();
    }
    
    // ========================================
    // ENTER PARA SUBMETER FORMULÁRIO
    // ========================================
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && loginForm) {
            loginForm.submit();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>