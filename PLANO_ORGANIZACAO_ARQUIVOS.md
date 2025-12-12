# 📁 PLANO DE ORGANIZAÇÃO DE ARQUIVOS - Sistema5

## 🎯 Objetivo
Organizar todos os arquivos PHP da raiz em pastas profissionais, similar à estrutura `dev/`, sem quebrar o sistema.

## 📊 PROGRESSO ATUAL

### ✅ MÓDULOS COMPLETOS (3/14)
- [x] **Barcode** - 100% Completo (2025-12-12)
- [x] **Logs** - 100% Completo (2025-12-12) - Commits: 8a9783b (inicial) + f299a63 (finalização)
- [x] **Users** - 100% Completo (2025-12-12) - Commit: 2f96e9a

### ⏳ EM ANDAMENTO (0/14)
- Nenhum

### 📋 PENDENTES (11/14)
- [ ] Movements (Próximo - Fase 1)
- [ ] Warranties
- [ ] Warehouse
- [ ] Machines
- [ ] Products
- [ ] Users
- [ ] Movements
- [ ] Admin
- [ ] Migrations
- [ ] AJAX
- [ ] Utils
- [ ] Config
- [ ] Public

---

## 📊 Estrutura Proposta

```
sistema5/
├── modules/                      # Módulos principais do sistema
│   ├── products/                 # Gestão de produtos
│   ├── machines/                 # Gestão de máquinas
│   ├── warehouse/                # Gestão de armazém
│   ├── warranties/               # Gestão de garantias
│   ├── users/                    # Gestão de usuários
│   ├── movements/                # Movimentações de estoque
│   ├── barcode/                  # Sistema de código de barras
│   └── logs/                     # Sistema de logs
├── admin/                        # Área administrativa
├── views/                        # Views de visualização/listagem
├── migrations/                   # (já existe - mover arquivos de migração da raiz)
├── ajax/                         # Endpoints AJAX
├── public/                       # Páginas públicas (dashboard, index, login)
├── utils/                        # Utilitários gerais
└── config/                       # Arquivos de configuração
```

---

## 📦 CATEGORIZAÇÃO DOS ARQUIVOS

### 1️⃣ MÓDULO: PRODUTOS (modules/products/)
- add_product.php
- edit_product.php
- delete_product.php
- products.php
- view_product.php
- product_stock_in.php
- product_stock_out.php
- product_history_view.php
- product_inputs_log.php
- product_outputs_log.php
- product_logs.php
- product_movement.php
- product_movements_log.php
- listProduct_stock_out.php
- give_stock_in.php
- give_stock_out.php
- quick_stock_in.php

### 2️⃣ MÓDULO: MÁQUINAS (modules/machines/)
- add_machine.php
- edit_machine.php
- delete_machine.php
- machines.php
- view_machine.php
- ready_machines.php
- machine_stock_in.php
- machine_stock_out.php
- machine_history_view.php
- machine_inputs_log.php
- machine_outputs_log.php
- machine_logs.php
- machine_movements_log.php
- ListMachine_stock_out.php
- give_machine_stock_in.php
- update_machine_status.php
- add_warranty_to_machines.php
- edit_warranty_machine.php

### 3️⃣ MÓDULO: ARMAZÉM (modules/warehouse/)
- add_warehouse.php
- edit_warehouse.php
- delete_warehouse.php
- warehouse.php
- view_warehouse.php
- warehouse_stock_in.php
- warehouse_stock_out.php
- warehouse_history_view.php
- warehouse_inputs_log.php
- warehouse_outputs_log.php
- give_warehouse_stock_in.php
- give_warehouse_stock_out.php
- add_warranty_template_id_warehouse.php
- edit_warranty_warehouse.php

### 4️⃣ MÓDULO: GARANTIAS (modules/warranties/)
- warranties.php
- create_warranty_view.php
- edit_warranty.php
- warranty_history_view.php
- list_warranty_items.php
- warranty_templates.php
- warranty_suppliers.php
- warranty_template_selector.php
- warranty_template_selector_inline.php
- warranty_supplier_selector_inline.php
- find_warranty_product.php
- get_warranty_suppliers.php
- export_warranties_csv.php

### 5️⃣ MÓDULO: USUÁRIOS (modules/users/)
- users.php
- add_admin_user.php
- edit_user.php
- delete_user.php
- profile.php
- change_password_user.php
- update_avatar.php

### 6️⃣ MÓDULO: MOVIMENTAÇÕES (modules/movements/)
- movementations.php
- movementations_entrada.php
- movementations_saida.php
- movement_history.php

### 7️⃣ MÓDULO: CÓDIGO DE BARRAS (modules/barcode/)
- barcode_print.php
- BarcodeBar.php
- barcode-loader.php
- scanner.php
- scanner_modal.php
- generate_barcode_image.php

### 8️⃣ MÓDULO: LOGS (modules/logs/)
- logs.php
- admin_logs.php
- system_logs.php
- log_functions.php
- get_log_details.php

### 9️⃣ ADMIN (admin/)
- settings.php
- deleted_items.php
- low_stock_alerts.php
- update_logo.php
- save_theme_preference.php

### 🔟 VIEWS (views/)
- Já tem arquivos view_*.php que serão movidos para seus respectivos módulos

### 1️⃣1️⃣ MIGRAÇÕES (migrations/)
**Mover da raiz para migrations/:**
- execute_all_migrations.php
- execute_machine_migration.php
- execute_warehouse_warranty_migration.php
- migrate_passwords.php
- migrate_warehouse_template_id.php
- migrate_warranty_history.php
- run_migration.php
- run_warranty_migration.php
- setup_machine_stock_system.php
- setup_warranty_machines.php
- setup_warranty_machines_columns.php

### 1️⃣2️⃣ AJAX/API (ajax/)
- create_template_ajax.php
- find_warranty_product.php
- get_warranty_suppliers.php
- update_machine_status.php
- upload_image.php
- delete_image.php

### 1️⃣3️⃣ PUBLIC (public/)
- index.php
- dashboard.php
- login.php
- logout.php
- forgot_password.php
- reset_password.php

### 1️⃣4️⃣ UTILS (utils/)
- search.php
- search_code.php
- export_csv.php

### 1️⃣5️⃣ CONFIG (config/)
- config.php
- config_sqlite.php

---

## ⚙️ ESTRATÉGIA DE MIGRAÇÃO (SEM QUEBRAR NADA)

### **FASE 1: PREPARAÇÃO**
1. ✅ Criar backup completo
2. ✅ Criar estrutura de pastas
3. ✅ Criar arquivo de redirecionamento automático

### **FASE 2: CRIAR SISTEMA DE REDIRECIONAMENTO**
Criar um arquivo `_redirect_handler.php` na raiz que:
- Detecta quando um arquivo antigo é acessado
- Redireciona automaticamente para o novo local
- Registra os acessos em log

### **FASE 3: MIGRAÇÃO POR MÓDULO**
**Ordem sugerida:**
1. Módulo de Código de Barras (poucos arquivos, baixo impacto)
2. Módulo de Logs
3. Módulo de Garantias
4. Módulo de Armazém
5. Módulo de Máquinas
6. Módulo de Produtos
7. Módulo de Usuários
8. Módulo de Movimentações
9. Admin
10. Migrações
11. AJAX
12. Utils
13. Config
14. Public (por último, pois são mais acessados)

### **FASE 4: ATUALIZAÇÃO DE INCLUDES/REQUIRES**
- Atualizar todos os `require`, `require_once`, `include` nos arquivos
- Atualizar referências em JavaScript
- Atualizar links no HTML/PHP

### **FASE 5: TESTES E VALIDAÇÃO**
- Testar cada módulo após migração
- Verificar logs de erro
- Validar funcionalidades

### **FASE 6: LIMPEZA**
- Remover arquivos antigos da raiz
- Remover sistema de redirecionamento (opcional)
- Atualizar documentação

---

## 🚀 COMO EXECUTAR

### **Opção A: Migração Manual Etapa por Etapa**
Migrar um módulo por vez, testar, depois próximo.

### **Opção B: Script Automatizado com Redirecionamento**
Criar script que:
1. Move arquivos
2. Cria redirects automáticos
3. Atualiza includes
4. Valida links

### **Opção C: Híbrido (RECOMENDADO)**
1. Script cria estrutura e move arquivos
2. Sistema de redirect mantém compatibilidade
3. Atualização manual de includes importantes
4. Testes graduais por módulo

---

## ⚠️ ARQUIVOS QUE FICAM NA RAIZ

Estes arquivos devem permanecer na raiz:
- composer.json
- .htaccess (se existir)
- README.md e outros arquivos de documentação na raiz

---

## 📝 PRÓXIMOS PASSOS

**Escolha uma das opções:**

1. **Começar com Opção C (RECOMENDADO)**
   - Criar estrutura de pastas
   - Criar sistema de redirect
   - Migrar módulo por módulo

2. **Começar com módulo específico**
   - Escolher um módulo pequeno (ex: barcode)
   - Fazer migração completa
   - Testar
   - Replicar processo

3. **Fazer tudo de uma vez (RISCO ALTO)**
   - Não recomendado, mas possível com backup

---

## 🎯 DECISÃO NECESSÁRIA

**Qual opção você prefere?**
- A) Opção C - Híbrido com redirects automáticos
- B) Começar com um módulo pequeno (barcode ou logs)
- C) Outra abordagem

**Aguardando sua decisão para começar! 🚀**

---

## 📊 ANÁLISE DO ESTADO ATUAL (2025-12-12)

### ✅ O QUE JÁ FOI FEITO

#### 1. Módulo Barcode (100% Completo)
- ✅ Pasta `modules/barcode/` criada e funcional
- ✅ Biblioteca barcode-lib movida para `modules/barcode/barcode-lib/`
- ✅ Arquivos principais migrados:
  - [barcode_print.php](modules/barcode/barcode_print.php)
  - [generate_barcode_image.php](modules/barcode/generate_barcode_image.php)
  - [scanner_modal.php](modules/barcode/scanner_modal.php)
  - [barcode-loader.php](modules/barcode/barcode-loader.php)
  - [BarcodeBar.php](modules/barcode/BarcodeBar.php)
- ✅ Arquivos antigos removidos da raiz (visível no git status)
- ✅ Sistema de barcode testado e funcionando

**LIÇÕES APRENDIDAS DO MÓDULO BARCODE:**
- ✓ Migração de biblioteca completa funcionou bem
- ✓ Remoção dos arquivos da raiz foi segura
- ✓ Includes/requires foram atualizados corretamente

---

### 📂 NOVOS ARQUIVOS DETECTADOS (não listados no plano original)

#### Pasta `api/` (NOVA)
- Detectada pasta `api/` nos arquivos não rastreados
- **AÇÃO NECESSÁRIA**: Verificar conteúdo e documentar estrutura

#### Arquivos de Warehouse (já existem na raiz)
- ✅ add_warehouse.php
- ✅ edit_warehouse.php
- ✅ delete_warehouse.php
- ✅ warehouse.php
- ✅ view_warehouse.php
- ✅ warehouse_stock_in.php
- ✅ warehouse_stock_out.php
- ✅ warehouse_history_view.php
- ✅ warehouse_inputs_log.php
- ✅ warehouse_outputs_log.php
- ✅ give_warehouse_stock_in.php
- ✅ give_warehouse_stock_out.php

#### Arquivos de Warranty em Warehouse
- ✅ add_warranty_template_id_warehouse.php
- ✅ edit_warranty_warehouse.php

#### Novos Arquivos CSS
- ✅ CSS/machine-components-modal.css
- ✅ CSS/media-upload.css
- ✅ CSS/sidebar-animations.css

#### Novos Arquivos JavaScript
- ✅ js/history-modal.js
- ✅ js/machine-component-manager.js
- ✅ js/machine-components-modal.js
- ✅ js/media-upload.js
- ✅ js/simple-component-search.js

#### Novos Arquivos de Include
- ✅ includes/machine_components_functions.php
- ✅ includes/warranty_modal_edit_warehouse.php

---

### 🔍 ARQUIVOS AINDA NA RAIZ (por módulo)

#### PRODUTOS (15 arquivos)
```
✗ add_product.php
✗ edit_product.php
✗ delete_product.php
✗ products.php
✗ view_product.php (não listado originalmente)
✗ product_stock_in.php (não existe)
✗ product_stock_out.php (não existe)
✗ product_history_view.php
✗ give_stock_in.php
✗ give_stock_out.php
✗ quick_stock_in.php (não existe)
✗ listProduct_stock_out.php
✗ product_inputs_log.php (não existe)
✗ product_outputs_log.php (não existe)
✗ product_logs.php (não existe)
✗ product_movement.php (não existe)
✗ product_movements_log.php (não existe)
```

#### MÁQUINAS (14 arquivos)
```
✗ add_machine.php
✗ edit_machine.php
✗ delete_machine.php
✗ machines.php
✗ view_machine.php (não existe)
✗ ready_machines.php
✗ machine_stock_in.php (não existe)
✗ machine_stock_out.php (não existe)
✗ machine_history_view.php (não existe)
✗ ListMachine_stock_out.php
✗ give_machine_stock_in.php
✗ update_machine_status.php
✗ add_warranty_to_machines.php
✗ edit_warranty_machine.php
✗ machine_inputs_log.php (não existe)
✗ machine_outputs_log.php (não existe)
✗ machine_logs.php (não existe)
✗ machine_movements_log.php (não existe)
```

#### WAREHOUSE (12 arquivos)
```
✗ add_warehouse.php
✗ edit_warehouse.php
✗ delete_warehouse.php
✗ warehouse.php
✗ view_warehouse.php
✗ warehouse_stock_in.php
✗ warehouse_stock_out.php
✗ warehouse_history_view.php
✗ warehouse_inputs_log.php
✗ warehouse_outputs_log.php
✗ give_warehouse_stock_in.php
✗ give_warehouse_stock_out.php
✗ add_warranty_template_id_warehouse.php
✗ edit_warranty_warehouse.php
```

#### GARANTIAS (11 arquivos)
```
✗ warranties.php
✗ create_warranty_view.php
✗ edit_warranty.php
✗ warranty_history_view.php
✗ list_warranty_items.php
✗ warranty_templates.php
✗ warranty_suppliers.php
✗ warranty_template_selector.php
✗ warranty_template_selector_inline.php
✗ warranty_supplier_selector_inline.php
✗ find_warranty_product.php
✗ get_warranty_suppliers.php
✗ export_warranties_csv.php
```

#### USUÁRIOS (7 arquivos)
```
✗ users.php
✗ add_admin_user.php
✗ edit_user.php
✗ delete_user.php
✗ profile.php
✗ change_password_user.php
✗ update_avatar.php
```

#### MOVIMENTAÇÕES (4 arquivos)
```
✗ movementations.php
✗ movementations_entrada.php
✗ movementations_saida.php
✗ movement_history.php (não existe)
```

#### LOGS (5 arquivos)
```
✗ logs.php
✗ admin_logs.php
✗ system_logs.php
✗ log_functions.php
✗ get_log_details.php
```

#### ADMIN (5 arquivos)
```
✗ settings.php
✗ deleted_items.php
✗ low_stock_alerts.php
✗ update_logo.php
✗ save_theme_preference.php
```

#### MIGRAÇÕES (10 arquivos)
```
✗ execute_all_migrations.php
✗ execute_machine_migration.php
✗ execute_warehouse_warranty_migration.php
✗ migrate_passwords.php
✗ migrate_warehouse_template_id.php
✗ migrate_warranty_history.php
✗ run_migration.php
✗ run_warranty_migration.php
✗ setup_machine_stock_system.php
✗ setup_warranty_machines.php (não existe)
✗ setup_warranty_machines_columns.php (não existe)
```

#### AJAX/API (7 arquivos)
```
✗ create_template_ajax.php
✗ find_warranty_product.php
✗ get_warranty_suppliers.php
✗ update_machine_status.php
✗ upload_image.php
✗ delete_image.php
✗ Pasta api/ (verificar conteúdo)
```

#### PUBLIC (6 arquivos)
```
✗ index.php
✗ dashboard.php
✗ login.php
✗ logout.php
✗ forgot_password.php
✗ reset_password.php
```

#### UTILS (3 arquivos)
```
✗ search.php
✗ search_code.php
✗ export_csv.php
```

#### CONFIG (2 arquivos)
```
✗ config.php
✗ config_sqlite.php
```

---

## 🎯 RECOMENDAÇÕES PARA CONTINUIDADE

### ESTRATÉGIA RECOMENDADA: Abordagem Incremental

**Com base no sucesso do módulo Barcode, sugiro continuar com:**

### FASE 1: MÓDULOS PEQUENOS (Próximos 3 módulos)
**Ordem recomendada:**

#### 1️⃣ PRÓXIMO: Módulo LOGS
**Motivo:** Poucos arquivos (5), baixo impacto, funcionalidade isolada
**Arquivos:**
- logs.php
- admin_logs.php
- system_logs.php
- log_functions.php
- get_log_details.php

**Estimativa de complexidade:** BAIXA
**Tempo estimado:** 30-60 minutos
**Risco:** MUITO BAIXO

---

#### 2️⃣ SEGUNDO: Módulo USUÁRIOS
**Motivo:** Funcionalidade isolada, poucos arquivos (7)
**Arquivos:**
- users.php
- add_admin_user.php
- edit_user.php
- delete_user.php
- profile.php
- change_password_user.php
- update_avatar.php

**Estimativa de complexidade:** BAIXA
**Tempo estimado:** 45-90 minutos
**Risco:** BAIXO

---

#### 3️⃣ TERCEIRO: Módulo MOVIMENTAÇÕES
**Motivo:** Poucos arquivos (4), funcionalidade específica
**Arquivos:**
- movementations.php
- movementations_entrada.php
- movementations_saida.php

**Estimativa de complexidade:** MÉDIA
**Tempo estimado:** 60-120 minutos
**Risco:** MÉDIO (pode ter interdependências com produtos/warehouse)

---

### FASE 2: MÓDULOS MÉDIOS (Próximos 4 módulos)

#### 4️⃣ QUARTO: Módulo GARANTIAS
**Arquivos:** 11 arquivos
**Complexidade:** MÉDIA
**Risco:** MÉDIO (integração com produtos, máquinas e warehouse)

#### 5️⃣ QUINTO: Módulo WAREHOUSE
**Arquivos:** 12 arquivos
**Complexidade:** MÉDIA-ALTA
**Risco:** MÉDIO-ALTO (muitas integrações)

#### 6️⃣ SEXTO: Módulo PRODUTOS
**Arquivos:** 15 arquivos (verificar quais existem)
**Complexidade:** ALTA
**Risco:** ALTO (core do sistema)

#### 7️⃣ SÉTIMO: Módulo MÁQUINAS
**Arquivos:** 14 arquivos (verificar quais existem)
**Complexidade:** ALTA
**Risco:** ALTO (core do sistema)

---

### FASE 3: ARQUIVOS DE SUPORTE

#### 8️⃣ ADMIN (5 arquivos)
#### 9️⃣ AJAX/API (verificar pasta api/)
#### 🔟 UTILS (3 arquivos)
#### 1️⃣1️⃣ MIGRAÇÕES (10 arquivos)
#### 1️⃣2️⃣ CONFIG (2 arquivos)
#### 1️⃣3️⃣ PUBLIC (6 arquivos) - POR ÚLTIMO

---

## 🚀 PRÓXIMOS PASSOS IMEDIATOS

### OPÇÃO A: Continuar migração (RECOMENDADO)
1. **Migrar módulo LOGS** (próximo na fila)
   - Criar pasta `modules/logs/`
   - Mover 5 arquivos
   - Atualizar includes/requires
   - Testar funcionalidade
   - Commit

2. **Migrar módulo USUÁRIOS**
   - Seguir mesmo processo

3. **Migrar módulo MOVIMENTAÇÕES**
   - Seguir mesmo processo

### OPÇÃO B: Verificar arquivos novos primeiro
1. Verificar conteúdo da pasta `api/`
2. Documentar arquivos não listados no plano original
3. Atualizar categorização
4. Depois continuar migração

### OPÇÃO C: Criar sistema de redirect antes de continuar
1. Criar `_redirect_handler.php`
2. Configurar .htaccess
3. Testar redirecionamentos
4. Continuar migrações com rede de segurança

---

## ⚠️ OBSERVAÇÕES IMPORTANTES

### Arquivos que não existem (listados no plano mas não encontrados):
- product_stock_in.php
- product_stock_out.php
- product_inputs_log.php
- product_outputs_log.php
- product_logs.php
- product_movement.php
- product_movements_log.php
- quick_stock_in.php
- machine_stock_in.php
- machine_stock_out.php
- machine_history_view.php
- machine_inputs_log.php
- machine_outputs_log.php
- machine_logs.php
- machine_movements_log.php
- movement_history.php
- setup_warranty_machines.php
- setup_warranty_machines_columns.php
- view_product.php
- view_machine.php

**AÇÃO NECESSÁRIA:** Verificar se esses arquivos:
1. Foram removidos/renomeados
2. Nunca existiram
3. Estão em outra localização

### Novos arquivos detectados (não documentados):
- Pasta `api/` completa
- Vários arquivos CSS novos
- Vários arquivos JavaScript novos
- Arquivos de include novos

**AÇÃO NECESSÁRIA:** Documentar e categorizar

---

## 📋 CHECKLIST PARA PRÓXIMA MIGRAÇÃO (Módulo LOGS)

- [ ] Criar pasta `modules/logs/`
- [ ] Mover arquivos:
  - [ ] logs.php
  - [ ] admin_logs.php
  - [ ] system_logs.php
  - [ ] log_functions.php
  - [ ] get_log_details.php
- [ ] Buscar e atualizar todos os `require/include` que referenciam esses arquivos
- [ ] Buscar e atualizar todos os links HTML/PHP
- [ ] Buscar e atualizar referências em JavaScript
- [ ] Testar funcionalidade de logs
- [ ] Verificar erros no console/logs PHP
- [ ] Commit com mensagem descritiva
- [ ] Atualizar este plano marcando módulo como completo

---

## 🎯 DECISÃO IMEDIATA NECESSÁRIA

**Qual próxima ação você prefere?**

A) **Migrar módulo LOGS agora** (rápido, baixo risco, +1 módulo completo)

B) **Verificar pasta api/ e arquivos novos** (documentação e análise)

C) **Criar sistema de redirect primeiro** (segurança extra)

D) **Outra ação**

**Aguardando sua decisão! 🚀**
