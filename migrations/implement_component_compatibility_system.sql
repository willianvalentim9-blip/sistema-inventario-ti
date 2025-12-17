-- ========================================
-- SISTEMA DE COMPATIBILIDADE DE COMPONENTES
-- ========================================
-- Data: 2025-12-17
-- Versão: 1.0
-- Descrição: Implementa sistema de validação automática de compatibilidade
--            de componentes para montagem de máquinas

-- ========================================
-- 1. CATEGORIAS DE COMPONENTES
-- ========================================

CREATE TABLE IF NOT EXISTS component_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL COMMENT 'Nome da categoria (CPU, Placa Mãe, etc)',
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'URL-friendly identifier',
    description TEXT COMMENT 'Descrição detalhada da categoria',
    icon VARCHAR(50) COMMENT 'Ícone FontAwesome (fa-microchip, etc)',
    sort_order INT DEFAULT 0 COMMENT 'Ordem de exibição',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_slug (slug),
    KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. ESPECIFICAÇÕES DE COMPONENTES
-- ========================================

CREATE TABLE IF NOT EXISTS component_specs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL COMMENT 'Referência para product.id',
    component_category_id INT NOT NULL COMMENT 'Categoria do componente',
    
    -- ===== CPU / PROCESSADOR =====
    cpu_manufacturer VARCHAR(100) COMMENT 'Intel, AMD, etc',
    cpu_model_name VARCHAR(255) COMMENT 'Nome exato do modelo',
    cpu_cores INT COMMENT 'Quantidade de núcleos',
    cpu_threads INT COMMENT 'Quantidade de threads',
    cpu_base_clock DECIMAL(5,2) COMMENT 'Clock base em GHz',
    cpu_boost_clock DECIMAL(5,2) COMMENT 'Clock turbo em GHz',
    cpu_tdp INT COMMENT 'Consumo de energia em W',
    cpu_socket VARCHAR(50) COMMENT 'Socket compatível (AM5, LGA1700, etc)',
    cpu_architecture VARCHAR(50) COMMENT 'Arquitetura (7nm, 5nm, 3nm, etc)',
    cpu_cache_l3 INT COMMENT 'Cache L3 em MB',
    cpu_pci_lanes INT COMMENT 'Linhas PCIe disponíveis',
    cpu_max_temp INT COMMENT 'Temperatura máxima em °C',
    
    -- ===== PLACA MÃE =====
    motherboard_manufacturer VARCHAR(100),
    motherboard_model_name VARCHAR(255),
    motherboard_socket VARCHAR(50) COMMENT 'Socket suportado (AM5, LGA1700, etc)',
    motherboard_chipset VARCHAR(100) COMMENT 'Chipset (B850, X870, Z790, etc)',
    motherboard_ram_type VARCHAR(50) COMMENT 'Tipo de RAM suportado (DDR5, DDR4)',
    motherboard_max_ram INT COMMENT 'RAM máxima em GB',
    motherboard_ram_slots INT COMMENT 'Quantidade de slots RAM',
    motherboard_pcie_gen VARCHAR(10) COMMENT 'Geração PCIe (PCIe 5.0, etc)',
    motherboard_form_factor VARCHAR(50) COMMENT 'Tamanho (ATX, Micro-ATX, Mini-ITX)',
    motherboard_sata_ports INT COMMENT 'Portas SATA',
    motherboard_m2_slots INT COMMENT 'Slots M.2 disponíveis',
    motherboard_vrm_phases INT COMMENT 'Fases VRM',
    motherboard_usb_headers INT COMMENT 'Headers USB internos',
    motherboard_power_connectors JSON COMMENT 'Conectores de poder disponíveis: 24-pin, 8-pin, etc',
    
    -- ===== MEMÓRIA RAM =====
    ram_manufacturer VARCHAR(100),
    ram_type VARCHAR(50) COMMENT 'Tipo de memória (DDR5, DDR4, DDR3)',
    ram_speed INT COMMENT 'Velocidade em MHz',
    ram_capacity INT COMMENT 'Capacidade em GB (2, 4, 8, 16, 32, 64, 96)',
    ram_cas_latency INT COMMENT 'CAS Latency (CL)',
    ram_voltage DECIMAL(3,2) COMMENT 'Voltagem em V',
    ram_ecc BOOLEAN COMMENT 'Suporta ECC',
    ram_registered BOOLEAN COMMENT 'É memória Registered',
    ram_modules INT COMMENT 'Quantidade de módulos no kit',
    ram_heat_sink BOOLEAN COMMENT 'Possui dissipador',
    
    -- ===== GPU / PLACA DE VÍDEO =====
    gpu_manufacturer VARCHAR(100),
    gpu_model_name VARCHAR(255),
    gpu_vram INT COMMENT 'Memória em GB',
    gpu_vram_type VARCHAR(50) COMMENT 'Tipo de VRAM (GDDR6X, GDDR6, etc)',
    gpu_memory_bus INT COMMENT 'Memory bus em bits',
    gpu_memory_bandwidth DECIMAL(6,2) COMMENT 'Bandwidth em GB/s',
    gpu_power_requirement INT COMMENT 'Consumo em W',
    gpu_pcie_gen VARCHAR(10) COMMENT 'Geração PCIe necessária',
    gpu_power_connectors JSON COMMENT '["6-pin", "8-pin", "12-pin"]',
    gpu_output_ports JSON COMMENT '["HDMI 2.1", "DisplayPort 1.4a", "USB-C"]',
    gpu_dimensions VARCHAR(100) COMMENT 'Dimensões (L x A x P) em mm',
    gpu_max_temp INT COMMENT 'Temperatura máxima em °C',
    
    -- ===== ARMAZENAMENTO (SSD/HDD) =====
    storage_manufacturer VARCHAR(100),
    storage_type VARCHAR(50) COMMENT 'Tipo (SSD NVMe, SSD SATA, HDD)',
    storage_capacity INT COMMENT 'Capacidade em GB',
    storage_interface VARCHAR(50) COMMENT 'Interface (M.2 NVMe PCIe 4.0, SATA 3.0, U.2, etc)',
    storage_form_factor VARCHAR(50) COMMENT 'Tamanho (2.5", 3.5", M.2 2280, etc)',
    storage_read_speed INT COMMENT 'Velocidade leitura em MB/s',
    storage_write_speed INT COMMENT 'Velocidade escrita em MB/s',
    storage_iops_read INT COMMENT 'IOPS leitura',
    storage_iops_write INT COMMENT 'IOPS escrita',
    storage_mtbf INT COMMENT 'MTBF em horas',
    storage_power_consumption_idle INT COMMENT 'Consumo idle em mW',
    storage_power_consumption_active INT COMMENT 'Consumo ativo em mW',
    
    -- ===== FONTE / PSU =====
    psu_manufacturer VARCHAR(100),
    psu_model_name VARCHAR(255),
    psu_wattage INT COMMENT 'Potência em W',
    psu_efficiency_rating VARCHAR(50) COMMENT '80 Plus Bronze/Gold/Platinum/Titanium',
    psu_modular VARCHAR(50) COMMENT 'Tipo (Modular, Semi-Modular, Non-Modular)',
    psu_form_factor VARCHAR(50) COMMENT 'Tamanho (ATX, SFX, etc)',
    psu_power_connectors JSON COMMENT 'Conectores disponíveis',
    psu_silent_mode BOOLEAN COMMENT 'Modo silencioso',
    psu_audible_noise INT COMMENT 'Nível de ruído em dB',
    psu_warranty_years INT COMMENT 'Garantia em anos',
    
    -- ===== CASE / GABINETE =====
    case_manufacturer VARCHAR(100),
    case_model_name VARCHAR(255),
    case_form_factor VARCHAR(50) COMMENT 'Suporta (ATX, Micro-ATX, Mini-ITX)',
    case_max_motherboard_size VARCHAR(50),
    case_max_gpu_length INT COMMENT 'Comprimento máximo GPU em mm',
    case_max_cooler_height INT COMMENT 'Altura máxima cooler em mm',
    case_max_psu_depth INT COMMENT 'Profundidade máxima PSU em mm',
    case_drive_bays_35 INT COMMENT 'Baias 3.5"',
    case_drive_bays_25 INT COMMENT 'Baias 2.5"',
    case_front_usb_headers INT COMMENT 'Headers USB na frente',
    case_expansion_slots INT COMMENT 'Slots de expansão',
    case_color VARCHAR(50),
    case_dimensions VARCHAR(100) COMMENT '(L x A x P) em mm',
    case_side_panel VARCHAR(50) COMMENT 'Tipo (Vidro temperado, Plástico, etc)',
    
    -- ===== COOLER / RESFRIADOR =====
    cooler_manufacturer VARCHAR(100),
    cooler_model_name VARCHAR(255),
    cooler_type VARCHAR(50) COMMENT 'Tipo (Air, Liquid AIO 120mm, 240mm, 360mm, etc)',
    cooler_socket_compatibility JSON COMMENT '["AM5", "LGA1700", "TR4"]',
    cooler_max_height INT COMMENT 'Altura máxima em mm (Air)',
    cooler_radiator_size VARCHAR(50) COMMENT 'Tamanho radiador (Liquid): 120mm, 240mm, 360mm',
    cooler_tdp_capability INT COMMENT 'TDP que consegue dissipar em W',
    cooler_noise_level_min INT COMMENT 'Nível mínimo de ruído em dB',
    cooler_noise_level_max INT COMMENT 'Nível máximo de ruído em dB',
    cooler_warranty_years INT COMMENT 'Garantia em anos',
    
    -- ===== CAMPOS GERAIS =====
    warranty_months INT COMMENT 'Meses de garantia do componente',
    additional_notes TEXT COMMENT 'Notas adicionais importantes',
    benchmark_score INT COMMENT 'Score de benchmark (para comparações)',
    is_recommended BOOLEAN DEFAULT 0 COMMENT 'Componente recomendado',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (component_category_id) REFERENCES component_categories(id) ON DELETE RESTRICT,
    
    KEY idx_product_id (product_id),
    KEY idx_category_id (component_category_id),
    KEY idx_socket (cpu_socket, motherboard_socket),
    KEY idx_ram_type (motherboard_ram_type, ram_type)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. MATRIZ DE COMPATIBILIDADE
-- ========================================

CREATE TABLE IF NOT EXISTS compatibility_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Tipos de componentes que se relacionam
    component_type_1 VARCHAR(100) NOT NULL COMMENT 'Tipo 1 (cpu, motherboard, ram, etc)',
    component_type_2 VARCHAR(100) NOT NULL COMMENT 'Tipo 2',
    
    -- Campo que determina a compatibilidade
    spec_field_1 VARCHAR(100) NOT NULL COMMENT 'Campo em component_specs tipo 1',
    spec_field_2 VARCHAR(100) NOT NULL COMMENT 'Campo em component_specs tipo 2',
    
    -- Tipo de regra
    rule_type VARCHAR(50) NOT NULL COMMENT 'exact, contains, startswith, numeric_gte, numeric_lte',
    
    -- Valores da regra
    expected_value VARCHAR(255) COMMENT 'Valor esperado para compatibilidade',
    
    -- Mensagens
    compatibility_message VARCHAR(255) COMMENT 'Mensagem quando compatível',
    incompatibility_message VARCHAR(255) COMMENT 'Mensagem quando incompatível',
    warning_message VARCHAR(255) COMMENT 'Aviso se houver (ex: recomenda mais RAM)',
    
    priority INT DEFAULT 0 COMMENT 'Prioridade da verificação',
    is_critical BOOLEAN DEFAULT 1 COMMENT 'Se false, é apenas aviso',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_rule (component_type_1, component_type_2, spec_field_1, spec_field_2),
    KEY idx_component_types (component_type_1, component_type_2)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. CACHE DE RECOMENDAÇÕES
-- ========================================

CREATE TABLE IF NOT EXISTS component_recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    component_id INT NOT NULL COMMENT 'Componente selecionado',
    recommended_component_id INT NOT NULL COMMENT 'Componente recomendado',
    
    compatibility_score DECIMAL(3,2) COMMENT 'Score de compatibilidade (0.0 a 1.0)',
    reason VARCHAR(255) COMMENT 'Por que é recomendado',
    reason_type VARCHAR(50) COMMENT 'Type: socket_compatible, price_range, performance_match, etc',
    
    priority INT DEFAULT 0 COMMENT 'Prioridade da recomendação',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (component_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (recommended_component_id) REFERENCES products(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_recommendation (component_id, recommended_component_id),
    KEY idx_component_id (component_id),
    KEY idx_compatibility_score (compatibility_score DESC)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. HISTÓRICO DE CONFIGURAÇÕES SALVAS
-- ========================================

CREATE TABLE IF NOT EXISTS pc_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    name VARCHAR(255) NOT NULL COMMENT 'Nome da configuração',
    description TEXT COMMENT 'Descrição da configuração',
    
    created_by INT NOT NULL,
    
    -- Componentes em JSON para flexibilidade
    components JSON COMMENT '{"cpu_id": 1, "motherboard_id": 2, "ram_id": 3, ...}',
    
    -- Informações agregadas
    total_price DECIMAL(10,2) COMMENT 'Preço total da configuração',
    performance_level VARCHAR(50) COMMENT 'Budget, Intermediate, High-End, Gaming, Workstation',
    performance_score INT COMMENT 'Score agregado de performance',
    
    -- Compatibilidade
    is_compatible BOOLEAN DEFAULT 1,
    compatibility_warnings TEXT COMMENT 'Warnings de compatibilidade em JSON',
    
    -- Uso
    is_public BOOLEAN DEFAULT 0 COMMENT 'Pode ser compartilhada?',
    times_viewed INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    
    KEY idx_created_by (created_by),
    KEY idx_performance_level (performance_level),
    KEY idx_is_compatible (is_compatible),
    KEY idx_created_at (created_at DESC)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. LOG DE VALIDAÇÕES
-- ========================================

CREATE TABLE IF NOT EXISTS compatibility_check_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    user_id INT,
    
    component_id_1 INT,
    component_id_2 INT,
    
    check_result VARCHAR(50) COMMENT 'compatible, incompatible, warning',
    details JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_created_at (created_at),
    KEY idx_result (check_result)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT: CATEGORIAS INICIAIS
-- ========================================

INSERT INTO component_categories (name, slug, description, icon, sort_order) VALUES
('CPU / Processador', 'cpu', 'Processadores Intel e AMD para desktop e servidor', 'fa-microchip', 1),
('Placa Mãe', 'motherboard', 'Placas mãe com diferentes sockets e chipsets', 'fa-project-diagram', 2),
('Memória RAM', 'ram', 'Memória RAM DDR4, DDR5 e ECC', 'fa-memory', 3),
('GPU / Placa de Vídeo', 'gpu', 'Placas de vídeo NVIDIA e AMD', 'fa-cube', 4),
('Armazenamento', 'storage', 'SSDs NVMe, SATA e HDDs', 'fa-database', 5),
('Fonte / PSU', 'psu', 'Fontes de alimentação modular e semi-modular', 'fa-plug', 6),
('Case / Gabinete', 'case', 'Gabinetes ATX, Micro-ATX e Mini-ITX', 'fa-box', 7),
('Cooler / Resfriador', 'cooler', 'Coolers a ar e líquido (AIO)', 'fa-fan', 8);

-- ========================================
-- INSERT: REGRAS DE COMPATIBILIDADE
-- ========================================

-- CPU com Placa Mãe (Socket)
INSERT INTO compatibility_rules (component_type_1, component_type_2, spec_field_1, spec_field_2, rule_type, compatibility_message, incompatibility_message, is_critical) VALUES
('cpu', 'motherboard', 'cpu_socket', 'motherboard_socket', 'exact', 'Socket compatível', 'Socket incompatível! CPU não encaixa na placa mãe', 1);

-- RAM com Placa Mãe (Tipo DDR)
INSERT INTO compatibility_rules (component_type_1, component_type_2, spec_field_1, spec_field_2, rule_type, compatibility_message, incompatibility_message, is_critical) VALUES
('ram', 'motherboard', 'ram_type', 'motherboard_ram_type', 'exact', 'Tipo de memória compatível', 'Tipo de memória incompatível!', 1);

-- RAM Quantidade Máxima com Placa Mãe
INSERT INTO compatibility_rules (component_type_1, component_type_2, spec_field_1, spec_field_2, rule_type, warning_message, is_critical) VALUES
('ram', 'motherboard', 'ram_capacity', 'motherboard_max_ram', 'numeric_lte', 'RAM dentro do limite suportado', 0);

-- GPU com Slots PCIe
INSERT INTO compatibility_rules (component_type_1, component_type_2, spec_field_1, spec_field_2, rule_type, compatibility_message, incompatibility_message, is_critical) VALUES
('gpu', 'motherboard', 'gpu_pcie_gen', 'motherboard_pcie_gen', 'exact', 'PCIe compatível', 'GPU pode não funcionar com PCIe dessa geração', 0);

-- Cooler com Socket
INSERT INTO compatibility_rules (component_type_1, component_type_2, spec_field_1, spec_field_2, rule_type, compatibility_message, incompatibility_message, is_critical) VALUES
('cooler', 'cpu', 'cooler_socket_compatibility', 'cpu_socket', 'contains', 'Socket suportado', 'Cooler não é compatível com este socket', 1);

-- ========================================
-- FIM DO SCRIPT
-- ========================================
-- Versão: 1.0
-- Data: 2025-12-17
-- Status: ✅ Pronto para produção
