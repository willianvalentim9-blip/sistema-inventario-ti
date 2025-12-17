<!-- FILE: GUIA_IMPLEMENTACAO_COMPONENTES.md -->

# 🚀 Guia de Implementação - Sistema de Compatibilidade de Componentes

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Arquivos Criados](#arquivos-criados)
3. [Instalação e Configuração](#instalação-e-configuração)
4. [Fluxo de Funcionamento](#fluxo-de-funcionamento)
5. [Integração com Produto](#integração-com-produto)
6. [API Endpoints](#api-endpoints)
7. [Exemplo Prático](#exemplo-prático)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

O sistema de compatibilidade de componentes foi desenvolvido para facilitar a montagem de máquinas com validação automática similar aos sites Pichau e Kabum.

### Componentes do Sistema

```
┌─────────────────────────────────────────────────────────────┐
│          INTERFACE DE SELEÇÃO (HTML + Bootstrap)            │
│  component_selector.html - UI com grid responsivo          │
└────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│         CONTROLADOR JAVASCRIPT (ES6 Class)                   │
│  component-selector.js - Lógica de interação                │
└────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────┴─────────┐
                    ↓                   ↓
    ┌──────────────────────┐  ┌──────────────────────┐
    │   API GET Components │  │  API Validate Compat │
    │ (Fetch & Filter)     │  │  (Validação Real)    │
    └──────────────────────┘  └──────────────────────┘
                    ↓                   ↓
                    └─────────┬─────────┘
                              ↓
                    ┌──────────────────────┐
                    │   MySQL Database     │
                    │  (Componentes Info)  │
                    └──────────────────────┘
```

---

## 📁 Arquivos Criados

### 1. **Frontend (HTML + CSS)**
```
modules/machines/component_selector.html (1.2 KB)
├── Header com branding
├── Layout 3-colunas responsivo
├── Painel de categorias
├── Grid de componentes
└── Painel de resumo/checkout
```

### 2. **JavaScript (Controlador)**
```
js/component-selector.js (12 KB)
├── Classe: ComponentSelector
├── Métodos principais:
│   ├── init() - Inicialização
│   ├── loadCategories() - Carrega categorias
│   ├── loadCategory(category) - Carrega componentes
│   ├── selectComponent() - Seleciona componente
│   ├── validateCompatibility() - Valida compatibilidade
│   ├── renderComponents() - Renderiza grid
│   ├── updateSummary() - Atualiza resumo
│   └── showCompatibilityStatus() - Mostra status
└── Integração AJAX com APIs
```

### 3. **Backend APIs (PHP)**
```
modules/machines/api_get_components.php
├── Busca componentes por categoria
├── Filtragem dinâmica
├── Validação de compatibilidade prévia
└── Retorna JSON com specs

modules/machines/api_validate_compatibility.php
├── Valida compatibilidade completa
├── Calcula TDP total
├── Estima performance
├── Retorna issues e warnings
└── Retorna JSON detalhado
```

### 4. **Database (SQL Migration)**
```
migrations/implement_component_compatibility_system.sql
├── component_categories
├── component_specs (com ~50 colunas por categoria)
├── compatibility_rules
├── component_recommendations
├── pc_configurations
└── compatibility_check_logs
```

### 5. **Documentação**
```
ANALISE_COMPATIBILIDADE_COMPONENTES.md (400+ linhas)
├── Análise de requisitos
├── Design de banco de dados
├── Especificação de APIs
├── Exemplos de implementação
└── Comparação com Pichau/Kabum

GUIA_IMPLEMENTACAO_COMPONENTES.md (Este arquivo)
├── Passo-a-passo de instalação
├── Fluxo de funcionamento
├── Exemplos práticos
└── Troubleshooting
```

---

## ⚙️ Instalação e Configuração

### Passo 1: Fazer Deploy da Migração SQL

```bash
# Via MySQL CLI
mysql -u seu_usuario -p sua_senha seu_banco < migrations/implement_component_compatibility_system.sql

# Ou via phpMyAdmin
# 1. Abra phpMyAdmin
# 2. Selecione seu banco de dados
# 3. Vá para "SQL"
# 4. Cole o conteúdo do arquivo SQL
# 5. Clique em "Executar"
```

### Passo 2: Verificar Tabelas Criadas

```sql
-- Verifique as 6 novas tabelas
SHOW TABLES LIKE '%component%';
SHOW TABLES LIKE '%compatibility%';
SHOW TABLES LIKE '%pc_configurations%';

-- Esperado:
-- component_categories
-- component_specs
-- compatibility_rules
-- component_recommendations
-- pc_configurations
-- compatibility_check_logs
```

### Passo 3: Verificar Estrutura

```sql
-- Veja a estrutura de cada tabela
DESCRIBE component_categories;
DESCRIBE component_specs;
DESCRIBE compatibility_rules;

-- Verificar dados iniciais
SELECT * FROM component_categories;
SELECT * FROM compatibility_rules;
```

### Passo 4: Configurar Permissões

Os arquivos já existem nas pastas corretas:
- `js/component-selector.js` - Acesso HTTP necessário
- `modules/machines/component_selector.html` - Acesso HTTP
- `modules/machines/api_get_components.php` - Acesso HTTP
- `modules/machines/api_validate_compatibility.php` - Acesso HTTP

Verifique que `uploads/` é gravável:
```bash
# Windows CMD
attrib uploads /D
# Linux/Mac
chmod 755 uploads/
```

### Passo 5: Testar Acesso

Acesse em seu navegador:
```
http://localhost/sistema5/modules/machines/component_selector.html
```

Você deve ver a interface com 8 categorias de componentes listadas.

---

## 🔄 Fluxo de Funcionamento

### Fluxo 1: Carregamento Inicial

```javascript
// 1. Usuário acessa component_selector.html
//    ↓
// 2. DOM loaded → ComponentSelector.init()
//    ↓
// 3. Renderiza categorias (CPU, RAM, GPU, etc)
//    ↓
// 4. Aguarda clique do usuário em uma categoria
```

### Fluxo 2: Seleção de Categoria

```javascript
// 1. Clique em "CPU / Processador"
//    ↓
// 2. this.loadCategory('cpu')
//    ↓
// 3. POST /api_get_components.php
//    {
//      "category": "cpu",
//      "filters": {},
//      "selectedComponents": {}
//    }
//    ↓
// 4. Backend retorna JSON com CPUs disponíveis
//    ↓
// 5. renderComponents() desenha o grid
```

### Fluxo 3: Seleção de Componente

```javascript
// 1. Clique em um processador
//    ↓
// 2. this.selectComponent(componentId, 'cpu')
//    ↓
// 3. Salva em selectedComponents['cpu'] = componentId
//    ↓
// 4. POST /api_validate_compatibility.php
//    {
//      "selectedComponents": {
//        "cpu": 123
//      }
//    }
//    ↓
// 5. Backend valida compatibilidade
//    ↓
// 6. showCompatibilityStatus() exibe resultado
//    ↓
// 7. updateSummary() atualiza painel direito
```

### Fluxo 4: Validação de Compatibilidade

```sql
-- Backend valida regras:
-- 1. Socket: CPU.socket == Motherboard.socket
-- 2. RAM Type: RAM.type == Motherboard.supported_ram
-- 3. Power: Total TDP <= PSU.watts * 0.85
-- 4. PCIe: GPU.pcie_gen <= Motherboard.pcie_gen
-- 5. Cooling: Cooler.tdp_capability >= CPU.tdp
-- 6. Dimensions: GPU.length <= Case.max_gpu_length
-- 7. RAM Slots: RAM.quantity <= Motherboard.ram_slots

-- Retorna JSON:
{
  "compatible": true,
  "issues": [],
  "warnings": [
    {
      "title": "Aviso",
      "message": "PSU está próximo do limite",
      "severity": "warning"
    }
  ],
  "stats": {
    "total_tdp": 320,
    "total_power_recommended": 650,
    "performance_level": "High-End Gaming"
  }
}
```

---

## 🔗 Integração com Produto

### Opção 1: Iframe em add_product.php

```php
<?php
// Em add_product.php, após form de produto padrão

if ($_GET['type'] ?? false === 'machine') {
    echo '<div id="component-selector-iframe">';
    echo '<iframe src="modules/machines/component_selector.html" 
                  style="width: 100%; height: 1000px; border: none;"></iframe>';
    echo '</div>';
}
?>
```

### Opção 2: Modal Bootstrap

```php
<!-- Em includes/footer.php -->
<div class="modal fade" id="componentSelectorModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Montagem de Máquina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <iframe src="modules/machines/component_selector.html" 
                        style="width: 100%; height: 800px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Botão para abrir -->
<button class="btn btn-primary" data-bs-toggle="modal" 
        data-bs-target="#componentSelectorModal">
    🖥️ Montar Máquina
</button>
```

### Opção 3: Integração Direta

```javascript
// Adicione em add_product.php quando é máquina

// Após salvar produto, obter configuração selecionada
const config = window.componentSelector.selectedComponents;

// Enviar para backend
fetch('save_machine_configuration.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        machineId: productId,
        components: config,
        totalPrice: calculateTotal(config),
        totalPower: calculatePower(config)
    })
});
```

---

## 🔌 API Endpoints

### Endpoint 1: GET Components

**URL:** `POST /modules/machines/api_get_components.php`

**Request:**
```json
{
  "category": "cpu",
  "filters": {
    "socket": "1700",
    "min_cores": 4,
    "manufacturer": "Intel",
    "min_price": 300,
    "max_price": 800
  },
  "selectedComponents": {
    "cpu": null,
    "motherboard": 15
  }
}
```

**Response:**
```json
{
  "success": true,
  "components": [
    {
      "id": 123,
      "name": "Intel Core i5-13600K",
      "model": "BX8070805600K",
      "price": 629.90,
      "image_path": "/uploads/components/cpu123.jpg",
      "description": "Processador topo de linha...",
      "cpu_cores": 14,
      "cpu_threads": 20,
      "cpu_socket": "1700",
      "cpu_base_clock": 3.5,
      "cpu_boost_clock": 5.1,
      "cpu_tdp": 125,
      "compatibility": "compatible",
      "compatibility_message": "Compatível com sua placa mãe",
      "is_recommended": true
    }
  ],
  "count": 5,
  "filters_applied": {
    "socket": "1700",
    "min_cores": 4
  }
}
```

### Endpoint 2: Validate Compatibility

**URL:** `POST /modules/machines/api_validate_compatibility.php`

**Request:**
```json
{
  "selectedComponents": {
    "cpu": 123,
    "motherboard": 45,
    "ram": 67,
    "gpu": 89,
    "psu": 101
  }
}
```

**Response:**
```json
{
  "compatible": true,
  "issues": [],
  "warnings": [
    {
      "title": "PSU Próximo do Limite",
      "message": "PSU de 650W está em 95% de uso",
      "severity": "warning",
      "icon": "exclamation-triangle"
    }
  ],
  "stats": {
    "total_tdp": 320,
    "total_power_recommended": 680,
    "total_price": 3250.50,
    "performance_level": "High-End Gaming (Ultra 1440p)",
    "estimated_fps_1440p": "120+ FPS",
    "estimated_fps_4k": "60-80 FPS"
  },
  "compatibility_rules_checked": 7,
  "timestamp": "2024-01-15T14:30:45Z"
}
```

---

## 💡 Exemplo Prático

### Caso de Uso: Montar PC Gamer

#### Passo 1: Usuário seleciona CPU
```
✓ Clica em "CPU / Processador"
✓ Filtra por "Intel" e "Socket 1700"
✓ Seleciona "Intel i9-13900KS" (3.2k)
```

#### Passo 2: Sistema sugere Placa Mãe
```
✓ API detecta socket 1700
✓ Filtra placas mãe compatíveis
✓ Mostra opções com DDR5
✓ Marca "ASUS ROG STRIX Z790" como recomendada
```

#### Passo 3: Usuário seleciona RAM
```
✓ Clica em "Memória RAM"
✓ API sugere DDR5 (compatível com Z790)
✓ Mostra até 192GB máximo (suportado por placa)
✓ Usuário seleciona "64GB DDR5-6000"
```

#### Passo 4: Validação em Tempo Real
```
✓ CPU: Intel socket 1700 ✓
✓ Placa Mãe: Z790 com socket 1700 ✓
✓ RAM: DDR5 compatível com Z790 ✓
✓ TDP: 253W + 25W = 278W (dentro do limite) ✓
✓ Resfriamento: Noctua NH-D15L suporta até 250W ✗

⚠️ AVISO: Seu cooler pode não ser suficiente!
   Recomendamos: Noctua NH-D15S (até 270W)
```

#### Passo 5: Finalizar
```
✓ Resumo mostra todos componentes
✓ Total: R$ 5.280,50
✓ Performance estimada: Ultra 1440p 144+FPS
✓ Clica em "Finalizar Configuração"
✓ Configuração salva como máquina pré-montada
```

---

## 🐛 Troubleshooting

### Problema 1: API retorna 404

**Sintoma:** 
```
Erro ao carregar componentes: HTTP 404
```

**Solução:**
```bash
# Verifique se o arquivo existe
ls -la modules/machines/api_get_components.php

# Verifique se tem permissão de leitura
chmod 644 modules/machines/api_get_components.php

# Teste a URL diretamente
curl -X POST http://localhost/sistema5/modules/machines/api_get_components.php
```

### Problema 2: Banco de dados vazio

**Sintoma:**
```
Nenhum componente disponível mesmo após selecionar categoria
```

**Solução:**
```sql
-- Verifique se component_specs tem dados
SELECT COUNT(*) FROM component_specs;

-- Se vazio, adicione dados de teste
INSERT INTO component_specs (...) VALUES (...);

-- Veja ANALISE_COMPATIBILIDADE_COMPONENTES.md
-- para exemplos de INSERT
```

### Problema 3: Compatibilidade não valida corretamente

**Sintoma:**
```
Componentes incompatíveis marcados como compatíveis
```

**Solução:**
```sql
-- Verifique regras de compatibilidade
SELECT * FROM compatibility_rules;

-- Valide manualmente
SELECT DISTINCT 
    c.name as cpu_name,
    m.name as motherboard_name,
    c.cpu_socket,
    m.motherboard_socket
FROM component_specs c
JOIN component_specs m ON 1=1
WHERE c.category = 'cpu' AND m.category = 'motherboard'
LIMIT 5;
```

### Problema 4: Grid não responsivo

**Sintoma:**
```
Grid fica muito largo em celular
```

**Solução:**
```javascript
// Adicione em component-selector.js
// Detecte viewport e ajuste grid-template-columns

if (window.innerWidth < 768) {
    document.querySelector('.component-grid').style.gridTemplateColumns 
        = 'repeat(auto-fill, minmax(100px, 1fr))';
}
```

### Problema 5: Imagens de componentes não carregam

**Sintoma:**
```
Placeholder vazio para todas as imagens
```

**Solução:**
```php
// Verifique path em component_specs
SELECT id, name, image_path FROM component_specs LIMIT 5;

// Atualize paths se necessário
UPDATE component_specs 
SET image_path = CONCAT('/uploads/components/', filename)
WHERE image_path IS NULL;

// Certifique que uploads/ é acessível
// http://localhost/sistema5/uploads/components/
```

---

## 📈 Próximos Passos

### Curto Prazo (1-2 semanas)
- [ ] Popular banco com componentes reais
- [ ] Testar compatibilidade de regras
- [ ] Integrar com add_machine.php
- [ ] Adicionar imagens dos componentes

### Médio Prazo (1 mês)
- [ ] Criar endpoint de recomendações
- [ ] Implementar sistema de favoritos
- [ ] Adicionar histórico de configurações
- [ ] Criar dashboard de trending builds

### Longo Prazo (3+ meses)
- [ ] Integração com API de preços (Kabum, Pichau)
- [ ] Sincronização automática de estoque
- [ ] Machine learning para recomendações
- [ ] Comparação com builds comunitárias

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Consulte `ANALISE_COMPATIBILIDADE_COMPONENTES.md` para detalhes técnicos
2. Verifique `database_schema.sql` para estrutura do banco
3. Teste manualmente os endpoints com Postman/Insomnia
4. Verifique logs de erro em `logs/`

---

**Última atualização:** Janeiro 2024  
**Versão:** 1.0  
**Status:** Beta - Pronto para deploy em produção com testes adicionais
