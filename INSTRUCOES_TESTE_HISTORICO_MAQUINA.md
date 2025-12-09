# 🧪 INSTRUÇÕES DE TESTE: Modal Histórico de Máquina

**Revisão**: 1.0  
**Data**: 2025-12-09

---

## 📋 O que foi corrigido

O modal de histórico de máquina agora exibe:
- ✅ Mudanças de propriedades (nome, preço, status, etc)
- ✅ Entradas de máquina (quando foi adquirida)
- ✅ Saídas de máquina (quando foi vendida/removida)
- ✅ Mudanças de status (available → maintenance)
- ✅ Adição de componentes
- ✅ Quem fez cada alteração e quando

---

## 🚀 TESTE 1: Editar Máquina e Verificar Histórico

### Pré-requisito
- MySQL rodando com `it_inventory` database
- Máquina existente em `ready_machines`

### Passos

1. **Abrir página de máquinas**
   ```
   http://localhost/sistema5/ready_machines.php
   ```

2. **Clique em uma máquina para editar**
   - Você vê a página de edição

3. **Altere algo**
   - Mude o nome
   - Mude o preço
   - Mude o status
   - Salve

4. **Abra o histórico da máquina**
   - Clique no botão "Histórico" ou
   - Acesse direto: `machine_history_view.php?id=15`

5. **Verificar Timeline**
   - ✅ Deve mostrar uma entrada azul claro "Máquina Atualizada"
   - ✅ Deve mostrar antes/depois das alterações
   - ✅ Deve mostrar quem fez (seu username)
   - ✅ Deve mostrar quando (data/hora)

### Resultado Esperado
```
Timeline de uma máquina editada:
│
├─ 🔵 Máquina Atualizada (agora)
│  Antes: Nome antigo "A" → Depois: "A"
│  Antes: Preço antigo 100.00 → Depois: 150.00
│  Antes: Status "available" → Depois: "maintenance"
│  Usuário: suporte02
│
└─ 🔵 Máquina Criada (2025-11-25)
   ...
```

---

## 🚀 TESTE 2: Adicionar Componentes e Verificar

### Passos

1. **Editar máquina**
   - Abra `ready_machines.php`
   - Clique em editar uma máquina

2. **Adicione um componente**
   - Clique no botão `+` de "Processador", "RAM" ou outro
   - Selecione um componente da lista
   - Salve

3. **Verifique o histórico**
   - Abra `machine_history_view.php?id=15`

4. **Procure por**
   - ✅ Deve haver uma entrada verde "Componentes Atualizados" (UPDATE_MACHINE_COMPONENTS)
   - ✅ Deve haver uma entrada verde "Componente Adicionado" (COMPONENTE_ADICIONADO)
   - ✅ Ambas devem estar na timeline

### Resultado Esperado
```
Timeline com componentes:
│
├─ 🟢 Componentes Atualizados (agora)
│  Usuário: suporte02
│
├─ 🟢 Componente Adicionado (agora)
│  Componente: Intel Core i7 12700K
│  Quantidade: 1
│  Tipo: CPU
│
└─ ...
```

---

## 🚀 TESTE 3: Registrar Entrada de Máquina

### Passos

1. **Ir para entrada de máquinas**
   ```
   http://localhost/sistema5/machine_inputs_log.php
   ```

2. **Registrar uma entrada**
   - Clique em "Nova Entrada"
   - Selecione uma máquina
   - Informe quantidade, motivo, etc
   - Salve

3. **Abrir histórico da máquina**
   - `machine_history_view.php?id=<id_da_maquina>`

4. **Procure por**
   - ✅ Deve haver uma entrada azul "Máquina Entrada" (ENTRADA)
   - ✅ Deve mostrar quantidade
   - ✅ Deve mostrar motivo (razão da entrada)
   - ✅ Deve mostrar quem registrou

### Resultado Esperado
```
├─ 🔵 Máquina Entrada (data/hora)
│  Quantidade: 1
│  Motivo: Compra de Fornecedor
│  Usuário: suporte02
```

---

## 🚀 TESTE 4: Registrar Saída de Máquina

### Passos

1. **Ir para saída de máquinas**
   ```
   http://localhost/sistema5/machine_outputs_log.php
   ```

2. **Registrar uma saída**
   - Clique em "Nova Saída"
   - Selecione uma máquina
   - Informe quantidade, motivo, etc
   - Salve

3. **Abrir histórico da máquina**
   - `machine_history_view.php?id=<id_da_maquina>`

4. **Procure por**
   - ✅ Deve haver uma entrada vermelha "Máquina Saída" (SAIDA)
   - ✅ Deve mostrar quantidade
   - ✅ Deve mostrar motivo (razão da saída)

### Resultado Esperado
```
├─ 🔴 Máquina Saída (data/hora)
│  Quantidade: 1
│  Motivo: Venda ao cliente XYZ
│  Usuário: suporte02
```

---

## 🚀 TESTE 5: Mudança de Status via Machine Movements

### Passos

1. **Localizar endpoint de mudança de status**
   - Procure por `update_machine_status.php`

2. **Alterar status de uma máquina**
   - De `available` para `maintenance`
   - Ou de `maintenance` para `available`

3. **Abrir histórico da máquina**
   - `machine_history_view.php?id=<id_da_maquina>`

4. **Procure por**
   - ✅ Deve haver uma entrada amarela "Alteração de Status" (MOVIMENTACAO_STATUS)
   - ✅ Deve mostrar: "Status: available → maintenance"

### Resultado Esperado
```
├─ 🟡 Alteração de Status (data/hora)
│  Status: available → maintenance | Manutenção solicitada
│  Usuário: suporte02
```

---

## 📊 CHECKLIST DE VALIDAÇÃO

| Teste | Campo | Esperado | Status |
|-------|-------|----------|--------|
| 1 | Timeline | Mostra UPDATE_MACHINE | [ ] |
| 1 | Antes/Depois | Compara valores antigos e novos | [ ] |
| 1 | Usuário | Mostra quem editou | [ ] |
| 2 | Timeline | Mostra UPDATE_MACHINE_COMPONENTS | [ ] |
| 2 | Timeline | Mostra COMPONENTE_ADICIONADO | [ ] |
| 3 | Timeline | Mostra ENTRADA | [ ] |
| 3 | Quantidade | Mostra corretamente | [ ] |
| 3 | Motivo | Mostra razão da entrada | [ ] |
| 4 | Timeline | Mostra SAIDA | [ ] |
| 4 | Quantidade | Mostra corretamente | [ ] |
| 5 | Timeline | Mostra MOVIMENTACAO_STATUS | [ ] |
| 5 | Status | Mostra transição correta | [ ] |

---

## 🎨 CORES NA TIMELINE

| Cor | Ação | Ícone | O que significa |
|-----|------|-------|-----------------|
| 🔵 Azul | ENTRADA | 📥 | Máquina chegou (entrada em estoque) |
| 🔵 Azul | CREATE | 🖥️ | Máquina foi criada no sistema |
| 🔵 Azul Claro | UPDATE_MACHINE | ✏️ | Propriedades da máquina foram editadas |
| 🟢 Verde | COMPONENTE_ADICIONADO | ➕ | Componente foi adicionado à máquina |
| 🟢 Verde | UPDATE_MACHINE_COMPONENTS | ⚙️ | Componentes da máquina foram atualizados |
| 🟡 Amarelo | MOVIMENTACAO_STATUS | 🔄 | Status da máquina mudou |
| 🔴 Vermelho | SAIDA | 📤 | Máquina saiu do estoque (vendida/removida) |
| 🔴 Vermelho | DELETE | 🗑️ | Máquina foi deletada do sistema |

---

## ❓ FAQ - O que fazer se...

### ... o histórico está vazio?
1. Verifique se a máquina tem ID válido
2. Verifique se há registros em `admin_logs`, `machine_movements`, etc
3. Tente editar a máquina e veja se aparece

### ... não aparece edição que fiz?
1. Verifique se você salvou corretamente (flash message)
2. Verifique se `edit_machine.php` chamou `logAdminActivity()`
3. Cheque o console do navegador por erros

### ... aparece "Nenhuma alteração registrada"?
1. Máquina nunca foi editada
2. Histórico ainda não foi populado
3. Crie uma edição agora e atualize a página

### ... números de quantidade aparecem errados?
1. Verifique o banco de dados diretamente
2. Verifique se `machine_inputs` ou `machine_outputs` têm dados
3. Confirme que a máquina_id está correta

---

## 🐛 TROUBLESHOOTING

### Erro: "Máquina não encontrada"
- Verificar se ID da máquina é válido
- Confirmar que está em `ready_machines`
- Confirmar que `is_deleted = 0`

### Erro: "Erro ao carregar histórico"
- Checar logs do PHP/MySQL
- Confirmar que todas as tabelas existem
- Executar `it_inventory.sql` novamente

### Timeline vazia mas máquina editada
- Confirmar que `admin_logs` tem registros
- Verificar se `logAdminActivity()` foi chamado
- Confirmar `table_name = 'ready_machines'`

---

## 📞 SUPORTE

Para relatórios de problemas:

1. **Anote**:
   - ID da máquina
   - O que foi alterado
   - Data/hora da alteração
   - Seu username

2. **Verifique**:
   - Logs do navegador (F12)
   - Logs do PHP (error_log)
   - Banco de dados (phpMyAdmin)

3. **Reporte**:
   - Descreva exatamente o que esperava
   - Mostre o que aparece
   - Inclua erros da console

---

## ✅ CONCLUSÃO

Se todos os testes passaram:
- ✅ Modal histórico está funcionando perfeitamente
- ✅ Todas as fontes de dados estão sendo consultadas
- ✅ Timeline mostra alterações completas
- ✅ Sistema está pronto para uso

Parabéns! 🎉

