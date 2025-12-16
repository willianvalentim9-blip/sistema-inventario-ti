<?php
// ========================================
// CENTRAL DE DIAGNÓSTICOS DO SISTEMA
// ========================================
session_start();

// Carregar configurações se existir
$config_path = __DIR__ . '/../../config.php';
$has_config = file_exists($config_path);
if ($has_config) {
    require_once $config_path;
}

// Verificar se usuário é admin (se config existe)
$is_admin = false;
if ($has_config && isLoggedIn()) {
    $is_admin = isAdmin();
}

$page_title = "Central de Diagnósticos";

// Lista de diagnósticos disponíveis
$diagnosticos = [
    'sistema' => [
        'title' => 'Diagnóstico do Sistema',
        'icon' => 'fa-server',
        'file' => 'sistema.php',
        'description' => 'Verifica configurações gerais do servidor (PHP, extensões, etc)',
        'color' => 'primary'
    ],
    'barcode' => [
        'title' => 'Código de Barras',
        'icon' => 'fa-barcode',
        'file' => 'barcode.php',
        'description' => 'Testa geração de códigos de barras e QR codes',
        'color' => 'info'
    ],
    'database' => [
        'title' => 'Banco de Dados',
        'icon' => 'fa-database',
        'file' => 'database.php',
        'description' => 'Verifica conexão e estrutura do banco de dados',
        'color' => 'success'
    ],
    'soft_delete' => [
        'title' => 'Soft Delete',
        'icon' => 'fa-trash-restore',
        'file' => 'soft_delete.php',
        'description' => 'Verifica implementação de soft delete (produtos/máquinas)',
        'color' => 'warning'
    ],
    'warranty' => [
        'title' => 'Sistema de Garantias',
        'icon' => 'fa-shield-alt',
        'file' => 'warranty.php',
        'description' => 'Verifica estrutura das tabelas de garantia',
        'color' => 'secondary'
    ],
    'session' => [
        'title' => 'Sessões',
        'icon' => 'fa-user-lock',
        'file' => 'session.php',
        'description' => 'Testa configurações de sessão e autenticação',
        'color' => 'danger'
    ],
    'uploads' => [
        'title' => 'Upload de Arquivos',
        'icon' => 'fa-upload',
        'file' => 'uploads.php',
        'description' => 'Verifica permissões e validação MIME de uploads',
        'color' => 'dark'
    ],
];

// Pegar diagnóstico solicitado
$current = $_GET['test'] ?? '';
$current_test = isset($diagnosticos[$current]) ? $diagnosticos[$current] : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Estoque TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-radius: 15px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .diagnostic-card {
            transition: all 0.3s;
            cursor: pointer;
            border-radius: 10px;
        }
        .diagnostic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .diagnostic-result {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .back-link {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }
        .back-link:hover {
            color: #f0f0f0;
        }
        .badge-custom {
            font-size: 0.9rem;
            padding: 8px 15px;
        }
        iframe {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$current_test): ?>
            <!-- MENU PRINCIPAL DE DIAGNÓSTICOS -->
            <div class="card">
                <div class="card-header text-center">
                    <h1 class="mb-0">
                        <i class="fas fa-stethoscope me-2"></i>
                        Central de Diagnósticos
                    </h1>
                    <p class="mb-0 mt-2">Sistema de Estoque TI - Ferramentas de Verificação</p>
                </div>
                <div class="card-body p-4">

                    <?php if ($has_config && $is_admin): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-user-shield me-2"></i>
                            <strong>Acesso Administrativo Detectado</strong> - Você tem acesso completo a todos os diagnósticos.
                        </div>
                    <?php elseif ($has_config && !$is_admin): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Acesso Limitado</strong> - Faça login como administrador para acesso completo.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Modo Standalone</strong> - Configure o sistema para acesso administrativo completo.
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <?php foreach ($diagnosticos as $key => $diag): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card diagnostic-card border-<?php echo $diag['color']; ?>" onclick="location.href='?test=<?php echo $key; ?>'">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas <?php echo $diag['icon']; ?> fa-3x text-<?php echo $diag['color']; ?>"></i>
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($diag['title']); ?></h5>
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars($diag['description']); ?>
                                        </p>
                                        <span class="badge badge-custom bg-<?php echo $diag['color']; ?>">
                                            Executar Teste
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="../../settings.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Voltar às Configurações
                        </a>
                        <a href="?test=all" class="btn btn-primary">
                            <i class="fas fa-tasks me-2"></i>Executar Todos os Testes
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- RESULTADO DO DIAGNÓSTICO -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">
                            <i class="fas <?php echo $current_test['icon']; ?> me-2"></i>
                            <?php echo htmlspecialchars($current_test['title']); ?>
                        </h2>
                        <a href="?" class="back-link">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="badge bg-<?php echo $current_test['color']; ?>">
                            <?php echo htmlspecialchars($current_test['description']); ?>
                        </span>
                    </div>

                    <div class="diagnostic-result">
                        <?php
                        $test_file = __DIR__ . '/' . $current_test['file'];
                        if (file_exists($test_file)) {
                            include $test_file;
                        } else {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                            echo '<strong>Erro:</strong> Arquivo de teste não encontrado: ' . htmlspecialchars($current_test['file']);
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="?" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar ao Menu
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-secondary">
                            <i class="fas fa-sync me-2"></i>Executar Novamente
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <div class="text-center mt-4">
            <p class="text-white">
                <i class="fas fa-tools me-2"></i>
                Sistema de Diagnósticos v1.0 |
                <small>Desenvolvido por Claude Code</small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
