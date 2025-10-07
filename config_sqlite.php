<?php
// ========================================
// ARQUIVO DE CONFIGURAÇÃO DO SISTEMA - SQLite
// ========================================
// Este arquivo contém as configurações principais do sistema para testes

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// CONFIGURAÇÕES DO BANCO DE DADOS - SQLite
// ========================================
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/database.sqlite');

// ========================================
// CONFIGURAÇÕES DO SISTEMA
// ========================================
define('SITE_NAME', 'Sistema de Estoque TI');
define('COMPANY_NAME', 'Sua Empresa');
define('COMPANY_SLOGAN', 'Controle total do seu estoque');
define('COMPANY_LOGO', '');

// ========================================
// CONFIGURAÇÕES DE SEGURANÇA
// ========================================
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// ========================================
// FUNÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ========================================
function getConnection() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Cria as tabelas se não existirem
        createTables($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
        die("Erro de conexão com o banco de dados.");
    }
}

// ========================================
// FUNÇÃO PARA CRIAR TABELAS
// ========================================
function createTables($pdo) {
    // Tabela de usuários
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role VARCHAR(20) DEFAULT 'user',
            full_name VARCHAR(255),
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela de produtos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL,
            manufacturer VARCHAR(100),
            model VARCHAR(100),
            sku VARCHAR(100) UNIQUE,
            barcode VARCHAR(100) UNIQUE,
            qr_code VARCHAR(100) UNIQUE,
            serial_number VARCHAR(100) UNIQUE,
            quantity INTEGER NOT NULL DEFAULT 0,
            min_quantity INTEGER DEFAULT 0,
            max_quantity INTEGER DEFAULT 0,
            price DECIMAL(10, 2),
            location VARCHAR(100),
            status VARCHAR(50) DEFAULT 'available',
            image VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela de máquinas prontas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ready_machines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            processor VARCHAR(255),
            memory VARCHAR(255),
            storage VARCHAR(255),
            graphics VARCHAR(255),
            motherboard VARCHAR(255),
            power_supply VARCHAR(255),
            case_type VARCHAR(255),
            specifications TEXT,
            sale_price DECIMAL(10, 2) NOT NULL,
            cost_price DECIMAL(10, 2),
            serial_number VARCHAR(100) UNIQUE,
            barcode VARCHAR(100) UNIQUE,
            qr_code VARCHAR(100) UNIQUE,
            status VARCHAR(50) DEFAULT 'available',
            location VARCHAR(100),
            image VARCHAR(255),
            notes TEXT,
            windows_10_compatible BOOLEAN DEFAULT FALSE,
            windows_11_compatible BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela de configurações do sistema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela de logs do sistema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Cria usuário admin padrão se não existir
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin123', 'admin@sistema.com', 'admin', 'Administrador']);
    }
}

// ========================================
// FUNÇÕES DE AUTENTICAÇÃO
// ========================================
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// ========================================
// FUNÇÕES AUXILIARES
// ========================================
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>

