<?php
// ========================================
// ARQUIVO DE CABEÇALHO HTML (VERSÃO COMPLETA E CORRIGIDA)
// ========================================
require_once __DIR__ . '/../config.php';

// Lógica para carregar o tema do usuário ou o padrão do sistema
$current_theme = $system_settings["theme_color"] ?? "light";
if (isLoggedIn()) {
    $user_id = $_SESSION["user_id"];
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute(["user_theme_{$user_id}"]);
        $user_theme_preference = $stmt->fetchColumn();
        if ($user_theme_preference) {
            $current_theme = $user_theme_preference;
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar preferência de tema do usuário: " . $e->getMessage());
    }
}
$logo_path = $system_settings['company_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="/sistema5/css/bootstrap.min.css" rel="stylesheet">
    <link href="/sistema5/css/themes.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/sistema5/css/custom.css" rel="stylesheet">
    <link href="/sistema5/css/custom-improved.css" rel="stylesheet">
    <link href="/sistema5/css/media-upload.css" rel="stylesheet">
    <link href="/sistema5/css/sidebar-animations.css" rel="stylesheet">
</head>
<body class="theme-<?php echo htmlspecialchars($current_theme); ?>">

<div class="wrapper">
    <?php if (isLoggedIn() && !isset($hide_sidebar)): ?>
        <nav id="sidebar" class="sidebar-custom d-flex flex-column">
            <div class="sidebar-header">
                <div onclick="toggleSidebar()" class="sidebar-brand-toggle d-flex flex-column justify-content-center align-items-center">
                    <div>
                        <i class="fas fa-warehouse fa-2x text-white-30"></i>
                    </div>
                </div>
            </div>
            <hr class="text-white-50 mx-2 mt-0">
            
            <ul class="nav nav-pills flex-column mb-auto px-2">
                <li class="nav-item">
                    <a href="<?php echo url('dashboard.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt fa-fw animate-float"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('modules/products/products.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes fa-fw animate-pulse"></i>
                        <span class="sidebar-text">Produtos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('ready_machines.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'ready_machines.php' ? 'active' : ''; ?>">
                        <i class="fas fa-desktop fa-fw animate-bounce"></i>
                        <span class="sidebar-text">Máquinas</span>
                    </a>
                </li>

                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')): ?>
                <li>
                    <a href="<?php echo url('modules/warehouse/warehouse.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'warehouse.php' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes-stacked fa-fw animate-float"></i>
                        <span class="sidebar-text">Armazém</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="<?php echo url('modules/warranties/warranties.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'warranties.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt fa-fw animate-swing"></i>
                        <span class="sidebar-text">Garantias</span>
                    </a>
                </li>
                
                <hr class="sidebar-divider my-2">

                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="#movementationSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" ? "true" : "false"; ?>">
                        <i class="fas fa-exchange-alt fa-fw"></i>
                        <span class="sidebar-text">Movimentação</span>
                        <i class="fas fa-chevron-down ms-auto small"></i>
                    </a>
                    <ul class="collapse <?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" ? "show" : ""; ?>" id="movementationSubmenu">
                        <li class="nav-item">
                            <a href="<?php echo url('modules/movements/movementations.php?type=entrada'); ?>" class="nav-link sidebar-link ps-5 <?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" && ($_GET['type'] ?? '') === 'entrada' ? "active" : ""; ?>">
                                <i class="fas fa-arrow-circle-down fa-fw text-success"></i>
                                <span class="sidebar-text">Entradas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo url('modules/movements/movementations.php?type=saida'); ?>" class="nav-link sidebar-link ps-5 <?php echo basename($_SERVER["PHP_SELF"]) == "movementations.php" && ($_GET['type'] ?? '') === 'saida' ? "active" : ""; ?>">
                                <i class="fas fa-arrow-circle-down fa-fw text-danger"></i>
                                <span class="sidebar-text">Saídas</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <hr class="sidebar-divider my-2">

                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="<?php echo url('deleted_items.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'deleted_items.php' ? 'active' : ''; ?>">
                            <i class="fas fa-trash-restore fa-fw animate-blink"></i>
                            <span class="sidebar-text">Itens Deletados</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <hr class="sidebar-divider my-2">
                    <li><a href="<?php echo url('users.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users fa-fw animate-shake"></i><span class="sidebar-text">Usuários</span></a></li>
                    <li><a href="<?php echo url('settings.php'); ?>" class="nav-link sidebar-link <?php echo basename($_SERVER["PHP_SELF"]) == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cogs fa-fw animate-spin"></i><span class="sidebar-text">Configurações</span></a></li>
                <?php endif; ?>
            </ul>
             <hr class="text-white-50 mx-2">
             <div class="px-2 pb-3">
                 <a href="<?php echo url('logout.php'); ?>" class="nav-link sidebar-link"><i class="fas fa-sign-out-alt fa-fw"></i><span class="sidebar-text">Sair</span></a>
            </div>
        </nav>
    <?php endif; ?>
        
    <div id="main-content-wrapper" class="d-flex flex-column flex-grow-1">
        <?php if (isLoggedIn() && !isset($hide_sidebar)): ?>
        <header class="d-flex align-items-center py-2 px-3 border-bottom bg-light no-print">
            
            <ul class="navbar-nav d-flex flex-row align-items-center w-100">

                <li class="nav-item d-lg-none me-2">
                    <button class="btn btn-light" type="button" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                </li>

                <li class="nav-item">
                    <a href="<?php echo url('dashboard.php'); ?>" class="navbar-brand-logo">
                        <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                            <img src="<?php echo htmlspecialchars($logo_path); ?>?v=<?php echo time(); ?>" alt="Logo da Empresa">
                        <?php endif; ?>
                    </a>
                </li>
        
                <li class="nav-item ms-auto">
                    <button id="theme-toggle" class="btn btn-sm btn-outline-secondary" title="Alternar Tema">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>

                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle text-dark d-flex align-items-center p-1" href="#" role="button" data-bs-toggle="dropdown">
                        <?php
                        $user_avatar = $_SESSION['user_avatar'] ?? '';
                        $avatar_path = __ROOT__ . '/uploads/avatars/' . $user_avatar;
                        if (!empty($user_avatar) && file_exists($avatar_path)) {
                            echo '<img src="/sistema5/uploads/avatars/' . htmlspecialchars($user_avatar) . '?v=' . time() . '" alt="Avatar" class="rounded-circle me-2" style="width: 55px; height: 55px; object-fit: cover;">';
                        } else {
                            echo '<i class="fas fa-user-circle avatar-icon me-2 fs-4"></i>';
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

        </header>
        <?php endif; ?>
        
        <main class="p-3 p-md-4 flex-grow-1">
            <div id="alert-container"></div>