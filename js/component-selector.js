/**
 * ========================================
 * SELETOR DE COMPONENTES COM VALIDAÇÃO
 * ========================================
 * Arquivo: js/component-selector.js
 * Sistema interativo de montagem de máquina
 * Similar a Pichau.com.br e Kabum.com.br
 */

class ComponentSelector {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/sistema5';
        this.selectedComponents = {};
        this.recommendations = {};
        this.containerSelector = options.container || '#component-selector';
        this.init();
    }

    /**
     * Inicializa o componente
     */
    init() {
        console.log('🎮 ComponentSelector inicializado');
        this.setupEventListeners();
        this.loadCategories();
    }

    /**
     * Configura listeners de eventos
     */
    setupEventListeners() {
        // Clique em categoria
        document.addEventListener('click', (e) => {
            if (e.target.closest('.category-btn')) {
                const category = e.target.closest('.category-btn').dataset.category;
                this.loadCategory(category);
            }
        });

        // Seleção de componente
        document.addEventListener('click', (e) => {
            if (e.target.closest('.component-select-btn')) {
                const componentId = e.target.closest('.component-select-btn').dataset.componentId;
                const category = e.target.closest('.component-select-btn').dataset.category;
                this.selectComponent(componentId, category);
            }
        });

        // Remover componente
        document.addEventListener('click', (e) => {
            if (e.target.closest('.component-remove-btn')) {
                const category = e.target.closest('.component-remove-btn').dataset.category;
                this.removeComponent(category);
            }
        });

        // Aplicar filtros
        document.addEventListener('click', (e) => {
            if (e.target.closest('.apply-filters-btn')) {
                const category = e.target.closest('.apply-filters-btn').dataset.category;
                this.applyFilters(category);
            }
        });
    }

    /**
     * Carrega categorias disponíveis
     */
    loadCategories() {
        const categories = [
            { name: 'CPU / Processador', slug: 'cpu', icon: 'fa-microchip' },
            { name: 'Placa Mãe', slug: 'motherboard', icon: 'fa-project-diagram' },
            { name: 'Memória RAM', slug: 'ram', icon: 'fa-memory' },
            { name: 'GPU / Placa de Vídeo', slug: 'gpu', icon: 'fa-cube' },
            { name: 'Armazenamento', slug: 'storage', icon: 'fa-database' },
            { name: 'Fonte / PSU', slug: 'psu', icon: 'fa-plug' },
            { name: 'Case / Gabinete', slug: 'case', icon: 'fa-box' },
            { name: 'Cooler / Resfriador', slug: 'cooler', icon: 'fa-fan' }
        ];

        const categoriesPanel = document.querySelector('.component-categories');
        if (!categoriesPanel) return;

        categoriesPanel.innerHTML = categories
            .map(cat => `
                <button class="category-btn btn btn-outline-primary mb-2 w-100" 
                        data-category="${cat.slug}"
                        title="Selecionar ${cat.name}">
                    <i class="fas ${cat.icon} me-2"></i>${cat.name}
                </button>
            `)
            .join('');
    }

    /**
     * Carrega componentes de uma categoria
     */
    async loadCategory(category) {
        try {
            console.log(`📦 Carregando categoria: ${category}`);

            // Mostra loading
            this.showLoading();

            const response = await fetch(`${this.baseUrl}/modules/machines/api_get_components.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    category: category,
                    filters: this.getFiltersFromUI(category),
                    selectedComponents: this.selectedComponents
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (!data.success) throw new Error(data.error || 'Erro desconhecido');

            this.renderComponents(category, data.components);
            this.renderFilters(category);

        } catch (error) {
            console.error('❌ Erro ao carregar componentes:', error);
            this.showError(`Erro ao carregar ${category}: ${error.message}`);
        }
    }

    /**
     * Busca filtros da interface
     */
    getFiltersFromUI(category) {
        const filters = {};
        const filterContainer = document.querySelector(`[data-filter-container="${category}"]`);

        if (!filterContainer) return filters;

        // Manufacturer
        const manufacturer = filterContainer.querySelector('[name="manufacturer"]');
        if (manufacturer?.value) filters.manufacturer = manufacturer.value;

        // Preço
        const minPrice = filterContainer.querySelector('[name="min_price"]');
        const maxPrice = filterContainer.querySelector('[name="max_price"]');
        if (minPrice?.value) filters.min_price = minPrice.value;
        if (maxPrice?.value) filters.max_price = maxPrice.value;

        // Filtros específicos por categoria
        switch (category) {
            case 'cpu':
                const socket = filterContainer.querySelector('[name="socket"]');
                const cores = filterContainer.querySelector('[name="min_cores"]');
                if (socket?.value) filters.socket = socket.value;
                if (cores?.value) filters.min_cores = cores.value;
                break;

            case 'motherboard':
                const mbSocket = filterContainer.querySelector('[name="socket"]');
                const chipset = filterContainer.querySelector('[name="chipset"]');
                if (mbSocket?.value) filters.socket = mbSocket.value;
                if (chipset?.value) filters.chipset = chipset.value;
                break;

            case 'ram':
                const ramType = filterContainer.querySelector('[name="type"]');
                const capacity = filterContainer.querySelector('[name="min_capacity"]');
                if (ramType?.value) filters.type = ramType.value;
                if (capacity?.value) filters.min_capacity = capacity.value;
                break;
        }

        return filters;
    }

    /**
     * Renderiza lista de componentes
     */
    renderComponents(category, components) {
        const html = `
            <div class="component-list-container">
                <h5>Componentes Disponíveis</h5>
                <div class="component-grid">
                    ${components.map(comp => this.renderComponentCard(comp, category)).join('')}
                </div>
            </div>
        `;

        const listContainer = document.querySelector('.products-panel #component-list');
        if (listContainer) {
            listContainer.innerHTML = html;
        }

        console.log(`✅ Renderizados ${components.length} componentes`);
    }

    /**
     * Cria card de componente
     */
    renderComponentCard(component, category) {
        const isSelected = this.selectedComponents[category] == component.id;
        const price = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(component.price);

        let specs = this.getComponentSpecsHTML(component, category);

        return `
            <div class="component-card ${isSelected ? 'selected' : ''} ${component.compatibility === 'incompatible' ? 'incompatible' : ''}">
                <div class="component-image">
                    <img src="${component.image_path || '/sistema5/images/placeholder.png'}" 
                         alt="${component.name}"
                         onerror="this.src='/sistema5/images/placeholder.png'">
                </div>
                
                <div class="component-info">
                    <h6 class="component-name">${component.name}</h6>
                    <p class="component-model text-muted small">${component.model || 'Modelo não especificado'}</p>
                    
                    <div class="component-specs">
                        ${specs}
                    </div>

                    ${component.compatibility && component.compatibility_message ? `
                        <div class="compatibility-badge alert alert-${component.compatibility === 'compatible' ? 'success' : 'warning'} p-2 mt-2 small">
                            <i class="fas fa-${component.compatibility === 'compatible' ? 'check-circle' : 'exclamation-triangle'}"></i>
                            ${component.compatibility_message}
                        </div>
                    ` : ''}

                    ${component.is_recommended ? `
                        <div class="alert alert-info p-2 small">
                            <i class="fas fa-star"></i> Recomendado para você
                        </div>
                    ` : ''}
                </div>

                <div class="component-footer">
                    <div class="price"><strong>${price}</strong></div>
                    <button class="btn btn-sm ${isSelected ? 'btn-success' : 'btn-primary'} component-select-btn"
                            data-component-id="${component.id}"
                            data-category="${category}">
                        <i class="fas fa-${isSelected ? 'check' : 'plus'}"></i>
                        ${isSelected ? 'Selecionado' : 'Selecionar'}
                    </button>
                </div>

                <div class="component-details">
                    <button class="btn btn-sm btn-link" data-bs-toggle="collapse" 
                            data-bs-target="#details-${component.id}">
                        <i class="fas fa-chevron-down"></i> Detalhes
                    </button>
                    <div id="details-${component.id}" class="collapse">
                        <hr>
                        <p>${component.description || 'Sem descrição'}</p>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Retorna HTML com especificações do componente
     */
    getComponentSpecsHTML(comp, category) {
        let html = '';

        switch (category) {
            case 'cpu':
                html = `
                    <small>
                        <div><strong>${comp.cpu_cores}C/${comp.cpu_threads}T</strong></div>
                        <div>${comp.cpu_base_clock}GHz Base / ${comp.cpu_boost_clock}GHz Turbo</div>
                        <div>Socket: <strong>${comp.cpu_socket}</strong></div>
                        <div>TDP: ${comp.cpu_tdp}W</div>
                    </small>
                `;
                break;

            case 'motherboard':
                html = `
                    <small>
                        <div><strong>${comp.motherboard_form_factor}</strong> - ${comp.motherboard_chipset}</div>
                        <div>Socket: <strong>${comp.motherboard_socket}</strong></div>
                        <div>${comp.motherboard_ram_type} (até ${comp.motherboard_max_ram}GB)</div>
                        <div>${comp.motherboard_ram_slots} slots de RAM</div>
                    </small>
                `;
                break;

            case 'ram':
                html = `
                    <small>
                        <div><strong>${comp.ram_capacity}GB ${comp.ram_type}</strong></div>
                        <div>${comp.ram_speed}MHz - CL${comp.ram_cas_latency}</div>
                        <div>${comp.ram_modules} módulos</div>
                    </small>
                `;
                break;

            case 'gpu':
                html = `
                    <small>
                        <div><strong>${comp.gpu_vram}GB</strong> ${comp.gpu_vram_type}</div>
                        <div>Power: ${comp.gpu_power_requirement}W</div>
                        <div>Dimensões: ${comp.gpu_dimensions}</div>
                    </small>
                `;
                break;

            case 'storage':
                html = `
                    <small>
                        <div><strong>${comp.storage_capacity}GB</strong> ${comp.storage_type}</div>
                        <div>Leitura: ${comp.storage_read_speed}MB/s</div>
                        <div>Interface: ${comp.storage_interface}</div>
                    </small>
                `;
                break;

            case 'psu':
                html = `
                    <small>
                        <div><strong>${comp.psu_wattage}W</strong></div>
                        <div>${comp.psu_efficiency_rating}</div>
                        <div>${comp.psu_modular}</div>
                    </small>
                `;
                break;

            case 'cooler':
                html = `
                    <small>
                        <div><strong>${comp.cooler_type}</strong></div>
                        <div>TDP: até ${comp.cooler_tdp_capability}W</div>
                        <div>Ruído: ${comp.cooler_noise_level_min}-${comp.cooler_noise_level_max}dB</div>
                    </small>
                `;
                break;
        }

        return html;
    }

    /**
     * Seleciona um componente
     */
    async selectComponent(componentId, category) {
        try {
            console.log(`✅ Selecionando ${category} ID: ${componentId}`);

            this.selectedComponents[category] = componentId;

            // Valida compatibilidade
            await this.validateCompatibility();

            // Carrega recomendações
            await this.loadRecommendations();

            // Atualiza resumo
            this.updateSummary();

            // Atualiza UI
            this.updateComponentsList();

        } catch (error) {
            console.error('❌ Erro ao selecionar componente:', error);
            this.showError(error.message);
        }
    }

    /**
     * Remove um componente da seleção
     */
    removeComponent(category) {
        delete this.selectedComponents[category];
        this.validateCompatibility();
        this.updateSummary();
        this.updateComponentsList();
    }

    /**
     * Valida compatibilidade entre componentes selecionados
     */
    async validateCompatibility() {
        try {
            const response = await fetch(`${this.baseUrl}/modules/machines/api_validate_compatibility.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    selectedComponents: this.selectedComponents
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const validation = await response.json();
            this.showCompatibilityStatus(validation);

        } catch (error) {
            console.error('❌ Erro ao validar compatibilidade:', error);
        }
    }

    /**
     * Mostra status de compatibilidade
     */
    showCompatibilityStatus(validation) {
        const panel = document.getElementById('compatibility-info');
        if (!panel) return;

        let html = '';

        // Erros críticos
        if (validation.issues && validation.issues.length > 0) {
            validation.issues.forEach(issue => {
                html += `
                    <div class="alert alert-${issue.severity} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${issue.icon}"></i>
                        <strong>${issue.title}:</strong> ${issue.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            });
        }

        // Avisos
        if (validation.warnings && validation.warnings.length > 0) {
            validation.warnings.forEach(warning => {
                html += `
                    <div class="alert alert-${warning.severity} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${warning.icon}"></i>
                        <strong>${warning.title}:</strong> ${warning.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            });
        }

        // Sucesso
        if (!validation.issues || validation.issues.length === 0) {
            html += `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <strong>✅ Compatibilidade Verificada!</strong>
                    Todos os componentes são compatíveis.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        panel.innerHTML = html;
    }

    /**
     * Carrega recomendações para próximas categorias
     */
    async loadRecommendations() {
        // Implementação de recomendações baseada em seleções
        console.log('💡 Carregando recomendações...');
    }

    /**
     * Atualiza resumo da configuração
     */
    updateSummary() {
        const summaryTable = document.getElementById('summary-body');
        if (!summaryTable) return;

        let html = '';
        let totalPrice = 0;

        const categoryNames = {
            'cpu': 'CPU / Processador',
            'motherboard': 'Placa Mãe',
            'ram': 'Memória RAM',
            'gpu': 'GPU / Placa de Vídeo',
            'storage': 'Armazenamento',
            'psu': 'Fonte / PSU',
            'case': 'Case / Gabinete',
            'cooler': 'Cooler'
        };

        Object.entries(this.selectedComponents).forEach(([category, productId]) => {
            const categoryName = categoryNames[category] || category;

            html += `
                <tr>
                    <td>${categoryName}</td>
                    <td>${productId} (a atualizar)</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger component-remove-btn" 
                                data-category="${category}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        summaryTable.innerHTML = html;
    }

    /**
     * Renderiza filtros
     */
    renderFilters(category) {
        // Implementação de renderização de filtros
        console.log(`🎛️ Filtros para ${category}`);
    }

    /**
     * Aplica filtros
     */
    applyFilters(category) {
        this.loadCategory(category);
    }

    /**
     * Atualiza lista de componentes selecionados
     */
    updateComponentsList() {
        // Recarrega componentes com indicação visual de seleção
        Object.entries(this.selectedComponents).forEach(([category, productId]) => {
            const buttons = document.querySelectorAll(
                `.component-select-btn[data-category="${category}"][data-component-id="${productId}"]`
            );
            buttons.forEach(btn => {
                btn.classList.add('btn-success');
                btn.classList.remove('btn-primary');
                btn.innerHTML = '<i class="fas fa-check"></i> Selecionado';
            });
        });
    }

    /**
     * Exibe mensagem de loading
     */
    showLoading() {
        const listContainer = document.querySelector('.products-panel #component-list');
        if (listContainer) {
            listContainer.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando componentes...</p>
                </div>
            `;
        }
    }

    /**
     * Exibe mensagem de erro
     */
    showError(message) {
        const listContainer = document.querySelector('.products-panel #component-list');
        if (listContainer) {
            listContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Erro:</strong> ${message}
                </div>
            `;
        }
    }
}

// Inicializa quando o DOM está pronto
document.addEventListener('DOMContentLoaded', () => {
    window.componentSelector = new ComponentSelector({
        baseUrl: '/sistema5',
        container: '#component-selector'
    });
});
