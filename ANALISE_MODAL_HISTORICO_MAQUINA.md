# 🔍 ANÁLISE: Modal Histórico de Máquina - Por que não está puxando alterações

**Data**: 2025-12-09
**Arquivo principal**: `machine_history_view.php`
**Status**: ❌ PROBLEMA IDENTIFICADO

---

## 📋 SUMÁRIO DO PROBLEMA

O modal de histórico de máquina (`machine_history_view.php`) **não está exibindo as alterações** feitas nas máquinas porque:

1. ❌ **Está buscando dados da tabela errada** para histórico de edições
2. ❌ **Não está consultando `machine_movements`** para movimentações
3. ❌ **Não está consultando `machine_inputs` e `machine_outputs`** para entrada/saída
4. ⚠️ **Admin_logs** tem dados, mas o código não está filtrando corretamente

---

## 🗄️ ANÁLISE DAS TABELAS DO BANCO

### Tabelas Relevantes para Histórico de Máquina:

| Tabela | Propósito | Campos-Chave | Status |
|--------|-----------|--------------|--------|
| **ready_machines** | Máquina principal | id, name, status, created_at, updated_at | ✅ Existente |
| **machine_movements** | Alterações de status | machine_id, movement_type, old_status, new_status, movement_date | ✅ Existente |
| **machine_inputs** | Entrada de máquinas | machine_id, reason, input_date, quantity_added | ✅ Existente |
| **machine_outputs** | Saída de máquinas | machine_id, reason, output_date, quantity_removed | ✅ Existente |
| **admin_logs** | Alterações gerais | table_name='ready_machines', record_id, action, old_values, new_values | ✅ Existente |
| **machine_products** | Componentes adicionados | machine_id, product_id, created_at | ✅ Existente |

---

## 🐛 PROBLEMAS ENCONTRADOS NO CÓDIGO

### Arquivo: `machine_history_view.php` (Linhas 50-100)

#### ❌ PROBLEMA 1: Apenas buscando `machine_products` e `admin_logs`

```php
// ❌ CÓDIGO ATUAL
// Obter histórico de componentes adicionados
$stmt = $pdo->prepare("
    SELECT
        'COMPONENTE_ADICIONADO' as action,
        p.name as component_name,
        mp.quantity,
        mp.created_at as action_date,
        NULL as username,
        NULL as full_name,
        mp.component_type as details
    FROM machine_products mp
    LEFT JOIN products p ON mp.product_id = p.id
    WHERE mp.machine_id = ?
");

// ❌ NÃO está buscando em:
// - machine_movements (status changes)
// - machine_inputs (entrada)
// - machine_outputs (saída)
```

**Resultado**: Mostram apenas componentes, não as **alterações de propriedades** da máquina!

---

#### ❌ PROBLEMA 2: Estrutura `admin_logs` pode ter dados, mas a query é genérica

```php
// ❌ Buscando apenas:
SELECT al.* 
FROM admin_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE al.table_name = 'ready_machines'  // ✅ OK
AND al.record_id = ?                      // ✅ OK
AND al.action IN ('CREATE', 'UPDATE', 'DELETE', 'RESTORE')
```

**Problema**: Não há garantia de que os dados estejam sendo inseridos em `admin_logs` quando máquinas são editadas!

---

## 🔧 FLUXO CORRETO ESPERADO

```
Usuário edita máquina em edit_machine.php
    ↓
[DEVERIA] Chamar logAdminActivity() em config.php
    ↓
[DEVERIA] Inserir em admin_logs (table_name='ready_machines', action='UPDATE')
    ↓
machine_history_view.php lê admin_logs
    ↓
Timeline mostra alteração
```

---

## 📊 CHECKLIST: O QUE ESTÁ FALTANDO

### 1. Histórico de Movimentações (`machine_movements`)
- ❌ **Não consultado** em `machine_history_view.php`
- ✅ Tabela existe com dados (entrada/saída/status)
- **Deve incluir**: Mudanças de status (available → sold → maintenance, etc)

### 2. Histórico de Entrada (`machine_inputs`)
- ❌ **Não consultado** em `machine_history_view.php`
- ✅ Tabela existe com dados
- **Deve incluir**: Quando máquina foi adquirida

### 3. Histórico de Saída (`machine_outputs`)
- ❌ **Não consultado** em `machine_history_view.php`
- ✅ Tabela existe com dados
- **Deve incluir**: Quando máquina foi vendida/removida

### 4. Logs de Edição (`admin_logs`)
- ⚠️ **Consultado**, mas precisa verificar se está sendo populado
- **Falta verificar**: Se `edit_machine.php` chama `logAdminActivity()`

### 5. Componentes (`machine_products`)
- ✅ **Consultado** corretamente
- ✅ Mostra adições de componentes

---

## ✅ SOLUÇÃO PROPOSTA

### Passo 1: Verificar se `edit_machine.php` chama `logAdminActivity()`

Procurar em `edit_machine.php` por:
```php
logAdminActivity($user_id, 'UPDATE', 'ready_machines', $machine_id, $old_data, $new_data);
```

**Se NÃO encontrar**: É o problema! Edições não estão sendo registradas.

---

### Passo 2: Atualizar `machine_history_view.php` para incluir todas as fontes

O arquivo deve consultar **5 ORIGENS** de dados:

1. ✅ `machine_products` - Componentes adicionados
2. ❌ `machine_movements` - Mudanças de status
3. ❌ `machine_inputs` - Entrada
4. ❌ `machine_outputs` - Saída
5. ⚠️ `admin_logs` - Edições (se estiver sendo populado)

---

## 📝 IMPLEMENTAÇÃO

### Arquivo afetado: `machine_history_view.php`

Adicionar as querys faltantes:

```php
// 1. Histórico de Entrada
$stmt = $pdo->prepare("
    SELECT
        'ENTRADA' as action,
        NULL as component_name,
        mi.quantity_added as quantity,
        mi.input_date as action_date,
        u.username,
        u.full_name,
        mi.reason as details
    FROM machine_inputs mi
    LEFT JOIN users u ON mi.user_id = u.id
    WHERE mi.machine_id = ?
");
$stmt->execute([$machine_id]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Histórico de Movimentação (Status)
$stmt = $pdo->prepare("
    SELECT
        'MOVIMENTACAO' as action,
        NULL as component_name,
        NULL as quantity,
        mm.movement_date as action_date,
        u.username,
        u.full_name,
        CONCAT(mm.old_status, ' → ', mm.new_status, ': ', mm.details) as details
    FROM machine_movements mm
    LEFT JOIN users u ON mm.user_id = u.id
    WHERE mm.machine_id = ?
");
$stmt->execute([$machine_id]);
$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Histórico de Saída
$stmt = $pdo->prepare("
    SELECT
        'SAIDA' as action,
        NULL as component_name,
        mo.quantity_removed as quantity,
        mo.output_date as action_date,
        u.username,
        u.full_name,
        mo.reason as details
    FROM machine_outputs mo
    LEFT JOIN users u ON mo.user_id = u.id
    WHERE mo.machine_id = ?
");
$stmt->execute([$machine_id]);
$outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combinar tudo:
$history = array_merge($components, $entries, $movements, $outputs, $admin_actions);
```

---

## 🎨 Mapeamento de Cores (Sugestão)

```php
$action_colors = [
    'COMPONENTE_ADICIONADO' => 'success',  // Verde
    'ENTRADA' => 'primary',                 // Azul
    'MOVIMENTACAO' => 'warning',            // Amarelo
    'SAIDA' => 'danger',                    // Vermelho
    'CREATE' => 'primary',
    'UPDATE' => 'info',
    'DELETE' => 'warning',
];

$action_icons = [
    'ENTRADA' => 'fa-plus-circle',
    'MOVIMENTACAO' => 'fa-exchange-alt',
    'SAIDA' => 'fa-minus-circle',
];
```

---

## 🔍 PRÓXIMAS AÇÕES

1. **Verificar `edit_machine.php`** - Está chamando `logAdminActivity()`?
2. **Atualizar `machine_history_view.php`** - Adicionar queries das 5 fontes
3. **Testar fluxo completo**:
   - Editar máquina → Deve aparecer em admin_logs
   - Adicionar componente → Deve aparecer em machine_products
   - Alterar status → Deve aparecer em machine_movements
4. **Verificar filtros** - O modal mostra corretamente?

---

## 📌 RESUMO FINAL

| Aspecto | Status | Ação |
|---------|--------|------|
| Tabelas existem | ✅ | Nenhuma |
| Dados sendo inseridos | ⚠️ | Verificar `edit_machine.php` |
| Query consultando todas | ❌ | **Atualizar `machine_history_view.php`** |
| Interface exibindo | ⚠️ | Testar após alterações |

