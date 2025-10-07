<?php
ob_start();
// ========================================
// ARQUIVO DE CONFIGURAÇÃO DO SISTEMA (VERSÃO CORRIGIDA COM LOGS DETALHADOS)
// ========================================

// ========================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ========================================
define("DB_HOST", "db");
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
    return isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin";
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
// FUNÇÃO CENTRAL DE LOGS
// ========================================

function logProductMovement($productId, $userId, $movementType, $quantity, $previousQuantity, $newQuantity, $reason) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO product_movements (product_id, user_id, movement_type, quantity, previous_quantity, new_quantity, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
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
        error_log("FATAL: Erro ao registrar log de movimento de produto no banco de dados: " . $e->getMessage());
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

// Limpa qualquer saída acidental antes de o script terminar
$output = ob_get_clean();
if (!empty($output)) {
    // Apenas loga, mas não exibe para não quebrar respostas JSON
    error_log("Saída inesperada em config.php: " . $output);
}
?>