<!-- FILE: QUICK_START.md -->

# ⚡ Quick Start - Deploy em 5 Minutos

## 🎯 Objetivo
Colocar o sistema de seleção de componentes funcionando AGORA, com dados de teste.

---

## 📋 Pré-Requisitos
- MySQL/MariaDB rodando
- PHP 7.4+ com PDO MySQL
- Navegador moderno (Chrome, Firefox, etc)
- Acesso ao phpMyAdmin OU MySQL CLI

---

## 🚀 Passo 1: Executar SQL Migration (2 min)

### Via MySQL CLI
```bash
cd c:\xampp\htdocs\sistema5
mysql -u root -p seu_banco < migrations/implement_component_compatibility_system.sql
```

### Via phpMyAdmin
1. Abra http://localhost/phpmyadmin
2. Selecione seu banco de dados
3. Vá para aba **SQL**
4. Copie conteúdo de `migrations/implement_component_compatibility_system.sql`
5. Clique **Executar**

✓ Deve criar 6 tabelas e dados iniciais

---

## ✅ Passo 2: Verificar Criação (1 min)

Abra MySQL CLI ou phpMyAdmin e execute:

```sql
-- Verificar tabelas
SHOW TABLES LIKE 'component%';
SHOW TABLES LIKE 'compatibility%';
SHOW TABLES LIKE 'pc_%';

-- Verificar dados
SELECT COUNT(*) FROM component_categories;  -- Deve ser 8
SELECT COUNT(*) FROM compatibility_rules;   -- Deve ser 7+
```

---

## 🌐 Passo 3: Acessar Interface (1 min)

Abra seu navegador e vá para:

```
http://localhost/sistema5/modules/machines/component_selector.html
```

Você deve ver:
- ✓ Header roxo com "Montagem de Máquina"
- ✓ Painel esquerdo com 8 categorias
- ✓ Painel central vazio (aguardando seleção)
- ✓ Painel direito com resumo

---

## 🧪 Passo 4: Teste Rápido (1 min)

### Teste de Carregamento
1. Clique em **"CPU / Processador"** (primeira categoria)
2. Deve mostrar loading e depois componentes
3. Clique em um processador qualquer
4. Deve selecionar e atualizar resumo

### Teste de Compatibilidade
1. Selecione uma CPU (ex: Intel i5)
2. Clique em **"Placa Mãe"**
3. Deve filtrar placas compatíveis
4. Selecione uma
5. Deve validar compatibilidade sem erros

---

## 🔧 Passo 5: Adicionar Dados (Opcional, 5+ min)

Se quiser mais de 1 componente por categoria:

```sql
-- Adicionar CPU de teste
INSERT INTO component_specs (
    category, name, model, price, description,
    cpu_cores, cpu_threads, cpu_socket, 
    cpu_base_clock, cpu_boost_clock, cpu_tdp
) VALUES (
    'cpu', 'Intel Core i7-13700K', 'BX8070805600K', 699.90,
    'Processador topo de linha',
    16, 24, '1700', 3.4, 5.4, 125
);

-- Adicionar Placa Mãe de teste
INSERT INTO component_specs (
    category, name, model, price, description,
    motherboard_socket, motherboard_chipset, motherboard_form_factor,
    motherboard_ram_type, motherboard_max_ram, motherboard_ram_slots
) VALUES (
    'motherboard', 'ASUS ROG STRIX Z790', 'ROG-STRIX-Z790', 1200.00,
    'Placa mãe premium para gaming',
    '1700', 'Z790', 'ATX',
    'DDR5', 192, 4
);

-- Adicionar RAM de teste
INSERT INTO component_specs (
    category, name, model, price, description,
    ram_capacity, ram_type, ram_speed, ram_cas_latency, ram_modules
) VALUES (
    'ram', 'G.Skill Trident Z5 RGB', 'F5-6000J3040G32GX2-TZ5RK', 450.00,
    'Memória DDR5 super rápida',
    32, 'DDR5', 6000, 30, 2
);
```

---

## 🎨 Próximos Passos

### Integrar com add_machine.php
```php
// Em add_machine.php, após formulário básico
if (isset($_GET['type']) && $_GET['type'] === 'complete') {
    ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5>🖥️ Seleção de Componentes</h5>
        </div>
        <div class="card-body">
            <iframe src="modules/machines/component_selector.html" 
                    style="width: 100%; height: 900px; border: 1px solid #ddd; border-radius: 4px;"></iframe>
        </div>
    </div>
    <?php
}
?>
```

### Usar em Modal
```javascript
// Adicionar em add_machine.php
<button class="btn btn-primary" data-bs-toggle="modal" 
        data-bs-target="#componentModal">
    🖥️ Montar Máquina
</button>

<div class="modal fade" id="componentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Seleção de Componentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <iframe src="modules/machines/component_selector.html" 
                        style="width: 100%; height: 800px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
```

---

## 🐛 Troubleshooting Rápido

| Problema | Solução |
|----------|---------|
| **404 Not Found** | Verifique caminho do HTML em navegador |
| **Nenhum componente carrega** | Execute SQL migration completa |
| **API error 500** | Verifique logs PHP em `logs/` |
| **Componentes aparecem vazios** | Adicione dados INSERT (veja passo 5) |
| **Layout quebrado em mobile** | Limpe cache (Ctrl+Shift+R) |

---

## ✨ Checklist Rápido

- [ ] SQL migration executada
- [ ] 6 tabelas criadas
- [ ] Interface acessível
- [ ] Categorias carregam
- [ ] Seleção funciona
- [ ] Validação não gera erro

Tudo pronto? 🎉

---

## 📚 Documentação Completa

Para detalhes técnicos e troubleshooting avançado, consulte:

- **[GUIA_IMPLEMENTACAO_COMPONENTES.md](./GUIA_IMPLEMENTACAO_COMPONENTES.md)** - Guia completo
- **[CHECKLIST_IMPLEMENTACAO.md](./CHECKLIST_IMPLEMENTACAO.md)** - Checklist detalhado
- **[ANALISE_COMPATIBILIDADE_COMPONENTES.md](./ANALISE_COMPATIBILIDADE_COMPONENTES.md)** - Análise técnica

---

## 🎯 Próximo?

1. ✅ Deploy básico (este guia)
2. 📊 Adicionar mais dados (componentes reais)
3. 🔗 Integrar com add_machine.php
4. ✨ Customizar interface conforme necessário

Bom deploy! 🚀
