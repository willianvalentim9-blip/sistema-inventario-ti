# 📋 ANÁLISE: Sistema de Compatibilidade Automática de Componentes de PC

## 🎯 Objetivo
Implementar um sistema de montagem de máquina similar ao Pichau.com.br e Kabum.com.br com:
- ✅ Seleção de componentes com descrição detalhada
- ✅ Validação automática de compatibilidade
- ✅ Filtros dinâmicos baseados em seleções
- ✅ Recomendações inteligentes
- ✅ Interface interativa com AJAX

---

## 📊 ESTRUTURA ATUAL vs NECESSÁRIA

### ❌ PROBLEMA ATUAL
```
Tabelas existentes:
├── products (genérica para todos os tipos)
├── ready_machines (máquinas montadas)
└── machine_components (relação máquina ↔ produtos)

Limitações:
- Sem informações técnicas de componentes
- Sem matriz de compatibilidade
- Sem especificações detalhadas por categoria
```

### ✅ ESTRUTURA PROPOSTA

#### 1. **Novas Tabelas Necessárias**

```sql
-- Categorias de Componentes (Especializadas)
CREATE TABLE component_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,      -- 'CPU', 'Placa Mãe', 'RAM', etc
    slug VARCHAR(100) UNIQUE,                -- 'cpu', 'motherboard', 'ram'
    description TEXT,
    icon VARCHAR(50),                        -- ícone FontAwesome
    sort_order INT DEFAULT 0
);

-- Especificações de Componentes
CREATE TABLE component_specs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    component_category_id INT NOT NULL,
    
    -- CPU/Processador
    cpu_cores INT,
    cpu_threads INT,
    cpu_base_clock DECIMAL(3,2),            -- em GHz
    cpu_boost_clock DECIMAL(3,2),
    cpu_tdp INT,                             -- em W
    cpu_socket VARCHAR(50),                  -- 'AM5', 'LGA1700', 'TR4'
    cpu_architecture VARCHAR(50),            -- '7nm', '5nm'
    
    -- Placa Mãe
    motherboard_socket VARCHAR(50),          -- 'AM5', 'LGA1700'
    motherboard_chipset VARCHAR(100),        -- 'B850', 'Z790'
    motherboard_ram_type VARCHAR(50),        -- 'DDR5', 'DDR4'
    motherboard_max_ram INT,                 -- em GB
    motherboard_ram_slots INT,               -- quantidade de slots
    motherboard_pcie_gen VARCHAR(10),        -- 'PCIe 5.0', 'PCIe 4.0'
    motherboard_form_factor VARCHAR(50),     -- 'ATX', 'Micro-ATX', 'Mini-ITX'
    
    -- Memória RAM
    ram_type VARCHAR(50),                    -- 'DDR5', 'DDR4'
    ram_speed INT,                           -- em MHz
    ram_capacity INT,                        -- em GB (2, 4, 8, 16, 32)
    ram_cas_latency INT,                     -- CAS Latency
    
    -- GPU/Placa de Vídeo
    gpu_vram INT,                            -- em GB
    gpu_vram_type VARCHAR(50),               -- 'GDDR6X', 'GDDR6'
    gpu_memory_bandwidth INT,                -- em Gbps
    gpu_power_requirement INT,               -- em W
    gpu_pcie_gen VARCHAR(10),
    gpu_output_ports JSON,                   -- ['HDMI', 'DP', 'USB-C']
    
    -- Armazenamento (SSD/HDD)
    storage_type VARCHAR(50),                -- 'SSD NVMe', 'SSD SATA', 'HDD'
    storage_capacity INT,                    -- em GB
    storage_interface VARCHAR(50),           -- 'M.2 NVMe', 'SATA 3.0', '2.5"'
    storage_speed INT,                       -- em MB/s
    
    -- Fonte (PSU)
    psu_wattage INT,                         -- em W
    psu_efficiency_rating VARCHAR(50),       -- '80 Plus Gold', '80 Plus Platinum'
    psu_modular VARCHAR(50),                 -- 'Modular', 'Semi-Modular', 'Non-Modular'
    psu_power_connections JSON,              -- conectores disponíveis
    
    -- Case
    case_form_factor VARCHAR(50),            -- 'ATX', 'Micro-ATX', 'Mini-ITX'
    case_max_motherboard_size VARCHAR(50),
    case_max_gpu_length INT,                 -- em mm
    case_max_cooler_height INT,              -- em mm
    case_max_psu_depth INT,                  -- em mm
    
    -- Cooler
    cooler_type VARCHAR(50),                 -- 'Air', 'Liquid AIO', 'Passive'
    cooler_socket_compatibility JSON,        -- ['AM5', 'LGA1700']
    cooler_max_height INT,                   -- em mm
    cooler_tdp_capability INT,               -- em W
    cooler_noise_level INT,                  -- em dB
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (component_category_id) REFERENCES component_categories(id)
);

-- Matriz de Compatibilidade
CREATE TABLE compatibility_matrix (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_type_1 VARCHAR(100),           -- 'CPU'
    component_type_2 VARCHAR(100),           -- 'Motherboard'
    
    -- Regras de compatibilidade
    spec_1_field VARCHAR(100),               -- 'cpu_socket'
    spec_1_value VARCHAR(100),               -- 'AM5'
    
    spec_2_field VARCHAR(100),               -- 'motherboard_socket'
    spec_2_value VARCHAR(100),               -- 'AM5'
    
    compatibility_level VARCHAR(50),         -- 'Compatible', 'Partial', 'Incompatible'
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cache de Recomendações
CREATE TABLE component_recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_id INT NOT NULL,               -- Componente selecionado
    recommended_component_id INT NOT NULL,   -- Componente recomendado
    compatibility_score DECIMAL(3,2),        -- 0.0 a 1.0
    reason VARCHAR(255),                     -- Por que é recomendado
    
    FOREIGN KEY (component_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (recommended_component_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (component_id, recommended_component_id)
);

-- Histórico de Configurações (para análise de vendas)
CREATE TABLE pc_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT,
    
    -- Componentes utilizados (JSON para flexibilidade)
    components JSON,                         -- {'cpu_id': 1, 'motherboard_id': 2, ...}
    total_price DECIMAL(10,2),
    performance_level VARCHAR(50),           -- 'Budget', 'Intermediate', 'High-End', 'Gaming'
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

---

## 🏗️ FLUXO DE FUNCIONAMENTO

### **Etapa 1: Seleção de Categoria**
```
Usuário clica em "Configurar Máquina"
    ↓
Exibe categorias disponíveis:
├─ CPU / Processador
├─ Placa Mãe
├─ Memória RAM
├─ GPU / Placa de Vídeo
├─ Armazenamento
├─ Fonte (PSU)
├─ Case / Gabinete
└─ Cooler / Resfriamento
```

### **Etapa 2: Seleção de Componente com Filtros**
```
Usuário seleciona "CPU / Processador"
    ↓
Interface dinâmica com:
├─ Filtros (Marca, Cores, Threads, etc)
├─ Listagem com IMAGEM e DETALHES
│   └─ Nome
│   └─ Especificações (Cores, Threads, Clock)
│   └─ Socket (AM5, LGA1700)
│   └─ Preço
│   └─ Botão "Selecionar"
└─ Descrição Expandida
    ├─ Benchmarks
    ├─ TDP (Consumo)
    ├─ Arquitetura
    └─ Compatibilidades
```

### **Etapa 3: Validação e Sugestões Automáticas**
```
CPU Selecionada: "Ryzen 7 7700X (Socket AM5)"
    ↓
Sistema busca no banco:
1. Quais PLACAS MÃE são compatíveis?
   → Mostra: B850, X870, X870E (com AM5)
   
2. Qual RAM é compatível?
   → Mostra: DDR5 que suporta AM5
   
3. Qual cooler é adequado?
   → Mostra: Coolers com suporte AM5 e TDP >= 105W
   
4. Qual fonte é suficiente?
   → Calcula TDP total e sugere fontes
```

### **Etapa 4: Resumo e Validação Final**
```
Configuração:
✅ CPU: Ryzen 7 7700X (AM5) - 105W
✅ Motherboard: MSI B850 - Compatível
✅ RAM: 32GB DDR5 6000MHz - Compatível
✅ GPU: RTX 4060 Ti - Compatível
✅ SSD: 1TB NVMe - Compatível
✅ PSU: 750W Gold - Suficiente (recomendado 850W)
⚠️ Case: Verificar altura do cooler

Total Estimado: R$ 6.500,00
```

---

## 💾 BANCO DE DADOS - EXEMPLO DE DADOS

### Categorias
```php
INSERT INTO component_categories VALUES
(1, 'CPU/Processador', 'cpu', 'icon-cpu', 1),
(2, 'Placa Mãe', 'motherboard', 'icon-board', 2),
(3, 'Memória RAM', 'ram', 'icon-ram', 3),
(4, 'GPU/Placa Vídeo', 'gpu', 'icon-gpu', 4),
(5, 'Armazenamento', 'storage', 'icon-storage', 5),
(6, 'Fonte/PSU', 'psu', 'icon-power', 6),
(7, 'Case/Gabinete', 'case', 'icon-case', 7),
(8, 'Cooler/Resfriador', 'cooler', 'icon-cooler', 8);
```

### Especificações (Exemplo CPU)
```php
-- Ryzen 7 7700X
INSERT INTO component_specs (product_id, component_category_id, ...)
VALUES (
    1,                  -- product_id (já existe em products)
    1,                  -- component_category_id (CPU)
    8,                  -- cores
    16,                 -- threads
    4.50,               -- base_clock
    5.40,               -- boost_clock
    105,                -- tdp
    'AM5',              -- socket
    '5nm'               -- architecture
);
```

### Matriz de Compatibilidade
```php
-- CPU AM5 é compatível com Motherboard AM5
INSERT INTO compatibility_matrix VALUES
(NULL,
 'CPU',
 'Motherboard',
 'cpu_socket',
 'AM5',
 'motherboard_socket',
 'AM5',
 'Compatible',
 'Socket AM5 é totalmente compatível'
);

-- DDR5 com Motherboard DDR5
INSERT INTO compatibility_matrix VALUES
(NULL,
 'RAM',
 'Motherboard',
 'ram_type',
 'DDR5',
 'motherboard_ram_type',
 'DDR5',
 'Compatible',
 'Memória DDR5 é compatível com placa mãe DDR5'
);
```

---

## 🎨 INTERFACE - ESTRUTURA HTML/JS

### **View: Seletor de Componentes**

```php
<!-- File: modules/machines/components_selector.php -->

<div class="component-selector-container">
    <!-- Painel de Categorias -->
    <div class="categories-panel">
        <h5>Selecione os Componentes</h5>
        <div class="component-categories">
            <button class="category-btn" data-category="cpu">
                <i class="fas fa-microchip"></i> CPU
            </button>
            <button class="category-btn" data-category="motherboard">
                <i class="fas fa-cpu"></i> Placa Mãe
            </button>
            <!-- ... -->
        </div>
    </div>

    <!-- Painel de Produtos -->
    <div class="products-panel">
        <div class="component-list" id="component-list">
            <!-- Carregado via AJAX -->
        </div>
    </div>

    <!-- Painel de Compatibilidade -->
    <div class="compatibility-panel">
        <h5>Compatibilidade Verificada</h5>
        <div id="compatibility-info">
            <!-- Alertas de compatibilidade -->
        </div>
    </div>

    <!-- Resumo da Configuração -->
    <div class="configuration-summary">
        <h5>Sua Configuração</h5>
        <table class="summary-table">
            <tbody id="summary-body">
                <!-- Componentes selecionados -->
            </tbody>
        </table>
        <button class="btn btn-primary" onclick="saveMachine()">
            Salvar Máquina
        </button>
    </div>
</div>
```

### **AJAX: Buscar Componentes Compatíveis**

```javascript
// File: js/component-selector.js

class ComponentSelector {
    constructor() {
        this.selectedComponents = {};
        this.init();
    }

    init() {
        // Event listeners
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.loadCategory(e.target.dataset.category));
        });
    }

    /**
     * Carrega componentes de uma categoria
     */
    async loadCategory(category) {
        try {
            const response = await fetch('get_components.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    category: category,
                    filters: this.getFilters(category),
                    selectedComponents: this.selectedComponents
                })
            });

            const data = await response.json();
            this.renderComponents(data.components, data.compatibility);
        } catch (error) {
            console.error('Erro ao carregar componentes:', error);
        }
    }

    /**
     * Seleciona um componente e valida compatibilidade
     */
    async selectComponent(componentId, category) {
        try {
            // 1. Salva seleção
            this.selectedComponents[category] = componentId;

            // 2. Valida compatibilidade
            const response = await fetch('validate_compatibility.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    selectedComponents: this.selectedComponents
                })
            });

            const validation = await response.json();

            // 3. Mostra avisos/recomendações
            this.showCompatibilityStatus(validation);

            // 4. Carrega recomendações para próximas categorias
            this.loadRecommendations();

            // 5. Atualiza resumo
            this.updateSummary();

        } catch (error) {
            console.error('Erro ao selecionar componente:', error);
        }
    }

    /**
     * Valida e mostra status de compatibilidade
     */
    showCompatibilityStatus(validation) {
        const panel = document.getElementById('compatibility-info');
        panel.innerHTML = '';

        validation.issues.forEach(issue => {
            const badge = document.createElement('div');
            badge.className = `alert alert-${issue.severity}`;
            badge.innerHTML = `
                <i class="fas fa-${issue.icon}"></i>
                <strong>${issue.title}:</strong>
                ${issue.message}
            `;
            panel.appendChild(badge);
        });

        if (validation.warnings.length === 0) {
            const success = document.createElement('div');
            success.className = 'alert alert-success';
            success.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <strong>Compatibilidade Verificada!</strong>
                Todos os componentes são compatíveis.
            `;
            panel.appendChild(success);
        }
    }

    /**
     * Carrega recomendações para próximas categorias
     */
    async loadRecommendations() {
        try {
            const response = await fetch('get_recommendations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    selectedComponents: this.selectedComponents
                })
            });

            const recommendations = await response.json();
            this.highlightRecommendedComponents(recommendations);

        } catch (error) {
            console.error('Erro ao carregar recomendações:', error);
        }
    }

    /**
     * Atualiza o resumo da configuração
     */
    async updateSummary() {
        try {
            const response = await fetch('get_configuration_summary.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    selectedComponents: this.selectedComponents
                })
            });

            const summary = await response.json();
            const tbody = document.getElementById('summary-body');
            tbody.innerHTML = summary.html;

            // Atualiza total
            document.getElementById('total-price').textContent = 
                new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(summary.totalPrice);

        } catch (error) {
            console.error('Erro ao atualizar resumo:', error);
        }
    }
}

// Inicializa
document.addEventListener('DOMContentLoaded', () => {
    new ComponentSelector();
});
```

### **Backend: Validar Compatibilidade**

```php
// File: modules/machines/validate_compatibility.php

<?php
require_once '../../config.php';

$selectedComponents = json_decode($_POST['selectedComponents'] ?? '{}', true);
$issues = [];
$warnings = [];

$pdo = getConnection();

// Verifica compatibilidades conhecidas
$compatibilityRules = [
    'cpu' => ['motherboard'],  // CPU deve ser compatível com Placa Mãe
    'motherboard' => ['ram', 'cpu'],  // Placa Mãe define tipo de RAM
    'case' => ['motherboard'],  // Case deve suportar tamanho da placa
];

foreach ($compatibilityRules as $component => $checkAgainst) {
    if (!isset($selectedComponents[$component])) continue;

    foreach ($checkAgainst as $otherComponent) {
        if (!isset($selectedComponents[$otherComponent])) continue;

        // Busca especificações de ambos
        $spec1 = getComponentSpecs($selectedComponents[$component], $pdo);
        $spec2 = getComponentSpecs($selectedComponents[$otherComponent], $pdo);

        // Valida compatibilidade
        $result = checkCompatibility($spec1, $spec2, $component, $otherComponent);

        if (!$result['compatible']) {
            $issues[] = [
                'severity' => 'danger',
                'icon' => 'times-circle',
                'title' => ucfirst($component) . ' Incompatível',
                'message' => $result['reason']
            ];
        } elseif ($result['warning']) {
            $warnings[] = [
                'severity' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'Aviso',
                'message' => $result['warning']
            ];
        }
    }
}

echo json_encode([
    'compatible' => count($issues) === 0,
    'issues' => $issues,
    'warnings' => $warnings
]);
?>
```

---

## 🔄 MIGRAÇÃO DO BANCO DE DADOS

```php
// File: migrations/add_component_compatibility_system.php

<?php
require_once '../config.php';

$pdo = getConnection();

try {
    $pdo->beginTransaction();

    // 1. Criar tabelas
    // [SQL das tabelas acima]

    // 2. Migrar dados existentes
    // Mapear produtos atuais para novas categorias
    $stmt = $pdo->prepare("
        SELECT id, category FROM products
        WHERE category IN ('CPU', 'RAM', 'SSD', 'Placa Mãe', ...)
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        // Insere em component_specs com dados básicos
        // (requer dados mais detalhados do usuário)
    }

    $pdo->commit();
    echo "✅ Migração concluída com sucesso!";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Erro: " . $e->getMessage();
}
?>
```

---

## 📊 COMPARAÇÃO COM PICHAU/KABUM

| Feature | Pichau | Kabum | Nossa Proposta |
|---------|--------|-------|---|
| Seleção por Categoria | ✅ | ✅ | ✅ |
| Filtros Dinâmicos | ✅ | ✅ | ✅ |
| Validação Compatibilidade | ✅ | ✅ | ✅ |
| Descrição Expandida | ✅ | ✅ | ✅ |
| Recomendações Inteligentes | ✅ | ✅ | ✅ |
| Estimativa de Performance | ⚠️ | ⚠️ | 🔄 |
| Comparativo de Preço | ✅ | ✅ | 🔄 |
| Salvar Configuração | ✅ | ✅ | ✅ |

---

## 📝 REQUISITOS TÉCNICOS

### **Frontend**
- [ ] Bootstrap 5 (já disponível)
- [ ] Font Awesome (já disponível)
- [ ] JavaScript Vanilla + AJAX
- [ ] CSS customizado para seletor

### **Backend**
- [ ] API endpoints para validação
- [ ] Lógica de compatibilidade
- [ ] Cache de recomendações
- [ ] Sistema de filtros

### **Banco de Dados**
- [ ] 5 novas tabelas
- [ ] Índices para performance
- [ ] Dados de compatibilidade

---

## 🚀 PRÓXIMOS PASSOS

1. **FASE 1**: Criar tabelas e estrutura de dados
2. **FASE 2**: Implementar validação de compatibilidade
3. **FASE 3**: Criar interface de seleção
4. **FASE 4**: Implementar recomendações inteligentes
5. **FASE 5**: Testes e otimizações

---

## 📞 CONTATO PARA DÚVIDAS

Análise criada em: 2025-12-17
Versão: 1.0
Status: ✅ Pronto para implementação
