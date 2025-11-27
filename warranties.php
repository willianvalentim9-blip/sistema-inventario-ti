<?php
require_once 'config.php';
require_once 'includes/warranty_functions.php';
requireLogin();

$page_title = 'Garantias de Produtos';
$pdo = getConnection();

// =====================================================
// PROCESSAR AÇÕES DE TEMPLATES (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Validação comum para templates - CORRIGIDO: Validação mais rigorosa
    $warranty_provider = trim($_POST['warranty_provider'] ?? '');
    $warranty_period_value = intval($_POST['warranty_period_value'] ?? 0);
    $warranty_period_unit = $_POST['warranty_period_unit'] ?? 'months';
    $template_name = trim($_POST['template_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $warranty_notes = trim($_POST['warranty_notes'] ?? '');
    
    // NOVO: Validar limite máximo de período (prevenir valores absurdos)
    if ($warranty_period_value > 10000) {
        $warranty_period_value = 10000;
    }
    
    // NOVO: Validar que unidade é válida
    if (!in_array($warranty_period_unit, ['days', 'months', 'years'])) {
        $warranty_period_unit = 'months';
    }

    // ===== CRIAR NOVO TEMPLATE =====
    if ($action === 'create_template') {
        // CORRIGIDO: Validação mais rigorosa incluindo período máximo
        if (empty($template_name) || $warranty_period_value <= 0 || $warranty_period_value > 10000) {
            $_SESSION['flash_message'] = '✗ Preencha todos os campos obrigatórios (período deve estar entre 1 e 10000)';
            $_SESSION['flash_type'] = 'danger';
        } else {
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
                            name, description, warranty_provider,
                            period_value, period_unit, warranty_notes,
                            is_active, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$template_name, $description, $warranty_provider, $warranty_period_value, $warranty_period_unit, $warranty_notes]);
                    
                    $_SESSION['flash_message'] = '✓ Template criado com sucesso!';
                    $_SESSION['flash_type'] = 'success';
                    logAdminActivity($_SESSION['user_id'], 'CREATE_WARRANTY_TEMPLATE', 'warranty_templates', null, null, ['name' => $template_name]);
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao criar template';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=templates');
        exit;
    }

    // ===== ATUALIZAR TEMPLATE =====
    if ($action === 'update_template') {
        $template_id = intval($_POST['template_id'] ?? 0);
        // CORRIGIDO: Validação mais rigorosa incluindo período máximo
        if ($template_id <= 0 || empty($template_name) || $warranty_period_value <= 0 || $warranty_period_value > 10000) {
            $_SESSION['flash_message'] = '✗ Dados inválidos (período deve estar entre 1 e 10000)';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar se já existe outro template com mesmo nome (exceto este)
                $stmt_check = $pdo->prepare("SELECT id FROM warranty_templates WHERE name = ? COLLATE utf8mb4_general_ci AND id != ? LIMIT 1");
                $stmt_check->execute([$template_name, $template_id]);
                $existing = $stmt_check->fetch();

                if ($existing) {
                    $_SESSION['flash_message'] = '✗ Já existe outro template com o nome "' . htmlspecialchars($template_name) . '". Por favor, use um nome diferente.';
                    $_SESSION['flash_type'] = 'warning';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE warranty_templates SET
                            name = ?, description = ?, warranty_provider = ?,
                            period_value = ?, period_unit = ?,
                            warranty_notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$template_name, $description, $warranty_provider, $warranty_period_value, $warranty_period_unit, $warranty_notes, $template_id]);
                    
                    $_SESSION['flash_message'] = '✓ Template atualizado com sucesso!';
                    $_SESSION['flash_type'] = 'success';
                    logAdminActivity($_SESSION['user_id'], 'UPDATE_WARRANTY_TEMPLATE', 'warranty_templates', $template_id, null, ['name' => $template_name]);
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao atualizar template';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=templates');
        exit;
    }

    // ===== DUPLICAR TEMPLATE =====
    if ($action === 'duplicate_template') {
        $template_id = intval($_POST['template_id'] ?? 0);
        if ($template_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM warranty_templates WHERE id = ?");
                $stmt->execute([$template_id]);
                $original = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($original) {
                    $new_name = $original['name'] . ' (Cópia)';
                    $stmt = $pdo->prepare("
                        INSERT INTO warranty_templates 
                        (name, description, warranty_provider, period_value, period_unit, warranty_notes, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$new_name, $original['description'], $original['warranty_provider'], $original['period_value'], $original['period_unit'], $original['warranty_notes']]);
                    
                    $_SESSION['flash_message'] = '✓ Template duplicado com sucesso!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao duplicar template';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=templates');
        exit;
    }

    // ===== EXCLUIR TEMPLATE =====
    if ($action === 'delete_template') {
        $template_id = intval($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $_SESSION['flash_message'] = '✗ Template inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: warranties.php?tab=templates');
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

            // Verificar se template está sendo usado - CORRIGIDO: Verificar AMBAS as tabelas
            // Produtos
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as count FROM products 
                WHERE warranty_template_id = ?
            ");
            $stmt_check->execute([$template_id]);
            $usage_products = $stmt_check->fetch()['count'];
            
            // Máquinas (ready_machines)
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as count FROM ready_machines 
                WHERE warranty_template_id = ?
            ");
            $stmt_check->execute([$template_id]);
            $usage_machines = $stmt_check->fetch()['count'];
            
            $total_usage = $usage_products + $usage_machines;

            if ($total_usage > 0) {
                $msg = '✗ Não é possível excluir! Este template está vinculado a ';
                if ($usage_products > 0) $msg .= $usage_products . ' produto(s)';
                if ($usage_machines > 0) $msg .= ($usage_products > 0 ? ' e ' : '') . $usage_machines . ' máquina(s)';
                $_SESSION['flash_message'] = $msg . '.';
                $_SESSION['flash_type'] = 'warning';
                header('Location: warranties.php?tab=templates');
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

        header('Location: warranties.php?tab=templates');
        exit;
    }

    // ===== ATIVAR/DESATIVAR TEMPLATE =====
    if ($action === 'toggle_template') {
        $template_id = intval($_POST['template_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        if ($template_id > 0) {
            try {
                $new_status = 1 - $is_active;
                $stmt = $pdo->prepare("UPDATE warranty_templates SET is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $template_id]);
                
                $_SESSION['flash_message'] = '✓ Template ' . ($new_status ? 'ativado' : 'desativado') . ' com sucesso!';
                $_SESSION['flash_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao atualizar template';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=templates');
        exit;
    }

    // ===== CRIAR NOVO FORNECEDOR =====
    if ($action === 'create_supplier') {
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $supplier_cnpj = trim($_POST['supplier_cnpj'] ?? '');
        $supplier_contact_person = trim($_POST['supplier_contact_person'] ?? '');
        $supplier_email = trim($_POST['supplier_email'] ?? '');
        $supplier_phone = trim($_POST['supplier_phone'] ?? '');
        $supplier_mobile = trim($_POST['supplier_mobile'] ?? '');
        $supplier_website = trim($_POST['supplier_website'] ?? '');
        $supplier_address_street = trim($_POST['supplier_address_street'] ?? '');
        $supplier_address_number = trim($_POST['supplier_address_number'] ?? '');
        $supplier_address_complement = trim($_POST['supplier_address_complement'] ?? '');
        $supplier_address_neighborhood = trim($_POST['supplier_address_neighborhood'] ?? '');
        $supplier_address_city = trim($_POST['supplier_address_city'] ?? '');
        $supplier_address_state = trim($_POST['supplier_address_state'] ?? '');
        $supplier_address_postal_code = trim($_POST['supplier_address_postal_code'] ?? '');
        $supplier_warranty_policy = trim($_POST['supplier_warranty_policy'] ?? '');
        $supplier_payment_terms = trim($_POST['supplier_payment_terms'] ?? '');
        $supplier_notes = trim($_POST['supplier_notes'] ?? '');

        if (empty($supplier_name)) {
            $_SESSION['flash_message'] = '✗ Nome da empresa é obrigatório';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar se CNPJ já existe (se fornecido)
                if (!empty($supplier_cnpj)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM warranty_suppliers WHERE cnpj = ?");
                    $stmt_check->execute([$supplier_cnpj]);
                    if ($stmt_check->fetch()) {
                        $_SESSION['flash_message'] = '✗ Já existe um fornecedor com este CNPJ';
                        $_SESSION['flash_type'] = 'warning';
                        header('Location: warranties.php?tab=suppliers');
                        exit;
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO warranty_suppliers (
                        name, cnpj, contact_person, email, phone, mobile, website,
                        address_street, address_number, address_complement, 
                        address_neighborhood, address_city, address_state, address_postal_code,
                        warranty_policy, payment_terms, notes, is_active, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $supplier_name, $supplier_cnpj, $supplier_contact_person, $supplier_email,
                    $supplier_phone, $supplier_mobile, $supplier_website,
                    $supplier_address_street, $supplier_address_number, $supplier_address_complement,
                    $supplier_address_neighborhood, $supplier_address_city, $supplier_address_state, 
                    $supplier_address_postal_code, $supplier_warranty_policy, $supplier_payment_terms, 
                    $supplier_notes
                ]);
                
                $_SESSION['flash_message'] = '✓ Fornecedor criado com sucesso!';
                $_SESSION['flash_type'] = 'success';
                logAdminActivity($_SESSION['user_id'], 'CREATE_WARRANTY_SUPPLIER', 'warranty_suppliers', null, null, ['name' => $supplier_name]);
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao criar fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=suppliers');
        exit;
    }

    // ===== DELETAR FORNECEDOR =====
    if ($action === 'delete_supplier') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        
        if ($supplier_id <= 0) {
            $_SESSION['flash_message'] = '✗ ID inválido';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar se existem produtos associados
                $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE warranty_supplier_id = ?");
                $stmt_check->execute([$supplier_id]);
                $count = $stmt_check->fetch()['count'];

                if ($count > 0) {
                    $_SESSION['flash_message'] = '✗ Não é possível deletar: existem ' . $count . ' produto(s) associado(s) a este fornecedor';
                    $_SESSION['flash_type'] = 'warning';
                } else {
                    // Obter nome para log
                    $stmt_get = $pdo->prepare("SELECT name FROM warranty_suppliers WHERE id = ?");
                    $stmt_get->execute([$supplier_id]);
                    $supplier = $stmt_get->fetch();
                    $supplier_name = $supplier['name'] ?? 'Desconhecido';

                    // Deletar fornecedor
                    $stmt_delete = $pdo->prepare("DELETE FROM warranty_suppliers WHERE id = ?");
                    $stmt_delete->execute([$supplier_id]);

                    $_SESSION['flash_message'] = '✓ Fornecedor deletado com sucesso!';
                    $_SESSION['flash_type'] = 'success';
                    logAdminActivity($_SESSION['user_id'], 'DELETE_WARRANTY_SUPPLIER', 'warranty_suppliers', $supplier_id, null, ['name' => $supplier_name]);
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao deletar fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=suppliers');
        exit;
    }

    // ===== ATUALIZAR FORNECEDOR =====
    if ($action === 'update_supplier') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $supplier_cnpj = trim($_POST['supplier_cnpj'] ?? '');
        $supplier_contact_person = trim($_POST['supplier_contact_person'] ?? '');
        $supplier_email = trim($_POST['supplier_email'] ?? '');
        $supplier_phone = trim($_POST['supplier_phone'] ?? '');
        $supplier_mobile = trim($_POST['supplier_mobile'] ?? '');
        $supplier_website = trim($_POST['supplier_website'] ?? '');
        $supplier_address_street = trim($_POST['supplier_address_street'] ?? '');
        $supplier_address_number = trim($_POST['supplier_address_number'] ?? '');
        $supplier_address_complement = trim($_POST['supplier_address_complement'] ?? '');
        $supplier_address_neighborhood = trim($_POST['supplier_address_neighborhood'] ?? '');
        $supplier_address_city = trim($_POST['supplier_address_city'] ?? '');
        $supplier_address_state = trim($_POST['supplier_address_state'] ?? '');
        $supplier_address_postal_code = trim($_POST['supplier_address_postal_code'] ?? '');
        $supplier_warranty_policy = trim($_POST['supplier_warranty_policy'] ?? '');
        $supplier_payment_terms = trim($_POST['supplier_payment_terms'] ?? '');
        $supplier_notes = trim($_POST['supplier_notes'] ?? '');

        if ($supplier_id <= 0 || empty($supplier_name)) {
            $_SESSION['flash_message'] = '✗ Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Verificar se CNPJ já existe em outro fornecedor (se fornecido)
                if (!empty($supplier_cnpj)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM warranty_suppliers WHERE cnpj = ? AND id != ?");
                    $stmt_check->execute([$supplier_cnpj, $supplier_id]);
                    if ($stmt_check->fetch()) {
                        $_SESSION['flash_message'] = '✗ Já existe outro fornecedor com este CNPJ';
                        $_SESSION['flash_type'] = 'warning';
                        header('Location: warranties.php?tab=suppliers');
                        exit;
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE warranty_suppliers SET
                        name = ?, cnpj = ?, contact_person = ?, email = ?, phone = ?, mobile = ?, website = ?,
                        address_street = ?, address_number = ?, address_complement = ?, 
                        address_neighborhood = ?, address_city = ?, address_state = ?, address_postal_code = ?,
                        warranty_policy = ?, payment_terms = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $supplier_name, $supplier_cnpj, $supplier_contact_person, $supplier_email,
                    $supplier_phone, $supplier_mobile, $supplier_website,
                    $supplier_address_street, $supplier_address_number, $supplier_address_complement,
                    $supplier_address_neighborhood, $supplier_address_city, $supplier_address_state, 
                    $supplier_address_postal_code, $supplier_warranty_policy, $supplier_payment_terms, 
                    $supplier_notes, $supplier_id
                ]);
                
                $_SESSION['flash_message'] = '✓ Fornecedor atualizado com sucesso!';
                $_SESSION['flash_type'] = 'success';
                logAdminActivity($_SESSION['user_id'], 'UPDATE_WARRANTY_SUPPLIER', 'warranty_suppliers', $supplier_id, null, ['name' => $supplier_name]);
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '✗ Erro ao atualizar fornecedor: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: warranties.php?tab=suppliers');
        exit;
    }
}

// ===== OBTER DADOS PARA EXIBIÇÃO =====
// Verificar se é uma requisição AJAX para obter dados de fornecedor
if (!empty($_GET['action']) && $_GET['action'] === 'get_supplier' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    $supplier_id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM warranty_suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($supplier) {
            echo json_encode(['success' => true, 'supplier' => $supplier]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Verificar se CNPJ já existe (AJAX)
if (!empty($_GET['action']) && $_GET['action'] === 'check_cnpj') {
    header('Content-Type: application/json');
    $cnpj = trim($_GET['cnpj'] ?? '');
    $exclude_id = intval($_GET['exclude_id'] ?? 0); // Para UPDATE, excluir o ID atual
    
    try {
        if (empty($cnpj)) {
            echo json_encode(['exists' => false, 'message' => 'CNPJ vazio']);
            exit;
        }
        
        if ($exclude_id > 0) {
            // UPDATE: Verificar se existe outro fornecedor com este CNPJ
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM warranty_suppliers WHERE cnpj = ? AND id != ?");
            $stmt->execute([$cnpj, $exclude_id]);
        } else {
            // CREATE: Verificar se existe qualquer fornecedor com este CNPJ
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM warranty_suppliers WHERE cnpj = ?");
            $stmt->execute([$cnpj]);
        }
        
        $result = $stmt->fetch();
        $exists = $result['count'] > 0;
        
        echo json_encode([
            'exists' => $exists,
            'message' => $exists ? 'CNPJ já cadastrado' : 'CNPJ disponível'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['exists' => false, 'message' => 'Erro ao verificar CNPJ']);
    }
    exit;
}

// =====================================================
// OBTER DADOS PARA EXIBIÇÃO
// =====================================================

$current_tab = $_GET['tab'] ?? 'products';

// Parâmetros de busca e filtro (para aba produtos)
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$product_status_filter = $_GET['product_status'] ?? '';
$warranty_status_filter = $_GET['warranty_status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// DADOS PARA ABA TEMPLATES
$filter_active = $_GET['filter'] ?? 'active';
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

// DADOS PARA ABA PRODUTOS
$where_conditions = ["has_warranty = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR model LIKE ? OR manufacturer LIKE ? OR warranty_provider LIKE ? OR invoice_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}
if (!empty($category_filter)) { $where_conditions[] = "category = ?"; $params[] = $category_filter; }
if (!empty($product_status_filter)) { $where_conditions[] = "status = ?"; $params[] = $product_status_filter; }

if (!empty($warranty_status_filter)) {
    $today = date('Y-m-d');
    if ($warranty_status_filter === 'active') {
        $where_conditions[] = "warranty_end_date >= ?";
        $params[] = $today;
    } elseif ($warranty_status_filter === 'expiring_soon') {
        $future_date = date('Y-m-d', strtotime('+30 days'));
        $where_conditions[] = "warranty_end_date >= ? AND warranty_end_date <= ?";
        $params[] = $today;
        $params[] = $future_date;
    } elseif ($warranty_status_filter === 'expired') {
        $where_conditions[] = "warranty_end_date < ?";
        $params[] = $today;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);
    
    // CORRIGIDO: Usar prepared statement para LIMIT e OFFSET (prevenir SQL Injection)
    $sql = "SELECT * FROM products $where_clause ORDER BY warranty_end_date ASC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $products = $stmt->fetchAll();
    
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $categories_stmt->fetchAll();
    
    $error_message = '';
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar garantias: ' . $e->getMessage();
    $products = []; $categories = []; $total_products = 0; $total_pages = 0;
}

// DADOS PARA ABA MÁQUINAS
$search_machines = trim($_GET['search_machines'] ?? '');
$machines_status_filter = $_GET['machines_status'] ?? '';
$machines_page = max(1, intval($_GET['machines_page'] ?? 1));
$machines_per_page = 20;
$machines_offset = ($machines_page - 1) * $machines_per_page;

$machines_where_conditions = [];
$machines_params = [];

if (!empty($search_machines)) {
    $machines_where_conditions[] = "(name LIKE ? OR serial_number LIKE ? OR processor LIKE ?)";
    $search_machines_param = "%$search_machines%";
    $machines_params = [$search_machines_param, $search_machines_param, $search_machines_param];
}
if (!empty($machines_status_filter)) {
    $machines_where_conditions[] = "status = ?";
    $machines_params[] = $machines_status_filter;
}

$machines_where_clause = !empty($machines_where_conditions) ? 'WHERE ' . implode(' AND ', $machines_where_conditions) : '';

try {
    $machines_count_sql = "SELECT COUNT(*) as total FROM ready_machines $machines_where_clause";
    $machines_count_stmt = $pdo->prepare($machines_count_sql);
    $machines_count_stmt->execute($machines_params);
    $total_machines = $machines_count_stmt->fetch()['total'];
    $machines_total_pages = ceil($total_machines / $machines_per_page);
    
    $machines_sql = "SELECT * FROM ready_machines $machines_where_clause ORDER BY name ASC LIMIT ? OFFSET ?";
    $machines_stmt = $pdo->prepare($machines_sql);
    $machines_stmt->execute(array_merge($machines_params, [$machines_per_page, $machines_offset]));
    $machines = $machines_stmt->fetchAll();
    
    $machines_error = '';
} catch (PDOException $e) {
    $machines_error = 'Erro ao carregar máquinas: ' . $e->getMessage();
    $machines = [];
    $total_machines = 0;
    $machines_total_pages = 0;
}

// DADOS PARA ABA HISTÓRICO
$product_id_history = intval($_GET['product_id'] ?? 0);
$history = [];
if ($product_id_history > 0) {
    $history = getWarrantyHistory($pdo, $product_id_history, 100);
}

// Funções auxiliares
function getCategoryIcon($category) { 
    $icons = ['CPU' => 'fa-microchip', 'RAM' => 'fa-memory', 'SSD' => 'fa-hdd', 'HDD' => 'fa-hdd', 'GPU' => 'fa-tv', 'Motherboard' => 'fa-microchip', 'PSU' => 'fa-plug', 'Case' => 'fa-cube', 'Cable' => 'fa-ethernet', 'Monitor' => 'fa-desktop', 'Keyboard' => 'fa-keyboard', 'Mouse' => 'fa-mouse', 'Network' => 'fa-network-wired', 'Other' => 'fa-box-open']; 
    return $icons[$category] ?? 'fa-box'; 
}
function getProductStatusBadgeClass($status) { 
    $classes = ['available' => 'bg-success', 'in_use' => 'bg-warning text-dark', 'defective' => 'bg-danger', 'maintenance' => 'bg-info']; 
    return $classes[$status] ?? 'bg-secondary'; 
}
function getProductStatusText($status) { 
    $texts = ['available' => 'Disponível', 'in_use' => 'Em Uso', 'defective' => 'Defeituoso', 'maintenance' => 'Manutenção']; 
    return $texts[$status] ?? ucfirst($status); 
}
function getWarrantyStatusBadge($endDate) {
    if (empty($endDate)) return ['text' => 'N/A', 'class' => 'bg-secondary'];
    $today = new DateTime();
    $end = new DateTime($endDate);
    $interval = $today->diff($end);

    if ($end < $today) {
        return ['text' => 'Expirada', 'class' => 'bg-danger'];
    } elseif ($interval->days <= 30) {
        return ['text' => 'Expira em breve', 'class' => 'bg-warning text-dark'];
    } else {
        return ['text' => 'Ativa', 'class' => 'bg-success'];
    }
}

?>

<?php include 'includes/header.php'; ?>

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
    <h1 class="h2 text-primary-custom"><i class="fas fa-shield-alt me-2"></i> Garantias de Produtos</h1>
</div>

<!-- Abas -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <ul class="nav nav-tabs mb-0" id="warrantyTabs">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" href="?tab=products">
                <i class="fas fa-list me-2"></i> Produtos (<?php echo $total_products; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab === 'suppliers' ? 'active' : ''; ?>" href="?tab=suppliers">
                <i class="fas fa-building me-2"></i> Fornecedores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab === 'machines' ? 'active' : ''; ?>" href="?tab=machines">
                <i class="fas fa-desktop me-2"></i> Máquinas (<?php echo $total_machines; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab === 'templates' ? 'active' : ''; ?>" href="?tab=templates">
                <i class="fas fa-file-invoice me-2"></i> Templates
            </a>
        </li>
    </ul>
    
    <!-- Botão de Export Otimizado -->
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
        <i class="fas fa-download me-1"></i> Exportar Dados
    </button>
</div>

<!-- Modal de Exportação -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-download me-2"></i> Exportar Dados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Selecione o tipo de dados que deseja exportar em formato CSV:</p>
                
                <div class="row g-3">
                    <!-- Garantias de Produtos -->
                    <div class="col-md-6">
                        <div class="card border-success cursor-pointer export-card" onclick="downloadExport('products')">
                            <div class="card-body text-center">
                                <i class="fas fa-box fa-2x text-success mb-2"></i>
                                <h6 class="card-title">Garantias de Produtos</h6>
                                <p class="card-text small text-muted">
                                    Exporta: ID, Nome, SKU, Template, Fornecedor, Período, Notas
                                </p>
                                <small class="text-muted d-block mt-2">
                                    📊 <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_warranty = 1");
                                    $count = $stmt->fetch()['total'];
                                    echo "$count registros";
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Garantias de Máquinas -->
                    <div class="col-md-6">
                        <div class="card border-info cursor-pointer export-card" onclick="downloadExport('machines')">
                            <div class="card-body text-center">
                                <i class="fas fa-desktop fa-2x text-info mb-2"></i>
                                <h6 class="card-title">Máquinas Prontas</h6>
                                <p class="card-text small text-muted">
                                    Exporta: Nome, Serial, Processador, Memória, Storage, Status
                                </p>
                                <small class="text-muted d-block mt-2">
                                    📊 <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines");
                                    $count = $stmt->fetch()['total'];
                                    echo "$count registros";
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Templates de Garantia -->
                    <div class="col-md-6">
                        <div class="card border-warning cursor-pointer export-card" onclick="downloadExport('templates')">
                            <div class="card-body text-center">
                                <i class="fas fa-file-contract fa-2x text-warning mb-2"></i>
                                <h6 class="card-title">Templates de Garantia</h6>
                                <p class="card-text small text-muted">
                                    Exporta: Nome, Descrição, Período, Provedor, Notas, Status
                                </p>
                                <small class="text-muted d-block mt-2">
                                    📊 <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_templates");
                                    $count = $stmt->fetch()['total'];
                                    echo "$count registros";
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fornecedores de Garantia -->
                    <div class="col-md-6">
                        <div class="card border-danger cursor-pointer export-card" onclick="downloadExport('suppliers')">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x text-danger mb-2"></i>
                                <h6 class="card-title">Fornecedores de Garantia</h6>
                                <p class="card-text small text-muted">
                                    Exporta: Nome, CNPJ, Email, Telefone, Cidade, Estado, Status
                                </p>
                                <small class="text-muted d-block mt-2">
                                    📊 <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_suppliers");
                                    $count = $stmt->fetch()['total'];
                                    echo "$count registros";
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Formato:</strong> CSV (Excel compatível) • <strong>Encoding:</strong> UTF-8 • <strong>Separador:</strong> Ponto e vírgula (;)
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================== ABA PRODUTOS ===================================== -->
<?php if ($current_tab === 'products'): ?>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="tab" value="products">
            <div class="col-md-3">
                <label for="search" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search" name="search" placeholder="Produto, NF, fornecedor..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label form-label-custom"><i class="fas fa-folder me-1"></i> Categoria</label>
                <select class="form-select form-control-custom" id="category" name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="product_status" class="form-label form-label-custom"><i class="fas fa-box me-1"></i> Status Produto</label>
                <select class="form-select form-control-custom" id="product_status" name="product_status">
                    <option value="">Todos</option>
                    <option value="available" <?php echo $product_status_filter === 'available' ? 'selected' : ''; ?>>Disponível</option>
                    <option value="in_use" <?php echo $product_status_filter === 'in_use' ? 'selected' : ''; ?>>Em Uso</option>
                    <option value="defective" <?php echo $product_status_filter === 'defective' ? 'selected' : ''; ?>>Defeituoso</option>
                    <option value="maintenance" <?php echo $product_status_filter === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="warranty_status" class="form-label form-label-custom"><i class="fas fa-shield-alt me-1"></i> Status Garantia</label>
                <select class="form-select form-control-custom" id="warranty_status" name="warranty_status">
                    <option value="">Todos</option>
                    <option value="active" <?php echo $warranty_status_filter === 'active' ? 'selected' : ''; ?>>Ativa</option>
                    <option value="expiring_soon" <?php echo $warranty_status_filter === 'expiring_soon' ? 'selected' : ''; ?>>Expira em breve (30 dias)</option>
                    <option value="expired" <?php echo $warranty_status_filter === 'expired' ? 'selected' : ''; ?>>Expirada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-filter me-1"></i> Filtrar</button>
                </div>
            </div>
        </form>
        <?php if (!empty($search) || !empty($category_filter) || !empty($product_status_filter) || !empty($warranty_status_filter)): ?>
            <div class="mt-3">
                <a href="warranties.php?tab=products" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> Limpar Filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="text-center py-5">
        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhum produto com garantia encontrado</h5>
        <p class="text-muted">Adicione produtos com garantia ou ajuste seus filtros.</p>
    </div>
<?php else: ?>
    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Fornecedor Garantia</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Status Garantia</th>
                            <th>Status Produto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $warranty_status = getWarrantyStatusBadge($product['warranty_end_date']);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="product-icon me-3"><i class="fas <?php echo getCategoryIcon($product['category']); ?> fa-2x text-primary-custom"></i></div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <?php if (!empty($product['model'])): ?><small class="text-muted"><?php echo htmlspecialchars($product['model']); ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($product['warranty_provider'] ?: 'N/A'); ?></td>
                                <td><?php echo !empty($product['warranty_start_date']) ? date('d/m/Y', strtotime($product['warranty_start_date'])) : 'N/A'; ?></td>
                                <td><?php echo !empty($product['warranty_end_date']) ? date('d/m/Y', strtotime($product['warranty_end_date'])) : 'N/A'; ?></td>
                                <td><span class="badge <?php echo $warranty_status['class']; ?>"><?php echo $warranty_status['text']; ?></span></td>
                                <td><span class="badge <?php echo getProductStatusBadgeClass($product['status']); ?>"><?php echo getProductStatusText($product['status']); ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="openActionModal('view_product.php?id=<?php echo $product['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Visualizar"><i class="fas fa-eye"></i></button>
                                        <button type="button" class="btn btn-outline-info" onclick="openActionModal('edit_warranty.php?id=<?php echo $product['id']; ?>&modal=true', 'Editar Garantia: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Editar Garantia"><i class="fas fa-shield-alt"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="openActionModal('warranty_history_view.php?id=<?php echo $product['id']; ?>&modal=true', 'Histórico: <?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Ver Histórico"><i class="fas fa-history"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação de garantias" class="mt-4">
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

<!-- ===================================== ABA MÁQUINAS ===================================== -->
<?php elseif ($current_tab === 'machines'): ?>

<?php if (!empty($machines_error)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($machines_error); ?>
    </div>
<?php endif; ?>

<div class="card card-custom mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="tab" value="machines">
            <div class="col-md-4">
                <label for="search_machines" class="form-label form-label-custom"><i class="fas fa-search me-1"></i> Buscar</label>
                <input type="text" class="form-control form-control-custom" id="search_machines" name="search_machines" placeholder="Nome, Serial, Processador..." value="<?php echo htmlspecialchars($search_machines); ?>">
            </div>
            <div class="col-md-3">
                <label for="machines_status" class="form-label form-label-custom"><i class="fas fa-info-circle me-1"></i> Status</label>
                <select class="form-select form-control-custom" id="machines_status" name="machines_status">
                    <option value="">Todos</option>
                    <option value="available" <?php echo $machines_status_filter === 'available' ? 'selected' : ''; ?>>Disponível</option>
                    <option value="in_use" <?php echo $machines_status_filter === 'in_use' ? 'selected' : ''; ?>>Em Uso</option>
                    <option value="defective" <?php echo $machines_status_filter === 'defective' ? 'selected' : ''; ?>>Defeituoso</option>
                    <option value="maintenance" <?php echo $machines_status_filter === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-filter me-1"></i> Filtrar</button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <a href="?tab=machines" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Limpar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($machines)): ?>
    <div class="text-center py-5">
        <i class="fas fa-laptop fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhuma máquina encontrada</h5>
        <p class="text-muted">Adicione máquinas prontas ou ajuste seus filtros.</p>
    </div>
<?php else: ?>
    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-header-custom">
                        <tr>
                            <th>Máquina</th>
                            <th>Serial</th>
                            <th>Processador</th>
                            <th>Memória</th>
                            <th>Storage</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($machines as $machine): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-laptop fa-2x text-info"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($machine['name']); ?></h6>
                                            <?php if (!empty($machine['model'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($machine['model']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light p-2 rounded"><?php echo htmlspecialchars($machine['serial_number'] ?: 'N/A'); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($machine['processor'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($machine['memory'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($machine['storage'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        $status_classes = [
                                            'available' => 'bg-success',
                                            'in_use' => 'bg-warning text-dark',
                                            'defective' => 'bg-danger',
                                            'maintenance' => 'bg-info'
                                        ];
                                        echo $status_classes[$machine['status']] ?? 'bg-secondary';
                                    ?>">
                                        <?php 
                                            $status_texts = [
                                                'available' => 'Disponível',
                                                'in_use' => 'Em Uso',
                                                'defective' => 'Defeituoso',
                                                'maintenance' => 'Manutenção'
                                            ];
                                            echo $status_texts[$machine['status']] ?? ucfirst($machine['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="openActionModal('view_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Visualizar: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Visualizar"><i class="fas fa-eye"></i></button>
                                        <button type="button" class="btn btn-outline-info" onclick="openActionModal('edit_warranty_machine.php?id=<?php echo $machine['id']; ?>&modal=true', 'Editar Garantia: <?php echo htmlspecialchars(addslashes($machine['name'])); ?>')" title="Editar Garantia"><i class="fas fa-shield-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($machines_total_pages > 1): ?>
        <nav aria-label="Paginação de máquinas" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($machines_page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['machines_page' => 1])); ?>"><i class="fas fa-angle-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['machines_page' => $machines_page - 1])); ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif; ?>
                <?php 
                $machines_start_page = max(1, $machines_page - 2);
                $machines_end_page = min($machines_total_pages, $machines_page + 2);
                for ($i = $machines_start_page; $i <= $machines_end_page; $i++): 
                ?>
                    <li class="page-item <?php echo $i === $machines_page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['machines_page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($machines_page < $machines_total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['machines_page' => $machines_page + 1])); ?>"><i class="fas fa-angle-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['machines_page' => $machines_total_pages])); ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- ===================================== ABA TEMPLATES ===================================== -->
<?php elseif ($current_tab === 'templates'): ?>

<?php if ($_SESSION['user_role'] === 'admin'): ?>

<div class="row mb-4">
    <div class="col-md-8">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link <?php echo $filter_active === 'active' ? 'active' : ''; ?>" href="?tab=templates&filter=active">Ativos (<?php echo count(array_filter($templates, fn($t) => $t['is_active'])); ?>)</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $filter_active === 'inactive' ? 'active' : ''; ?>" href="?tab=templates&filter=inactive">Inativos (<?php echo count(array_filter($templates, fn($t) => !$t['is_active'])); ?>)</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $filter_active === 'all' ? 'active' : ''; ?>" href="?tab=templates&filter=all">Todos (<?php echo count($templates); ?>)</a></li>
        </ul>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
            <i class="fas fa-plus me-1"></i> Novo Template
        </button>
    </div>
</div>

<div class="row">
    <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> Nenhum template encontrado</div>
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
                            <div><strong>Período:</strong> <?php echo htmlspecialchars($template['period_value'] ?? ''); ?> 
                                <?php echo formatPeriodUnit($template['period_unit'] ?? 'months'); ?></div>
                            <?php if ($template['warranty_provider']): ?>
                                <div><strong>Fornecedor:</strong> <?php echo htmlspecialchars($template['warranty_provider']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($template['warranty_notes']): ?>
                            <div class="alert alert-light small mb-3">
                                <strong>Observações:</strong> <?php echo htmlspecialchars($template['warranty_notes']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" onclick="loadEditForm(<?php echo $template['id']; ?>)" data-bs-toggle="modal" data-bs-target="#editTemplateModal">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="duplicate_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-info"><i class="fas fa-copy"></i> Duplicar</button>
                            </form>
                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza?');">
                                <input type="hidden" name="action" value="toggle_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $template['is_active'] ? '1' : '0'; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $template['is_active'] ? 'warning' : 'success'; ?>">
                                    <i class="fas fa-<?php echo $template['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    <?php echo $template['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                </button>
                            </form>
                            <form action="" method="POST" class="d-inline" 
                                  onsubmit="return confirm('⚠️ ATENÇÃO! Excluir este template?\n\nEsta ação não pode ser desfeita!\n\nContinuar?');">
                                <input type="hidden" name="action" value="delete_template">
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

<!-- Modal: Criar Template -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Template de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create_template">
                <div class="modal-body">
                    <!-- Alerta de nome duplicado -->
                    <div id="duplicate-name-alert" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>⚠️ Atenção!</strong> Já existe um template com este nome.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="mb-3">
                        <label for="template_name" class="form-label">Nome do Template *</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="warranty_period_value" class="form-label">Período *</label>
                            <input type="number" class="form-control" id="warranty_period_value" name="warranty_period_value" value="12" min="1" required>
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

<!-- Modal: Editar Template -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Template de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="editForm">
                <input type="hidden" name="action" value="update_template">
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
                            <input type="number" class="form-control" id="edit_warranty_period_value" name="warranty_period_value" min="1" required>
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
function loadEditForm(templateId) {
    const template = document.querySelector(`[data-template-id="${templateId}"]`);
    // Implementar fetch AJAX se necessário
}
</script>

<?php else: ?>
    <div class="alert alert-warning"><i class="fas fa-lock"></i> Apenas administradores podem gerenciar templates</div>
<?php endif; ?>

<!-- ===================================== ABA FORNECEDORES ===================================== -->
<?php elseif ($current_tab === 'suppliers'): ?>

<?php if ($_SESSION['user_role'] === 'admin'): ?>

<?php
// GET - LISTAGEM DE FORNECEDORES
$pdo = getConnection();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = trim($_GET['search'] ?? '');
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "WHERE is_active = 1";
$params = [];

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR cnpj LIKE ? OR email LIKE ?)";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param];
}

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM warranty_suppliers {$where}");
$stmt->execute($params);
$total_suppliers = $stmt->fetch()['count'];
$total_pages_suppliers = ceil($total_suppliers / $per_page);

$stmt = $pdo->prepare("SELECT * FROM warranty_suppliers {$where} ORDER BY name ASC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$suppliers = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" href="?tab=suppliers">Ativos (<?php echo $total_suppliers; ?>)</a></li>
        </ul>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
            <i class="fas fa-plus me-1"></i> Novo Fornecedor
        </button>
    </div>
</div>

<!-- Busca -->
<div class="row mb-4">
    <div class="col-12">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="tab" value="suppliers">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CNPJ ou email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Buscar</button>
            </div>
            <?php if (!empty($search)): ?>
                <div class="col-md-3">
                    <a href="?tab=suppliers" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i> Limpar</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Grid de Cards -->
<div class="row">
    <?php if (empty($suppliers)): ?>
        <div class="col-12">
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> Nenhum fornecedor encontrado</div>
        </div>
    <?php else: ?>
        <?php foreach ($suppliers as $supplier): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($supplier['name']); ?></h5>
                            <span class="badge bg-success">Ativo</span>
                        </div>
                        
                        <div class="supplier-details mb-3 small">
                            <?php if ($supplier['cnpj']): ?>
                                <div><strong>CNPJ:</strong> <?php echo htmlspecialchars($supplier['cnpj']); ?></div>
                            <?php endif; ?>
                            <?php if ($supplier['email']): ?>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($supplier['email']); ?></div>
                            <?php endif; ?>
                            <?php if ($supplier['phone']): ?>
                                <div><strong>Telefone:</strong> <?php echo htmlspecialchars($supplier['phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($supplier['mobile']): ?>
                                <div><strong>Celular:</strong> <?php echo htmlspecialchars($supplier['mobile']); ?></div>
                            <?php endif; ?>
                            <?php if ($supplier['address_city']): ?>
                                <div><strong>Cidade:</strong> <?php echo htmlspecialchars($supplier['address_city']); ?> - <?php echo htmlspecialchars($supplier['address_state'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($supplier['warranty_policy']): ?>
                            <div class="alert alert-light small mb-3">
                                <strong>Política de Garantia:</strong> <?php echo htmlspecialchars(substr($supplier['warranty_policy'], 0, 80)) . (strlen($supplier['warranty_policy']) > 80 ? '...' : ''); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewSupplier(<?php echo $supplier['id']; ?>)">
                                <i class="fas fa-eye"></i> Visualizar
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="editSupplier(<?php echo $supplier['id']; ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(<?php echo $supplier['id']; ?>)">
                                <i class="fas fa-trash"></i> Deletar
                            </button>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-muted small">
                        <div class="d-flex justify-content-between">
                            <span>ID: <?php echo $supplier['id']; ?></span>
                            <span>Criado: <?php echo formatDatePT($supplier['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Paginação -->
<?php if ($total_pages_suppliers > 1): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?tab=suppliers&page=1"><i class="fas fa-angle-double-left"></i></a></li>
                <li class="page-item"><a class="page-link" href="?tab=suppliers&page=<?php echo $page - 1; ?>"><i class="fas fa-angle-left"></i></a></li>
            <?php endif; ?>
            
            <li class="page-item active"><span class="page-link"><?php echo $page; ?> / <?php echo $total_pages_suppliers; ?></span></li>
            
            <?php if ($page < $total_pages_suppliers): ?>
                <li class="page-item"><a class="page-link" href="?tab=suppliers&page=<?php echo $page + 1; ?>"><i class="fas fa-angle-right"></i></a></li>
                <li class="page-item"><a class="page-link" href="?tab=suppliers&page=<?php echo $total_pages_suppliers; ?>"><i class="fas fa-angle-double-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php else: ?>
    <div class="alert alert-warning"><i class="fas fa-lock"></i> Apenas administradores podem gerenciar fornecedores</div>
<?php endif; ?>

<?php endif; ?>

<!-- Modal: Criar Fornecedor -->
<div class="modal fade" id="createSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Fornecedor de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="createSupplierForm">
                <input type="hidden" name="action" value="create_supplier">
                <div class="modal-body">
                    <div id="cnpj-duplicate-alert" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <strong>⚠ CNPJ Duplicado!</strong> Já existe um fornecedor com este CNPJ.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_name" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplier_cnpj" class="form-label">CNPJ <small class="text-muted">(XX.XXX.XXX/0001-XX)</small></label>
                            <input type="text" class="form-control" id="supplier_cnpj" name="supplier_cnpj" placeholder="XX.XXX.XXX/0001-XX" maxlength="18" data-validate="cnpj">
                            <small id="cnpj-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_contact_person" class="form-label">Pessoa de Contato</label>
                            <input type="text" class="form-control" id="supplier_contact_person" name="supplier_contact_person">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplier_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="supplier_email" name="supplier_email" data-validate="email">
                            <small id="email-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_phone" class="form-label">Telefone <small class="text-muted">(XX) XXXX-XXXX</small></label>
                            <input type="tel" class="form-control" id="supplier_phone" name="supplier_phone" placeholder="(XX) XXXX-XXXX" maxlength="14">
                            <small id="phone-feedback" class="form-text"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplier_mobile" class="form-label">Celular <small class="text-muted">(XX) XXXXX-XXXX</small></label>
                            <input type="tel" class="form-control" id="supplier_mobile" name="supplier_mobile" placeholder="(XX) XXXXX-XXXX" maxlength="15">
                            <small id="mobile-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="supplier_website" name="supplier_website">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_address_street" class="form-label">Rua/Avenida</label>
                            <input type="text" class="form-control" id="supplier_address_street" name="supplier_address_street">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="supplier_address_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="supplier_address_number" name="supplier_address_number">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="supplier_address_complement" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="supplier_address_complement" name="supplier_address_complement" placeholder="Apt, Sala, etc">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_address_neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="supplier_address_neighborhood" name="supplier_address_neighborhood">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="supplier_address_city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="supplier_address_city" name="supplier_address_city">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="supplier_address_state" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="supplier_address_state" name="supplier_address_state" maxlength="2" placeholder="SP">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_address_postal_code" class="form-label">CEP <small class="text-muted">(XXXXX-XXX)</small></label>
                        <input type="text" class="form-control" id="supplier_address_postal_code" name="supplier_address_postal_code" placeholder="XXXXX-XXX" maxlength="9">
                        <small id="cep-feedback" class="form-text"></small>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_warranty_policy" class="form-label">Política de Garantia</label>
                        <textarea class="form-control" id="supplier_warranty_policy" name="supplier_warranty_policy" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_payment_terms" class="form-label">Termos de Pagamento</label>
                        <input type="text" class="form-control" id="supplier_payment_terms" name="supplier_payment_terms">
                    </div>
                    <div class="mb-3">
                        <label for="supplier_notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="supplier_notes" name="supplier_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="flex-grow-1">
                        <small id="validation-errors" class="text-danger d-block mb-2"></small>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="createSupplierSubmit">Criar Fornecedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Visualizar Fornecedor -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nome da Empresa</label>
                        <p id="view_supplier_name" class="text-break"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">CNPJ</label>
                        <p id="view_supplier_cnpj" class="text-break"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Pessoa de Contato</label>
                        <p id="view_supplier_contact_person" class="text-break"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <p id="view_supplier_email" class="text-break"><a id="view_supplier_email_link" href="#"></a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Telefone</label>
                        <p id="view_supplier_phone" class="text-break"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Celular</label>
                        <p id="view_supplier_mobile" class="text-break"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Website</label>
                    <p id="view_supplier_website" class="text-break"><a id="view_supplier_website_link" href="#" target="_blank"></a></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Rua/Avenida</label>
                        <p id="view_supplier_address_street" class="text-break"></p>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Nº</label>
                        <p id="view_supplier_address_number" class="text-break"></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Complemento</label>
                        <p id="view_supplier_address_complement" class="text-break"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Bairro</label>
                        <p id="view_supplier_address_neighborhood" class="text-break"></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Cidade</label>
                        <p id="view_supplier_address_city" class="text-break"></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Estado</label>
                        <p id="view_supplier_address_state" class="text-break"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">CEP</label>
                    <p id="view_supplier_address_postal_code" class="text-break"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Política de Garantia</label>
                    <p id="view_supplier_warranty_policy" class="text-break" style="white-space: pre-wrap;"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Termos de Pagamento</label>
                    <p id="view_supplier_payment_terms" class="text-break"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Observações</label>
                    <p id="view_supplier_notes" class="text-break" style="white-space: pre-wrap;"></p>
                </div>
                <div class="row text-muted small">
                    <div class="col-md-6">
                        <p><strong>Criado em:</strong> <span id="view_supplier_created_at"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Atualizado em:</strong> <span id="view_supplier_updated_at"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editFromView()">Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Fornecedor -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Fornecedor de Garantia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="editSupplierForm">
                <input type="hidden" name="action" value="update_supplier">
                <input type="hidden" name="supplier_id" id="edit_supplier_id">
                <div class="modal-body">
                    <div id="edit_cnpj-duplicate-alert" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <strong>⚠ CNPJ Duplicado!</strong> Já existe outro fornecedor com este CNPJ.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_name" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="edit_supplier_name" name="supplier_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_cnpj" class="form-label">CNPJ <small class="text-muted">(XX.XXX.XXX/0001-XX)</small></label>
                            <input type="text" class="form-control" id="edit_supplier_cnpj" name="supplier_cnpj" placeholder="XX.XXX.XXX/0001-XX" maxlength="18" data-validate="cnpj">
                            <small id="edit_cnpj-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_contact_person" class="form-label">Pessoa de Contato</label>
                            <input type="text" class="form-control" id="edit_supplier_contact_person" name="supplier_contact_person">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_supplier_email" name="supplier_email" data-validate="email">
                            <small id="edit_email-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_phone" class="form-label">Telefone <small class="text-muted">(XX) XXXX-XXXX</small></label>
                            <input type="tel" class="form-control" id="edit_supplier_phone" name="supplier_phone" placeholder="(XX) XXXX-XXXX" maxlength="14">
                            <small id="edit_phone-feedback" class="form-text"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_mobile" class="form-label">Celular <small class="text-muted">(XX) XXXXX-XXXX</small></label>
                            <input type="tel" class="form-control" id="edit_supplier_mobile" name="supplier_mobile" placeholder="(XX) XXXXX-XXXX" maxlength="15">
                            <small id="edit_mobile-feedback" class="form-text"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="edit_supplier_website" name="supplier_website">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_address_street" class="form-label">Rua/Avenida</label>
                            <input type="text" class="form-control" id="edit_supplier_address_street" name="supplier_address_street">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_supplier_address_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="edit_supplier_address_number" name="supplier_address_number">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_supplier_address_complement" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="edit_supplier_address_complement" name="supplier_address_complement" placeholder="Apt, Sala, etc">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_address_neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="edit_supplier_address_neighborhood" name="supplier_address_neighborhood">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_supplier_address_city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="edit_supplier_address_city" name="supplier_address_city">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_supplier_address_state" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="edit_supplier_address_state" name="supplier_address_state" maxlength="2" placeholder="SP">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_address_postal_code" class="form-label">CEP <small class="text-muted">(XXXXX-XXX)</small></label>
                        <input type="text" class="form-control" id="edit_supplier_address_postal_code" name="supplier_address_postal_code" placeholder="XXXXX-XXX" maxlength="9">
                        <small id="edit_cep-feedback" class="form-text"></small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_warranty_policy" class="form-label">Política de Garantia</label>
                        <textarea class="form-control" id="edit_supplier_warranty_policy" name="supplier_warranty_policy" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_payment_terms" class="form-label">Termos de Pagamento</label>
                        <input type="text" class="form-control" id="edit_supplier_payment_terms" name="supplier_payment_terms">
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="edit_supplier_notes" name="supplier_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="flex-grow-1">
                        <small id="edit_validation-errors" class="text-danger d-block mb-2"></small>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="editSupplierSubmit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>


<!-- Script para gráfico Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ===== VALIDAÇÕES E MÁSCARAS PARA FORNECEDOR =====

// CNPJ: Validação removida (agora é server-side apenas - PHP)

// Validar DDD
function validarDDD(ddd) {
    const num = ddd.replace(/\D/g, '');
    if (num.length < 2) return true;
    const ddds = ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28', '31', '32', '33', '34', '35', '37', '38', '39', '41', '42', '43', '44', '45', '46', '47', '48', '49', '51', '53', '54', '55', '61', '62', '63', '64', '65', '66', '67', '68', '69', '71', '73', '74', '75', '77', '79', '81', '82', '83', '84', '85', '86', '87', '88', '89', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
    return ddds.includes(num.substring(0, 2));
}

// Formatar telefone
function formatarTelefone(value, isCelular = false) {
    const num = value.replace(/\D/g, '');
    if (num.length <= 2) return num;
    if (num.length <= 6) return num.replace(/(\d{2})(\d+)/, '($1) $2');
    if (isCelular) {
        return num.replace(/(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
    } else {
        return num.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
    }
}

// Formatar CEP
function formatarCEP(value) {
    const num = value.replace(/\D/g, '');
    if (num.length <= 5) return num;
    return num.replace(/(\d{5})(\d+)/, '$1-$2');
}

// Buscar endereço por CEP
function buscarCEP(cep) {
    const num = cep.replace(/\D/g, '');
    if (num.length !== 8) return;
    
    const feedback = document.getElementById('cep-feedback');
    feedback.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando endereço...';
    feedback.className = 'form-text text-info';
    
    fetch(`https://viacep.com.br/ws/${num}/json/`)
        .then(r => r.json())
        .then(data => {
            if (data.erro) {
                feedback.innerHTML = '✗ CEP não encontrado';
                feedback.className = 'form-text text-danger';
            } else {
                document.getElementById('supplier_address_street').value = data.logradouro || '';
                document.getElementById('supplier_address_neighborhood').value = data.bairro || '';
                document.getElementById('supplier_address_city').value = data.localidade || '';
                document.getElementById('supplier_address_state').value = data.uf || '';
                
                feedback.innerHTML = '✓ Endereço preenchido automaticamente';
                feedback.className = 'form-text text-success';
                setTimeout(() => feedback.innerHTML = '', 3000);
            }
        })
        .catch(e => {
            feedback.innerHTML = '✗ Erro ao buscar CEP';
            feedback.className = 'form-text text-danger';
        });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // CNPJ - REMOVIDO: Validação agora é apenas server-side (PHP)
    
    // Telefone
    const phoneInput = document.getElementById('supplier_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = formatarTelefone(this.value, false);
            const feedback = document.getElementById('phone-feedback');
            const num = this.value.replace(/\D/g, '');
            if (num.length >= 2 && !validarDDD(num)) {
                feedback.innerHTML = '⚠ DDD inválido';
                feedback.className = 'form-text text-warning';
            } else if ((num.length === 10 || num.length === 11) && validarDDD(num)) {
                feedback.innerHTML = '✓ Válido';
                feedback.className = 'form-text text-success';
            } else {
                feedback.innerHTML = '';
            }
        });
    }
    
    // Celular
    const mobileInput = document.getElementById('supplier_mobile');
    if (mobileInput) {
        mobileInput.addEventListener('input', function() {
            this.value = formatarTelefone(this.value, true);
            const feedback = document.getElementById('mobile-feedback');
            const num = this.value.replace(/\D/g, '');
            if (num.length >= 2 && !validarDDD(num)) {
                feedback.innerHTML = '⚠ DDD inválido';
                feedback.className = 'form-text text-warning';
            } else if (num.length === 11 && validarDDD(num)) {
                feedback.innerHTML = '✓ Válido';
                feedback.className = 'form-text text-success';
            } else {
                feedback.innerHTML = '';
            }
        });
    }
    
    // CEP
    const cepInput = document.getElementById('supplier_address_postal_code');
    if (cepInput) {
        cepInput.addEventListener('input', function() {
            this.value = formatarCEP(this.value);
            const num = this.value.replace(/\D/g, '');
            if (num.length === 8) {
                buscarCEP(this.value);
            }
        });
    }
});

// ===== FUNÇÕES PARA FORNECEDORES =====
function viewSupplier(supplierId) {
    loadSupplierData(supplierId, 'view');
    const modal = new bootstrap.Modal(document.getElementById('viewSupplierModal'));
    modal.show();
}

function editSupplier(supplierId) {
    loadSupplierData(supplierId);
    const modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
    modal.show();
}

function deleteSupplier(supplierId) {
    if (!confirm('Tem certeza que deseja deletar este fornecedor?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierId);

    fetch('warranties.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        // Recarrega a página para mostrar a mensagem flash
        location.reload();
    })
    .catch(e => alert('Erro ao deletar: ' + e));
}

// Carregar dados do fornecedor para edição
function loadSupplierData(supplierId, mode = 'edit') {
    fetch('warranties.php?action=get_supplier&id=' + supplierId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.supplier;
                
                if (mode === 'view') {
                    // Modo visualização - preencher elementos read-only
                    document.getElementById('view_supplier_name').textContent = s.name || '-';
                    document.getElementById('view_supplier_cnpj').textContent = s.cnpj || '-';
                    document.getElementById('view_supplier_contact_person').textContent = s.contact_person || '-';
                    
                    const emailLink = document.getElementById('view_supplier_email_link');
                    if (s.email) {
                        emailLink.href = 'mailto:' + s.email;
                        emailLink.textContent = s.email;
                    } else {
                        emailLink.textContent = '-';
                    }
                    
                    document.getElementById('view_supplier_phone').textContent = s.phone || '-';
                    document.getElementById('view_supplier_mobile').textContent = s.mobile || '-';
                    
                    const websiteLink = document.getElementById('view_supplier_website_link');
                    if (s.website) {
                        websiteLink.href = s.website;
                        websiteLink.textContent = s.website;
                    } else {
                        websiteLink.textContent = '-';
                    }
                    
                    document.getElementById('view_supplier_address_street').textContent = s.address_street || '-';
                    document.getElementById('view_supplier_address_number').textContent = s.address_number || '-';
                    document.getElementById('view_supplier_address_complement').textContent = s.address_complement || '-';
                    document.getElementById('view_supplier_address_neighborhood').textContent = s.address_neighborhood || '-';
                    document.getElementById('view_supplier_address_city').textContent = s.address_city || '-';
                    document.getElementById('view_supplier_address_state').textContent = s.address_state || '-';
                    document.getElementById('view_supplier_address_postal_code').textContent = s.address_postal_code || '-';
                    document.getElementById('view_supplier_warranty_policy').textContent = s.warranty_policy || '-';
                    document.getElementById('view_supplier_payment_terms').textContent = s.payment_terms || '-';
                    document.getElementById('view_supplier_notes').textContent = s.notes || '-';
                    document.getElementById('view_supplier_created_at').textContent = s.created_at || '-';
                    document.getElementById('view_supplier_updated_at').textContent = s.updated_at || '-';
                    
                    // Armazenar ID para função editFromView
                    window.currentViewSupplierId = s.id;
                } else {
                    // Modo edição - preencher formulário
                    document.getElementById('edit_supplier_id').value = s.id;
                    document.getElementById('edit_supplier_name').value = s.name || '';
                    document.getElementById('edit_supplier_cnpj').value = s.cnpj || '';
                    document.getElementById('edit_supplier_contact_person').value = s.contact_person || '';
                    document.getElementById('edit_supplier_email').value = s.email || '';
                    document.getElementById('edit_supplier_phone').value = s.phone || '';
                    document.getElementById('edit_supplier_mobile').value = s.mobile || '';
                    document.getElementById('edit_supplier_website').value = s.website || '';
                    document.getElementById('edit_supplier_address_street').value = s.address_street || '';
                    document.getElementById('edit_supplier_address_number').value = s.address_number || '';
                    document.getElementById('edit_supplier_address_complement').value = s.address_complement || '';
                    document.getElementById('edit_supplier_address_neighborhood').value = s.address_neighborhood || '';
                    document.getElementById('edit_supplier_address_city').value = s.address_city || '';
                    document.getElementById('edit_supplier_address_state').value = s.address_state || '';
                    document.getElementById('edit_supplier_address_postal_code').value = s.address_postal_code || '';
                    document.getElementById('edit_supplier_warranty_policy').value = s.warranty_policy || '';
                    document.getElementById('edit_supplier_payment_terms').value = s.payment_terms || '';
                    document.getElementById('edit_supplier_notes').value = s.notes || '';
                    
                    // Aplicar validações aos campos de edição
                    setupEditValidations();
                }
            } else {
                alert('Erro ao carregar fornecedor: ' + data.message);
            }
        })
        .catch(e => alert('Erro: ' + e));
}

// Editar a partir do modal de visualização
function editFromView() {
    const supplierId = window.currentViewSupplierId;
    if (supplierId) {
        // Fechar modal de visualização
        bootstrap.Modal.getInstance(document.getElementById('viewSupplierModal')).hide();
        
        // Carregar dados para edição
        loadSupplierData(supplierId, 'edit');
        
        // Abrir modal de edição
        const editModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
        editModal.show();
    }
}

// Configurar validações para edição
function setupEditValidations() {
    // CNPJ - REMOVIDO: Validação agora é apenas server-side (PHP)
    
    const editPhoneInput = document.getElementById('edit_supplier_phone');
    if (editPhoneInput && !editPhoneInput.hasListener) {
        editPhoneInput.hasListener = true;
        editPhoneInput.addEventListener('input', function() {
            this.value = formatarTelefone(this.value, false);
            const feedback = document.getElementById('edit_phone-feedback');
            const num = this.value.replace(/\D/g, '');
            if (num.length >= 2 && !validarDDD(num)) {
                feedback.innerHTML = '⚠ DDD inválido';
                feedback.className = 'form-text text-warning';
            } else if ((num.length === 10 || num.length === 11) && validarDDD(num)) {
                feedback.innerHTML = '✓ Válido';
                feedback.className = 'form-text text-success';
            } else {
                feedback.innerHTML = '';
            }
        });
    }
    
    const editMobileInput = document.getElementById('edit_supplier_mobile');
    if (editMobileInput && !editMobileInput.hasListener) {
        editMobileInput.hasListener = true;
        editMobileInput.addEventListener('input', function() {
            this.value = formatarTelefone(this.value, true);
            const feedback = document.getElementById('edit_mobile-feedback');
            const num = this.value.replace(/\D/g, '');
            if (num.length >= 2 && !validarDDD(num)) {
                feedback.innerHTML = '⚠ DDD inválido';
                feedback.className = 'form-text text-warning';
            } else if (num.length === 11 && validarDDD(num)) {
                feedback.innerHTML = '✓ Válido';
                feedback.className = 'form-text text-success';
            } else {
                feedback.innerHTML = '';
            }
        });
    }
    
    const editCepInput = document.getElementById('edit_supplier_address_postal_code');
    if (editCepInput && !editCepInput.hasListener) {
        editCepInput.hasListener = true;
        editCepInput.addEventListener('input', function() {
            this.value = formatarCEP(this.value);
            const num = this.value.replace(/\D/g, '');
            if (num.length === 8) {
                buscarCEPEdit(this.value);
            }
        });
    }
}

// Buscar CEP para edição
function buscarCEPEdit(cep) {
    const num = cep.replace(/\D/g, '');
    if (num.length !== 8) return;
    
    const feedback = document.getElementById('edit_cep-feedback');
    feedback.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando endereço...';
    feedback.className = 'form-text text-info';
    
    fetch(`https://viacep.com.br/ws/${num}/json/`)
        .then(r => r.json())
        .then(data => {
            if (data.erro) {
                feedback.innerHTML = '✗ CEP não encontrado';
                feedback.className = 'form-text text-danger';
            } else {
                document.getElementById('edit_supplier_address_street').value = data.logradouro || '';
                document.getElementById('edit_supplier_address_neighborhood').value = data.bairro || '';
                document.getElementById('edit_supplier_address_city').value = data.localidade || '';
                document.getElementById('edit_supplier_address_state').value = data.uf || '';
                
                feedback.innerHTML = '✓ Endereço preenchido automaticamente';
                feedback.className = 'form-text text-success';
                setTimeout(() => feedback.innerHTML = '', 3000);
            }
        })
        .catch(e => {
            feedback.innerHTML = '✗ Erro ao buscar CEP';
            feedback.className = 'form-text text-danger';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    // Additional functionality can be added here
});

// Função para editar templates
function loadEditForm(templateId) {
    const templates = <?php echo json_encode($templates); ?>;
    const template = templates.find(t => t.id === templateId);
    if (template) {
        document.getElementById('edit_template_id').value = template.id;
        document.getElementById('edit_template_name').value = template.name;
        document.getElementById('edit_description').value = template.description || '';
        document.getElementById('edit_warranty_period_value').value = template.period_value;
        document.getElementById('edit_warranty_period_unit').value = template.period_unit;
        document.getElementById('edit_warranty_provider').value = template.warranty_provider || '';
        document.getElementById('edit_warranty_notes').value = template.warranty_notes || '';
    }
}

// ========================================
// VALIDAÇÃO DE FORNECEDOR EM TEMPO REAL
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Validação CREATE SUPPLIER
    const createSupplierForm = document.getElementById('createSupplierForm');
    const createSupplierSubmit = document.getElementById('createSupplierSubmit');
    const validationErrors = document.getElementById('validation-errors');
    
    if (createSupplierForm) {
        const cnpjInput = document.getElementById('supplier_cnpj');
        const emailInput = document.getElementById('supplier_email');
        const cnpjFeedback = document.getElementById('cnpj-feedback');
        const emailFeedback = document.getElementById('email-feedback');
        
        function validateCNPJ(cnpj) {
            if (!cnpj) return true; // Campo opcional
            const num = cnpj.replace(/\D/g, '');
            if (num.length !== 14) return false;
            if (/^(\d)\1{13}$/.test(num)) return false;
            // Validação básica do dígito verificador
            return num.match(/^\d{14}$/);
        }
        
        function validateEmail(email) {
            if (!email) return true; // Campo opcional
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        function checkValidation() {
            let errors = [];
            let isValid = true;
            
            // Validar CNPJ
            if (cnpjInput.value) {
                if (!validateCNPJ(cnpjInput.value)) {
                    cnpjFeedback.innerHTML = '✗ CNPJ inválido ou incorreto';
                    cnpjFeedback.className = 'form-text text-danger';
                    errors.push('CNPJ inválido');
                    isValid = false;
                } else {
                    // Verificar se CNPJ já existe via AJAX
                    const cnpjNum = cnpjInput.value.replace(/\D/g, '');
                    fetch(`?action=check_cnpj&cnpj=${encodeURIComponent(cnpjNum)}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.exists) {
                                cnpjFeedback.innerHTML = '✗ CNPJ já cadastrado';
                                cnpjFeedback.className = 'form-text text-danger';
                                document.getElementById('cnpj-duplicate-alert').classList.remove('d-none');
                                createSupplierSubmit.disabled = true;
                                createSupplierSubmit.style.opacity = '0.6';
                                createSupplierSubmit.title = 'CNPJ já existe no sistema';
                            } else {
                                cnpjFeedback.innerHTML = '✓ CNPJ válido';
                                cnpjFeedback.className = 'form-text text-success';
                                document.getElementById('cnpj-duplicate-alert').classList.add('d-none');
                                // Revalidar se não há outros erros
                                if (document.querySelectorAll('.form-text.text-danger').length === 0) {
                                    createSupplierSubmit.disabled = false;
                                    createSupplierSubmit.style.opacity = '1';
                                    createSupplierSubmit.title = '';
                                }
                            }
                        })
                        .catch(e => console.error('Erro ao verificar CNPJ:', e));
                }
            } else {
                cnpjFeedback.innerHTML = '';
                document.getElementById('cnpj-duplicate-alert').classList.add('d-none');
            }
            
            // Validar Email
            if (emailInput.value) {
                if (!validateEmail(emailInput.value)) {
                    emailFeedback.innerHTML = '✗ Email inválido';
                    emailFeedback.className = 'form-text text-danger';
                    errors.push('Email inválido');
                    isValid = false;
                } else {
                    emailFeedback.innerHTML = '✓ Email válido';
                    emailFeedback.className = 'form-text text-success';
                }
            } else {
                emailFeedback.innerHTML = '';
            }
            
            // Mostrar erro genérico (sem considerar duplicado pois já trata)
            let hasErrors = document.querySelectorAll('.form-text.text-danger').length > 0;
            if (hasErrors) {
                createSupplierSubmit.disabled = true;
                createSupplierSubmit.style.opacity = '0.6';
                createSupplierSubmit.title = 'Corrija os erros antes de salvar';
            } else if (!document.getElementById('cnpj-duplicate-alert').classList.contains('d-none')) {
                // Se há alert de duplicado, já desabilitou acima
            } else {
                createSupplierSubmit.disabled = false;
                createSupplierSubmit.style.opacity = '1';
                createSupplierSubmit.title = '';
            }
            
            return isValid;
        }
        
        // Event listeners
        cnpjInput.addEventListener('input', checkValidation);
        emailInput.addEventListener('input', checkValidation);
        
        // Validar ao submeter
        createSupplierForm.addEventListener('submit', function(e) {
            if (!checkValidation()) {
                e.preventDefault();
            }
        });
    }
    
    // Validação EDIT SUPPLIER
    const editSupplierForm = document.getElementById('editSupplierForm');
    const editSupplierSubmit = document.getElementById('editSupplierSubmit');
    const editValidationErrors = document.getElementById('edit_validation-errors');
    
    if (editSupplierForm) {
        const editCnpjInput = document.getElementById('edit_supplier_cnpj');
        const editEmailInput = document.getElementById('edit_supplier_email');
        const editCnpjFeedback = document.getElementById('edit_cnpj-feedback');
        const editEmailFeedback = document.getElementById('edit_email-feedback');
        
        function validateCNPJ(cnpj) {
            if (!cnpj) return true;
            const num = cnpj.replace(/\D/g, '');
            if (num.length !== 14) return false;
            if (/^(\d)\1{13}$/.test(num)) return false;
            return num.match(/^\d{14}$/);
        }
        
        function validateEmail(email) {
            if (!email) return true;
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        function checkValidation() {
            let errors = [];
            let isValid = true;
            const supplierId = document.getElementById('edit_supplier_id').value;
            
            if (editCnpjInput.value) {
                if (!validateCNPJ(editCnpjInput.value)) {
                    editCnpjFeedback.innerHTML = '✗ CNPJ inválido ou incorreto';
                    editCnpjFeedback.className = 'form-text text-danger';
                    errors.push('CNPJ inválido');
                    isValid = false;
                } else {
                    // Verificar se CNPJ já existe em outro fornecedor via AJAX
                    const cnpjNum = editCnpjInput.value.replace(/\D/g, '');
                    fetch(`?action=check_cnpj&cnpj=${encodeURIComponent(cnpjNum)}&exclude_id=${supplierId}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.exists) {
                                editCnpjFeedback.innerHTML = '✗ CNPJ já cadastrado em outro fornecedor';
                                editCnpjFeedback.className = 'form-text text-danger';
                                document.getElementById('edit_cnpj-duplicate-alert').classList.remove('d-none');
                                editSupplierSubmit.disabled = true;
                                editSupplierSubmit.style.opacity = '0.6';
                                editSupplierSubmit.title = 'CNPJ já existe em outro fornecedor';
                            } else {
                                editCnpjFeedback.innerHTML = '✓ CNPJ válido';
                                editCnpjFeedback.className = 'form-text text-success';
                                document.getElementById('edit_cnpj-duplicate-alert').classList.add('d-none');
                                if (document.querySelectorAll('.form-text.text-danger').length === 0) {
                                    editSupplierSubmit.disabled = false;
                                    editSupplierSubmit.style.opacity = '1';
                                    editSupplierSubmit.title = '';
                                }
                            }
                        })
                        .catch(e => console.error('Erro ao verificar CNPJ:', e));
                }
            } else {
                editCnpjFeedback.innerHTML = '';
                document.getElementById('edit_cnpj-duplicate-alert').classList.add('d-none');
            }
            
            if (editEmailInput.value) {
                if (!validateEmail(editEmailInput.value)) {
                    editEmailFeedback.innerHTML = '✗ Email inválido';
                    editEmailFeedback.className = 'form-text text-danger';
                    errors.push('Email inválido');
                    isValid = false;
                } else {
                    editEmailFeedback.innerHTML = '✓ Email válido';
                    editEmailFeedback.className = 'form-text text-success';
                }
            } else {
                editEmailFeedback.innerHTML = '';
            }
            
            if (errors.length > 0) {
                editValidationErrors.innerHTML = '⚠ Corrija os erros: ' + errors.join(', ');
                editSupplierSubmit.disabled = true;
                editSupplierSubmit.style.opacity = '0.6';
                editSupplierSubmit.title = 'Corrija os erros antes de salvar';
            } else {
                editValidationErrors.innerHTML = '';
                editSupplierSubmit.disabled = false;
                editSupplierSubmit.style.opacity = '1';
                editSupplierSubmit.title = '';
            }
            
            return isValid;
        }
        
        editCnpjInput.addEventListener('input', checkValidation);
        editEmailInput.addEventListener('input', checkValidation);
        
        editSupplierForm.addEventListener('submit', function(e) {
            if (!checkValidation()) {
                e.preventDefault();
            }
        });
    }
});

// ========================================
// VALIDAÇÃO DE NOME DUPLICADO EM TEMPO REAL
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const templates = <?php echo json_encode($templates); ?>;
    const templateNameInput = document.getElementById('template_name');
    const duplicateAlert = document.getElementById('duplicate-name-alert');
    const createBtn = document.getElementById('createTemplateBtn');
    const createTemplateForm = document.querySelector('#createTemplateModal form');

    if (templateNameInput && duplicateAlert && createBtn) {
        templateNameInput.addEventListener('input', function() {
            const name = this.value.trim().toLowerCase();
            
            // Verifica se há um template com o mesmo nome
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

        // Resetar ao fechar modal
        const createModal = document.getElementById('createTemplateModal');
        if (createModal) {
            createModal.addEventListener('hidden.bs.modal', function() {
                templateNameInput.value = '';
                duplicateAlert.classList.add('d-none');
                createBtn.disabled = false;
                createBtn.style.opacity = '1';
                createBtn.style.cursor = 'pointer';
            });
        }
    }
});

// ========================================
// EXPORTAR DADOS - DOWNLOAD CSV
// ========================================
function downloadExport(type) {
    const url = `export_warranties_csv.php?type=${type}`;
    const link = document.createElement('a');
    link.href = url;
    link.click();
    
    // Fechar modal após clique
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    if (modal) {
        modal.hide();
    }
}

// ========================================
// CSS PARA CARDS DE EXPORT
// ========================================
const style = document.createElement('style');
style.innerHTML = `
    .export-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .export-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .export-card .card-body {
        padding: 1.5rem;
    }
    
    .export-card i {
        transition: all 0.3s ease;
    }
    
    .export-card:hover i {
        transform: scale(1.1);
    }
    
    .cursor-pointer {
        cursor: pointer;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>