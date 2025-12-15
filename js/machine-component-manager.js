/**
 * Machine Component Manager
 * Gerencia seleção dinâmica de componentes/produtos para máquinas
 */

class MachineComponentManager {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'machine-components-container',
            apiUrl: config.apiUrl || ((window.location.pathname.split('/')[1] ? '/' + window.location.pathname.split('/')[1] : '') + '/api/get_products_by_category.php'),
            ...config
        };
        
        this.components = [];
        this.selectedProducts = {};
        this.init();
    }
    
    init() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;
        
        this.container = container;
        this.renderComponentSelectors();
        this.attachEventListeners();
    }
    
    // Mapa de categorias de produtos para tipos de componentes
    getComponentTypes() {
        return {
            'CPU': { name: 'Processador', category: 'CPU', icon: 'fa-microchip' },
            'RAM': { name: 'Memória RAM', category: 'RAM', icon: 'fa-memory' },
            'HDD': { name: 'Disco Rígido', category: 'HDD', icon: 'fa-hdd' },
            'SSD': { name: 'SSD', category: 'SSD', icon: 'fa-database' },
            'GPU': { name: 'Placa de Vídeo', category: 'GPU', icon: 'fa-square' },
            'Motherboard': { name: 'Placa Mãe', category: 'Motherboard', icon: 'fa-microchip' },
            'PSU': { name: 'Fonte', category: 'PSU', icon: 'fa-plug' },
            'Case': { name: 'Gabinete', category: 'Case', icon: 'fa-box' }
        };
    }
    
    renderComponentSelectors() {
        const types = this.getComponentTypes();
        let html = `
            <div class="components-grid">
        `;
        
        for (const [key, component] of Object.entries(types)) {
            html += `
                <div class="component-card" data-component-type="${key}">
                    <div class="component-header">
                        <i class="fas ${component.icon} text-primary-custom"></i>
                        <h6 class="mb-0">${component.name}</h6>
                    </div>
                    
                    <div class="component-body">
                        <div class="search-box mb-2">
                            <input type="text" 
                                   class="form-control form-control-custom component-search" 
                                   placeholder="Buscar ${component.name}..."
                                   data-category="${component.category}">
                            <small class="form-text text-muted d-block mt-1">
                                <i class="fas fa-sync fa-spin" style="display:none" id="loading-${key}"></i>
                            </small>
                        </div>
                        
                        <div class="product-list" id="products-${key}" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-muted text-center py-3">
                                <small>Digite para buscar produtos</small>
                            </div>
                        </div>
                        
                        <div class="selected-product mt-2 p-2 bg-light rounded" id="selected-${key}" style="display: none;">
                            <small class="d-block mb-1"><strong>Selecionado:</strong></small>
                            <div id="selected-info-${key}" class="selected-info"></div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-selection" data-component="${key}">
                                <i class="fas fa-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        html += `
            </div>
            <div class="components-summary mt-4 p-3 bg-light rounded">
                <h6 class="mb-3"><i class="fas fa-check-circle"></i> Componentes Selecionados</h6>
                <div id="components-summary" class="components-summary-list">
                    <p class="text-muted">Nenhum componente selecionado</p>
                </div>
                <input type="hidden" id="machine-components-json" name="machine_components" value="{}">
            </div>
        `;
        
        this.container.innerHTML = html;
        
        // Estilo CSS embutido
        this.addStyles();
    }
    
    addStyles() {
        if (document.getElementById('machine-component-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'machine-component-styles';
        style.innerHTML = `
            .components-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .component-card {
                background: #fff;
                border: 1px solid #3a4656;
                border-radius: 8px;
                padding: 1rem;
                transition: all 0.3s ease;
            }
            
            .component-card:hover {
                border-color: #5b9fd1;
                box-shadow: 0 2px 8px rgba(91, 159, 209, 0.1);
            }
            
            .component-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid #3a4656;
            }
            
            .component-header i {
                font-size: 1.25rem;
            }
            
            .component-header h6 {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 600;
            }
            
            .search-box {
                position: relative;
            }
            
            .component-search {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }
            
            .product-list {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                padding: 0.5rem;
            }
            
            .product-item {
                padding: 0.5rem 0.75rem;
                margin-bottom: 0.25rem;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 0.85rem;
            }
            
            .product-item:hover {
                background: #e8f0f9;
                border-color: #5b9fd1;
            }
            
            .product-item-name {
                font-weight: 500;
                margin-bottom: 0.25rem;
            }
            
            .product-item-details {
                font-size: 0.75rem;
                color: #6c757d;
                display: flex;
                flex-direction: column;
                gap: 0.1rem;
            }
            
            .product-item-details span {
                display: flex;
                justify-content: space-between;
            }
            
            .selected-info {
                font-size: 0.85rem;
            }
            
            .selected-info .info-row {
                display: flex;
                justify-content: space-between;
                padding: 0.25rem 0;
                border-bottom: 1px dotted #dee2e6;
            }
            
            .selected-info .info-row strong {
                min-width: 100px;
            }
            
            .components-summary-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 0.75rem;
            }
            
            .summary-item {
                padding: 0.75rem;
                background: #fff;
                border-left: 4px solid #5b9fd1;
                border-radius: 4px;
                font-size: 0.85rem;
            }
            
            .summary-item strong {
                display: block;
                color: #5b9fd1;
                margin-bottom: 0.25rem;
            }
            
            .remove-selection {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .product-stock-alert {
                animation: slideIn 0.3s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .alert-sm {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                margin-bottom: 0;
            }
        `;
        document.head.appendChild(style);
    }
    
    attachEventListeners() {
        // Event listeners para busca de produtos
        document.querySelectorAll('.component-search').forEach(input => {
            input.addEventListener('input', (e) => this.handleSearch(e));
        });
        
        // Event listeners para remover seleção
        document.querySelectorAll('.remove-selection').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const component = e.currentTarget.dataset.component;
                this.removeSelection(component);
            });
        });
    }
    
    async handleSearch(event) {
        const input = event.target;
        const category = input.dataset.category;
        const search = input.value.trim();
        const componentType = input.closest('.component-card').dataset.componentType;
        const listContainer = document.getElementById(`products-${componentType}`);
        const loadingIcon = document.getElementById(`loading-${componentType}`);
        
        if (!search) {
            listContainer.innerHTML = `
                <div class="text-muted text-center py-3">
                    <small>Digite para buscar produtos</small>
                </div>
            `;
            return;
        }
        
        loadingIcon.style.display = 'inline';
        
        try {
            const params = new URLSearchParams({
                category: category,
                search: search
            });
            
            const response = await fetch(`${this.config.apiUrl}?${params}`);
            const data = await response.json();
            
            loadingIcon.style.display = 'none';
            
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(product => {
                    html += `
                        <div class="product-item" data-product-id="${product.id}" data-component="${componentType}">
                            <div class="product-item-name">
                                ${product.name}
                                ${product.manufacturer ? ` (${product.manufacturer})` : ''}
                            </div>
                            <div class="product-item-details">
                                ${product.model ? `<span><strong>Modelo:</strong> ${product.model}</span>` : ''}
                                <span><strong>Quantidade:</strong> ${product.quantity}</span>
                                ${product.price ? `<span><strong>Preço:</strong> R$ ${parseFloat(product.price).toFixed(2)}</span>` : ''}
                            </div>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
                
                // Attach click listeners para seleção
                listContainer.querySelectorAll('.product-item').forEach(item => {
                    item.addEventListener('click', (e) => this.selectProduct(e, componentType));
                });
            } else {
                listContainer.innerHTML = `
                    <div class="alert alert-info alert-sm m-0" role="alert">
                        <small>Nenhum produto encontrado em "${category}"</small>
                    </div>
                `;
            }
        } catch (error) {
            loadingIcon.style.display = 'none';
            console.error('Erro ao buscar produtos:', error);
            listContainer.innerHTML = `
                <div class="alert alert-danger alert-sm m-0" role="alert">
                    <small>Erro ao buscar produtos</small>
                </div>
            `;
        }
    }
    
    selectProduct(event, componentType) {
        const item = event.currentTarget;
        const productId = item.dataset.productId;
        const productName = item.querySelector('.product-item-name').textContent.trim();
        const details = {};
        
        item.querySelectorAll('.product-item-details span').forEach(span => {
            const parts = span.textContent.split(':');
            if (parts.length === 2) {
                details[parts[0].trim()] = parts[1].trim();
            }
        });
        
        // Armazena seleção
        this.selectedProducts[componentType] = {
            id: productId,
            name: productName,
            ...details
        };
        
        // Verifica estoque e mostra alerta se necessário
        this.checkAndShowStockAlert(componentType, item);
        
        // Atualiza UI
        this.updateComponentDisplay(componentType);
        this.updateSummary();
    }
    
    checkAndShowStockAlert(componentType, item) {
        const quantityText = item.querySelector('.product-item-details span:nth-child(2)')?.textContent || '';
        const quantity = parseInt(quantityText.match(/\d+/)?.[0] || 0);
        
        // Define limite de estoque baixo
        const LOW_STOCK_THRESHOLD = 5;
        
        let alertHTML = '';
        let alertType = '';
        
        if (quantity <= 0) {
            alertHTML = `
                <div class="alert alert-danger alert-sm d-flex align-items-center" role="alert">
                    <i class="fas fa-times-circle me-2"></i>
                    <div>
                        <strong>Sem Estoque!</strong>
                        <small class="d-block">Este produto não tem estoque disponível</small>
                    </div>
                </div>
            `;
            alertType = 'danger';
        } else if (quantity < LOW_STOCK_THRESHOLD) {
            alertHTML = `
                <div class="alert alert-warning alert-sm d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Estoque Baixo!</strong>
                        <small class="d-block">Você tem apenas <strong>${quantity}</strong> unidade(s) disponível(is)</small>
                    </div>
                </div>
            `;
            alertType = 'warning';
        }
        
        if (alertHTML) {
            const selectedDiv = document.getElementById(`selected-${componentType}`);
            let existingAlert = selectedDiv.querySelector('.product-stock-alert');
            
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'product-stock-alert mt-2';
            alertDiv.innerHTML = alertHTML;
            selectedDiv.insertBefore(alertDiv, selectedDiv.firstChild);
        }
    }
    
    updateComponentDisplay(componentType) {
        const selected = this.selectedProducts[componentType];
        const selectedDiv = document.getElementById(`selected-${componentType}`);
        const infoDiv = document.getElementById(`selected-info-${componentType}`);
        
        if (selected) {
            let infoHtml = `
                <div class="info-row">
                    <strong>Produto:</strong>
                    <span>${selected.name}</span>
                </div>
            `;
            
            for (const [key, value] of Object.entries(selected)) {
                if (key !== 'id' && key !== 'name') {
                    infoHtml += `
                        <div class="info-row">
                            <strong>${key}:</strong>
                            <span>${value}</span>
                        </div>
                    `;
                }
            }
            
            infoDiv.innerHTML = infoHtml;
            selectedDiv.style.display = 'block';
        } else {
            selectedDiv.style.display = 'none';
        }
    }
    
    updateSummary() {
        const summary = document.getElementById('components-summary');
        const json = document.getElementById('machine-components-json');
        
        if (Object.keys(this.selectedProducts).length === 0) {
            summary.innerHTML = '<p class="text-muted">Nenhum componente selecionado</p>';
            json.value = '{}';
            return;
        }
        
        let html = '';
        const componentsObj = {};
        
        for (const [componentType, product] of Object.entries(this.selectedProducts)) {
            html += `
                <div class="summary-item">
                    <strong>${componentType}</strong>
                    ${product.name}<br>
                    ${product['Modelo'] ? `<small>${product['Modelo']}</small>` : ''}
                </div>
            `;
            
            componentsObj[componentType] = {
                productId: product.id,
                productName: product.name
            };
        }
        
        summary.innerHTML = html;
        json.value = JSON.stringify(componentsObj);
    }
    
    removeSelection(componentType) {
        delete this.selectedProducts[componentType];
        const selectedDiv = document.getElementById(`selected-${componentType}`);
        const searchInput = document.querySelector(`.component-search[data-category]`);
        
        if (selectedDiv) selectedDiv.style.display = 'none';
        
        // Limpa campo de busca
        const searchField = document.querySelector(`[data-component-type="${componentType}"] .component-search`);
        if (searchField) searchField.value = '';
        
        this.updateSummary();
    }
    
    getSelectedComponents() {
        return JSON.parse(document.getElementById('machine-components-json').value);
    }
}

// Inicializa quando o documento está pronto
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('machine-components-container');
    if (container) {
        window.machineComponentManager = new MachineComponentManager({
            containerId: 'machine-components-container',
            apiUrl: (window.location.pathname.split('/')[1] ? '/' + window.location.pathname.split('/')[1] : '') + '/api/get_products_by_category.php'
        });
    }
});
