DROP DATABASE IF EXISTS it_inventory;
CREATE DATABASE it_inventory;
USE it_inventory;

-- ========================================
-- TABELA DE USUÁRIOS DO SISTEMA
-- ========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    full_name VARCHAR(255),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- TABELA DE PRODUTOS EM ESTOQUE
-- ========================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100) UNIQUE,
    qr_code VARCHAR(100) UNIQUE,
    serial_number VARCHAR(100) UNIQUE,
    quantity INT NOT NULL DEFAULT 0,
    min_quantity INT DEFAULT 0,
    max_quantity INT DEFAULT 0,
    price DECIMAL(10, 2),
    location VARCHAR(100),
    status VARCHAR(50) DEFAULT 'available',
    image VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- TABELA DE MÁQUINAS PRONTAS PARA VENDA
-- ========================================
CREATE TABLE ready_machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    quantity INT NOT NULL DEFAULT 1,
    status VARCHAR(50) DEFAULT 'available',
    location VARCHAR(100),
    image VARCHAR(255),
    notes TEXT,
    windows_10_compatible BOOLEAN DEFAULT FALSE,
    windows_11_compatible BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- TABELA DE CONFIGURAÇÕES DO SISTEMA
-- ========================================
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- TABELA DE LOGS DO SISTEMA
-- ========================================
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABELA DE LOGS DE ADMINISTRADOR
-- ========================================
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABELA DE MOVIMENTAÇÕES DE PRODUTOS
-- ========================================
CREATE TABLE product_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    movement_type VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    reason TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABELA DE MOVIMENTAÇÕES DE MÁQUINAS
-- ========================================
CREATE TABLE machine_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    user_id INT,
    movement_type VARCHAR(50) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    details TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES ready_machines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABELA DE SAÍDAS DE PRODUTOS
-- ========================================
CREATE TABLE product_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    quantity_removed INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    details TEXT,
    output_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    product_name VARCHAR(255),
    product_category VARCHAR(100),
    product_serial_number VARCHAR(100),
    product_barcode VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABELA DE SAÍDAS DE MÁQUINAS
-- ========================================
CREATE TABLE machine_outputs (
  id int(11) NOT NULL AUTO_INCREMENT,
  machine_id int(11) NOT NULL,
  user_id int(11) DEFAULT NULL,
  quantity_removed int(11) NOT NULL DEFAULT 1,
  reason varchar(255) NOT NULL,
  details text DEFAULT NULL,
  output_date timestamp NOT NULL DEFAULT current_timestamp(),
  machine_name varchar(255) DEFAULT NULL,
  machine_serial_number varchar(100) DEFAULT NULL,
  final_sale_price decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  CONSTRAINT machine_outputs_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);

-- =======================================
-- TABELA DE ENTRADAS DE PRODUTOS
-- =======================================
CREATE TABLE product_inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    quantity_added INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    details TEXT,
    input_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    product_name VARCHAR(255),
    product_category VARCHAR(100),
    product_serial_number VARCHAR(100),
    product_barcode VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- =======================================
-- TABELA DE ENTRADAS DE MÁQUINAS
-- =======================================
CREATE TABLE machine_inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    user_id INT,
    quantity_added INT NOT NULL DEFAULT 1,
    reason VARCHAR(255) NOT NULL,
    details TEXT,
    input_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    machine_name VARCHAR(255),
    machine_serial_number VARCHAR(100),
    machine_cost_price DECIMAL(10, 2),
    FOREIGN KEY (machine_id) REFERENCES ready_machines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- DADOS INICIAIS - USUÁRIOS DO SISTEMA
-- ========================================
INSERT INTO users (username, password, email, role, full_name) VALUES 
('admin', 'admin123', 'admin@sistema.com', 'admin', 'Administrador do Sistema'),
('user', 'user123', 'user@sistema.com', 'user', 'Usuário Padrão');

-- ========================================
-- DADOS INICIAIS - CONFIGURAÇÕES DO SISTEMA
-- ========================================
INSERT INTO system_settings (setting_key, setting_value) VALUES 
('site_name', 'Sistema de Estoque TI'),
('company_name', 'Sua Empresa'),
('company_slogan', 'Sistema completo de controle de estoque de TI'),
('company_logo', ''),
('theme', 'light'),
('backup_frequency', 'daily'),
('low_stock_alert', '5'),
('system_version', '1.0');