/**
 * Sistema SIMPLES de busca de componentes
 * SEM modais duplos, SEM cascatas, SEM complexidade
 */

// Previne carregamento duplicado
if (typeof window.simpleComponentSearchLoaded !== 'undefined' && window.simpleComponentSearchLoaded === true) {
    console.warn('⚠️ simple-component-search.js já foi carregado, ignorando duplicata');
    // Retorna imediatamente sem executar o resto do script
} else {
    // Marca como carregado
    window.simpleComponentSearchLoaded = true;
    console.log('✅ simple-component-search.js carregando...');

// ===================================================
// FUNÇÃO SIMPLES DE BUSCA
// ===================================================
    function searchComponent(event, category, targetFieldId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    console.log('═══════════════════════════════════════════════════════════');
    console.log('🔍 BUSCAR COMPONENTE:');
    console.log('  Categoria:', category);
    console.log('  Campo:', targetFieldId);
    console.log('═══════════════════════════════════════════════════════════');

    // Busca produtos da categoria
    // Detecta o caminho base do projeto (ex: /sistema5/)
    const pathParts = window.location.pathname.split('/').filter(p => p);
    const projectRoot = pathParts.length > 0 ? '/' + pathParts[0] : '';
    const apiUrl = `${projectRoot}/api/get_products_by_category.php?category=${encodeURIComponent(category)}`;
    console.log('🔧 DEBUG PATH:', {
        fullPath: window.location.pathname,
        pathParts: pathParts,
        projectRoot: projectRoot,
        finalUrl: apiUrl
    });
    
    fetch(apiUrl)
        .then(response => {
            console.log('✅ Resposta recebida. Status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Dados da API:', data);
            
            if (data.success && data.data && data.data.length > 0) {
                console.log(`✅ ${data.data.length} produto(s) encontrado(s)`);
                
                // 🔍 DEBUG: Mostra informações de estoque de cada produto
                console.log('📊 Detalhes de estoque dos produtos:');
                data.data.forEach((product, idx) => {
                    console.log(`  [${idx}] ${product.name}: ${product.quantity} un. (ID: ${product.id})`);
                });
                
                showProductsModal(data.data, targetFieldId, category);
            } else {
                console.warn('❌ Nenhum produto encontrado');
                console.log('   Razão:', data.message || 'Sem mensagem');
                alert(`❌ Nenhum produto encontrado na categoria: ${category}\n\nVocê pode ter criado um produto, mas:\n1. Ele pode estar com estoque = 0\n2. Pode estar com status = 'sold' ou outro\n3. A categoria pode estar diferente\n\nVerifique em Produtos > Estoque`);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao buscar produtos:', error);
            alert('❌ Erro ao buscar produtos. Verifique o console para mais detalhes.');
        });
}

// ===================================================
// MODAL SIMPLES DE SELEÇÃO (SEM BOOTSTRAP)
// ===================================================
function showProductsModal(products, targetFieldId, category) {
    // Remove modal anterior se existir
    const oldModal = document.getElementById('simpleComponentModal');
    if (oldModal) {
        oldModal.remove();
    }

    // Cria lista de produtos COM DEBUG
    console.log('📦 Produtos recebidos:', products.length);
    products.forEach((p, i) => {
        console.log(`  [${i}] ID: ${p.id}, Nome: ${p.name}`);
    });

    const productsList = products.map(product => {
        // IMPORTANTE: Garante que product.id não seja undefined
        const prodId = product.id || product.product_id || 0;
        const stockQty = parseInt(product.quantity) || 0;
        if (!prodId) {
            console.warn('⚠️ Produto sem ID:', product);
        }

        return `
        <div class="product-item border-bottom p-3" style="cursor: pointer;"
             onclick="selectProduct(event, '${product.name.replace(/'/g, "\\'")}', '${targetFieldId}', ${prodId}, ${stockQty})">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${product.name}</strong>
                    ${product.manufacturer ? `<br><small class="text-secondary">${product.manufacturer} ${product.model || ''}</small>` : ''}
                    <br><small class="text-muted">ID: ${prodId}</small>
                </div>
                <span class="badge ${getStockClass(product.quantity)}">${product.quantity} un.</span>
            </div>
        </div>
        `;
    }).join('');

    // Cria modal HTML SEM Bootstrap (apenas CSS)
    const modalHTML = `
        <div id="simpleComponentModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 10000; padding: 20px;">
            <div class="modal-content" style="width: 100%; max-width: 500px; max-height: 70vh; display: flex; flex-direction: column; overflow: hidden;">
                <!-- Header -->
                <div class="modal-header" style="flex-shrink: 0;">
                    <h5 class="modal-title" style="margin: 0;">
                        <i class="fas fa-search me-2"></i>Selecionar Produto
                    </h5>
                    <button type="button" class="btn-close" onclick="closeSimpleComponentModal()" style="margin: 0;"></button>
                </div>
                
                <!-- Body -->
                <div class="modal-body" style="overflow-y: auto; flex: 1; display: flex; flex-direction: column; padding: 15px;">
                    <input type="text" class="form-control mb-3" id="productSearchInput" placeholder="Buscar produto..." style="font-size: 0.95rem; flex-shrink: 0;">
                    <div id="productsListContainer" style="overflow-y: auto; flex: 1; min-height: 0;">
                        ${productsList}
                    </div>
                </div>
                
                <!-- Footer -->
                <div style="flex-shrink: 0; padding: 12px 15px; border-top: 1px solid var(--bs-border-color); display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeSimpleComponentModal()">Fechar</button>
                </div>
            </div>
        </div>
    `;

    // Adiciona ao body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Busca no modal
    document.getElementById('productSearchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const filtered = products.filter(p =>
            p.name.toLowerCase().includes(searchTerm) ||
            (p.manufacturer && p.manufacturer.toLowerCase().includes(searchTerm)) ||
            (p.model && p.model.toLowerCase().includes(searchTerm))
        );

        const newList = filtered.map(product => `
            <div class="product-item border-bottom p-3" style="cursor: pointer;" onclick="selectProduct(event, '${product.name.replace(/'/g, "\\'")}', '${targetFieldId}', ${product.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${product.name}</strong>
                        ${product.manufacturer ? `<br><small class="text-secondary">${product.manufacturer} ${product.model || ''}</small>` : ''}
                    </div>
                    <span class="badge ${getStockClass(product.quantity)}">${product.quantity} un.</span>
                </div>
            </div>
        `).join('');

        document.getElementById('productsListContainer').innerHTML = newList || '<p class="text-muted text-center py-4">Nenhum produto encontrado</p>';
    });

    // Fechar ao clicar no backdrop
    document.getElementById('simpleComponentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSimpleComponentModal();
        }
    });
}

// ===================================================
// FECHAR MODAL CUSTOMIZADO
// ===================================================
function closeSimpleComponentModal() {
    const modal = document.getElementById('simpleComponentModal');
    if (modal) {
        modal.remove();
    }
}

// ===================================================
// SELECIONAR PRODUTO COM VALIDAÇÃO DE ESTOQUE
// ===================================================
function selectProduct(event, productName, targetFieldId, productId, stockQty) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log('═══════════════════════════════════════════════════════════');
    console.log('🎯 selectProduct chamado (COM VALIDAÇÃO DE ESTOQUE):');
    console.log('  productName:', productName);
    console.log('  targetFieldId:', targetFieldId);
    console.log('  productId:', productId);
    console.log('  stockQty:', stockQty);
    console.log('═══════════════════════════════════════════════════════════');

    try {
        // 🔍 VALIDAÇÃO DE ESTOQUE: Conta quantas vezes este produto já foi adicionado
        const normalizedProductId = parseInt(productId, 10);
        let currentQuantity = 0;

        // Busca em TODOS os containers de chips por este produto
        const allChipsContainers = document.querySelectorAll('.component-chips-container');
        allChipsContainers.forEach(container => {
            const chips = container.querySelectorAll(`[data-product-id="${normalizedProductId}"]`);
            chips.forEach(chip => {
                const chipText = chip.querySelector('span').textContent;
                const quantityMatch = chipText.match(/\((\d+)x\)/);
                const qty = quantityMatch ? parseInt(quantityMatch[1]) : 1;
                currentQuantity += qty;
                console.log(`  📊 Encontrado chip: "${chipText}" -> qty=${qty}`);
            });
        });

        console.log(`  📊 Quantidade atual deste produto: ${currentQuantity}`);
        console.log(`  📦 Estoque disponível: ${stockQty}`);
        console.log(`  ✓ Quantidade após adicionar: ${currentQuantity + 1}`);

        // Valida se há estoque suficiente
        if (currentQuantity >= stockQty) {
            console.error('❌ ESTOQUE INSUFICIENTE!');
            alert(`❌ Estoque insuficiente!\n\n` +
                  `Produto: ${productName}\n` +
                  `Estoque disponível: ${stockQty} un.\n` +
                  `Já adicionado: ${currentQuantity} un.\n\n` +
                  `Você não pode adicionar mais unidades deste produto.`);
            return;  // Bloqueia a adição
        }

        console.log('✅ Estoque OK, prosseguindo...');

        // IMPORTANTE: Não verifica o campo aqui!
        // A função addProductChip já faz o mapeamento correto de CPU -> processor
        console.log('📍 Chamando addProductChip...');
        addProductChip(targetFieldId, productName, productId);
        console.log('✅ addProductChip executado com sucesso');

        // Fecha modal customizado
        console.log('📍 Fechando modal...');
        closeSimpleComponentModal();

        // Feedback com informação de estoque
        const remainingStock = stockQty - (currentQuantity + 1);
        if (productId) {
            showToast(`✅ ${productName} adicionado (Restam ${remainingStock} un. em estoque)`);
        } else {
            showToast(`⚠️ Produto selecionado: ${productName} (SEM ID)`);
        }

        console.log('═══════════════════════════════════════════════════════════');
        console.log('✅ selectProduct CONCLUÍDO');
        console.log('═══════════════════════════════════════════════════════════');

    } catch (error) {
        console.error('❌ ERRO em selectProduct:', error);
        console.error('Stack:', error.stack);
        alert('Erro ao adicionar produto: ' + error.message);
    }
}

/**
 * Adiciona um chip de produto abaixo do campo
 * Chips representam produtos vinculados ao estoque
 *
 * IMPORTANTE: fieldId pode ser tanto o ID do campo HTML (processor, storage)
 * quanto a chave do componente (CPU, HDD). Faz mapeamento automático.
 */
function addProductChip(fieldId, productName, productId) {
    console.log('-----------------------------------------------------------');
    console.log('📍 addProductChip INICIADO (simple-component-search.js)');
    console.log('  fieldId:', fieldId);
    console.log('  productName:', productName);
    console.log('  productId:', productId);
    
    // 🔑 CRUCIAL: Garante que productId é number para comparações funcionarem
    const normalizedProductId = parseInt(productId, 10);
    console.log('  normalizedProductId:', normalizedProductId);
    
    if (isNaN(normalizedProductId)) {
        console.error('❌ ERRO: productId inválido:', productId);
        throw new Error('Product ID inválido: ' + productId);
    }

    // Mapeamento de component key para field ID
    const componentKeyToFieldId = {
        'CPU': 'processor',
        'RAM': 'memory',
        'HDD': 'storage',
        'GPU': 'graphics',
        'Motherboard': 'motherboard',
        'PSU': 'power_supply',
        'Case': 'case_type'
    };

    // Se fieldId é uma chave de componente, converte para field ID
    const actualFieldId = componentKeyToFieldId[fieldId] || fieldId;
    console.log('  actualFieldId após mapeamento:', actualFieldId);

    const field = document.getElementById(actualFieldId);
    if (!field) {
        console.error('❌ ERRO: Campo não encontrado:', actualFieldId, '(original:', fieldId, ')');
        throw new Error('Campo ' + actualFieldId + ' não encontrado');
    }

    console.log('✅ Campo encontrado:', field.id);

    // Define se permite múltiplos
    // storage e memory permitem múltiplos
    // HDD e RAM também permitem (são os componentKeys)
    const allowMultiple = (actualFieldId === 'storage' || actualFieldId === 'memory' || fieldId === 'HDD' || fieldId === 'RAM');
    console.log('  allowMultiple:', allowMultiple);

    // Usa componentKey (original fieldId) para o ID do container de chips
    // Mantém compatibilidade com machine-components-integration.js
    const chipContainerId = `${fieldId}-chips`;
    console.log('  chipContainerId:', chipContainerId);

    // Encontra ou cria o container de chips
    let chipsContainer = document.getElementById(chipContainerId);
    if (!chipsContainer) {
        console.log('📍 Container de chips não existe, criando...');
        chipsContainer = document.createElement('div');
        chipsContainer.id = chipContainerId;
        chipsContainer.className = 'component-chips-container mt-2';
        chipsContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 20px;';

        // Insere após o input-group
        const inputGroup = field.closest('.input-group');
        if (inputGroup) {
            console.log('  Inserindo após input-group');
            inputGroup.parentNode.insertBefore(chipsContainer, inputGroup.nextSibling);
        } else {
            console.log('  Inserindo após campo');
            field.parentNode.insertBefore(chipsContainer, field.nextSibling);
        }
        console.log('✅ Container de chips criado:', chipsContainer.id);
    } else {
        console.log('✅ Container de chips já existe:', chipsContainer.id);
    }

    // Para campos com múltiplos (HDD, RAM)
    // Procura por chip EXISTENTE do MESMO produto
    let existingChip = null;
    let existingQuantity = 1;

    if (allowMultiple) {
        console.log(`  🔍 MODO MÚLTIPLO ATIVO - Procurando chips existentes do produto ${normalizedProductId}`);
        console.log(`  🔍 Container de busca: #${chipContainerId}`);
        console.log(`  🔍 Seletor: [data-product-id="${normalizedProductId}"]`);

        // DEBUG: Lista TODOS os chips no container
        const allChipsInContainer = chipsContainer.querySelectorAll('.component-chip');
        console.log(`  📊 Total de chips no container: ${allChipsInContainer.length}`);
        allChipsInContainer.forEach((chip, idx) => {
            console.log(`    [${idx}] productId: ${chip.getAttribute('data-product-id')}, text: "${chip.querySelector('span').textContent}"`);
        });

        const existingChips = chipsContainer.querySelectorAll(`[data-product-id="${normalizedProductId}"]`);
        console.log(`  📊 Chips existentes DO MESMO PRODUTO (${normalizedProductId}): ${existingChips.length}`);
        
        if (existingChips.length > 0) {
            console.log(`  ✅ CHIP EXISTENTE ENCONTRADO! Consolidando...`);
            // Se já existe chip deste produto, atualiza a quantidade
            existingChip = existingChips[existingChips.length - 1]; // Pega o último
            const existingText = existingChip.querySelector('span').textContent;
            console.log(`  📝 Texto existente do span: "${existingText}"`);
            console.log(`  📝 productId do chip existente: ${existingChip.getAttribute('data-product-id')}`);
            console.log(`  📝 productId normalizado buscado: ${normalizedProductId}`);

            // Extrai quantidade do texto: "HD 500GB (2x)" -> 2
            const quantityMatch = existingText.match(/\((\d+)x\)/);
            existingQuantity = quantityMatch ? parseInt(quantityMatch[1]) : 1;
            console.log(`  📝 Quantidade existente extraída: ${existingQuantity} (regex match: ${quantityMatch ? quantityMatch[1] : 'nenhum'})`);

            // Incrementa quantidade
            existingQuantity++;
            console.log(`  📝 Nova quantidade após incremento: ${existingQuantity}`);

            // Atualiza o chip existente com nova quantidade
            const productNameWithoutQuantity = existingText.replace(/\s*\(\d+x\)$/, '').trim();
            console.log(`  📝 Nome do produto sem quantidade: "${productNameWithoutQuantity}"`);

            const newText = existingQuantity > 1
                ? `${productNameWithoutQuantity} (${existingQuantity}x)`
                : productNameWithoutQuantity;
            console.log(`  📝 Novo texto calculado: "${newText}"`);

            existingChip.querySelector('span').textContent = newText;
            console.log(`  ✅ Texto do chip ATUALIZADO! Verificando: "${existingChip.querySelector('span').textContent}"`);
            console.log(`  ✅✅✅ PRODUTO DUPLICADO CONSOLIDADO COM SUCESSO!`);

            // Atualiza JSON e retorna (não cria novo chip)
            if (typeof buildAndUpdateComponentsJSON === 'function') {
                console.log('📝 Atualizando JSON após consolidação...');
                buildAndUpdateComponentsJSON();
            }
            console.log('-----------------------------------------------------------');
            return;
        } else {
            console.log(`  ℹ️ Nenhum chip existente encontrado para produto ${normalizedProductId}, criando novo chip...`);
        }
    } else {
        // Para componentes únicos (CPU, GPU, etc.), remove o anterior
        console.log('  Modo único, limpando chips anteriores...');
        chipsContainer.innerHTML = '';
    }

    console.log('📍 Criando novo chip...');

    // Gera ID único para o chip
    const chipUniqueId = `chip-${fieldId}-${normalizedProductId}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

    // Para múltiplos, não mostra quantidade no primeiro chip
    const displayQuantity = '';
    console.log(`  displayQuantity: "${displayQuantity}" (primeiro chip não mostra quantidade)`);

    // Cria o chip
    const chip = document.createElement('div');
    chip.className = 'component-chip';
    chip.id = chipUniqueId;
    chip.setAttribute('data-product-id', normalizedProductId);
    chip.setAttribute('data-product-name', productName);
    chip.style.cssText = `
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
        transition: all 0.2s ease;
        cursor: default;
    `;
    chip.innerHTML = `
        <i class="fas fa-link" style="font-size: 0.75rem;"></i>
        <span>${productName}${displayQuantity}</span>
        <button type="button" class="btn-remove-chip" style="
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            font-size: 0.7rem;
            transition: background 0.2s;
        " onmouseover="this.style.background='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <i class="fas fa-times"></i>
        </button>
    `;

    console.log('✅ Chip HTML criado');

    // Adiciona evento de remoção
    const removeBtn = chip.querySelector('.btn-remove-chip');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            console.log(`🗑️ Removendo chip: ${productName} (ID: ${normalizedProductId})`);
            chip.style.opacity = '0';
            chip.style.transform = 'scale(0.8)';
            setTimeout(() => {
                chip.remove();
                console.log('✅ Chip removido do DOM');

                // Chips duplicados são permitidos, então não precisa renumerar
                // Cada chip representa 1 unidade, mesmo que seja igual
                
                // IMPORTANTE: NÃO atualiza o campo de texto quando usando chips visuais
                // Os chips já representam visualmente os produtos, não precisa atualizar o texto
                
                // const remainingChips = chipsContainer.querySelectorAll('.component-chip');
                // if (remainingChips.length === 0) {
                //     field.value = '';
                // } else if (allowMultiple) {
                //     const names = Array.from(remainingChips).map(c => c.getAttribute('data-product-name'));
                //     field.value = names.join('\n');
                // } else {
                //     const lastName = remainingChips[0].getAttribute('data-product-name');
                //     field.value = lastName;
                // }

                // Atualiza o JSON de componentes
                if (typeof buildAndUpdateComponentsJSON === 'function') {
                    console.log('📝 Atualizando JSON após remoção...');
                    buildAndUpdateComponentsJSON();
                }
            }, 200);
        });
        console.log('✅ Evento de remoção adicionado');
    }

    // Adiciona o chip ao container
    chipsContainer.appendChild(chip);
    console.log('✅ Chip adicionado ao container');
    
    // DEBUG: Mostra o texto do chip imediatamente após criar
    const spanText = chip.querySelector('span').textContent;
    console.log(`   📝 Texto do span no novo chip: "${spanText}"`);
    console.log(`   📝 displayQuantity utilizado: "${displayQuantity}"`);
    console.log(`   📝 allowMultiple: ${allowMultiple}`);

    // IMPORTANTE: NÃO atualiza o campo de texto quando usando chips visuais
    // Os chips já representam visualmente os produtos selecionados
    // O campo de texto pode conter observações manuais do usuário
    // O valor real será obtido do JSON dos componentes (machine-components-json)
    
    // const allChips = chipsContainer.querySelectorAll('.component-chip');
    // if (allowMultiple) {
    //     const names = Array.from(allChips).map(c => c.getAttribute('data-product-name'));
    //     field.value = names.join('\n');
    // } else {
    //     field.value = productName;
    // }

    // Atualiza o JSON se a função estiver disponível
    if (typeof buildAndUpdateComponentsJSON === 'function') {
        console.log('📝 Atualizando JSON de componentes...');
        buildAndUpdateComponentsJSON();
        
        // 🔍 NOVO DEBUG: Mostra JSON imediatamente após atualizar
        const hiddenField = document.getElementById('machine-components-json');
        if (hiddenField) {
            console.log('📦 JSON ATUAL NO CAMPO HIDDEN:', hiddenField.value);
            try {
                const jsonObj = JSON.parse(hiddenField.value || '{}');
                console.table(jsonObj);
            } catch (e) {
                console.error('❌ ERRO ao fazer parse do JSON:', e);
            }
        }
    }

    console.log(`✅✅✅ CHIP ADICIONADO COM SUCESSO: ${productName} (ID: ${normalizedProductId}, Múltiplos: ${allowMultiple})`);
    console.log('-----------------------------------------------------------');
}

// NOTA: Não precisamos mais do listener de edição manual
// Os chips ficam separados dos campos de texto
// O usuário pode digitar livremente nos campos (texto manual)
// E os chips representam produtos vinculados ao estoque

// ===================================================
// RENUMERAR CHIPS DUPLICADOS
// ===================================================
/**
 * Renumera os chips duplicados do mesmo produto para manter contagem correta
 * Ex: RAM 8GB (2x), RAM 8GB (3x) -> após remover o primeiro -> RAM 8GB, RAM 8GB (2x)
 */
function renumberDuplicateChips(container, productId, productName) {
    const duplicates = Array.from(container.querySelectorAll(`[data-product-id="${productId}"]`));
    console.log(`🔄 Renumerando ${duplicates.length} chip(s) do produto ${productId}`);

    duplicates.forEach((chip, index) => {
        const span = chip.querySelector('span');
        if (span) {
            // Mostra quantidade para todos os chips
            const count = index + 1;
            if (duplicates.length > 1) {
                span.textContent = `${productName} (${count}x)`;
            } else {
                // Se é o único, não precisa de número
                span.textContent = productName;
            }
        }
    });
}

// ===================================================
// UTILITÁRIOS
// ===================================================
function getStockClass(quantity) {
    if (quantity <= 0) return 'bg-danger';
    if (quantity < 5) return 'bg-warning';
    return 'bg-success';
}

function showToast(message) {
    // Cria toast simples
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);';
    toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
    document.body.appendChild(toast);

    // Remove após 3 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

console.log('═══════════════════════════════════════════════════════════');
console.log('✅ Simple Component Search carregado');
console.log('Funções disponíveis:');
console.log('  - searchComponent()');
console.log('  - selectProduct()');
console.log('  - addProductChip()');
console.log('  - closeSimpleComponentModal()');
console.log('═══════════════════════════════════════════════════════════');

// Torna as funções globais para garantir acesso
window.searchComponent = searchComponent;
window.selectProduct = selectProduct;
window.addProductChip = addProductChip;
window.closeSimpleComponentModal = closeSimpleComponentModal;

console.log('✅ Funções registradas no window global');

} // Fecha o bloco 'else' de prevençao de carregamento duplicado
