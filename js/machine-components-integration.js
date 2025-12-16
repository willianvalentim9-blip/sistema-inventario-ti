/**
 * Sistema de Integração de Componentes para Máquinas
 * Funciona com simple-component-search.js
 *
 * IMPORTANTE: Este arquivo deve ser carregado APÓS simple-component-search.js
 */

// Previne carregamento duplicado
if (typeof window.machineComponentsIntegrationLoaded !== 'undefined' && window.machineComponentsIntegrationLoaded === true) {
    console.warn('⚠️ machine-components-integration.js já foi carregado, ignorando duplicata');
    // Retorna imediatamente sem executar o resto do script
    // As funções já carregadas continuam disponíveis
} else {
    // Marca como carregado
    window.machineComponentsIntegrationLoaded = true;
    console.log('✅ machine-components-integration.js carregado');

    /**
     * Aguarda o carregamento de simple-component-search.js
     */
    function waitForSearchComponent(callback, attempts = 0) {
        if (typeof searchComponent !== 'undefined') {
            console.log('✅ searchComponent disponível, inicializando...');
            callback();
        } else if (attempts < 30) {
            // Aguarda até 3 segundos
            setTimeout(() => {
                waitForSearchComponent(callback, attempts + 1);
            }, 100);
        } else {
            console.error('❌ ERRO: searchComponent não foi carregado!');
            console.error('Verif se simple-component-search.js está sendo carregado antes de machine-components-integration.js');
        }
    }

    /**
     * Inicializa listeners para os botões de adicionar componentes
     * USANDO EVENT DELEGATION para funcionar com conteúdo dinâmico (modais AJAX)
     */
    function initializeComponentButtons() {
        console.log('🔧 Inicializando sistema de componentes com EVENT DELEGATION');

        // Remove listener antigo se existir para evitar duplicatas
        if (window.componentButtonClickHandler) {
            document.removeEventListener('click', window.componentButtonClickHandler);
        }

        // Define o handler como função global para poder remover depois
        window.componentButtonClickHandler = function(e) {
            // Verifica se o clique foi em um botão de componente ou dentro dele
        const button = e.target.closest('.btn-add-component-modal');
        if (!button) return;

        e.preventDefault();
        e.stopPropagation();

        const componentKey = button.getAttribute('data-component-key');
        const componentName = button.getAttribute('data-component-name');
        const category = button.getAttribute('data-category');

        console.log(`🔍 Botão clicado via EVENT DELEGATION:`, {
            componentKey,
            componentName,
            category
        });

        // Chama a função de busca de componentes (definida em simple-component-search.js)
        if (typeof searchComponent !== 'undefined') {
            searchComponent(e, category, componentKey);
        } else {
            console.error('❌ ERRO: searchComponent não está disponível!');
            alert('Erro ao carregar sistema de busca. Recarregue a página.');
        }
    };

    // Registra o listener no document (event delegation)
    document.addEventListener('click', window.componentButtonClickHandler);

    console.log('✅ Event delegation configurado para .btn-add-component-modal');
}

/**
 * Inicializa imediatamente ou quando o DOM estiver pronto
 * Funciona tanto em páginas normais quanto em conteúdo carregado via AJAX
 */
function initializeIfReady() {
    if (document.readyState === 'loading') {
        console.log('⏳ DOM ainda carregando, aguardando DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOMContentLoaded disparado');
            waitForSearchComponent(initializeComponentButtons);
        });
    } else {
        console.log('✅ DOM já está pronto, inicializando imediatamente');
        // DOM já está pronto, executa imediatamente
        waitForSearchComponent(initializeComponentButtons);
    }
}

// Executa a inicialização
initializeIfReady();

/**
 * Processa a seleção de um produto para um componente
 * Sobrescreve a função do simple-component-search.js
 * ATUALIZADO: Agora inclui validação de estoque
 */
window.selectProduct = function(event, productName, targetFieldId, productId, stockQty) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log('═══════════════════════════════════════════════════════════');
    console.log('🎯 selectProduct - Componente de Máquina (COM VALIDAÇÃO ESTOQUE)');
    console.log('  componentKey:', targetFieldId);
    console.log('  productName:', productName);
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

        // Adiciona o chip usando a função de machine-components-integration
        machineComponentsAddProductChip(targetFieldId, productName, productId);

        // Fecha a modal de busca
        closeSimpleComponentModal();

        // Atualiza o JSON de componentes
        buildAndUpdateComponentsJSON();

        // Feedback com informação de estoque
        const remainingStock = stockQty - (currentQuantity + 1);
        showToast(`✅ ${productName} adicionado (Restam ${remainingStock} un. em estoque)`);

        console.log('✅ selectProduct completado');

    } catch (error) {
        console.error('❌ Erro em selectProduct:', error);
        alert('Erro ao adicionar componente: ' + error.message);
    }
};

/**
 * Constrói o JSON de componentes e o salva em um campo hidden
 */
function buildAndUpdateComponentsJSON() {
    // Se está cancelando a modal, não executa
    if (typeof window.isMachineModalCanceling !== 'undefined' && window.isMachineModalCanceling === true) {
        console.log('⚠️ buildAndUpdateComponentsJSON: Modal está sendo cancelada, ignorando...');
        return;
    }
    
    console.log('🔨 buildAndUpdateComponentsJSON - Iniciando');

    const componentsData = {};
    const componentTypes = ['CPU', 'RAM', 'HDD', 'GPU', 'Motherboard', 'PSU', 'Case'];

    componentTypes.forEach(type => {
        // Procura por chips deste componente
        // Os chips têm IDs como "CPU-chips", "RAM-chips", etc
        const chipsContainerId = type + '-chips';
        const chipsContainer = document.getElementById(chipsContainerId);

        if (chipsContainer) {
            const chips = chipsContainer.querySelectorAll('.component-chip');
            console.log(`  ${type}: ${chips.length} chip(s)`);

            if (chips.length > 0) {
                // Se tipo permite múltiplos (RAM, HDD), cria array
                if (type === 'RAM' || type === 'HDD') {
                    componentsData[type] = [];
                    console.log(`  🔍 Processando chips de ${type}...`);

                    chips.forEach((chip, chipIndex) => {
                        const productId = chip.getAttribute('data-product-id');
                        const productName = chip.getAttribute('data-product-name');

                        // 🔑 CRUCIAL: Extrai quantidade do texto do chip
                        // Formato: "HD 500GB" ou "HD 500GB (2x)" ou "HD 500GB (3x)"
                        const chipText = chip.querySelector('span').textContent;
                        console.log(`    ═══════════════════════════════════════`);
                        console.log(`    Chip ${chipIndex + 1}/${chips.length}:`);
                        console.log(`      Texto completo: "${chipText}"`);
                        console.log(`      productId: ${productId}`);
                        console.log(`      productName: ${productName}`);

                        const quantityMatch = chipText.match(/\((\d+)x\)/);
                        const quantity = quantityMatch ? parseInt(quantityMatch[1]) : 1;

                        console.log(`      Regex match: ${quantityMatch ? quantityMatch[0] : 'NENHUM'}`);
                        console.log(`      Quantidade extraída: ${quantity}`);
                        console.log(`      Vai adicionar ${quantity}x no array`);

                        // Adiciona "quantity" vezes o mesmo produto
                        for (let i = 0; i < quantity; i++) {
                            componentsData[type].push({
                                productId: parseInt(productId),
                                productName: productName
                            });
                            console.log(`        [${i + 1}/${quantity}] Item adicionado ao array`);
                        }
                        console.log(`    ═══════════════════════════════════════`);
                    });
                } else {
                    // Tipo única seleção, pega primeiro chip
                    const chip = chips[0];
                    const productId = chip.getAttribute('data-product-id');
                    const productName = chip.getAttribute('data-product-name');
                    componentsData[type] = {
                        productId: parseInt(productId),
                        productName: productName
                    };
                }
            }
        }
    });

    const json = JSON.stringify(componentsData);
    console.log('  JSON gerado:', json);
    console.log('  JSON parsed:', componentsData);
    console.table(componentsData);
    
    // 🔍 NOVO: Contagem de elementos no JSON por tipo
    let totalElements = 0;
    Object.entries(componentsData).forEach(([type, data]) => {
        if (Array.isArray(data)) {
            console.log(`  📊 ${type}: ${data.length} elemento(s)`);
            totalElements += data.length;
        } else if (data && typeof data === 'object' && data.productId) {
            console.log(`  📊 ${type}: 1 elemento`);
            totalElements += 1;
        }
    });
    console.log(`  📊 TOTAL DE ELEMENTOS NO JSON: ${totalElements}`);

    // Salva em campo hidden para o formulário
    const hiddenField = document.getElementById('machine-components-json');
    if (hiddenField) {
        hiddenField.value = json;
        console.log('✅ Campo hidden atualizado com:', json);
    } else {
        console.warn('⚠️ Campo hidden machine-components-json não encontrado');
    }

    console.log('✅ buildAndUpdateComponentsJSON completado');
}

/**
 * Adiciona um chip visual de produto selecionado
 * Reutiliza a função de simple-component-search.js mas ajusta o container
 *
 * IMPORTANTE: Delega para a função do simple-component-search.js para evitar duplicação
 */
function machineComponentsAddProductChip(componentKey, productName, productId) {
    console.log(`📍 machineComponentsAddProductChip (machine-components-integration.js) - Delegando para simple-component-search.js`);
    console.log(`  componentKey: ${componentKey}, productName: ${productName}, productId: ${productId}`);

    // Delega para a função global do simple-component-search.js
    // Ela já tem o mapeamento correto de componentKey para fieldId
    if (typeof window.addProductChip === 'function') {
        // Chama a função global
        window.addProductChip(componentKey, productName, productId);
    } else {
        console.error('❌ ERRO: addProductChip global não está disponível!');
        alert('Erro: Sistema de chips não carregado. Recarregue a página.');
    }
}

/**
 * Toast de notificação simples
 */
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 15px 20px;
        background: #28a745;
        color: white;
        border-radius: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

console.log('✅ machine-components-integration.js inicializado com sucesso');

} // Fecha o bloco 'else' de prevençao de carregamento duplicado
