# 🚀 REFERÊNCIA RÁPIDA: GARANTIA EM MÁQUINAS

## 📌 Links Importantes

### Páginas Principais
- **Máquinas:** http://localhost/sistema4/ready_machines.php
- **Ver Máquina:** ready_machines.php (clique em visualizar)
- **Editar Máquina:** Modal automaticamente

### Páginas de Teste
- **Teste Automático:** http://localhost/sistema4/test_warranty_machines.php
- **Documentação:** WARRANTY_MACHINES_CHECKLIST.md
- **Guia de Teste:** TESTING_WARRANTY_MACHINES.md

---

## 🎯 COMO USAR (Para Usuários)

### 1️⃣ EDITAR MÁQUINA
```
ready_machines.php → [Clique na máquina] → [Botão Editar] → Modal abre
```

### 2️⃣ ADICIONAR GARANTIA
```
Procure: "Com Garantia" (seção após Windows 11)
Marque:  Checkbox
Preencha: Fornecedor, Duração, Unidade
Clique:  "Salvar Alterações"
```

### 3️⃣ VISUALIZAR GARANTIA
```
ready_machines.php → [Clique na máquina] → [Botão Visualizar] → Card "Garantia"
```

---

## 🗄️ BANCO DE DADOS

### Tabela: `ready_machines`

#### Colunas Novas
```sql
- has_warranty          TINYINT(1)      DEFAULT 0
- warranty_provider     VARCHAR(255)    DEFAULT NULL
- warranty_period_value INT(11)         DEFAULT NULL
- warranty_period_unit  VARCHAR(50)     DEFAULT 'months'
```

#### Valores Válidos
- **has_warranty:** 0 (sem), 1 (com)
- **warranty_period_unit:** 'days', 'months', 'years'

---

## 💻 ARQUIVOS PRINCIPAIS

### Modificados
```
edit_machine.php      ← Formulário + processamento + JS
view_machine.php      ← Visualização de garantia
```

### De Suporte
```
migrations/001_add_warranty_to_machines.sql    ← Migração BD
test_warranty_machines.php                      ← Testes
IMPLEMENTATION_WARRANTY_MACHINES.md             ← Docs técnicas
WARRANTY_MACHINES_CHECKLIST.md                  ← Checklist
TESTING_WARRANTY_MACHINES.md                    ← Guia testes
WARRANTY_MACHINES_SUMMARY.txt                   ← Resumo
```

---

## 🔧 DADOS DO FORMULÁRIO

### Campos de Input
```
POST['has_warranty']                  → checkbox → 0 ou 1
POST['machine_warranty_provider']     → text     → até 255 chars
POST['machine_warranty_period_value'] → number   → inteiro
POST['machine_warranty_period_unit']  → select   → dias/meses/anos
```

### Banco de Dados
```
ready_machines.has_warranty
ready_machines.warranty_provider
ready_machines.warranty_period_value
ready_machines.warranty_period_unit
```

---

## 🎨 COMPONENTES UI

### Checkbox (Toggle)
```html
<input type="checkbox" name="has_warranty" value="1">
```

### Input Fornecedor
```html
<input type="text" name="machine_warranty_provider" 
       placeholder="Ex: Samsung, LG, Autorizado">
```

### Input Duração
```html
<input type="number" name="machine_warranty_period_value" 
       min="1" placeholder="Ex: 12">
```

### Select Unidade
```html
<select name="machine_warranty_period_unit">
    <option value="days">Dias</option>
    <option value="months" selected>Meses</option>
    <option value="years">Anos</option>
</select>
```

---

## 🧠 LÓGICA JAVASCRIPT

### Toggle Campos
```javascript
// Mostra campos quando checkbox está marcado
if (hasWarrantyCheckbox.checked) {
    warrantyFieldsRow.style.display = '';  // Mostra
} else {
    warrantyFieldsRow.style.display = 'none';  // Oculta
}
```

### Atualizar Resumo
```javascript
function updateWarrantySummaryMachine() {
    const provider = document.getElementById('machine_warranty_provider').value;
    const periodValue = document.getElementById('machine_warranty_period_value').value;
    const periodUnit = document.getElementById('machine_warranty_period_unit').value;
    
    document.getElementById('summary-provider-machine').textContent = provider;
    document.getElementById('summary-period-machine').textContent = periodValue + ' ' + periodUnit;
}
```

---

## 🔍 DEBUGAR PROBLEMAS

### Verificar Colunas no Banco
```sql
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'ready_machines' 
AND COLUMN_NAME LIKE 'warranty%' 
OR COLUMN_NAME = 'has_warranty';
```

### Verificar Dados de Máquina
```sql
SELECT id, name, has_warranty, warranty_provider, 
       warranty_period_value, warranty_period_unit 
FROM ready_machines WHERE id = 1;
```

### Ver Erros PHP
1. Abra F12 (DevTools)
2. Vá em "Network"
3. Clique em "Salvar"
4. Procure pela requisição POST
5. Veja a resposta em "Response"

### Ver Erros JavaScript
1. Abra F12 (DevTools)
2. Vá em "Console"
3. Procure por mensagens de erro (vermelho)
4. Clique para expandir e ver detalhes

---

## 📋 CHECKLIST RÁPIDO

### Antes de Usar
- [ ] Banco de dados tem as 4 colunas novas
- [ ] Arquivo edit_machine.php foi modificado
- [ ] Arquivo view_machine.php foi modificado
- [ ] Sem erros ao abrir ready_machines.php

### Ao Testar
- [ ] Marque "Com Garantia" e campos aparecem
- [ ] Preencha todos os campos
- [ ] Clique Salvar
- [ ] Mensagem de sucesso aparece
- [ ] Abra visualizar e veja o card de garantia

### Se Não Funcionar
- [ ] Execute test_warranty_machines.php
- [ ] Verifique se migração foi executada
- [ ] Limpe cache do navegador (Ctrl+Shift+Delete)
- [ ] Recarregue a página (F5)
- [ ] Abra console e procure por erros (F12)

---

## 🆘 CONTATO / SUPORTE

Se encontrar problemas:

1. **Verifique a migração:**
   ```
   http://localhost/sistema4/test_warranty_machines.php
   ```

2. **Leia a documentação:**
   ```
   TESTING_WARRANTY_MACHINES.md
   IMPLEMENTATION_WARRANTY_MACHINES.md
   ```

3. **Verifique os logs:**
   ```
   F12 → Console
   Procure por erros vermelhos
   ```

---

## 📊 RESUMO

| Item | Status |
|------|--------|
| Banco de Dados | ✅ Pronto |
| Formulário de Edição | ✅ Pronto |
| Visualização | ✅ Pronto |
| JavaScript | ✅ Pronto |
| Testes | ✅ Pronto |
| Documentação | ✅ Pronto |

**Status Geral: 🚀 OPERACIONAL**

---

**Última Atualização:** 2024
**Versão:** 1.0
