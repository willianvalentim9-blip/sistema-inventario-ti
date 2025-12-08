<?php
require_once 'config.php';
requireLogin();

$page_title = 'Gerenciar Fornecedores de Garantia';
$pdo = getConnection();

// =====================================================
// PROCESSAR AÇÕES POST
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validação básica
    $name = trim($_POST['name'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address_street = trim($_POST['address_street'] ?? '');
    $address_number = trim($_POST['address_number'] ?? '');
    $address_complement = trim($_POST['address_complement'] ?? '');
    $address_neighborhood = trim($_POST['address_neighborhood'] ?? '');
    $address_city = trim($_POST['address_city'] ?? '');
    $address_state = trim($_POST['address_state'] ?? '');
    $address_postal_code = trim($_POST['address_postal_code'] ?? '');
    $warranty_policy = trim($_POST['warranty_policy'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // ===== CRIAR NOVO FORNECEDOR =====
    if ($action === 'create_supplier') {
        if (empty($name)) {
            $_SESSION['flash_message'] = '✗ O nome do fornecedor é obrigatório';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar se já existe com o mesmo CNPJ
                if (!empty($cnpj)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM warranty_suppliers WHERE cnpj = ? AND cnpj != ''");
                    $stmt_check->execute([$cnpj]);
                    if ($stmt_check->fetch()) {
                        $_SESSION['flash_message'] = '✗ Já existe um fornecedor com este CNPJ';
                        $_SESSION['flash_type'] = 'warning';
                        header('Location: warranty_suppliers.php');
                        exit;
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO warranty_suppliers (
                        name, cnpj, contact_person, email, phone, mobile, website,
                        address_street, address_number, address_complement, address_neighborhood,
                        address_city, address_state, address_postal_code,
                        warranty_policy, payment_terms, notes, is_active, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                
                $stmt->execute([
                    $name, $cnpj, $contact_person, $email, $phone, $mobile, $website,
                    $address_street, $address_number, $address_complement, $address_neighborhood,
                    $address_city, $address_state, $address_postal_code,
                    $warranty_policy, $payment_terms, $notes
                ]);
                
                $_SESSION['flash_message'] = '✓ Fornecedor criado com sucesso!';
                $_SESSION['flash_type'] = 'success';
                logAdminActivity($_SESSION['user_id'], 'CREATE_WARRANTY_SUPPLIER', 'warranty_suppliers', null, null, ['name' => $name]);
                
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao criar fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
                error_log("Erro ao criar fornecedor: " . $e->getMessage());
            }
        }
        header('Location: warranty_suppliers.php');
        exit;
    }

    // ===== ATUALIZAR FORNECEDOR =====
    if ($action === 'update_supplier') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        if ($supplier_id <= 0 || empty($name)) {
            $_SESSION['flash_message'] = '✗ Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar CNPJ duplicado (exceto este fornecedor)
                if (!empty($cnpj)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM warranty_suppliers WHERE cnpj = ? AND id != ? AND cnpj != ''");
                    $stmt_check->execute([$cnpj, $supplier_id]);
                    if ($stmt_check->fetch()) {
                        $_SESSION['flash_message'] = '✗ Já existe outro fornecedor com este CNPJ';
                        $_SESSION['flash_type'] = 'warning';
                        header('Location: warranty_suppliers.php');
                        exit;
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE warranty_suppliers SET
                        name = ?, cnpj = ?, contact_person = ?, email = ?,
                        phone = ?, mobile = ?, website = ?,
                        address_street = ?, address_number = ?, address_complement = ?,
                        address_neighborhood = ?, address_city = ?, address_state = ?,
                        address_postal_code = ?, warranty_policy = ?, payment_terms = ?,
                        notes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $name, $cnpj, $contact_person, $email, $phone, $mobile, $website,
                    $address_street, $address_number, $address_complement, $address_neighborhood,
                    $address_city, $address_state, $address_postal_code,
                    $warranty_policy, $payment_terms, $notes, $supplier_id
                ]);
                
                $_SESSION['flash_message'] = '✓ Fornecedor atualizado com sucesso!';
                $_SESSION['flash_type'] = 'success';
                logAdminActivity($_SESSION['user_id'], 'UPDATE_WARRANTY_SUPPLIER', 'warranty_suppliers', $supplier_id, null, ['name' => $name]);
                
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao atualizar fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
                error_log("Erro ao atualizar fornecedor: " . $e->getMessage());
            }
        }
        header('Location: warranty_suppliers.php');
        exit;
    }

    // ===== EXCLUIR FORNECEDOR (SOFT DELETE) =====
    if ($action === 'delete_supplier') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        if ($supplier_id <= 0) {
            $_SESSION['flash_message'] = '✗ Fornecedor inválido';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Obter dados antes de excluir
                $stmt_get = $pdo->prepare("SELECT name FROM warranty_suppliers WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
                $stmt_get->execute([$supplier_id]);
                $supplier_data = $stmt_get->fetch();

                if (!$supplier_data) {
                    $_SESSION['flash_message'] = '✗ Fornecedor não encontrado ou já foi excluído';
                    $_SESSION['flash_type'] = 'warning';
                } else {
                    // SOFT DELETE: Marcar como deletado
                    $stmt = $pdo->prepare("
                        UPDATE warranty_suppliers
                        SET is_deleted = TRUE,
                            deleted_at = NOW(),
                            deleted_by = ?,
                            is_active = 0
                        WHERE id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $supplier_id]);

                    $_SESSION['flash_message'] = '✓ Fornecedor "' . htmlspecialchars($supplier_data['name']) . '" excluído com sucesso!';
                    $_SESSION['flash_type'] = 'success';
                    logAdminActivity($_SESSION['user_id'], 'DELETE_WARRANTY_SUPPLIER', 'warranty_suppliers', $supplier_id, ['name' => $supplier_data['name']], null);
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao excluir fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
                error_log("Erro ao excluir fornecedor: " . $e->getMessage());
            }
        }
        header('Location: warranty_suppliers.php');
        exit;
    }
}

// =====================================================
// OBTER DADOS PARA EXIBIÇÃO
// =====================================================
$search = trim($_GET['search'] ?? '');
$filter_active = $_GET['filter'] ?? 'active';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

// SEMPRE excluir itens deletados (soft delete)
$where_conditions[] = "(is_deleted = FALSE OR is_deleted IS NULL)";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR cnpj LIKE ? OR email LIKE ? OR contact_person LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($filter_active === 'active') {
    $where_conditions[] = "is_active = 1";
} elseif ($filter_active === 'inactive') {
    $where_conditions[] = "is_active = 0";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $count_sql = "SELECT COUNT(*) as total FROM warranty_suppliers $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_suppliers = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_suppliers / $per_page);
    
    $sql = "SELECT * FROM warranty_suppliers $where_clause ORDER BY is_active DESC, name ASC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
    
    $error_message = '';
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar fornecedores: ' . $e->getMessage();
    $suppliers = [];
    $total_suppliers = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<!-- Mensagens de Alerta -->
<?php if (isset($_SESSION["flash_message"])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION["flash_type"]); ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION["flash_message"]; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["flash_message"], $_SESSION["flash_type"]); ?>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-building me-2"></i> Fornecedores de Garantia</h1>
    <a href="warranties.php?tab=suppliers" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<!-- Barra de Busca -->
<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Nome, CNPJ, email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter" class="form-label form-label-custom"><i class="fas fa-filter me-1"></i> Filtro</label>
                <select class="form-select form-control-custom" id="filter" name="filter">
                    <option value="all" <?php echo $filter_active === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <option value="active" <?php echo $filter_active === 'active' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="inactive" <?php echo $filter_active === 'inactive' ? 'selected' : ''; ?>>Inativos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-search me-1"></i> Buscar</button>
                </div>
            </div>
        </form>
        <div class="mt-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                <i class="fas fa-plus me-1"></i> Novo Fornecedor
            </button>
            <?php if (!empty($search) || $filter_active !== 'active'): ?>
                <a href="warranty_suppliers.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-1"></i> Limpar Filtros
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($suppliers)): ?>
    <div class="text-center py-5">
        <i class="fas fa-building fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhum fornecedor encontrado</h5>
        <p class="text-muted">Crie um novo fornecedor para começar.</p>
    </div>
<?php else: ?>
    <!-- Tabela de Fornecedores -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Fornecedor</th>
                            <th>CNPJ</th>
                            <th>Contato</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                    <?php if (!empty($supplier['website'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-globe me-1"></i> <?php echo htmlspecialchars($supplier['website']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['cnpj'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($supplier['email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>"><?php echo htmlspecialchars($supplier['email']); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($supplier['phone'])): ?>
                                        <?php echo htmlspecialchars($supplier['phone']); ?>
                                        <?php if (!empty($supplier['mobile'])): ?>
                                            <br><small><?php echo htmlspecialchars($supplier['mobile']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['address_city'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $supplier['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $supplier['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" title="Visualizar Detalhes" data-bs-toggle="modal" data-bs-target="#viewSupplierModal" onclick="viewSupplier(<?php echo $supplier['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Editar" data-bs-toggle="modal" data-bs-target="#editSupplierModal" onclick="editSupplier(<?php echo $supplier['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este fornecedor?');">
                                            <input type="hidden" name="action" value="delete_supplier">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação de fornecedores" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"><i class="fas fa-angle-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif; ?>
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i class="fas fa-angle-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- MODAL: Criar Fornecedor -->
<div class="modal fade" id="createSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Novo Fornecedor de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create_supplier">
                <div class="modal-body">
                    <!-- Dados Básicos -->
                    <h6 class="mb-3 text-primary-custom"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="XX.XXX.XXX/XXXX-XX">
                        </div>
                    </div>

                    <!-- Contato -->
                    <h6 class="mb-3 text-primary-custom"><i class="fas fa-phone me-2"></i>Dados de Contato</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Pessoa de Contato</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="mobile" name="mobile">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website">
                    </div>

                    <!-- Endereço -->
                    <h6 class="mb-3 text-primary-custom"><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label for="address_street" class="form-label">Rua/Avenida</label>
                            <input type="text" class="form-control" id="address_street" name="address_street">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="address_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="address_number" name="address_number">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="address_complement" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="address_complement" name="address_complement">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="address_neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="address_neighborhood" name="address_neighborhood">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="address_city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="address_city" name="address_city">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="address_state" class="form-label">Estado (UF)</label>
                            <input type="text" class="form-control" id="address_state" name="address_state" maxlength="2">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="address_postal_code" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="address_postal_code" name="address_postal_code" placeholder="XXXXX-XXX">
                        </div>
                    </div>

                    <!-- Informações de Garantia -->
                    <h6 class="mb-3 text-primary-custom"><i class="fas fa-shield-alt me-2"></i>Informações de Garantia</h6>
                    <div class="mb-3">
                        <label for="warranty_policy" class="form-label">Política de Garantia</label>
                        <textarea class="form-control" id="warranty_policy" name="warranty_policy" rows="3" placeholder="Descreva a política de garantia deste fornecedor..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="payment_terms" class="form-label">Termos de Pagamento</label>
                        <input type="text" class="form-control" id="payment_terms" name="payment_terms" placeholder="Ex: À vista, 30 dias, 60 dias...">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Observações gerais sobre o fornecedor..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Criar Fornecedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Editar Fornecedor -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="editSupplierForm">
                <input type="hidden" name="action" value="update_supplier">
                <input type="hidden" name="supplier_id" id="edit_supplier_id">
                <div class="modal-body" id="editSupplierBody">
                    <!-- Carregado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Visualizar Fornecedor -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-building me-2"></i>Detalhes do Fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewSupplierBody">
                <!-- Carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editCurrentSupplier()"><i class="fas fa-edit me-1"></i>Editar</button>
            </div>
        </div>
    </div>
</div>

<script>
const suppliers = <?php echo json_encode($suppliers); ?>;

function editSupplier(supplierId) {
    const supplier = suppliers.find(s => s.id === supplierId);
    if (supplier) {
        document.getElementById('edit_supplier_id').value = supplier.id;
        loadEditForm(supplier);
    }
}

function viewSupplier(supplierId) {
    const supplier = suppliers.find(s => s.id === supplierId);
    if (supplier) {
        loadViewForm(supplier);
    }
}

function editCurrentSupplier() {
    const supplierId = parseInt(document.getElementById('edit_supplier_id').value);
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewSupplierModal'));
    if (modal) modal.hide();
    
    const editModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
    editModal.show();
}

function loadEditForm(supplier) {
    const html = `
        <h6 class="mb-3 text-primary-custom"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Nome da Empresa *</label>
                <input type="text" class="form-control" name="name" value="${escapeHtml(supplier.name)}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">CNPJ</label>
                <input type="text" class="form-control" name="cnpj" value="${escapeHtml(supplier.cnpj || '')}">
            </div>
        </div>

        <h6 class="mb-3 text-primary-custom"><i class="fas fa-phone me-2"></i>Dados de Contato</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Pessoa de Contato</label>
                <input type="text" class="form-control" name="contact_person" value="${escapeHtml(supplier.contact_person || '')}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="${escapeHtml(supplier.email || '')}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" class="form-control" name="phone" value="${escapeHtml(supplier.phone || '')}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Celular</label>
                <input type="text" class="form-control" name="mobile" value="${escapeHtml(supplier.mobile || '')}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" class="form-control" name="website" value="${escapeHtml(supplier.website || '')}">
        </div>

        <h6 class="mb-3 text-primary-custom"><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
        <div class="row">
            <div class="col-md-7 mb-3">
                <label class="form-label">Rua/Avenida</label>
                <input type="text" class="form-control" name="address_street" value="${escapeHtml(supplier.address_street || '')}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Número</label>
                <input type="text" class="form-control" name="address_number" value="${escapeHtml(supplier.address_number || '')}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Complemento</label>
                <input type="text" class="form-control" name="address_complement" value="${escapeHtml(supplier.address_complement || '')}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Bairro</label>
                <input type="text" class="form-control" name="address_neighborhood" value="${escapeHtml(supplier.address_neighborhood || '')}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Cidade</label>
                <input type="text" class="form-control" name="address_city" value="${escapeHtml(supplier.address_city || '')}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Estado (UF)</label>
                <input type="text" class="form-control" name="address_state" value="${escapeHtml(supplier.address_state || '')}" maxlength="2">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">CEP</label>
                <input type="text" class="form-control" name="address_postal_code" value="${escapeHtml(supplier.address_postal_code || '')}">
            </div>
        </div>

        <h6 class="mb-3 text-primary-custom"><i class="fas fa-shield-alt me-2"></i>Informações de Garantia</h6>
        <div class="mb-3">
            <label class="form-label">Política de Garantia</label>
            <textarea class="form-control" name="warranty_policy" rows="3">${escapeHtml(supplier.warranty_policy || '')}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Termos de Pagamento</label>
            <input type="text" class="form-control" name="payment_terms" value="${escapeHtml(supplier.payment_terms || '')}">
        </div>
        <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="notes" rows="3">${escapeHtml(supplier.notes || '')}</textarea>
        </div>
    `;
    document.getElementById('editSupplierBody').innerHTML = html;
}

function loadViewForm(supplier) {
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary-custom mb-3"><i class="fas fa-building me-2"></i>Dados Básicos</h6>
                <p><strong>Nome:</strong> ${escapeHtml(supplier.name)}</p>
                <p><strong>CNPJ:</strong> ${escapeHtml(supplier.cnpj || '-')}</p>
                <p><strong>Website:</strong> ${supplier.website ? `<a href="${escapeHtml(supplier.website)}" target="_blank">${escapeHtml(supplier.website)}</a>` : '-'}</p>
                <p><strong>Status:</strong> <span class="badge bg-${supplier.is_active ? 'success' : 'secondary'}">${supplier.is_active ? 'Ativo' : 'Inativo'}</span></p>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary-custom mb-3"><i class="fas fa-phone me-2"></i>Contato</h6>
                <p><strong>Pessoa:</strong> ${escapeHtml(supplier.contact_person || '-')}</p>
                <p><strong>Email:</strong> ${supplier.email ? `<a href="mailto:${escapeHtml(supplier.email)}">${escapeHtml(supplier.email)}</a>` : '-'}</p>
                <p><strong>Telefone:</strong> ${escapeHtml(supplier.phone || '-')}</p>
                <p><strong>Celular:</strong> ${escapeHtml(supplier.mobile || '-')}</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary-custom mb-3"><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
                <p>
                    ${escapeHtml(supplier.address_street || '')}
                    ${supplier.address_number ? ', ' + escapeHtml(supplier.address_number) : ''}
                    ${supplier.address_complement ? ' - ' + escapeHtml(supplier.address_complement) : ''}<br>
                    ${supplier.address_neighborhood ? escapeHtml(supplier.address_neighborhood) + ' - ' : ''}
                    ${supplier.address_city || ''} ${supplier.address_state ? '- ' + escapeHtml(supplier.address_state) : ''}<br>
                    ${supplier.address_postal_code ? 'CEP: ' + escapeHtml(supplier.address_postal_code) : ''}
                </p>
            </div>
        </div>
        ${supplier.warranty_policy || supplier.payment_terms ? `
            <hr>
            <div class="row">
                <div class="col-md-6">
                    ${supplier.warranty_policy ? `
                        <h6 class="text-primary-custom mb-2"><i class="fas fa-shield-alt me-2"></i>Política de Garantia</h6>
                        <p>${escapeHtml(supplier.warranty_policy)}</p>
                    ` : ''}
                </div>
                <div class="col-md-6">
                    ${supplier.payment_terms ? `
                        <h6 class="text-primary-custom mb-2"><i class="fas fa-money-bill me-2"></i>Termos de Pagamento</h6>
                        <p>${escapeHtml(supplier.payment_terms)}</p>
                    ` : ''}
                </div>
            </div>
        ` : ''}
        ${supplier.notes ? `
            <hr>
            <h6 class="text-primary-custom mb-2"><i class="fas fa-sticky-note me-2"></i>Observações</h6>
            <p>${escapeHtml(supplier.notes)}</p>
        ` : ''}
    `;
    document.getElementById('viewSupplierBody').innerHTML = html;
    document.getElementById('edit_supplier_id').value = supplier.id;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
