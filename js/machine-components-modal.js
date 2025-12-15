/**
 * Machine Components Modal Manager - VERSÃO CASCATA
 * Gerencia seleção de componentes via modal para máquinas
 * Suporta modais em cascata (modal sobre modal)
 */

class MachineComponentsModalManager {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'machine-components-modal-container',
            apiUrl: config.apiUrl || ((window.location.pathname.split('/')[1] ? '/' + window.location.pathname.split('/')[1] : '') + '/api/get_products_by_category.php'),
            parentModalId: config.parentModalId || null, // ID do modal pai, se houver
            ...config
        };

        this.components = [
            { key: 'CPU', name: 'Processador', category: 'CPU', icon: 'fa-microchip' },
            { key: 'RAM', name: 'Memória RAM', category: 'RAM', icon: 'fa-memory' },
            { key: 'HDD', name: 'Disco Rígido', category: 'HDD', icon: 'fa-hdd' },
            { key: 'SSD', name: 'SSD', category: 'SSD', icon: 'fa-database' },
            { key: 'GPU', name: 'Placa de Vídeo', category: 'GPU', icon: 'fa-square' },
            { key: 'Motherboard', name: 'Placa Mãe', category: 'Motherboard', icon: 'fa-microchip' },
            { key: 'PSU', name: 'Fonte', category: 'PSU', icon: 'fa-plug' },
            { key: 'Case', name: 'Gabinete', category: 'Case', icon: 'fa-box' }
        ];

        this.selectedProducts = {};
        this.currentModal = null;
        this.modalStack = []; // Pilha de modais para gerenciar cascata
        this.init();
    }

    init() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        this.container = container;
        this.renderComponentsList();
        this.addStyles();
    }

    renderComponentsList() {
        let html = '<div class="components-list-modal">';

        this.components.forEach(component => {
            const selected = this.selectedProducts[component.key];

            html += `
                <div class="component-item-modal">
                    <div class="component-info">
                        <i class="fas ${component.icon} component-icon"></i>
                        <div class="component-details">
                            <span class="component-label">${component.name}</span>
                            ${selected ? `
                                <span class="component-selected">
                                    <i class="fas fa-check-circle text-success me-1"></i>${selected.name}
                                </span>
                            ` : `
                                <span class="component-empty text-muted">Nenhum selecionado</span>
                            `}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-add-component" data-component-key="${component.key}" data-component-name="${component.name}" data-category="${component.category}">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
        });

        html += `
            <div class="components-summary-modal mt-3">
                <h6 class="mb-2"><i class="fas fa-list me-2"></i>Componentes Selecionados</h6>
                <div id="components-summary-modal">
                    <p class="text-muted small">Nenhum componente selecionado</p>
                </div>
                <input type="hidden" id="machine-components-json" name="machine_components" value="{}">
            </div>
        </div>`;

        this.container.innerHTML = html;
        this.attachEventListeners();
        this.updateSummary();
    }

    attachEventListeners() {
        document.querySelectorAll('.btn-add-component').forEach(btn => {
            btn.addEventListener('click', (e) => this.openComponentModal(e));
        });
    }

    openComponentModal(e) {
        const componentKey = e.currentTarget.dataset.componentKey;
        const componentName = e.currentTarget.dataset.componentName;
        const category = e.currentTarget.dataset.category;

        this.showLoadingModal(componentName);
        this.fetchProductsForComponent(componentKey, componentName, category);
    }

    getNextZIndex() {
        // Calcula próximo z-index baseado na pilha de modais
        const baseZIndex = 1050; // z-index base do Bootstrap modal
        return baseZIndex + (this.modalStack.length * 10);
    }

    showLoadingModal(componentName) {
        const zIndex = this.getNextZIndex();
        const backdropZIndex = zIndex - 5;

        const modalHtml = `
            <div class="modal fade" id="componentModal" tabindex="-1" role="dialog" aria-labelledby="componentModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg" role="document" style="z-index: ${zIndex};">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="componentModalTitle">
                                <i class="fas fa-search me-2"></i>Selecionar ${componentName}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando produtos disponíveis...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const oldModal = document.getElementById('componentModal');
        if (oldModal) oldModal.remove();

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml;
        document.body.appendChild(tempDiv.firstElementChild);

        const modalElement = document.getElementById('componentModal');
        this.currentModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });

        // Ajusta z-index do backdrop
        modalElement.addEventListener('shown.bs.modal', () => {
            const backdrop = document.querySelector('.modal-backdrop:last-of-type');
            if (backdrop) {
                backdrop.style.zIndex = backdropZIndex;
            }
            // Ajusta z-index do modal
            modalElement.style.zIndex = zIndex;
        });

        // Adiciona à pilha
        this.modalStack.push(modalElement);

        // Remove da pilha quando fechar
        modalElement.addEventListener('hidden.bs.modal', () => {
            const index = this.modalStack.indexOf(modalElement);
            if (index > -1) {
                this.modalStack.splice(index, 1);
            }
        }, { once: true });

        this.currentModal.show();
    }

    async fetchProductsForComponent(componentKey, componentName, category) {
        try {
            const params = new URLSearchParams({
                category: category
            });

            const response = await fetch(`${this.config.apiUrl}?${params}`);
            const data = await response.json();

            if (data.success && data.data.length > 0) {
                this.showProductsModal(componentKey, componentName, data.data);
            } else {
                this.showNoProductsModal(componentName);
            }
        } catch (error) {
            console.error('Erro ao buscar produtos:', error);
            this.showErrorModal(componentName);
        }
    }

    showProductsModal(componentKey, componentName, products) {
        const zIndex = this.getNextZIndex();
        const backdropZIndex = zIndex - 5;

        let productsHtml = products.map(product => `
            <div class="product-modal-item" data-product-id="${product.id}" data-component-key="${componentKey}" data-product-name="${product.name}">
                <div class="product-modal-header">
                    <div class="product-modal-name">
                        <strong>${product.name}</strong>
                        ${product.manufacturer ? `<br><small class="text-muted">${product.manufacturer}</small>` : ''}
                        ${product.model ? `<br><small class="text-muted">${product.model}</small>` : ''}
                    </div>
                    <div class="product-modal-stock">
                        ${this.getStockBadge(product.quantity)}
                    </div>
                </div>
                <div class="product-modal-footer">
                    <small class="text-muted">
                        ${product.price ? `R$ ${parseFloat(product.price).toFixed(2)} | ` : ''}
                        ${product.serial_number ? `SN: ${product.serial_number}` : ''}
                    </small>
                    <button type="button" class="btn btn-sm btn-primary btn-select-product">
                        <i class="fas fa-check me-1"></i>Selecionar
                    </button>
                </div>
            </div>
        `).join('');

        const searchHtml = `
            <div class="modal-search mb-3">
                <input type="text" class="form-control" id="productSearch" placeholder="Buscar por nome, modelo ou série...">
            </div>
        `;

        const modalHtml = `
            <div class="modal fade" id="componentModal" tabindex="-1" role="dialog" aria-labelledby="componentModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg" role="document" style="z-index: ${zIndex};">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="componentModalTitle">
                                <i class="fas fa-boxes me-2"></i>Selecionar ${componentName}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${searchHtml}
                            <div class="products-list-modal" id="productsListModal">
                                ${productsHtml}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const oldModal = document.getElementById('componentModal');
        if (oldModal) oldModal.remove();

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml;
        document.body.appendChild(tempDiv.firstElementChild);

        const modalElement = document.getElementById('componentModal');
        this.currentModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });

        // Ajusta z-index do backdrop
        modalElement.addEventListener('shown.bs.modal', () => {
            const backdrop = document.querySelector('.modal-backdrop:last-of-type');
            if (backdrop) {
                backdrop.style.zIndex = backdropZIndex;
            }
            // Ajusta z-index do modal
            modalElement.style.zIndex = zIndex;
        });

        // Adiciona à pilha
        this.modalStack.push(modalElement);

        // Remove da pilha quando fechar
        modalElement.addEventListener('hidden.bs.modal', () => {
            const index = this.modalStack.indexOf(modalElement);
            if (index > -1) {
                this.modalStack.splice(index, 1);
            }
        }, { once: true });

        this.currentModal.show();

        // Attach event listeners para seleção
        this.attachProductSelectionListeners(componentKey, componentName);
        this.attachSearchListener(products, componentKey, componentName);
    }

    attachProductSelectionListeners(componentKey, componentName) {
        document.querySelectorAll('.btn-select-product').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const item = e.currentTarget.closest('.product-modal-item');
                const productId = item.dataset.productId;
                const productName = item.dataset.productName;

                console.log('🎯 Modal Cascata: Produto selecionado', {
                    componentKey: componentKey,
                    productId: productId,
                    productName: productName
                });

                // Armazena seleção
                this.selectedProducts[componentKey] = {
                    id: productId,
                    name: productName
                };

                // CORREÇÃO: Atualiza o campo correspondente no formulário
                this.updateFormField(componentKey, productName, productId);

                // Mostra alerta de estoque se necessário
                const quantity = parseInt(item.querySelector('.product-modal-stock span').textContent.match(/\d+/)?.[0] || 0);
                this.showStockAlert(productName, quantity, componentName);

                // Fecha modal e atualiza UI
                this.currentModal.hide();
                this.renderComponentsList();
            });
        });
    }

    /**
     * Atualiza o campo de texto correspondente no formulário edit_machine
     * NOVA LÓGICA: Não preenche o campo, cria chips visuais abaixo
     */
    updateFormField(componentKey, productName, productId) {
        // Mapeamento de componentKey para fieldId
        const fieldMapping = {
            'CPU': 'processor',
            'RAM': 'memory',
            'HDD': 'storage',
            'SSD': 'storage',
            'GPU': 'graphics',
            'Motherboard': 'motherboard',
            'PSU': 'power_supply',
            'Case': 'case_type'
        };

        const fieldId = fieldMapping[componentKey];
        if (!fieldId) {
            console.warn('⚠️ Campo não mapeado para componentKey:', componentKey);
            return;
        }

        const field = document.getElementById(fieldId);
        if (!field) {
            console.warn('⚠️ Campo não encontrado:', fieldId);
            return;
        }

        // Define se o componente permite múltiplos itens
        const allowMultiple = (componentKey === 'HDD' || componentKey === 'RAM' || componentKey === 'SSD');

        // Encontra ou cria o container de chips
        let chipsContainer = document.getElementById(`${fieldId}-chips`);
        if (!chipsContainer) {
            chipsContainer = document.createElement('div');
            chipsContainer.id = `${fieldId}-chips`;
            chipsContainer.className = 'component-chips-container mt-2';
            chipsContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 20px;';

            // Insere após o input-group (que contém o campo e os botões)
            const inputGroup = field.closest('.input-group');
            if (inputGroup) {
                inputGroup.parentNode.insertBefore(chipsContainer, inputGroup.nextSibling);
            } else {
                field.parentNode.insertBefore(chipsContainer, field.nextSibling);
            }
        }

        // Se NÃO permite múltiplos, remove chips anteriores
        if (!allowMultiple) {
            chipsContainer.innerHTML = '';
        }

        // Verifica se o produto já foi adicionado
        const existingChip = chipsContainer.querySelector(`[data-product-id="${productId}"]`);
        if (existingChip) {
            console.log(`⚠️ Produto já adicionado: ${productName}`);
            // Destaca o chip existente
            existingChip.style.animation = 'pulse 0.5s';
            setTimeout(() => existingChip.style.animation = '', 500);
            return;
        }

        // Cria o chip do produto
        const chip = document.createElement('div');
        chip.className = 'component-chip';
        chip.setAttribute('data-product-id', productId);
        chip.setAttribute('data-component-key', componentKey);
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
            <span>${productName}</span>
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

        // Adiciona evento de remoção
        chip.querySelector('.btn-remove-chip').addEventListener('click', () => {
            chip.style.opacity = '0';
            chip.style.transform = 'scale(0.8)';
            setTimeout(() => chip.remove(), 200);
            console.log(`🗑️ Chip removido: ${productName} (ID: ${productId})`);
        });

        // Adiciona o chip ao container
        chipsContainer.appendChild(chip);

        // NÃO preenche o campo de texto - deixa em branco para digitação manual
        console.log(`✅ Chip adicionado para ${fieldId}:`, {
            productName: productName,
            productId: productId,
            allowMultiple: allowMultiple
        });
    }

    attachSearchListener(products, componentKey, componentName) {
        const searchInput = document.getElementById('productSearch');
        const productsList = document.getElementById('productsListModal');

        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();

            const filtered = products.filter(p =>
                p.name.toLowerCase().includes(searchTerm) ||
                (p.manufacturer && p.manufacturer.toLowerCase().includes(searchTerm)) ||
                (p.model && p.model.toLowerCase().includes(searchTerm)) ||
                (p.serial_number && p.serial_number.toLowerCase().includes(searchTerm))
            );

            if (filtered.length === 0) {
                productsList.innerHTML = `
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>Nenhum produto encontrado
                    </div>
                `;
                return;
            }

            let html = filtered.map(product => `
                <div class="product-modal-item" data-product-id="${product.id}" data-component-key="${componentKey}" data-product-name="${product.name}">
                    <div class="product-modal-header">
                        <div class="product-modal-name">
                            <strong>${product.name}</strong>
                            ${product.manufacturer ? `<br><small class="text-muted">${product.manufacturer}</small>` : ''}
                            ${product.model ? `<br><small class="text-muted">${product.model}</small>` : ''}
                        </div>
                        <div class="product-modal-stock">
                            ${this.getStockBadge(product.quantity)}
                        </div>
                    </div>
                    <div class="product-modal-footer">
                        <small class="text-muted">
                            ${product.price ? `R$ ${parseFloat(product.price).toFixed(2)} | ` : ''}
                            ${product.serial_number ? `SN: ${product.serial_number}` : ''}
                        </small>
                        <button type="button" class="btn btn-sm btn-primary btn-select-product">
                            <i class="fas fa-check me-1"></i>Selecionar
                        </button>
                    </div>
                </div>
            `).join('');

            productsList.innerHTML = html;
            this.attachProductSelectionListeners(componentKey, componentName);
        });
    }

    getStockBadge(quantity) {
        if (quantity <= 0) {
            return `<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>${quantity}</span>`;
        } else if (quantity < 5) {
            return `<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>${quantity}</span>`;
        } else {
            return `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>${quantity}</span>`;
        }
    }

    showStockAlert(productName, quantity, componentName) {
        let alertType = 'info';
        let alertIcon = 'fa-info-circle';
        let alertMessage = `Produto selecionado com sucesso!`;

        console.log(`📊 Verificando estoque: ${productName} (Qtd: ${quantity})`);

        if (quantity <= 0) {
            alertType = 'danger';
            alertIcon = 'fa-times-circle';
            alertMessage = `⚠️ ATENÇÃO: Este produto está SEM ESTOQUE!`;
            console.error(`❌ PRODUTO SEM ESTOQUE: ${productName}`);
        } else if (quantity <= 1) {
            alertType = 'danger';
            alertIcon = 'fa-exclamation-triangle';
            alertMessage = `⚠️⚠️⚠️ CRÍTICO: Este é o ÚLTIMO item em estoque! Após salvar, o estoque ficará ZERO.`;
            console.warn(`⚠️⚠️⚠️ ÚLTIMO ITEM: ${productName} (Qtd: ${quantity})`);
        } else if (quantity < 5) {
            alertType = 'warning';
            alertIcon = 'fa-exclamation-triangle';
            alertMessage = `⚠️ ESTOQUE BAIXO: Apenas ${quantity} unidade(s) disponível(is)`;
            console.warn(`⚠️ ESTOQUE BAIXO: ${productName} (Qtd: ${quantity})`);
        } else {
            console.log(`✅ Estoque OK: ${productName} (Qtd: ${quantity})`);
        }

        const alertHtml = `
            <div class="alert alert-${alertType} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <i class="fas ${alertIcon} me-2"></i>
                <strong>${componentName}:</strong> ${productName}
                <br>
                <small>${alertMessage}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = alertHtml;
        const alertElement = tempDiv.firstElementChild;
        document.body.appendChild(alertElement);

        // Remove automaticamente após 5 segundos
        setTimeout(() => {
            if (alertElement && alertElement.parentNode) {
                alertElement.classList.remove('show');
                setTimeout(() => alertElement.remove(), 150);
            }
        }, 5000);
    }

    showNoProductsModal(componentName) {
        const zIndex = this.getNextZIndex();
        const backdropZIndex = zIndex - 5;

        const modalHtml = `
            <div class="modal fade" id="componentModal" tabindex="-1" role="dialog" aria-labelledby="componentModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg" role="document" style="z-index: ${zIndex};">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="componentModalTitle">
                                <i class="fas fa-search me-2"></i>Selecionar ${componentName}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><strong>Nenhum ${componentName} disponível em estoque</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const oldModal = document.getElementById('componentModal');
        if (oldModal) oldModal.remove();

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml;
        document.body.appendChild(tempDiv.firstElementChild);

        const modalElement = document.getElementById('componentModal');
        this.currentModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });

        // Ajusta z-index
        modalElement.addEventListener('shown.bs.modal', () => {
            const backdrop = document.querySelector('.modal-backdrop:last-of-type');
            if (backdrop) {
                backdrop.style.zIndex = backdropZIndex;
            }
            modalElement.style.zIndex = zIndex;
        });

        this.modalStack.push(modalElement);

        modalElement.addEventListener('hidden.bs.modal', () => {
            const index = this.modalStack.indexOf(modalElement);
            if (index > -1) {
                this.modalStack.splice(index, 1);
            }
        }, { once: true });

        this.currentModal.show();
    }

    showErrorModal(componentName) {
        const zIndex = this.getNextZIndex();
        const backdropZIndex = zIndex - 5;

        const modalHtml = `
            <div class="modal fade" id="componentModal" tabindex="-1" role="dialog" aria-labelledby="componentModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg" role="document" style="z-index: ${zIndex};">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="componentModalTitle">
                                <i class="fas fa-exclamation-circle me-2"></i>Erro
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                            <p class="text-danger"><strong>Erro ao carregar ${componentName}</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const oldModal = document.getElementById('componentModal');
        if (oldModal) oldModal.remove();

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml;
        document.body.appendChild(tempDiv.firstElementChild);

        const modalElement = document.getElementById('componentModal');
        this.currentModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });

        // Ajusta z-index
        modalElement.addEventListener('shown.bs.modal', () => {
            const backdrop = document.querySelector('.modal-backdrop:last-of-type');
            if (backdrop) {
                backdrop.style.zIndex = backdropZIndex;
            }
            modalElement.style.zIndex = zIndex;
        });

        this.modalStack.push(modalElement);

        modalElement.addEventListener('hidden.bs.modal', () => {
            const index = this.modalStack.indexOf(modalElement);
            if (index > -1) {
                this.modalStack.splice(index, 1);
            }
        }, { once: true });

        this.currentModal.show();
    }

    updateSummary() {
        const summary = document.getElementById('components-summary-modal');
        const json = document.getElementById('machine-components-json');

        if (Object.keys(this.selectedProducts).length === 0) {
            summary.innerHTML = '<p class="text-muted small">Nenhum componente selecionado</p>';
            json.value = '{}';
            return;
        }

        let html = '<div class="row">';
        const componentsObj = {};

        this.components.forEach(component => {
            if (this.selectedProducts[component.key]) {
                const product = this.selectedProducts[component.key];
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="component-summary-item">
                            <strong>${component.name}:</strong>
                            <span class="text-success">${product.name}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-remove-component" data-component-key="${component.key}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                componentsObj[component.key] = {
                    productId: product.id,
                    productName: product.name
                };
            }
        });

        html += '</div>';
        summary.innerHTML = html;
        json.value = JSON.stringify(componentsObj);

        // Attach remove listeners
        document.querySelectorAll('.btn-remove-component').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const componentKey = e.currentTarget.dataset.componentKey;
                delete this.selectedProducts[componentKey];
                this.updateSummary();
                this.renderComponentsList();
            });
        });
    }

    addStyles() {
        if (document.getElementById('machine-components-modal-styles')) return;

        const style = document.createElement('style');
        style.id = 'machine-components-modal-styles';
        style.innerHTML = `
            /* GARANTIR QUE MODAIS EM CASCATA FUNCIONEM */
            .modal.show {
                display: block !important;
            }

            .modal-backdrop {
                background-color: rgba(0, 0, 0, 0.5);
            }

            .modal-backdrop.show {
                opacity: 0.5;
            }

            .components-list-modal {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .component-item-modal {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 15px;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                transition: all 0.3s ease;
            }

            .component-item-modal:hover {
                border-color: #0d6efd;
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
            }

            .component-info {
                display: flex;
                align-items: center;
                gap: 12px;
                flex: 1;
            }

            .component-icon {
                font-size: 1.5rem;
                color: #0d6efd;
                width: 40px;
                text-align: center;
            }

            .component-details {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .component-label {
                font-weight: 600;
                color: #333;
                font-size: 0.95rem;
            }

            .component-selected {
                font-size: 0.85rem;
                color: #198754;
            }

            .component-empty {
                font-size: 0.85rem;
            }

            .btn-add-component {
                padding: 8px 12px;
                font-size: 1.1rem;
                height: 40px;
                width: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }

            .btn-add-component:hover {
                transform: scale(1.1);
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
            }

            .components-summary-modal {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
                border-left: 4px solid #0d6efd;
            }

            .component-summary-item {
                padding: 8px;
                background: #fff;
                border-radius: 4px;
                border-left: 3px solid #198754;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.9rem;
                gap: 8px;
            }

            .product-modal-item {
                padding: 12px;
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                margin-bottom: 10px;
                transition: all 0.2s ease;
            }

            .product-modal-item:hover {
                background: #f8f9fa;
                border-color: #0d6efd;
                box-shadow: 0 2px 6px rgba(13, 110, 253, 0.1);
            }

            .product-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 8px;
            }

            .product-modal-name {
                flex: 1;
                font-size: 0.9rem;
            }

            .product-modal-stock {
                margin-left: 10px;
            }

            .product-modal-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 8px;
                border-top: 1px solid #e9ecef;
            }

            .modal-search {
                position: relative;
            }

            .modal-search input {
                padding: 10px 12px;
                border: 1px solid #dee2e6;
                border-radius: 6px;
            }

            .products-list-modal {
                max-height: 400px;
                overflow-y: auto;
            }
        `;
        document.head.appendChild(style);
    }
}
