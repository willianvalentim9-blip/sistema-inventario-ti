<?php
// ========================================
// PÁGINA DE RECUPAÇÃO DE SENHA (COM ENVIO DE E-MAIL REAL)
// ========================================
require_once '../../config.php';

// Inclui a biblioteca PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carrega os arquivos do PHPMailer manualmente
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// Se o usuário já estiver logado, redireciona
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor, insira um endereço de e-mail válido.';
    } else {
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Gerar token seguro
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // Token expira em 1 hora
                
                // Armazenar o token no banco de dados
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
                $update_stmt->execute([$token, $expires_at, $user['id']]);
                
                // Construir o link de redefinição
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/reset_password.php?token=" . $token;

                // Enviar o e-mail
                $mail = new PHPMailer(true);
                try {
                    // Configurações do servidor (SMTP do Gmail)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'projetomanus19@gmail.com'; // Seu e-mail do Gmail
                    
                    // ==============================================================================
                    // ATENÇÃO AQUI: COLOQUE A SENHA DE 16 LETRAS GERADA PELO GOOGLE NESTA LINHA
                    // ==============================================================================
                    $mail->Password   = 'COLOQUEASENHADEAPLICATIVOAQUI'; // <<< TROQUE ISTO PELA SUA SENHA DE 16 LETRAS
                    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet    = 'UTF-8';

                    // Remetente e Destinatário
                    $mail->setFrom('projetomanus19@gmail.com', 'Sistema de Estoque TI');
                    $mail->addAddress($user['email'], $user['username']);

                    // Conteúdo do E-mail
                    $mail->isHTML(true);
                    $mail->Subject = 'Redefinição de Senha - Sistema de Estoque';
                    $mail->Body    = "Olá, " . htmlspecialchars($user['username']) . "!<br><br>"
                                   . "Recebemos uma solicitação para redefinir sua senha. Se foi você, clique no link abaixo:<br>"
                                   . "<a href='{$reset_link}'>Redefinir Minha Senha</a><br><br>"
                                   . "Se você não solicitou isso, pode ignorar este e-mail.<br><br>"
                                   . "Atenciosamente,<br>Equipe do Sistema de Estoque.";
                    $mail->AltBody = "Olá, " . htmlspecialchars($user['username']) . "!\n\n"
                                   . "Recebemos uma solicitação para redefinir sua senha. Copie e cole o link abaixo em seu navegador:\n"
                                   . $reset_link . "\n\n"
                                   . "Se você não solicitou isso, pode ignorar este e-mail.";
                    
                    $mail->send();
                    $success_message = 'Um e-mail com as instruções para redefinir sua senha foi enviado. Por favor, verifique sua caixa de entrada e spam.';
                } catch (Exception $e) {
                    $error_message = "Não foi possível enviar o e-mail de recuperação. Erro: {$mail->ErrorInfo}";
                    error_log("Erro no PHPMailer: " . $mail->ErrorInfo);
                }
            } else {
                // Mensagem genérica para não revelar se o e-mail existe
                $success_message = 'Se um usuário com este e-mail existir em nosso sistema, um link de redefinição de senha foi enviado.';
            }
        } catch (Exception $e) {
            $error_message = 'Ocorreu um erro no servidor. Tente novamente mais tarde.';
            error_log("Erro em forgot_password.php: " . $e->getMessage());
        }
    }
}

$page_title = 'Esqueci minha Senha';
$hide_sidebar = true;
include 'includes/header.php';
?>

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="w-100" style="max-width: 450px;">
        <div class="card card-custom shadow-lg">
            <div class="card-header card-header-custom text-center">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Recuperar Senha</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php else: ?>
                    <p class="text-muted">Digite seu e-mail e enviaremos um link para você redefinir sua senha.</p>
                    <form method="POST" action="forgot_password.php">
                        <div class="mb-3">
                            <label for="email" class="form-label form-label-custom">
                                <i class="fas fa-envelope me-1"></i>
                                E-mail de Cadastro
                            </label>
                            <input type="email" class="form-control form-control-custom" id="email" name="email" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar Link de Recuperação
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Lembrou sua senha? 
                        <a href="login.php" class="text-decoration-none">Voltar para o Login</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>