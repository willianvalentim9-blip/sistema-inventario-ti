<?php
// ========================================
// ARQUIVO DE CABEÇALHO HTML (VERSÃO CORRIGIDA E MELHORADA)
// ========================================
require_once 'config.php';

// Carrega as configurações do sistema do banco de dados
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $system_settings = array_merge([
        'system_name' => 'Sistema de Estoque TI',
        'company_name' => 'Sua Empresa',
        'company_slogan' => 'Controle de estoque',
        'company_logo' => ''
    ], $db_settings);

    // Busca o tema do usuário logado
    $current_theme = 'blue'; // Tema padrão
    if (isLoggedIn()) {
        $stmt_user = $pdo->prepare("SELECT theme, avatar FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch();
        if ($user_data && !empty($user_data['theme'])) {
            $current_theme = $user_data['theme'];
        }
        $user_avatar = $user_data['avatar'] ?? '';
    }

} catch (PDOException $e) {
    $system_settings = []; // Fallback
    $current_theme = 'blue';
    $user_avatar = '';
}

$logo_path = $system_settings['company_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . ($system_settings['system_name'] ?? 'Sistema') : ($system_settings['system_name'] ?? 'Sistema'); ?></title>
    <link rel="icon" type="image/x-icon" href="/sistema5/assets/images/favicon.ico">
    <link href="/sistema5/css/bootstrap.min.css" rel="stylesheet">
    <link href="/sistema5/css/themes.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/sistema5/css/custom-improved.css" rel="stylesheet">
</head>
<body class="theme-<?php echo htmlspecialchars($current_theme); ?>">

<div class="wrapper">
    <?php if (isLoggedIn() && !isset($hide_sidebar)): ?>
        <nav id="sidebar" class="sidebar-custom d-flex flex-column">
            <div class="sidebar-header">
                <div id="sidebar-brand-toggle" class="sidebar-brand-toggle">
                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo da Empresa" class="sidebar-logo">
                    <?php else: ?>
                        <i class="fas fa-warehouse fa-fw fs-4"></i>
                    <?php endif; ?>
                    <div class="sidebar-text ms-2">
                        <span class="sidebar-title"><?php echo htmlspecialchars($system_settings['system_name'] ?? 'Sistema'); ?></span>
                        <?php if (!empty($system_settings['company_slogan'])): ?>
                            <span class="sidebar-slogan"><?php echo htmlspecialchars($system_settings['company_slogan']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr class="text-white-50 mx-2 mt-0">

            <ul class="nav nav-pills flex-column mb-auto px-2">
                <li class="nav-item">
                    <a href="<?php echo url('dashboard.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                        <i class="fas fa-tachometer-alt fa-fw"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('modules/products/products.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Produtos">
                        <i class="fas fa-boxes fa-fw"></i>
                        <span class="sidebar-text">Produtos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('modules/machines/ready_machines.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'ready_machines.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Máquinas">
                        <i class="fas fa-desktop fa-fw"></i>
                        <span class="sidebar-text">Máquinas</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('modules/warehouse/warehouse.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'warehouse.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Armazém">
                        <i class="fas fa-warehouse fa-fw"></i>
                        <span class="sidebar-text">Armazém</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('modules/warranties/warranties.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'warranties.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Garantias">
                        <i class="fas fa-shield-alt fa-fw"></i>
                        <span class="sidebar-text">Garantias</span>
                    </a>
                </li>
                
                <hr class="sidebar-divider my-2">
                
                <li class="nav-item">
                    <a href="<?php echo url('modules/movements/movementations.php?type=entrada'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" && ($_GET['type'] ?? '') === 'entrada' ? "active" : ""; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Entradas">
                        <i class="fas fa-arrow-circle-down fa-fw text-success"></i>
                        <span class="sidebar-text">Entradas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo url('modules/movements/movementations.php?type=saida'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" && ($_GET['type'] ?? '') === 'saida' ? "active" : ""; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Saídas">
                        <i class="fas fa-arrow-circle-up fa-fw text-danger"></i>
                        <span class="sidebar-text">Saídas</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('deleted_items.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'deleted_items.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Itens Deletados">
                        <i class="fas fa-trash fa-fw"></i>
                        <span class="sidebar-text">Itens Deletados</span>
                    </a>
                </li>
                
                <?php if (isAdmin()): ?>
                    <hr class="sidebar-divider my-2">
                    <li>
                        <a href="<?php echo url('modules/users/users.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Usuários">
                            <i class="fas fa-users fa-fw"></i>
                            <span class="sidebar-text">Usuários</span>
                        </a>
                    </li>
                    <?php $is_log_page_active = (basename($_SERVER['PHP_SELF']) == 'admin_logs.php' || strpos($_SERVER['PHP_SELF'], 'modules/logs/') !== false); ?>
                    <li class="nav-item">
                        <a href="#logs-collapse" data-bs-toggle="collapse" aria-expanded="<?php echo $is_log_page_active ? 'true' : 'false'; ?>" class="nav-link sidebar-link <?php echo !$is_log_page_active ? 'collapsed' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Logs">
                            <i class="fas fa-history fa-fw"></i>
                            <span class="sidebar-text">Logs</span>
                        </a>
                        <div class="collapse <?php echo $is_log_page_active ? 'show' : ''; ?>" id="logs-collapse">
                            <ul class="nav flex-column ms-3">
                                <li>
                                    <a href="<?php echo url('modules/logs/admin_logs.php'); ?>" class="nav-link sidebar-link ps-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                                        <i class="fas fa-shield-alt fa-fw"></i>
                                        <span class="sidebar-text">Sistema</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="<?php echo url('settings.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER["PHP_SELF"]) == 'settings.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Configurações">
                            <i class="fas fa-cogs fa-fw"></i>
                            <span class="sidebar-text">Configurações</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <hr class="text-white-50 mx-2">
            
            <div class="px-2 pb-3">
                 <a href="<?php echo url('logout.php'); ?>" class="nav-link sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Sair">
                    <i class="fas fa-sign-out-alt fa-fw"></i>
                    <span class="sidebar-text">Sair</span>
                </a>
            </div>
        </nav>
    <?php endif; ?>
        
    <div id="main-content-wrapper" class="d-flex flex-column flex-grow-1">
        <?php if (isLoggedIn() && !isset($hide_sidebar)): ?>
        <header class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom bg-light no-print">
            <button id="mobile-sidebar-toggle" class="btn btn-light d-lg-none" type="button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="ms-auto">
                 <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php
                            $avatar_path = __ROOT__ . '/uploads/avatars/' . $user_avatar;
                            $avatar_exists = !empty($user_avatar) && file_exists($avatar_path);
                            if ($avatar_exists) {
                                echo '<img src="' . htmlspecialchars(__ROOT__ . '/uploads/avatars/' . $user_avatar) . '?v=' . time() . '" alt="Avatar" class="rounded-circle me-2" style="width: 24px; height: 24px; object-fit: cover;">';
                            } else {
                                echo '<i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>';
                            }
                            ?>
                            <?php echo htmlspecialchars($_SESSION["username"] ?? "Usuário"); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo url('modules/users/profile.php'); ?>"><i class="fas fa-user-edit me-2"></i>Meu Perfil</a></li>
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?php echo url('settings.php'); ?>"><i class="fas fa-cogs me-2"></i>Configurações</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </header>
        <?php endif; ?>
        
        <main class="p-3 p-md-4 flex-grow-1">
            <div id="alert-container"></div>