<?php
/**
 * Função para registrar logs no sistema
 * * @param string $action Ação realizada
 * @param string $details Detalhes da ação
 * @param int|null $user_id ID do usuário (opcional)
 * @return bool True se o log foi registrado com sucesso
 */
function logSystemAction($action, $details = null, $user_id = null, $table_name = null, $record_id = null) {
    try {
        $pdo = getConnection();
        
        // Se user_id não foi fornecido, tenta pegar da sessão
        if ($user_id === null && isset($_SESSION["user_id"])) {
            $user_id = $_SESSION["user_id"];
        }
        
        // Pega o IP do usuário
        $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_X_REAL_IP"])) {
            $ip_address = $_SERVER["HTTP_X_REAL_IP"];
        }
        
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, table_name, record_id) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $action, $details, $ip_address, $table_name, $record_id]);
        
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra log de login
 */
function logLogin($user_id, $username) {
    return logSystemAction("login", "Usuário ".$username." fez login no sistema", $user_id, "users", $user_id);
}

/**
 * Registra log de logout
 */
function logLogout($user_id, $username) {
    return logSystemAction("logout", "Usuário ".$username." fez logout do sistema", $user_id, "users", $user_id);
}

/**
 * Registra log de criação de usuário
 */
function logUserCreated($created_user_id, $created_username, $creator_user_id = null) {
    return logSystemAction("user_created", "Usuário ".$created_username." foi criado (ID: $created_user_id)", $creator_user_id, "users", $created_user_id);
}

/**
 * Registra log de produto adicionado
 */
function logProductAdded($product_id, $product_name, $quantity = null) {
    $details = "Produto ".$product_name." foi adicionado (ID: $product_id)";
    if ($quantity !== null) {
        $details .= " com quantidade: $quantity";
    }
    return logSystemAction("product_added", $details, null, "products", $product_id);
}

/**
 * Registra log de aumento de estoque
 */
function logProductStockIncreased($product_id, $product_name, $old_quantity, $new_quantity) {
    $details = "Estoque do produto ".$product_name." (ID: $product_id) aumentado de $old_quantity para $new_quantity";
    return logSystemAction("product_stock_increased", $details, null, "products", $product_id);
}

/**
 * Registra log de redução de estoque
 */
function logProductStockDecreased($product_id, $product_name, $old_quantity, $new_quantity) {
    $details = "Estoque do produto ".$product_name." (ID: $product_id) reduzido de $old_quantity para $new_quantity";
    return logSystemAction("product_stock_decreased", $details, null, "products", $product_id);
}

/**
 * Registra log de produto excluído
 */
function logProductDeleted($product_id, $product_name) {
    return logSystemAction("product_deleted", "Produto ".$product_name." foi excluído (ID: $product_id)", null, "products", $product_id);
}

/**
 * Registra log de baixa de produto
 */
function logProductOut($product_id, $product_name, $quantity, $action_type, $reason, $observations = "") {
    $action_names = [
        "sold" => "Vendido",
        "used" => "Utilizado/Instalado",
        "damaged" => "Danificado",
        "expired" => "Vencido",
        "returned" => "Devolvido",
        "disposed" => "Descartado",
        "transferred" => "Transferido",
        "other" => "Outro"
    ];
    
    $action_name = $action_names[$action_type] ?? "Baixa";
    $details = "$action_name: $quantity unidade(s) do produto ".$product_name." (ID: $product_id) - Motivo: $reason";
    
    if (!empty($observations)) {
        $details .= " - Observações: $observations";
    }
    
    return logSystemAction("product_out", $details, null, "products", $product_id);
}

/**
 * Registra log de máquina criada
 */
function logMachineCreated($machine_id, $machine_name) {
    return logSystemAction("machine_created", "Máquina ".$machine_name." foi criada (ID: $machine_id)", null, "machines", $machine_id);
}

/**
 * Registra log de máquina excluída
 */
function logMachineDeleted($machine_id, $machine_name) {
    return logSystemAction("machine_deleted", "Máquina ".$machine_name." foi excluída (ID: $machine_id)", null, "machines", $machine_id);
}

/**
 * Registra log de máquina vendida
 */
function logMachineSold($machine_id, $machine_name) {
    return logSystemAction("machine_sold", "Máquina ".$machine_name." foi vendida (ID: $machine_id)", null, "machines", $machine_id);
}

/**
 * Registra uma nova entrada de produto na tabela de logs de entrada.
 */
function logProductInput($product_id, $user_id, $quantity_added, $reason, $details, $product_name, $product_category, $product_serial_number, $product_barcode, $unit_price = null) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO product_inputs (product_id, user_id, quantity_added, reason, details, product_name, product_category, product_serial_number, product_barcode, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $quantity_added, $reason, $details, $product_name, $product_category, $product_serial_number, $product_barcode, $unit_price]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar entrada de produto: " . $e->getMessage());
    }
}

/**
 * Registra uma nova entrada de máquina na tabela de logs de entrada.
 */
function logMachineInput($machine_id, $user_id, $quantity_added, $reason, $details, $machine_name, $machine_serial_number, $machine_cost_price) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO machine_inputs (machine_id, user_id, quantity_added, reason, details, machine_name, machine_serial_number, machine_cost_price) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$machine_id, $user_id, $quantity_added, $reason, $details, $machine_name, $machine_serial_number, $machine_cost_price]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar entrada de máquina: " . $e->getMessage());
    }
}