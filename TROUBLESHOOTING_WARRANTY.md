# 🆘 SUPORTE E TROUBLESHOOTING: GARANTIA EM MÁQUINAS

## 🚨 Problemas Comuns e Soluções

---

## 1. Problema: "Coluna não encontrada"

### Erro
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'has_warranty' in 'field list'
```

### Causas Possíveis
- Migração não foi executada
- Erro ao executar migração SQL
- Banco de dados errado

### Solução
```bash
# Opção 1: Executar migração via MySQL direto
mysql -h localhost -u admin -p@#8520@# it_inventory < migrations/001_add_warranty_to_machines.sql

# Opção 2: Acessar página de setup
http://localhost/sistema4/setup_warranty_machines.php

# Opção 3: Verificar colunas
mysql -h localhost -u admin -p@#8520@# it_inventory
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'ready_machines' 
AND (COLUMN_NAME = 'has_warranty' OR COLUMN_NAME LIKE 'warranty%');
```

---

## 2. Problema: "Campos de garantia não aparecem"

### Sintoma
- Checkbox existe mas não funciona
- Campos não aparecem ao marcar
- Resumo não atualiza

### Causas Possíveis
- Cache do navegador
- JavaScript desabilitado
- Erro de JavaScript no console
- Arquivo edit_machine.php corrompido

### Solução

**Passo 1: Limpar cache**
```
Ctrl + Shift + Delete
ou
F12 → Application → Clear Site Data
```

**Passo 2: Recarregar**
- Abra novamente: ready_machines.php
- Clique para editar uma máquina

**Passo 3: Verificar JavaScript**
```
F12 → Console → Procure por erros vermelhos
```

**Passo 4: Verificar código**
```
Abra: c:\xampp\htdocs\sistema4\edit_machine.php
Procure: "machine_has_warranty"
Procure: "updateWarrantySummaryMachine"
```

---

## 3. Problema: "Dados não salvam"

### Sintoma
- Clica em "Salvar Alterações"
- Nada acontece ou erro genérico

### Causas Possíveis
- Erro no banco de dados
- Dados inválidos
- Erro de permissão
- Conexão com banco perdida

### Solução

**Verificar Mensagem de Erro:**
```
F12 → Network → POST request → Response
```

**Verificar Banco de Dados:**
```sql
-- Verifique se tabela existe
SELECT * FROM ready_machines WHERE id = 1;

-- Verifique estrutura
SHOW COLUMNS FROM ready_machines;
```

**Verificar Permissões:**
```
Verifique se usuário tem permissão UPDATE
Verifique se banco está online
```

---

## 4. Problema: "Dados desaparecem após salvar"

### Sintoma
- Salva corretamente
- Dados não persistem
- Ao editar novamente está vazio

### Causas Possíveis
- Banco de dados não salva
- Transação não commitada
- Erro silencioso no código

### Solução

**Verificar dados no banco:**
```sql
SELECT id, has_warranty, warranty_provider, 
       warranty_period_value, warranty_period_unit
FROM ready_machines WHERE id = 1;
```

**Se os dados não estão lá:**
- Verifique se o UPDATE retorna sucesso
- Verifique se tem erros na resposta JSON
- Adicione debug logs no edit_machine.php

---

## 5. Problema: "Resumo não atualiza"

### Sintoma
- Digita nos campos
- Badge do resumo não muda
- Fica com valores antigos

### Causas Possíveis
- JavaScript erro
- Função não carregou
- ID dos elementos errado

### Solução

**Verificar Console:**
```
F12 → Console
Digite: updateWarrantySummaryMachine()
Se der erro, há problema no JavaScript
```

**Verificar IDs:**
```
F12 → Elements
Procure por: machine_warranty_provider
Procure por: summary-provider-machine
Se não encontrar, há problema no HTML
```

**Recarregar:**
```
F5 (refresh)
Ctrl+Shift+R (hard refresh)
```

---

## 6. Problema: "Unidade em outro idioma"

### Sintoma
- Seleciona "Meses"
- Salva como "months"
- Na visualização mostra "months" em inglês

### Causas Possíveis
- Tradução não implementada
- Valor salvo em inglês
- Exibição usa valor bruto

### Solução (Não é um problema - é assim mesmo)

Os valores são armazenados em inglês no banco:
- days = Dias
- months = Meses  
- years = Anos

Ao exibir, você pode traduzir com JavaScript ou PHP:

```php
// Em view_machine.php
$translations = [
    'days' => 'Dias',
    'months' => 'Meses',
    'years' => 'Anos'
];
$unit_pt = $translations[$machine['warranty_period_unit']] ?? $machine['warranty_period_unit'];
echo $unit_pt;
```

---

## 7. Problema: "Checkbox fica desmarcado"

### Sintoma
- Marca o checkbox
- Fecha o modal
- Reabre e está desmarcado

### Causas Possíveis
- Dados não salvaram
- Página não recarregou
- Estado no HTML está errado

### Solução

**Verificar se salvou:**
```
Abra console F12
Execute: location.reload()
Reabra o modal
```

**Verificar dados no banco:**
```sql
SELECT has_warranty FROM ready_machines WHERE id = 1;
```

Se for 0, significa que não salvou. Verifique resposta do servidor:
```
F12 → Network → POST → Response
```

---

## 8. Problema: "Modal não abre"

### Sintoma
- Clica em editar
- Nada acontece
- Modal não aparece

### Causas Possíveis
- Erro JavaScript
- Bootstrap não carregado
- Modal_ID errado

### Solução

**Verificar Bootstrap:**
```
Abra F12 → Elements
Procure por: <link...bootstrap...css>
Procure por: <script...bootstrap...js>
```

**Verificar erros:**
```
F12 → Console → Procure por erros
```

**Verificar estrutura HTML:**
```
F12 → Elements
Procure por: class="modal fade" id="..."
Procure por: data-bs-toggle="modal"
```

---

## 9. Problema: "Estilo diferente"

### Sintoma
- Campos não têm estilo
- Cores diferentes
- Layout quebrado

### Causas Possíveis
- CSS não carregou
- Custom CSS não funcionando
- Bootstrap conflito

### Solução

**Verificar CSS:**
```
F12 → Elements → Styles
Procure por: form-control, form-label
```

**Limpar cache:**
```
Ctrl + Shift + Delete
Recarregar F5
```

**Verificar Bootstrap:**
```html
<!-- Deve estar no head -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
```

---

## 10. Problema: "Erro 500 ao salvar"

### Sintoma
```
HTTP 500 Internal Server Error
Erro de banco de dados
```

### Causas Possíveis
- Conexão com banco falhou
- SQL syntax error
- Permissão negada
- Limite de memoria

### Solução

**Verificar logs:**
```
Verifique arquivo de log do PHP
Verifique erro_logs do MySQL
```

**Verificar Query:**
```php
// Adicione ao código:
var_dump($stmt->errorInfo());
```

**Verificar Banco:**
```sql
-- Verifique se banco está online
SELECT 1;

-- Verifique user/password
-- Verifique tabela existe
SHOW TABLES LIKE 'ready_machines';
```

---

## 🔧 Ferramentas de Debugagem

### 1. Browser DevTools (F12)

**Console:**
```javascript
// Testar função de atualização
updateWarrantySummaryMachine()

// Ver conteúdo de elemento
document.getElementById('machine_has_warranty').checked
```

**Network:**
- Monitorar requisições POST
- Ver response da servidor
- Checar status code

**Elements:**
- Verificar estrutura HTML
- Checar valores de atributos
- Validar IDs e classes

### 2. MySQL Command Line

```sql
-- Verificar dados
SELECT * FROM ready_machines WHERE id = 1;

-- Verificar colunas
SHOW COLUMNS FROM ready_machines;

-- Testar UPDATE
UPDATE ready_machines SET has_warranty = 1 WHERE id = 1;

-- Ver resultado
SELECT has_warranty FROM ready_machines WHERE id = 1;
```

### 3. PHP Error Checking

```php
// Adicione no código
error_log("Debug: " . print_r($_POST, true));
error_log("SQL: " . $sql);
var_dump($stmt->errorInfo());
```

---

## 📋 Checklist de Diagnóstico

```
Antes de reportar problema:

Database:
☐ Migração executada
☐ 4 colunas criadas
☐ Tipos de dados corretos
☐ Dados salvam no banco
☐ MySQL online

Files:
☐ edit_machine.php existe
☐ view_machine.php existe
☐ Sem erro de sintaxe PHP
☐ Sem erro de sintaxe JS
☐ CSS carregou

Browser:
☐ Cache limpo
☐ JavaScript habilitado
☐ Bootstrap carregou
☐ Console sem erros
☐ Network mostra 200

Functionality:
☐ Checkbox funciona
☐ Campos aparecem
☐ Resumo atualiza
☐ Dados salvam
☐ Dados persistem
```

---

## 🆘 Não Conseguiu Resolver?

1. **Abra o arquivo de teste:**
   ```
   http://localhost/sistema4/test_warranty_machines.php
   ```
   Ele mostrará qual teste falhou.

2. **Leia a documentação:**
   ```
   TESTING_WARRANTY_MACHINES.md
   QUICK_REFERENCE_WARRANTY.md
   IMPLEMENTATION_WARRANTY_MACHINES.md
   ```

3. **Verifique os logs:**
   ```
   F12 Console (JavaScript errors)
   MySQL logs
   PHP error_log
   ```

4. **Teste passo a passo:**
   ```
   Siga TESTING_WARRANTY_MACHINES.md
   Execute cada teste individualmente
   Identifique onde falha
   ```

---

## 📞 Informações de Contato Úteis

Para MySQL:
```
Host: localhost
User: admin
Pass: @#8520@#
Database: it_inventory
```

Para URLs:
```
Máquinas: http://localhost/sistema4/ready_machines.php
Testes: http://localhost/sistema4/test_warranty_machines.php
Docs: c:\xampp\htdocs\sistema4\*.md
```

---

**Última Atualização:** 2024
**Versão:** 1.0
