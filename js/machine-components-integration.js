/**
 * Sistema de Integração de Componentes para Máquinas
 * Funciona com simple-component-search.js
 *
 * IMPORTANTE: Este arquivo deve ser carregado APÓS simple-component-search.js
 */

// Previne carregamento duplicado
if (typeof window.machineComponentsIntegrationLoaded !== 'undefined') {
    console.warn('⚠️ machine-components-integration.js já foi carregado, ignorando duplicata');
    throw new Error('Script já carregado');
}

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
 */
window.selectProduct = function(event, productName, targetFieldId, productId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log('═══════════════════════════════════════════════════════════');
    console.log('🎯 selectProduct - Componente de Máquina');
    console.log('  componentKey:', targetFieldId);
    console.log('  productName:', productName);
    console.log('  productId:', productId);
    console.log('═══════════════════════════════════════════════════════════');

    try {
        // Adiciona o chip usando a função de machine-components-integration
        machineComponentsAddProductChip(targetFieldId, productName, productId);

        // Fecha a modal de busca
        closeSimpleComponentModal();

        // Atualiza o JSON de componentes
        buildAndUpdateComponentsJSON();

        // Feedback
        showToast(`✅ ${productName} adicionado ao componente`);

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
                    chips.forEach(chip => {
                        const productId = chip.getAttribute('data-product-id');
                        const productName = chip.getAttribute('data-product-name');
                        componentsData[type].push({
                            productId: parseInt(productId),
                            productName: productName
                        });
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

    // Salva em campo hidden para o formulário
    const hiddenField = document.getElementById('machine-components-json');
    if (hiddenField) {
        hiddenField.value = json;
        console.log('✅ Campo hidden atualizado');
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
