-- ========================================
-- TABELA DE USUÁRIOS DO SISTEMA
-- ========================================
-- Esta tabela armazena informações dos usuários que podem acessar o sistema
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,              -- ID único do usuário (chave primária)
    username VARCHAR(50) UNIQUE NOT NULL,           -- Nome de usuário para login (único)
    password VARCHAR(255) NOT NULL,                 -- Senha criptografada do usuário
    email VARCHAR(100) UNIQUE NOT NULL,             -- Email do usuário (único)
    role VARCHAR(20) DEFAULT 'user',                -- Nível de permissão (admin, user, etc.)
    full_name VARCHAR(255),                         -- Nome completo do usuário
    last_login TIMESTAMP NULL,                      -- Data/hora do último login
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data/hora de criação do registro
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Data/hora da última atualização
);

-- ========================================
-- TABELA DE PRODUTOS EM ESTOQUE
-- ========================================
-- Esta tabela armazena todos os itens de TI (hardware, peças, cabos, etc.)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,              -- ID único do produto (chave primária)
    name VARCHAR(255) NOT NULL,                     -- Nome do produto
    description TEXT,                               -- Descrição detalhada do produto
    category VARCHAR(100) NOT NULL,                 -- Categoria (CPU, RAM, SSD, Cabo, etc.)
    manufacturer VARCHAR(100),                      -- Fabricante do produto
    model VARCHAR(100),                             -- Modelo específico do produto
    sku VARCHAR(100) UNIQUE,                        -- SKU (Stock Keeping Unit) - Código interno (único se existir)
    barcode VARCHAR(100) UNIQUE,                    -- Código de barras (único se existir)
    qr_code VARCHAR(100) UNIQUE,                    -- Código QR (único se existir)
    serial_number VARCHAR(100) UNIQUE,              -- Número de série (adicionado)
    quantity INT NOT NULL DEFAULT 0,                -- Quantidade em estoque
    min_quantity INT DEFAULT 0,                     -- Quantidade mínima para alerta de estoque baixo
    max_quantity INT DEFAULT 0,                     -- Quantidade máxima permitida no estoque
    price DECIMAL(10, 2),                           -- Preço do produto (até 99999999.99)
    location VARCHAR(100),                          -- Localização física no estoque
    status VARCHAR(50) DEFAULT 'available',         -- Status (available, low_stock, out_of_stock, discontinued)
    image VARCHAR(255),                             -- Nome do arquivo da imagem principal do produto
    notes TEXT,                                     -- Observações adicionais sobre o produto
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data/hora de cadastro do produto
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Data/hora da última atualização
);

-- ========================================
-- TABELA DE MÁQUINAS PRONTAS PARA VENDA
-- ========================================
-- Esta tabela armazena informações sobre computadores montados prontos para venda
CREATE TABLE ready_machines (
    id INT AUTO_INCREMENT PRIMARY KEY,              -- ID único da máquina pronta (chave primária)
    name VARCHAR(255) NOT NULL,                     -- Nome da máquina (ex: PC Gamer Elite)
    description TEXT,                               -- Descrição da máquina
    processor VARCHAR(255),                         -- Processador
    memory VARCHAR(255),                            -- Memória RAM
    storage VARCHAR(255),                           -- Armazenamento (SSD/HDD)
    graphics VARCHAR(255),                          -- Placa de Vídeo
    motherboard VARCHAR(255),                       -- Placa Mãe
    power_supply VARCHAR(255),                      -- Fonte de Alimentação
    case_type VARCHAR(255),                         -- Tipo de Gabinete
    specifications TEXT,                            -- Especificações técnicas adicionais
    sale_price DECIMAL(10, 2) NOT NULL,             -- Preço de venda da máquina
    cost_price DECIMAL(10, 2),                      -- Preço de custo da máquina
    serial_number VARCHAR(100) UNIQUE,              -- Número de série da máquina (único se existir)
    barcode VARCHAR(100) UNIQUE,                    -- Código de barras (único se existir)
    qr_code VARCHAR(100) UNIQUE,                    -- Código QR (único se existir)
    status VARCHAR(50) DEFAULT 'available',         -- Status (available, sold, reserved, maintenance, testing)
    location VARCHAR(100),                          -- Localização física
    image VARCHAR(255),                             -- Nome do arquivo da imagem principal da máquina
    notes TEXT,                                     -- Observações adicionais
    windows_10_compatible BOOLEAN DEFAULT FALSE,    -- Compatível com Windows 10
    windows_11_compatible BOOLEAN DEFAULT FALSE,    -- Compatível com Windows 11
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data/hora de criação do registro
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Data/hora da última atualização
);

-- ========================================
-- TABELA DE CONFIGURAÇÕES DO SISTEMA
-- ========================================
-- Esta tabela armazena configurações gerais do sistema
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
-- Esta tabela armazena logs de atividades importantes do sistema
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
-- Esta tabela armazena logs de ações administrativas importantes
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
-- Esta tabela registra todas as entradas e saídas de produtos
CREATE TABLE product_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    movement_type VARCHAR(50) NOT NULL, -- 'entrada' ou 'saida'
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
-- Esta tabela registra todas as movimentações e alterações de status de máquinas
CREATE TABLE machine_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    user_id INT,
    movement_type VARCHAR(50) NOT NULL, -- e.g., 'venda', 'descarte', 'uso_interno', 'manutencao'
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    details TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES ready_machines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- ========================================
-- TABELA DE SAÍDAS DE PRODUTOS (NOVA)
-- ========================================
-- Esta tabela armazena um histórico detalhado de todas as baixas de estoque.
CREATE TABLE product_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,              -- ID único da saída
    product_id INT NOT NULL,                        -- ID do produto que saiu
    user_id INT,                                    -- ID do usuário que deu a baixa
    quantity_removed INT NOT NULL,                  -- Quantidade de itens removidos
    reason VARCHAR(255) NOT NULL,                   -- Motivo principal da baixa (Venda, Uso Interno, etc.)
    details TEXT,                                   -- Detalhes adicionais (Cliente, N° da Venda, Observações)
    output_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data e hora da baixa
    product_name VARCHAR(255),                      -- Cópia do nome do produto (para histórico)
    product_category VARCHAR(100),                  -- Cópia da categoria do produto
    product_serial_number VARCHAR(100),             -- Cópia do número de série
    product_barcode VARCHAR(100),                   -- Cópia do código de barras
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Adiciona a coluna de quantidade na tabela de máquinas, com valor padrão 1
ALTER TABLE `ready_machines` ADD `quantity` INT NOT NULL DEFAULT 1 AFTER `qr_code`;

-- ========================================
-- TABELA DE SAÍDAS DE MÁQUINAS (NOVA)
-- ========================================
-- Cria a nova tabela para armazenar o histórico de baixas de máquinas.
CREATE TABLE `machine_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_removed` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `output_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `machine_name` varchar(255) DEFAULT NULL,
  `machine_serial_number` varchar(100) DEFAULT NULL,
  `final_sale_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `machine_outputs_ibfk_1` FOREIGN