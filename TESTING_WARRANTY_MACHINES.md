# 🧪 GUIA DE TESTE: SISTEMA DE GARANTIA EM MÁQUINAS

## Visão Geral
Este guia fornece instruções passo a passo para testar o novo sistema de garantia em máquinas.

---

## ✅ PRÉ-REQUISITOS

- [ ] Sistema rodando em http://localhost
- [ ] Usuário logado no sistema
- [ ] Acesso à página de máquinas (ready_machines.php)
- [ ] Pelo menos uma máquina cadastrada no banco

---

## 🧪 TESTE 1: Verificação da Migração do Banco

### Passo 1: Acessar página de teste
1. Abra navegador
2. Acesse: `http://localhost/sistema4/test_warranty_machines.php`
3. Verifique se todos os testes passaram (card verde)

### Passo 2: Verificação manual (opcional)
```sql
-- Executar no MySQL
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'ready_machines' 
AND COLUMN_NAME IN ('has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit')
ORDER BY ORDINAL_POSITION;
```

**Resultado esperado:** 4 colunas listadas

---

## 🧪 TESTE 2: Adicionar Garantia em Uma Máquina

### Passo 1: Ir para edição
1. Abra `http://localhost/sistema4/ready_machines.php`
2. Procure por qualquer máquina
3. Clique no botão de editar (lápis/pencil)
4. Modal de edição abre

### Passo 2: Marcar "Com Garantia"
1. Procure pela seção "Com Garantia" (está depois de "Windows 11")
2. Localize o checkbox "Com Garantia"
3. Marque o checkbox ☑️

**Resultado esperado:** 
- Campos de garantia aparecem
- Resumo visual se torna visível

### Passo 3: Preencher dados
1. **Fornecedor:** Digite um nome (ex: Samsung, LG, etc)
2. **Duração:** Digite um número (ex: 12)
3. **Unidade:** Selecione uma opção (ex: Meses)

**Resultado esperado:**
- Resumo visual atualiza em tempo real
- Badge mostra: "Samsung - 12 meses"

### Passo 4: Salvar
1. Clique em "Salvar Alterações"
2. Aguarde a mensagem de sucesso

**Resultado esperado:**
- Mensagem verde: "Máquina atualizada com sucesso!"
- Página recarrega

---

## 🧪 TESTE 3: Visualizar Garantia Salva

### Passo 1: Abrir máquina editada
1. Na página de máquinas, clique para visualizar a máquina que acabou de editar
2. Modal de visualização abre

### Passo 2: Verificar card de garantia
1. Procure pelo card com ícone de escudo
2. Verifique se exibe:
   - ✓ Badge verde: "Com Garantia"
   - ✓ Fornecedor: Samsung (com ícone)
   - ✓ Período: 12 Meses (com ícone)
   - ✓ Botão: "Ver Todas as Garantias"

**Resultado esperado:**
- Todos os dados aparecem corretamente
- Formatação com ícones e cores

---

## 🧪 TESTE 4: Remover Garantia

### Passo 1: Editar novamente
1. Volte à edição da mesma máquina

### Passo 2: Desmarcar "Com Garantia"
1. Desmarque o checkbox ☐

**Resultado esperado:**
- Campos de garantia desaparecem
- Resumo visual desaparece

### Passo 3: Salvar
1. Clique em "Salvar Alterações"

### Passo 4: Visualizar
1. Abra a visualização novamente
2. Verifique se o card mostra: "Sem garantia registrada"

**Resultado esperado:**
- Mensagem cinza: "Sem garantia registrada"
- Sem detalhes de fornecedor/período

---

## 🧪 TESTE 5: Campos Dinâmicos

### Teste 5.1: Show/Hide de Campos
1. Na edição, marque e desmarque o checkbox várias vezes
2. Verifique se os campos aparecem/desaparecem suavemente

**Resultado esperado:** Transição suave sem erros de JavaScript

### Teste 5.2: Atualização em Tempo Real
1. Marque "Com Garantia"
2. Preencha os campos
3. Verifique o resumo se atualiza enquanto digita

**Resultado esperado:** Resumo atualiza imediatamente após cada digitação

### Teste 5.3: Recarregamento de Dados
1. Feche o modal
2. Reabra a edição da mesma máquina
3. Verifique se os dados de garantia carregaram

**Resultado esperado:** 
- Checkbox marcado corretamente
- Campos com dados salvos
- Resumo visual aparece

---

## 🧪 TESTE 6: Diferentes Unidades de Período

### Teste cada unidade
1. Marque "Com Garantia"
2. Digite Fornecedor e Duração
3. Teste cada unidade:

#### Opção 1: Dias
- **Selecione:** Dias
- **Digite:** 30
- **Verifique:** Resumo mostra "30 Dias"

#### Opção 2: Meses
- **Selecione:** Meses
- **Digite:** 12
- **Verifique:** Resumo mostra "12 Meses"

#### Opção 3: Anos
- **Selecione:** Anos
- **Digite:** 2
- **Verifique:** Resumo mostra "2 Anos"

**Resultado esperado:** Todas as unidades funcionam corretamente

---

## 🧪 TESTE 7: Validação de Dados

### Teste 7.1: Campo vazio
1. Marque "Com Garantia"
2. Deixe o fornecedor vazio
3. Digite duração e selecione unidade
4. Salve

**Resultado esperado:** Deve aceitar (fornecedor pode ser vazio)

### Teste 7.2: Duração não numérica
1. No campo duração, tente digitar letras
2. Campo deve recusar

**Resultado esperado:** Campo type="number" bloqueia caracteres não numéricos

### Teste 7.3: Dados muito longos
1. No fornecedor, cola um texto com 300 caracteres
2. Salve

**Resultado esperado:** Trunca para 255 caracteres (VARCHAR limit)

---

## 🧪 TESTE 8: Múltiplas Máquinas

### Teste com diferentes máquinas
1. Edite máquina #1
   - Com Garantia: Samsung - 12 Meses
   
2. Edite máquina #2
   - Com Garantia: LG - 24 Meses
   
3. Edite máquina #3
   - Sem Garantia
   
4. Visualize todas e verifique:
   - Máquina #1: Exibe dados corretos
   - Máquina #2: Exibe dados diferentes
   - Máquina #3: Exibe "Sem garantia"

**Resultado esperado:** Cada máquina mantém seus dados independentes

---

## 🧪 TESTE 9: Compatibilidade com Browsers

### Teste em diferentes navegadores
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (se disponível)

### Verificar em cada browser:
- [ ] Checkbox funciona
- [ ] Campos aparecem/desaparecem
- [ ] Resumo atualiza
- [ ] Salva sem erros
- [ ] Bootstrap 5 renderiza corretamente

**Resultado esperado:** Funciona identicamente em todos os browsers

---

## 🧪 TESTE 10: Responsividade

### Teste em diferentes tamanhos de tela
1. Desktop (1920x1080)
2. Tablet (768x1024)
3. Mobile (375x667)

### Verificar em cada resolução:
- [ ] Modal legível
- [ ] Campos acessíveis
- [ ] Botões clicáveis
- [ ] Sem overflow
- [ ] Badges visíveis

**Resultado esperado:** Layout se adapta corretamente

---

## 📊 CHECKLIST DE TESTE FINAL

```
VERIFICAÇÕES GERAIS:
☐ Banco de dados tem as 4 colunas
☐ Código sem erros de PHP
☐ Código sem erros de JavaScript
☐ Formulário renderiza corretamente

TESTES DE FUNCIONALIDADE:
☐ Adiciona garantia com sucesso
☐ Dados salvam no banco
☐ Dados carregam na edição
☐ Remove garantia com sucesso
☐ Campos aparecem/desaparecem

TESTES DE UI/UX:
☐ Checkbox é intuitivo
☐ Campos são organizados
☐ Resumo visual é claro
☐ Cores e ícones apropriados
☐ Responsive design funciona

TESTES DE DADOS:
☐ Suporta 3 unidades (dias/meses/anos)
☐ Fornecedor até 255 caracteres
☐ Duração aceita números
☐ Dados persistem após reload
☐ Múltiplas máquinas mantêm dados independentes
```

---

## 🐛 POSSÍVEIS PROBLEMAS E SOLUÇÕES

### Problema: Campos não aparecem ao marcar checkbox
**Solução:** 
1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Recarregue a página (F5)
3. Verifique se há erros no console (F12 → Console)

### Problema: Dados não salvam
**Solução:**
1. Verifique se há mensagem de erro vermelha
2. Abra console do navegador (F12)
3. Procure por erros em "Network"
4. Verifique se usuário tem permissão de editar

### Problema: Resumo não atualiza
**Solução:**
1. Verifique se há erros de JavaScript (F12 → Console)
2. Recarregue a página
3. Marque/desmarque o checkbox para resetar

### Problema: Coluna não encontrada no banco
**Solução:**
1. Execute a migração em setup_warranty_machines.php
2. Ou execute manualmente o SQL em verify_warranty_columns.sql
3. Verifique conexão com banco de dados

---

## ✅ RESULTADO ESPERADO

Ao final de todos os testes:
- ✓ Sistema funciona sem erros
- ✓ Dados persistem corretamente
- ✓ UI/UX é intuitiva
- ✓ Compatível com múltiplos browsers
- ✓ Responsivo em diferentes telas

---

**Testes Desenvolvidos:** 2024
**Versão:** 1.0
**Status:** Pronto para validação
