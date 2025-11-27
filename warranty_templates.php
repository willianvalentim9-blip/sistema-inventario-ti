<?php
/**
 * Sistema de Garantias - Gerenciar Templates
 * 
 * Permite criar, editar, duplicar e desativar templates de garantia
 * Facilita o uso de templates pré-configurados ao criar/editar produtos
 */

require 'config.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar permissão de admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// ===========================
// PROCESSAR AÇÕES POST
// ===========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Validação comum
    $warranty_provider = trim($_POST['warranty_provider'] ?? '');
    $warranty_period_value = intval($_POST['warranty_period_value'] ?? 0);
    $warranty_period_unit = $_POST['warranty_period_unit'] ?? 'months';
    $template_name = trim($_POST['template_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $warranty_notes = trim($_POST['warranty_notes'] ?? '');

    // Validar campos
    if (empty($template_name)) {
        $_SESSION['flash_message'] = 'Nome do template é obrigatório';
        $_SESSION['flash_type'] = 'danger';
        if ($action === 'create') {
            header('Location: warranty_templates.php');
        } else {
            header('Location: warranty_templates.php?action=edit&id=' . ($_POST['template_id'] ?? ''));
        }
        exit;
    }

    if ($warranty_period_value <= 0) {
        $_SESSION['flash_message'] = 'Período deve ser maior que 0';
        $_SESSION['flash_type'] = 'danger';
        if ($action === 'create') {
            header('Location: warranty_templates.php');
        } else {
            header('Location: warranty_templates.php?action=edit&id=' . ($_POST['template_id'] ?? ''));
        }
        exit;
    }

    // ===== CRIAR NOVO TEMPLATE =====
    if ($action === 'create') {
        try {
            // Verificar se já existe template com mesmo nome
            $stmt_check = $pdo->prepare("SELECT id FROM warranty_templates WHERE name = ? COLLATE utf8mb4_general_ci");
            $stmt_check->execute([$template_name]);
            $existing = $stmt_check->fetch();

            if ($existing) {
                $_SESSION['flash_message'] = '✗ Já existe um template com o nome "' . htmlspecialchars($template_name) . '". Por favor, use um nome diferente.';
                $_SESSION['flash_type'] = 'warning';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO warranty_templates (
                        name,
                        description,
                        warranty_provider,
                        period_value,
                        period_unit,
                        warranty_notes,
                        is_active,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ");

                $stmt->execute([
                    $template_name,
                    $description,
                    $warranty_provider,
                    $warranty_period_value,
                    $warranty_period_unit,
                    $warranty_notes
                ]);

                $_SESSION['flash_message'] = '✓ Template criado com sucesso!';
                $_SESSION['flash_type'] = 'success';

                // Registrar no log
                logAdminActivity($_SESSION['user_id'], 'CREATE_WARRANTY_TEMPLATE', 'warranty_templates', null, null, ['name' => $template_name]);
            }

        } catch (PDOException $e) {
            $_SESSION['flash_message'] = '✗ Erro ao criar template: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao criar template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }

    // ===== ATUALIZAR TEMPLATE =====
    if ($action === 'update') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }

        try {
            // Obter valores antigos para auditoria
            $stmt_old = $pdo->prepare("SELECT * FROM warranty_templates WHERE id = ?");
            $stmt_old->execute([$template_id]);
            $old_template = $stmt_old->fetch(PDO::FETCH_ASSOC);

            if (!$old_template) {
                throw new Exception('Template não encontrado');
            }

            // Verificar se já existe outro template com mesmo nome (exceto este)
            $stmt_check = $pdo->prepare("SELECT id FROM warranty_templates WHERE name = ? COLLATE utf8mb4_general_ci AND id != ? LIMIT 1");
            $stmt_check->execute([$template_name, $template_id]);
            $existing = $stmt_check->fetch();

            if ($existing) {
                $_SESSION['flash_message'] = '✗ Já existe outro template com o nome "' . htmlspecialchars($template_name) . '". Por favor, use um nome diferente.';
                $_SESSION['flash_type'] = 'warning';
                header('Location: warranty_templates.php?action=edit&id=' . $template_id);
                exit;
            }

            // Atualizar
            $stmt = $pdo->prepare("
                UPDATE warranty_templates SET
                    name = ?,
                    description = ?,
                    warranty_provider = ?,
                    period_value = ?,
                    period_unit = ?,
                    warranty_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $template_name,
                $description,
                $warranty_provider,
                $warranty_period_value,
                $warranty_period_unit,
                $warranty_notes,
                $template_id
            ]);

            $_SESSION['flash_message'] = '✓ Template atualizado com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // Registrar no log
            logAdminActivity($_SESSION['user_id'], 'UPDATE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, null, ['name' => $template_name]);

        } catch (Exception $e) {
            $_SESSION['flash_message'] = '✗ Erro ao atualizar template: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao atualizar template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }

    // ===== DUPLICAR TEMPLATE =====
    if ($action === 'duplicate') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }

        try {
            // Obter template original
            $stmt_orig = $pdo->prepare("
                SELECT 
                    name, description, warranty_provider, 
                    period_value, period_unit, warranty_notes
                FROM warranty_templates
                WHERE id = ?
            ");
            $stmt_orig->execute([$template_id]);
            $original = $stmt_orig->fetch(PDO::FETCH_ASSOC);

            if (!$original) {
                throw new Exception('Template não encontrado');
            }

            // Criar novo nome
            $new_name = $original['name'] . ' (Cópia)';

            // Inserir duplicado
            $stmt = $pdo->prepare("
                INSERT INTO warranty_templates (
                    name, description, warranty_provider,
                    period_value, period_unit, warranty_notes,
                    is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");

            $stmt->execute([
                $new_name,
                $original['description'],
                $original['warranty_provider'],
                $original['period_value'],
                $original['period_unit'],
                $original['warranty_notes']
            ]);

            $_SESSION['flash_message'] = '✓ Template duplicado com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // Registrar no log
            logAdminActivity($_SESSION['user_id'], 'DUPLICATE_WARRANTY_TEMPLATE', 'warranty_templates', null, null, ['name' => $new_name]);

        } catch (Exception $e) {
            $_SESSION['flash_message'] = '✗ Erro ao duplicar template: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao duplicar template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }

    // ===== DESATIVAR TEMPLATE =====
    if ($action === 'deactivate') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE warranty_templates SET
                    is_active = 0,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$template_id]);

            $_SESSION['flash_message'] = '✓ Template desativado com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // Registrar no log
            logAdminActivity($_SESSION['user_id'], 'DEACTIVATE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, null, ['status' => 'desativado']);

        } catch (PDOException $e) {
            $_SESSION['flash_message'] = '✗ Erro ao desativar template';
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao desativar template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }

    // ===== ATIVAR TEMPLATE =====
    if ($action === 'activate') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE warranty_templates SET
                    is_active = 1,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$template_id]);

            $_SESSION['flash_message'] = '✓ Template ativado com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // Registrar no log
            logAdminActivity($_SESSION['user_id'], 'ACTIVATE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, null, ['status' => 'ativado']);

        } catch (PDOException $e) {
            $_SESSION['flash_message'] = '✗ Erro ao ativar template';
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao ativar template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }

    // ===== EXCLUIR TEMPLATE =====
    if ($action === 'delete') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }

        try {
            // Obter dados do template antes de excluir (para log)
            $stmt_get = $pdo->prepare("SELECT name FROM warranty_templates WHERE id = ?");
            $stmt_get->execute([$template_id]);
            $template_data = $stmt_get->fetch();

            if (!$template_data) {
                throw new Exception('Template não encontrado');
            }

            // Verificar se template está sendo usado
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as count FROM products 
                WHERE warranty_template_id = ?
            ");
            $stmt_check->execute([$template_id]);
            $usage = $stmt_check->fetch();

            if ($usage['count'] > 0) {
                $_SESSION['flash_message'] = '✗ Não é possível excluir! Este template está vinculado a ' . $usage['count'] . ' produto(s).';
                $_SESSION['flash_type'] = 'warning';
                header('Location: warranty_templates.php');
                exit;
            }

            // Excluir template
            $stmt = $pdo->prepare("DELETE FROM warranty_templates WHERE id = ?");
            $stmt->execute([$template_id]);

            $_SESSION['flash_message'] = '✓ Template "' . htmlspecialchars($template_data['name']) . '" excluído com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // Registrar no log
            logAdminActivity($_SESSION['user_id'], 'DELETE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, ['name' => $template_data['name']], null);

        } catch (Exception $e) {
            $_SESSION['flash_message'] = '✗ Erro ao excluir template: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            error_log("Erro ao excluir template: " . $e->getMessage());
        }

        header('Location: warranty_templates.php');
        exit;
    }
}

// ===========================
// OBTER DADOS PARA EXIBIÇÃO
// ===========================

$action_mode = $_GET['action'] ?? '';
$edit_template = null;

// Se estiver em modo editar, obter dados do template
if ($action_mode === 'edit') {
    $template_id = intval($_GET['id'] ?? 0);
    if ($template_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM warranty_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $edit_template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$edit_template) {
            $_SESSION['flash_message'] = '✗ Template não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranty_templates.php');
            exit;
        }
    }
}

// Obter lista de templates
$filter_active = $_GET['filter'] ?? 'active'; // 'active', 'inactive', 'all'

$sql = "SELECT * FROM warranty_templates";

if ($filter_active === 'active') {
    $sql .= " WHERE is_active = 1";
} elseif ($filter_active === 'inactive') {
    $sql .= " WHERE is_active = 0";
}

$sql .= " ORDER BY CASE WHEN is_active = 1 THEN 0 ELSE 1 END, name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="mb-3">
            <i class="fas fa-file-invoice"></i> Gerenciar Templates de Garantia
        </h1>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($action_mode !== 'create' && $action_mode !== 'edit'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                <i class="fas fa-plus"></i> Novo Template
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Mensagens de alerta -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<!-- Aba de filtros -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo $filter_active === 'active' ? 'active' : ''; ?>" 
           href="?filter=active">
            Ativos (<?php echo count(array_filter($templates, fn($t) => $t['is_active'])); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $filter_active === 'inactive' ? 'active' : ''; ?>" 
           href="?filter=inactive">
            Inativos (<?php echo count(array_filter($templates, fn($t) => !$t['is_active'])); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $filter_active === 'all' ? 'active' : ''; ?>" 
           href="?filter=all">
            Todos (<?php echo count($templates); ?>)
        </a>
    </li>
</ul>

<!-- Lista de templates -->
<div class="row">
    <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Nenhum template encontrado
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $template): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 <?php echo !$template['is_active'] ? 'opacity-50' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($template['name']); ?></h5>
                            <span class="badge bg-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $template['is_active'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </div>

                        <?php if ($template['description']): ?>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($template['description']); ?></p>
                        <?php endif; ?>

                        <div class="template-details mb-3 small">
                            <div><strong>Período:</strong> <?php echo $template['period_value']; ?> 
                                <?php echo formatPeriodUnit($template['period_unit']); ?></div>
                            <?php if ($template['warranty_provider']): ?>
                                <div><strong>Fornecedor:</strong> <?php echo htmlspecialchars($template['warranty_provider']); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($template['warranty_notes']): ?>
                            <div class="alert alert-light small mb-3">
                                <strong>Observações:</strong>
                                <div><?php echo htmlspecialchars($template['warranty_notes']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="loadEditForm(<?php echo $template['id']; ?>)"
                                    data-bs-toggle="modal" data-bs-target="#editTemplateModal">
                                <i class="fas fa-edit"></i> Editar
                            </button>

                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="duplicate">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-copy"></i> Duplicar
                                </button>
                            </form>

                            <form action="" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Tem certeza?');">
                                <input type="hidden" name="action" value="<?php echo $template['is_active'] ? 'deactivate' : 'activate'; ?>">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $template['is_active'] ? 'warning' : 'success'; ?>">
                                    <i class="fas fa-<?php echo $template['is_active'] ? 'ban' : 'check'; ?>"></i> 
                                    <?php echo $template['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                </button>
                            </form>

                            <form action="" method="POST" class="d-inline" 
                                  onsubmit="return confirm('⚠️ ATENÇÃO! Excluir este template?\n\nEsta ação não pode ser desfeita!\n\nContinuar?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-footer bg-light text-muted small">
                        <div class="d-flex justify-content-between">
                            <span>Criado: <?php echo formatDatePT($template['created_at']); ?></span>
                            <?php if ($template['updated_at']): ?>
                                <span>Editado: <?php echo formatDatePT($template['updated_at']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Criar novo template -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Template de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <!-- Alerta de nome duplicado -->
                    <div id="duplicate-name-alert-create" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>⚠️ Atenção!</strong> Já existe um template com este nome.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="mb-3">
                        <label for="template_name" class="form-label">Nome do Template *</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" required>
                        <small class="text-muted">Ex: "Eletrônicos Padrão"</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="warranty_period_value" class="form-label">Período *</label>
                            <input type="number" class="form-control" id="warranty_period_value" 
                                   name="warranty_period_value" value="12" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="warranty_period_unit" class="form-label">Unidade *</label>
                            <select class="form-select" id="warranty_period_unit" name="warranty_period_unit" required>
                                <option value="days">Dias</option>
                                <option value="months" selected>Meses</option>
                                <option value="years">Anos</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="warranty_provider" class="form-label">Fornecedor/Fabricante</label>
                        <input type="text" class="form-control" id="warranty_provider" name="warranty_provider">
                        <small class="text-muted">Ex: "Dell", "HP", "Seagate"</small>
                    </div>

                    <div class="mb-3">
                        <label for="warranty_notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="warranty_notes" name="warranty_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="createTemplateBtn" class="btn btn-primary">Criar Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar template -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Template de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="template_id" id="edit_template_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_template_name" class="form-label">Nome do Template *</label>
                        <input type="text" class="form-control" id="edit_template_name" name="template_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_warranty_period_value" class="form-label">Período *</label>
                            <input type="number" class="form-control" id="edit_warranty_period_value" 
                                   name="warranty_period_value" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_warranty_period_unit" class="form-label">Unidade *</label>
                            <select class="form-select" id="edit_warranty_period_unit" name="warranty_period_unit" required>
                                <option value="days">Dias</option>
                                <option value="months">Meses</option>
                                <option value="years">Anos</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_warranty_provider" class="form-label">Fornecedor/Fabricante</label>
                        <input type="text" class="form-control" id="edit_warranty_provider" name="warranty_provider">
                    </div>

                    <div class="mb-3">
                        <label for="edit_warranty_notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="edit_warranty_notes" name="warranty_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Função para carregar dados do template no modal de edição
function loadEditForm(templateId) {
    // Aqui você pode fazer uma requisição AJAX para obter dados do template
    // Por enquanto, recarregamos a página com parâmetros
    window.location.href = '?action=edit&id=' + templateId;
}

// Se estamos em modo edição, preencher o modal automaticamente
<?php if ($edit_template): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('edit_template_id').value = '<?php echo $edit_template['id']; ?>';
    document.getElementById('edit_template_name').value = '<?php echo htmlspecialchars($edit_template['name']); ?>';
    document.getElementById('edit_description').value = '<?php echo htmlspecialchars($edit_template['description'] ?? ''); ?>';
    document.getElementById('edit_warranty_period_value').value = '<?php echo $edit_template['period_value']; ?>';
    document.getElementById('edit_warranty_period_unit').value = '<?php echo $edit_template['period_unit']; ?>';
    document.getElementById('edit_warranty_provider').value = '<?php echo htmlspecialchars($edit_template['warranty_provider'] ?? ''); ?>';
    document.getElementById('edit_warranty_notes').value = '<?php echo htmlspecialchars($edit_template['warranty_notes'] ?? ''); ?>';
    
    // Abrir modal automaticamente
    const editModal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
    editModal.show();
});
<?php endif; ?>

// Validação em tempo real para nome duplicado no modal de criar
document.addEventListener('DOMContentLoaded', function() {
    const templates = <?php echo json_encode($templates); ?>;
    const templateNameInput = document.getElementById('template_name');
    const duplicateAlert = document.getElementById('duplicate-name-alert-create');
    const createBtn = document.getElementById('createTemplateBtn');
    const createTemplateModal = document.getElementById('createTemplateModal');
    
    if (templateNameInput) {
        templateNameInput.addEventListener('input', function() {
            const name = this.value.trim().toLowerCase();
            const isDuplicate = templates.some(t => t.name.toLowerCase() === name);
            
            if (isDuplicate && name.length > 0) {
                duplicateAlert.classList.remove('d-none');
                createBtn.disabled = true;
                createBtn.style.opacity = '0.5';
                createBtn.style.cursor = 'not-allowed';
            } else {
                duplicateAlert.classList.add('d-none');
                createBtn.disabled = false;
                createBtn.style.opacity = '1';
                createBtn.style.cursor = 'pointer';
            }
        });
        
        // Resetar validação ao fechar o modal
        if (createTemplateModal) {
            createTemplateModal.addEventListener('hidden.bs.modal', function() {
                templateNameInput.value = '';
                duplicateAlert.classList.add('d-none');
                createBtn.disabled = false;
                createBtn.style.opacity = '1';
                createBtn.style.cursor = 'pointer';
            });
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
