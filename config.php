<?php
ob_start();
// ========================================
// ARQUIVO DE CONFIGURAÇÃO DO SISTEMA (VERSÃO CORRIGIDA COM LOGS DETALHADOS)
// ========================================

// ========================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ========================================
define("DB_HOST", "localhost");
define("DB_NAME", "it_inventory");
define("DB_USER", "admin");
define("DB_PASS", "@#8520@#");
define("DB_CHARSET", "utf8mb4");

// ========================================
// FUNÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ========================================
function getConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em caso de falha na conexão, usa configurações padrão e exibe erro
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            die("Erro de conexão com o banco de dados. Verifique as configurações em config.php e se o serviço MySQL está em execução.");
        }
    }
    return $pdo;
}

// ========================================
// CARREGAMENTO DAS CONFIGURAÇÕES GERAIS DO SISTEMA
// ========================================
$system_settings_defaults = [
    "system_name" => "Sistema de Estoque TI",
    "company_name" => "Sua Empresa",
    "company_slogan" => "Controle total do seu estoque",
    "theme_color" => "blue",
];

try {
    $pdo = getConnection();
    // Garante que a tabela de configurações exista para evitar erros na primeira execução
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT
    )");
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $system_settings = array_merge($system_settings_defaults, $db_settings);
} catch (PDOException $e) {
    error_log("Erro ao carregar configurações do banco: " . $e->getMessage());
    $system_settings = $system_settings_defaults;
}

// Definição das constantes globais
define("SITE_NAME", $system_settings["system_name"]);
define("COMPANY_NAME", $system_settings["company_name"]);
define("COMPANY_SLOGAN", $system_settings["company_slogan"]);

// ========================================
// CONFIGURAÇÕES DE SESSÃO
// ========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// FUNÇÕES DE AUTENTICAÇÃO
// ========================================
function isLoggedIn() {
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

function isAdmin() {
    return isset($_SESSION["user_role"]) && ($_SESSION["user_role"] === "admin" || $_SESSION["user_role"] === "administrativo");
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// ========================================
// CARREGAMENTO DE FUNÇÕES AUXILIARES
// ========================================
// Incluir funções de garantia se o arquivo existir
if (file_exists(__DIR__ . '/includes/warranty_functions.php')) {
    require_once __DIR__ . '/includes/warranty_functions.php';
}

// ========================================
// FUNÇÃO CENTRAL DE LOGS
// ========================================

function logProductMovement($productId, $userId, $movementType, $quantity, $previousQuantity, $newQuantity, $reason, $type = 'product') {
    try {
        $pdo = getConnection();
        
        if ($type === 'warehouse') {
            $stmt = $pdo->prepare("
                INSERT INTO warehouse_movements (warehouse_id, user_id, movement_type, quantity, previous_quantity, new_quantity, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_movements (product_id, user_id, movement_type, quantity, previous_quantity, new_quantity, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
        }
        
        $stmt->execute([
            $productId,
            $userId,
            $movementType,
            $quantity,
            $previousQuantity,
            $newQuantity,
            $reason
        ]);
    } catch (PDOException $e) {
        error_log("FATAL: Erro ao registrar log de movimento no banco de dados: " . $e->getMessage());
    }
}

function logMachineMovement($machineId, $userId, $movementType, $oldStatus, $newStatus, $details) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO machine_movements (machine_id, user_id, movement_type, old_status, new_status, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $machineId,
            $userId,
            $movementType,
            $oldStatus,
            $newStatus,
            $details
        ]);
    } catch (PDOException $e) {
        error_log("FATAL: Erro ao registrar log de movimento de máquina no banco de dados: " . $e->getMessage());
    }
}

/**
 * Função para logs de administrador (ações administrativas específicas)
 * **VERSÃO ATUALIZADA PARA SUPORTAR DETALHES DE ALTERAÇÕES**
 */
function logAdminActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $pdo = getConnection();
        $ip_address = $_SERVER["REMOTE_ADDR"] ?? '127.0.0.1';
        $user_agent = $_SERVER["HTTP_USER_AGENT"] ?? 'N/A';
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (user_id, action, table_name, record_id, ip_address, user_agent, old_values, new_values)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $ip_address,
            $user_agent,
            $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
            $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null
        ]);
    } catch (PDOException $e) {
        error_log("FATAL: Erro ao registrar log de administrador no banco de dados: " . $e->getMessage());
    }
}

/**
 * Registra alterações de garantia no histórico
 * Suporta: produto, máquina, armazém
 * 
 * @param PDO $pdo Conexão com banco
 * @param int $item_id ID do item (produto/máquina/armazém)
 * @param string $action Tipo de ação (CREATE, UPDATE, DELETE, CLAIM)
 * @param array $old_data Dados anteriores
 * @param array $new_data Dados novos
 * @param int $user_id ID do usuário
 * @param string $type Tipo de item: 'product', 'machine', 'warehouse'
 */
function registerWarrantyHistory($pdo, $item_id, $action, $old_data, $new_data, $user_id, $type = 'product') {
    try {
        // Mapear tipo para coluna correta
        $column_map = [
            'product' => 'product_id',
            'machine' => 'machine_id',
            'warehouse' => 'warehouse_id'
        ];
        
        if (!isset($column_map[$type])) {
            throw new Exception("Tipo de item inválido: $type");
        }
        
        $id_column = $column_map[$type];
        
        // Campos de garantia a rastrear
        $warranty_fields = [
            'warranty_provider',
            'warranty_start_date',
            'warranty_end_date',
            'warranty_period_value',
            'warranty_period_unit',
            'warranty_notes',
            'warranty_ticket_number',
            'warranty_label',
            'warranty_client_name',
            'warranty_supplier_id',
            'invoice_number'
        ];
        
        // Extrai apenas campos de garantia
        $old_warranty = [];
        $new_warranty = [];
        
        foreach ($warranty_fields as $field) {
            if (isset($old_data[$field])) {
                $old_warranty[$field] = $old_data[$field];
            }
            if (isset($new_data[$field])) {
                $new_warranty[$field] = $new_data[$field];
            }
        }
        
        // Monta o JSON para armazenar
        $old_json = !empty($old_warranty) ? json_encode($old_warranty, JSON_UNESCAPED_UNICODE) : null;
        $new_json = !empty($new_warranty) ? json_encode($new_warranty, JSON_UNESCAPED_UNICODE) : null;
        
        // Insere na warranty_history
        $sql = "INSERT INTO warranty_history ({$id_column}, user_id, action_type, old_values, new_values, change_description, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $item_id,
            $user_id,
            $action,
            $old_json,
            $new_json,
            "Alteração de garantia registrada automaticamente"
        ]);
        
        error_log("✅ Histórico de garantia registrado: {$type} ID={$item_id}, ação={$action}");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Erro ao registrar histórico de garantia: " . $e->getMessage());
        return false;
    }
}

// ========================================
// FUNÇÕES DE SOFT DELETE
// ========================================

/**
 * Faz soft delete de um produto (marca como deletado em vez de remover do BD)
 * @param int $productId ID do produto
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function softDeleteProduct($productId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados do produto antes de deletar
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_deleted = FALSE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return false;
        }
        
        // Marca como deletado e desativa a garantia
        $update_stmt = $pdo->prepare("
            UPDATE products
            SET is_deleted = TRUE,
                deleted_at = NOW(),
                has_warranty = 0
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$productId]);
        
        if ($result) {
            logAdminActivity($userId, "SOFT_DELETE", "products", $productId, $product, null);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao fazer soft delete de produto: " . $e->getMessage());
        return false;
    }
}

/**
 * Restaura um produto deletado (soft delete)
 * @param int $productId ID do produto
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function restoreProduct($productId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados do produto antes de restaurar
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_deleted = TRUE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return false;
        }
        
        // Restaura o produto
        $update_stmt = $pdo->prepare("
            UPDATE products 
            SET is_deleted = FALSE, deleted_at = NULL
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$productId]);
        
        if ($result) {
            $newData = array_merge($product, ['is_deleted' => false, 'deleted_at' => null]);
            logAdminActivity($userId, "RESTORE", "products", $productId, null, $newData);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao restaurar produto: " . $e->getMessage());
        return false;
    }
}

/**
 * Faz soft delete de uma máquina (marca como deletada em vez de remover do BD)
 * @param int $machineId ID da máquina
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function softDeleteMachine($machineId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados da máquina antes de deletar
        $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? AND is_deleted = FALSE");
        $stmt->execute([$machineId]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            return false;
        }
        
        // Marca como deletada e desativa a garantia
        $update_stmt = $pdo->prepare("
            UPDATE ready_machines
            SET is_deleted = TRUE,
                deleted_at = NOW(),
                has_warranty = 0
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$machineId]);
        
        if ($result) {
            logAdminActivity($userId, "SOFT_DELETE", "ready_machines", $machineId, $machine, null);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao fazer soft delete de máquina: " . $e->getMessage());
        return false;
    }
}

/**
 * Restaura uma máquina deletada (soft delete)
 * @param int $machineId ID da máquina
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function restoreMachine($machineId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados da máquina antes de restaurar
        $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? AND is_deleted = TRUE");
        $stmt->execute([$machineId]);
        $machine = $stmt->fetch();
        
        if (!$machine) {
            return false;
        }
        
        // Restaura a máquina
        $update_stmt = $pdo->prepare("
            UPDATE ready_machines 
            SET is_deleted = FALSE, deleted_at = NULL
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$machineId]);
        
        if ($result) {
            $newData = array_merge($machine, ['is_deleted' => false, 'deleted_at' => null]);
            logAdminActivity($userId, "RESTORE", "ready_machines", $machineId, null, $newData);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao restaurar máquina: " . $e->getMessage());
        return false;
    }
}

// ========================================
// FUNÇÕES DE NOTIFICAÇÃO DE ESTOQUE BAIXO
// ========================================

/**
 * Verifica se um produto tem estoque baixo e registra alerta se necessário
 * @param int $productId ID do produto
 * @return array|null Dados do alerta ou null se tudo OK
 */
function checkAndLogLowStock($productId, $type = 'product') {
    try {
        $pdo = getConnection();
        
        $table = ($type === 'warehouse') ? 'warehouse' : 'products';
        
        $stmt = $pdo->prepare("
            SELECT id, name, quantity, min_quantity, category 
            FROM $table
            WHERE id = ? AND is_deleted = FALSE
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return null;
        }
        
        // Se quantidade está acima do mínimo, está tudo bem
        if ($product['quantity'] > $product['min_quantity']) {
            return null;
        }
        
        // Determina o tipo de alerta
        $alert_type = ($product['quantity'] <= ($product['min_quantity'] * 0.5)) ? 'critical' : 'warning';
        
        // Verifica se já existe alerta ativo para este produto
        $check_stmt = $pdo->prepare("
            SELECT id FROM low_stock_logs 
            WHERE product_id = ? AND status = 'active' AND alert_type = ?
        ");
        $check_stmt->execute([$productId, $alert_type]);
        $existing_alert = $check_stmt->fetch();
        
        // Se não existe alerta, cria um novo
        if (!$existing_alert) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO low_stock_logs (product_id, current_quantity, min_quantity, alert_type, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            $insert_stmt->execute([
                $productId,
                $product['quantity'],
                $product['min_quantity'],
                $alert_type
            ]);
        }
        
        return [
            'product_id' => $productId,
            'name' => $product['name'],
            'quantity' => $product['quantity'],
            'min_quantity' => $product['min_quantity'],
            'alert_type' => $alert_type
        ];
        
    } catch (PDOException $e) {
        error_log("Erro ao verificar estoque baixo: " . $e->getMessage());
        return null;
    }
}

/**
 * Retorna lista de produtos com estoque baixo
 * @param string $status 'active' ou 'all'
 * @return array Lista de produtos com estoque baixo
 */
function getLowStockProducts($status = 'active') {
    try {
        $pdo = getConnection();
        
        $query = "
            SELECT DISTINCT
                p.id,
                p.name,
                p.category,
                p.quantity,
                p.min_quantity,
                CASE 
                    WHEN p.quantity <= (p.min_quantity * 0.5) THEN 'critical'
                    WHEN p.quantity <= p.min_quantity THEN 'warning'
                    ELSE 'ok'
                END AS alert_type,
                MAX(l.created_at) as last_alert
            FROM products p
            LEFT JOIN low_stock_logs l ON p.id = l.product_id
            WHERE p.is_deleted = FALSE AND p.quantity <= p.min_quantity
        ";
        
        if ($status === 'active') {
            $query .= " AND (l.status = 'active' OR l.id IS NULL)";
        }
        
        $query .= " GROUP BY p.id ORDER BY p.quantity ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Erro ao obter produtos com estoque baixo: " . $e->getMessage());
        return [];
    }
}

/**
 * Resolve um alerta de estoque baixo (marca como resolvido)
 * @param int $productId ID do produto
 * @param int $userId ID do usuário
 * @param string $notes Notas sobre a resolução
 * @return bool True se bem-sucedido
 */
function resolveLowStockAlert($productId, $userId, $notes = '') {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE low_stock_logs
            SET status = 'resolved', resolved_at = NOW(), resolved_by_user_id = ?, notes = ?
            WHERE product_id = ? AND status = 'active'
        ");
        
        return $stmt->execute([$userId, $notes, $productId]);
        
    } catch (PDOException $e) {
        error_log("Erro ao resolver alerta de estoque baixo: " . $e->getMessage());
        return false;
    }
}

/**
 * Faz soft delete de um item do warehouse
 * @param int $warehouseId ID do item warehouse
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function softDeleteWarehouse($warehouseId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados do item antes de deletar
        $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? AND is_deleted = FALSE");
        $stmt->execute([$warehouseId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        // Marca como deletado e desativa a garantia
        $update_stmt = $pdo->prepare("
            UPDATE warehouse
            SET is_deleted = TRUE,
                deleted_at = NOW(),
                has_warranty = 0
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$warehouseId]);
        
        if ($result) {
            logAdminActivity($userId, "SOFT_DELETE", "warehouse", $warehouseId, $item, null);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao fazer soft delete de item warehouse: " . $e->getMessage());
        return false;
    }
}

/**
 * Restaura um item de warehouse deletado (soft delete)
 * @param int $warehouseId ID do item do warehouse
 * @param int $userId ID do usuário realizando a ação
 * @return bool True se bem-sucedido
 */
function restoreWarehouse($warehouseId, $userId) {
    try {
        $pdo = getConnection();
        
        // Busca dados do item antes de restaurar
        $stmt = $pdo->prepare("SELECT * FROM warehouse WHERE id = ? AND is_deleted = TRUE");
        $stmt->execute([$warehouseId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        // Restaura o item
        $update_stmt = $pdo->prepare("
            UPDATE warehouse 
            SET is_deleted = FALSE, deleted_at = NULL
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$warehouseId]);
        
        if ($result) {
            $newData = array_merge($item, ['is_deleted' => false, 'deleted_at' => null]);
            logAdminActivity($userId, "RESTORE", "warehouse", $warehouseId, null, $newData);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao restaurar item warehouse: " . $e->getMessage());
        return false;
    }
}

/**
 * Restaura uma garantia deletada (soft delete recovery)
 * Apenas para usuários administrativos
 */
function restoreWarranty($warrantyId, $userId) {
    try {
        $pdo = getConnection();

        // Busca dados da garantia antes de restaurar
        $stmt = $pdo->prepare("
            SELECT w.*,
                   p.name as product_name,
                   m.name as machine_name
            FROM warranties w
            LEFT JOIN products p ON w.product_id = p.id
            LEFT JOIN ready_machines m ON w.machine_id = m.id
            WHERE w.id = ? AND w.is_deleted = TRUE
        ");
        $stmt->execute([$warrantyId]);
        $warranty = $stmt->fetch();

        if (!$warranty) {
            return false;
        }

        // Restaura a garantia
        $update_stmt = $pdo->prepare("
            UPDATE warranties
            SET is_deleted = FALSE,
                deleted_at = NULL,
                deleted_by = NULL
            WHERE id = ?
        ");
        $result = $update_stmt->execute([$warrantyId]);

        if ($result) {
            // Registrar no histórico de garantias se a tabela existir
            try {
                $item_type = $warranty['product_id'] ? 'product' : 'machine';
                $item_id = $warranty['product_id'] ?: $warranty['machine_id'];
                $item_name = $warranty['product_name'] ?: $warranty['machine_name'];

                $stmt = $pdo->prepare("
                    INSERT INTO warranty_history
                    (warranty_id, item_type, item_id, item_name, action, changed_by, changed_at)
                    VALUES (?, ?, ?, ?, 'restored', ?, NOW())
                ");
                $stmt->execute([
                    $warrantyId,
                    $item_type,
                    $item_id,
                    $item_name,
                    $userId
                ]);
            } catch (PDOException $e) {
                // Se a tabela warranty_history não existir, apenas loga o erro
                error_log("Aviso: não foi possível registrar no histórico de garantias: " . $e->getMessage());
            }

            $newData = array_merge($warranty, ['is_deleted' => false, 'deleted_at' => null, 'deleted_by' => null]);
            logAdminActivity($userId, "RESTORE", "warranties", $warrantyId, null, $newData);
            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Erro ao restaurar garantia: " . $e->getMessage());
        return false;
    }
}

// Limpa qualquer saída acidental antes de o script terminar
$output = ob_get_clean();
if (!empty($output)) {
    // Apenas loga, mas não exibe para não quebrar respostas JSON
    error_log("Saída inesperada em config.php: " . $output);
}
?>