<?php
// ========================================
// PÁGINA DE GERENCIAMENTO DE ITENS DELETADOS (SOFT DELETE RECOVERY)
// ========================================
// Admins: Vê tudo
// Administrativos: Vê apenas Armazém e Garantias

require_once '../../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    header('Location: ../../public/dashboard.php');
    exit;
}

$page_title = "Itens Deletados";

try {
    $pdo = getConnection();

    // Busca produtos deletados
    $stmt = $pdo->prepare("
        SELECT id, name, category, quantity, deleted_at
        FROM products
        WHERE is_deleted = TRUE
        ORDER BY deleted_at DESC
    ");
    $stmt->execute();
    $deleted_products = $stmt->fetchAll();

    // Busca máquinas deletadas
    $stmt = $pdo->prepare("
        SELECT id, name, status, deleted_at
        FROM ready_machines
        WHERE is_deleted = TRUE
        ORDER BY deleted_at DESC
    ");
    $stmt->execute();
    $deleted_machines = $stmt->fetchAll();

    // Busca usuários deletados
    $deleted_users = [];
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin')) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, email, role, deleted_at
                FROM users
                WHERE is_deleted = TRUE
                ORDER BY deleted_at DESC
            ");
            $stmt->execute();
            $deleted_users = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Aviso: não foi possível buscar usuários deletados: " . $e->getMessage());
            $deleted_users = [];
        }
    }

    // Busca itens do armazém deletados (apenas para administrativos)
    $deleted_warehouse = [];
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')) {
        $stmt = $pdo->prepare("
            SELECT id, name, category, quantity, deleted_at
            FROM warehouse
            WHERE is_deleted = TRUE
            ORDER BY deleted_at DESC
        ");
        $stmt->execute();
        $deleted_warehouse = $stmt->fetchAll();
    }

    // Busca fornecedores de garantia deletados (apenas para administrativos)
    $deleted_warranty_suppliers = [];
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, name, cnpj, email, phone, deleted_at
                FROM warranty_suppliers
                WHERE is_deleted = TRUE
                ORDER BY deleted_at DESC
            ");
            $stmt->execute();
            $deleted_warranty_suppliers = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Aviso: não foi possível buscar fornecedores deletados: " . $e->getMessage());
            $deleted_warranty_suppliers = [];
        }
    }

    // Busca templates de garantia deletados (apenas para administrativos)
    $deleted_warranty_templates = [];
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, name, description, warranty_provider, period_value, period_unit, deleted_at
                FROM warranty_templates
                WHERE is_deleted = TRUE
                ORDER BY deleted_at DESC
            ");
            $stmt->execute();
            $deleted_warranty_templates = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Aviso: não foi possível buscar templates deletados: " . $e->getMessage());
            $deleted_warranty_templates = [];
        }
    }

    // Busca garantias deletadas (apenas para administrativos)
    $deleted_warranties = [];
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')) {
        try {
            // Verificar se a tabela warranties existe
            $tables = $pdo->query("SHOW TABLES LIKE 'warranties'")->fetch();

            if ($tables) {
                // Garantias de produtos
                $stmt = $pdo->prepare("
                    SELECT
                        w.id,
                        w.product_id as item_id,
                        p.name as item_name,
                        'Produto' as item_type,
                        w.warranty_provider,
                        w.warranty_end_date,
                        w.deleted_at
                    FROM warranties w
                    LEFT JOIN products p ON w.product_id = p.id
                    WHERE w.is_deleted = TRUE AND w.product_id IS NOT NULL
                    ORDER BY w.deleted_at DESC
                ");
                $stmt->execute();
                $product_warranties = $stmt->fetchAll();

                // Garantias de máquinas
                $stmt = $pdo->prepare("
                    SELECT
                        w.id,
                        w.machine_id as item_id,
                        m.name as item_name,
                        'Máquina' as item_type,
                        w.warranty_provider,
                        w.warranty_end_date,
                        w.deleted_at
                    FROM warranties w
                    LEFT JOIN ready_machines m ON w.machine_id = m.id
                    WHERE w.is_deleted = TRUE AND w.machine_id IS NOT NULL
                    ORDER BY w.deleted_at DESC
                ");
                $stmt->execute();
                $machine_warranties = $stmt->fetchAll();

                $deleted_warranties = array_merge($product_warranties, $machine_warranties);
            }
        } catch (PDOException $e) {
            // Tabela warranties não existe ou erro na query
            error_log("Aviso: não foi possível buscar garantias deletadas: " . $e->getMessage());
            $deleted_warranties = [];
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar itens deletados: " . $e->getMessage());
    $deleted_products = [];
    $deleted_machines = [];
    $deleted_warehouse = [];
    $deleted_warranties = [];
    $deleted_warranty_suppliers = [];
    $deleted_warranty_templates = [];
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 d-inline-flex align-items-center">
                <i class="fas fa-trash-restore me-2 text-warning"></i>
                Itens Deletados (Recuperáveis)
            </h1>
            <p class="text-muted mt-2">
                Aqui você pode visualizar e restaurar itens que foram deletados.
            </p>
            
            <!-- Info sobre permissões -->
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Acesso Administrativo:</strong> Você pode visualizar TODOS os itens deletados (Produtos, Máquinas, Usuários, Armazém, Garantias).
                </div>
            <?php elseif ($_SESSION['user_role'] === 'administrativo'): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Acesso Limitado:</strong> Você pode visualizar apenas itens do Armazém e Garantias.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produtos Deletados (Apenas Admins) -->
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-box text-danger me-2"></i>
                        Produtos Deletados
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_products); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($deleted_products)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum produto deletado encontrado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Categoria</th>
                                        <th>Quantidade</th>
                                        <th>Deletado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_products as $product): ?>
                                        <tr>
                                            <td>#<?php echo $product['id']; ?></td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $product['quantity']; ?> un
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($product['deleted_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="restoreProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                                        title="Restaurar este produto">
                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Máquinas Deletadas (Apenas Admins) -->
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-desktop text-danger me-2"></i>
                        Máquinas Deletadas
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_machines); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($deleted_machines)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma máquina deletada encontrada.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Status</th>
                                        <th>Deletada em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_machines as $machine): ?>
                                        <tr>
                                            <td>#<?php echo $machine['id']; ?></td>
                                            <td><?php echo htmlspecialchars($machine['name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($machine['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($machine['deleted_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success"
                                                        onclick="restoreMachine(<?php echo $machine['id']; ?>, '<?php echo htmlspecialchars($machine['name']); ?>')"
                                                        title="Restaurar esta máquina">
                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Usuários Deletados (Apenas Admins) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-user-slash text-danger me-2"></i>
                        Usuários Deletados
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_users); ?></span>
                        <span class="badge bg-warning text-dark ms-2">Apenas Admins</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($deleted_users)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum usuário deletado encontrado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Deletado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_users as $user): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary">#<?php echo $user['id']; ?></span></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    $badge_class = 'bg-info';
                                                    if ($user['role'] === 'admin') {
                                                        $badge_class = 'bg-warning text-dark';
                                                    } elseif ($user['role'] === 'administrativo') {
                                                        $badge_class = 'bg-success text-white';
                                                    }
                                                    echo $badge_class;
                                                ?>">
                                                    <?php 
                                                    $role_labels = ['admin' => 'Administrador', 'administrativo' => 'Administrativo', 'user' => 'Usuário'];
                                                    echo $role_labels[$user['role']] ?? $user['role'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($user['deleted_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success"
                                                        onclick="restoreUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                        title="Restaurar este usuário">
                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'administrativo' || $_SESSION['user_role'] === 'admin')): ?>
    <!-- Itens do Armazém Deletados (Apenas Administrativos) -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes-stacked text-danger me-2"></i>
                        Armazém - Itens Deletados
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_warehouse); ?></span>
                        <span class="badge bg-warning text-dark ms-2">Apenas Administrativos</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($deleted_warehouse)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum item do armazém deletado encontrado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Categoria</th>
                                        <th>Quantidade</th>
                                        <th>Deletado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_warehouse as $item): ?>
                                        <tr>
                                            <td>#<?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $item['quantity']; ?> un
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($item['deleted_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success"
                                                        onclick="restoreWarehouse(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                                        title="Restaurar este item">
                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Garantias Deletadas (Apenas Administrativos) -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt text-danger me-2"></i>
                        Garantias Deletadas
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_warranties); ?></span>
                        <span class="badge bg-warning text-dark ms-2">Apenas Administrativos</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($deleted_warranties)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma garantia deletada encontrada.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Item</th>
                                        <th>Fornecedor</th>
                                        <th>Validade</th>
                                        <th>Deletada em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_warranties as $warranty): ?>
                                        <tr>
                                            <td>#<?php echo $warranty['id']; ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($warranty['item_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($warranty['item_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($warranty['warranty_provider'] ?? 'N/A'); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $warranty['warranty_end_date'] ? date('d/m/Y', strtotime($warranty['warranty_end_date'])) : 'N/A'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($warranty['deleted_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success"
                                                        onclick="restoreWarranty(<?php echo $warranty['id']; ?>, '<?php echo htmlspecialchars($warranty['item_name'] ?? 'Garantia'); ?>')"
                                                        title="Restaurar esta garantia">
                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Fornecedores de Garantia Deletados -->
    <?php if (!empty($deleted_warranty_suppliers)): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-building text-danger me-2"></i>
                        Fornecedores de Garantia Deletados
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_warranty_suppliers); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>CNPJ</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Deletado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deleted_warranty_suppliers as $supplier): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?php echo $supplier['id']; ?></span></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($supplier['cnpj'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($supplier['deleted_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success"
                                                    onclick="restoreWarrantySupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['name']); ?>')"
                                                    title="Restaurar este fornecedor">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Templates de Garantia Deletados -->
    <?php if (!empty($deleted_warranty_templates)): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice text-danger me-2"></i>
                        Templates de Garantia Deletados
                        <span class="badge bg-danger ms-2"><?php echo count($deleted_warranty_templates); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Fornecedor</th>
                                    <th>Período</th>
                                    <th>Deletado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deleted_warranty_templates as $template): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?php echo $template['id']; ?></span></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($template['description'] ?? 'N/A', 0, 50)); ?>
                                                <?php echo strlen($template['description'] ?? '') > 50 ? '...' : ''; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($template['warranty_provider'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php
                                                $unit_labels = ['days' => 'dias', 'months' => 'meses', 'years' => 'anos'];
                                                $unit = $template['period_unit'] ?? 'months';
                                                echo $template['period_value'] . ' ' . ($unit_labels[$unit] ?? $unit);
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($template['deleted_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success"
                                                    onclick="restoreWarrantyTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')"
                                                    title="Restaurar este template">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function restoreProduct(id, name) {
    if (confirm(`Deseja restaurar o produto "${name}"?\n\nEle voltará a aparecer nos relatórios de estoque.`)) {
        fetch('../../api/restore_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Produto restaurado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar produto', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar produto', 'danger');
        });
    }
}

function restoreMachine(id, name) {
    if (confirm(`Deseja restaurar a máquina "${name}"?\n\nEla voltará a aparecer nos relatórios.`)) {
        fetch('../../api/restore_machine.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Máquina restaurada com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar máquina', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar máquina', 'danger');
        });
    }
}

function restoreWarehouse(id, name) {
    if (confirm(`Deseja restaurar o item do armazém "${name}"?\n\nEle voltará a aparecer nos relatórios do armazém.`)) {
        fetch('../../api/restore_warehouse.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Item do armazém restaurado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar item do armazém', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar item do armazém', 'danger');
        });
    }
}

function restoreWarranty(id, name) {
    if (confirm(`Deseja restaurar a garantia de "${name}"?\n\nEla voltará a aparecer nos relatórios de garantias.`)) {
        fetch('../../api/restore_warranty.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Garantia restaurada com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar garantia', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar garantia', 'danger');
        });
    }
}

function restoreUser(id, name) {
    if (confirm(`Deseja restaurar o usuário "${name}"?\n\nEle voltará a ter acesso ao sistema.`)) {
        fetch('../../api/restore_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Usuário restaurado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar usuário', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar usuário', 'danger');
        });
    }
}

function restoreWarrantySupplier(id, name) {
    if (confirm(`Deseja restaurar o fornecedor "${name}"?\n\nEle voltará a aparecer na lista de fornecedores de garantia.`)) {
        fetch('../../api/restore_warranty_supplier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Fornecedor restaurado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar fornecedor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar fornecedor', 'danger');
        });
    }
}

function restoreWarrantyTemplate(id, name) {
    if (confirm(`Deseja restaurar o template "${name}"?\n\nEle voltará a aparecer na lista de templates de garantia.`)) {
        fetch('../../api/restore_warranty_template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Template restaurado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao restaurar template', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao restaurar template', 'danger');
        });
    }
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<?php
require_once '../../includes/footer.php';
?>
