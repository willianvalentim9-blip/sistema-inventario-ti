/**
 * Melhorias de UI para o Sistema de Estoque
 * Foco em responsividade e interações mobile.
 */

// ========================================
// FUNÇÕES UTILITÁRIAS
// ========================================

/**
 * Otimiza a execução de uma função que é chamada repetidamente (ex: em eventos de resize ou scroll).
 * A função só será executada após um certo tempo (wait) depois da última chamada.
 * @param {Function} func - A função a ser executada.
 * @param {number} wait - O tempo de espera em milissegundos.
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ========================================
// CONTROLE DA SIDEBAR (DESKTOP E MOBILE)
// ========================================

class SidebarController {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        if (!this.sidebar) return;

        this.overlay = this.createOverlay();
        this.isMobileLayout = window.innerWidth <= 991.98;

        this.init();
    }

    createOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
        return overlay;
    }

    init() {
        this.bindEvents();
        this.applyInitialState();
    }

    bindEvents() {
        // Botão principal no header (visível em mobile)
        document.getElementById('mobile-sidebar-toggle')?.addEventListener('click', () => this.toggleMobile());

        // Botão no logo da sidebar (funciona em desktop e mobile)
        document.getElementById('sidebar-brand-toggle')?.addEventListener('click', () => {
            if (this.isMobileLayout) {
                this.toggleMobile();
            } else {
                this.toggleDesktop();
            }
        });

        // Fechar com o overlay
        this.overlay.addEventListener('click', () => this.closeMobile());

        // Fechar com a tecla 'Escape'
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.sidebar.classList.contains('show')) { // CORRIGIDO: de .active para .show
                this.closeMobile();
            }
        });

        // Lógica de redimensionamento da janela
        window.addEventListener('resize', debounce(() => {
            const isNowMobile = window.innerWidth <= 991.98;
            if (this.isMobileLayout !== isNowMobile) {
                this.isMobileLayout = isNowMobile;
                this.onLayoutChange();
            }
        }, 200));
    }

    /**
     * Aplica o estado inicial da sidebar (recolhida ou não) com base no localStorage para desktop.
     */
    applyInitialState() {
        if (!this.isMobileLayout && localStorage.getItem('sidebarCollapsed') === 'true') {
            this.sidebar.classList.add('collapsed');
        }
        this.updateTooltips();
    }

    /**
     * Alterna a visibilidade da sidebar em telas pequenas (mobile).
     */
    toggleMobile() {
        this.sidebar.classList.contains('show') ? this.closeMobile() : this.openMobile(); // CORRIGIDO: de .active para .show
    }

    openMobile() {
        this.sidebar.classList.add('show'); // CORRIGIDO: de .active para .show
        this.overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    closeMobile() {
        this.sidebar.classList.remove('show'); // CORRIGIDO: de .active para .show
        this.overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    /**
     * Alterna o estado recolhido/expandido da sidebar em telas grandes (desktop).
     */
    toggleDesktop() {
        const isCollapsed = this.sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        this.updateTooltips();
    }

    /**
     * Lida com a transição entre layout mobile e desktop.
     */
    onLayoutChange() {
        this.closeMobile(); // Sempre fecha a sidebar mobile ao mudar de layout
        this.sidebar.classList.remove('collapsed'); // Remove o estado 'collapsed' no layout mobile
        this.updateTooltips();
    }

    /**
     * Ativa ou desativa os tooltips com base no estado da sidebar.
     * Em desktop, os tooltips só são necessários quando a sidebar está recolhida.
     */
    updateTooltips() {
        const tooltipTriggerList = [].slice.call(this.sidebar.querySelectorAll('[data-bs-toggle="tooltip"]'));
        
        const shouldEnableTooltips = !this.isMobileLayout && this.sidebar.classList.contains('collapsed');

        tooltipTriggerList.forEach(el => {
            const tooltip = bootstrap.Tooltip.getInstance(el);
            if (tooltip) {
                shouldEnableTooltips ? tooltip.enable() : tooltip.disable();
            }
        });
    }
}


// ========================================
// SISTEMA DE ALERTAS E CONFIRMAÇÕES GLOBAIS
// ========================================

/**
 * Exibe um alerta dinâmico na tela.
 * @param {string} message - A mensagem a ser exibida.
 * @param {string} [type='info'] - O tipo de alerta (success, danger, warning, info).
 * @param {number} [duration=5000] - Duração em milissegundos para o alerta desaparecer.
 */
window.showAlert = function(message, type = 'info', duration = 5000) {
    const alertsContainer = document.getElementById('alert-container');
    if (!alertsContainer) {
        console.error('Elemento #alert-container não encontrado. Usando alert() como fallback.');
        alert(message);
        return;
    }

    const icons = {
        'success': 'fa-check-circle',
        'danger': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-circle',
        'info': 'fa-info-circle',
    };
    const iconClass = icons[type] || 'fa-info-circle';

    const alertId = `alert-${Date.now()}`;
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas ${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    `;

    alertsContainer.insertAdjacentHTML('beforeend', alertHtml);

    if (duration > 0) {
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                bootstrap.Alert.getOrCreateInstance(alertElement).close();
            }
        }, duration);
    }
};

/**
 * Exibe um modal de confirmação.
 * @param {object} options - Opções para o modal.
 * @param {string} options.title - Título do modal.
 * @param {string} options.message - Mensagem do corpo do modal.
 * @param {string} [options.type='primary'] - Tema do botão de confirmação (primary, danger, etc.).
 * @param {string} [options.confirmText='Confirmar'] - Texto do botão de confirmação.
 * @param {string} [options.cancelText='Cancelar'] - Texto do botão de cancelar.
 * @param {function} options.onConfirm - Callback a ser executado ao confirmar.
 */
window.showConfirmation = function(options) {
    // Default options
    const config = {
        title: 'Confirmação',
        message: 'Você tem certeza?',
        type: 'primary',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        onConfirm: () => {},
        ...options
    };

    // Remove qualquer modal de confirmação existente
    const existingModal = document.getElementById('confirmationModal');
    if (existingModal) {
        // Tenta destruir a instância do Bootstrap Modal se existir
        const bsInstance = bootstrap.Modal.getInstance(existingModal);
        if (bsInstance) {
            bsInstance.hide();
        }
        existingModal.remove();
    }

    const modalHtml = `
        <div class="modal fade" id="confirmationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${config.title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${config.message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${config.cancelText}</button>
                        <button type="button" id="confirmBtn" class="btn btn-${config.type}">${config.confirmText}</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modalElement = document.getElementById('confirmationModal');
    const confirmBtn = document.getElementById('confirmBtn');
    const confirmationModal = new bootstrap.Modal(modalElement);

    // Cria um handler novo que será removido automaticamente
    const confirmHandler = () => {
        config.onConfirm();
        confirmationModal.hide();
    };

    // Adiciona o listener com { once: true } para executar apenas uma vez
    confirmBtn.addEventListener('click', confirmHandler, { once: true });

    // Remove o modal quando ele se fecha
    modalElement.addEventListener('hidden.bs.modal', () => {
        // Remove o listener para limpar
        confirmBtn.removeEventListener('click', confirmHandler);
        modalElement.remove();
    }, { once: true });

    confirmationModal.show();
};


// ========================================
// INICIALIZAÇÃO GERAL DO DOM
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o controlador da sidebar
    window.sidebarController = new SidebarController();

    // Inicializa todos os tooltips do Bootstrap na página (exceto os da sidebar, que são controlados pela classe)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]:not(#sidebar [data-bs-toggle="tooltip"])'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ========================================
    // SUBMENU FLUTUANTE PARA SIDEBAR RETRAÍDA
    // ========================================

    const sidebar = document.getElementById('sidebar');
    let currentFloatingMenu = null;
    let isMenuOpening = false; // Flag para prevenir fechamento imediato

    // Função para criar menu flutuante
    function createFloatingMenu(triggerElement, submenuId) {
        console.log('createFloatingMenu chamada com submenuId:', submenuId); // DEBUG

        // Remove menu flutuante anterior se existir
        if (currentFloatingMenu) {
            currentFloatingMenu.remove();
            currentFloatingMenu = null;
        }

        // Pega o submenu original
        const originalSubmenu = document.getElementById(submenuId);
        console.log('originalSubmenu encontrado:', originalSubmenu); // DEBUG

        if (!originalSubmenu) {
            console.error('ERRO: Submenu não encontrado!', submenuId); // DEBUG
            return null;
        }

        // Ativa flag de abertura
        isMenuOpening = true;

        // Cria o menu flutuante
        const floatingMenu = document.createElement('div');
        floatingMenu.className = 'sidebar-floating-menu';

        // Copia os itens do submenu original
        const items = originalSubmenu.querySelectorAll('.nav-item');
        console.log('Itens encontrados no submenu:', items.length); // DEBUG

        items.forEach((item, index) => {
            const link = item.querySelector('a');
            console.log(`Item ${index}:`, link); // DEBUG
            if (link) {
                const floatingItem = document.createElement('a');
                floatingItem.href = link.href;
                floatingItem.className = 'floating-menu-item';
                if (link.classList.contains('active')) {
                    floatingItem.classList.add('active');
                }

                // Copia o ícone
                const icon = link.querySelector('i');
                if (icon) {
                    const newIcon = icon.cloneNode(true);
                    floatingItem.appendChild(newIcon);
                }

                // Copia o texto
                const text = link.querySelector('.sidebar-text');
                if (text) {
                    const span = document.createElement('span');
                    span.textContent = text.textContent;
                    floatingItem.appendChild(span);
                }

                floatingMenu.appendChild(floatingItem);
                console.log('Item adicionado ao menu flutuante:', floatingItem); // DEBUG
            }
        });

        console.log('Total de itens no menu flutuante:', floatingMenu.children.length); // DEBUG

        // Adiciona ao body
        document.body.appendChild(floatingMenu);
        console.log('Menu flutuante adicionado ao body'); // DEBUG

        // Posiciona o menu ao lado do ícone
        const rect = triggerElement.getBoundingClientRect();
        floatingMenu.style.top = rect.top + 'px';
        console.log('Posição do menu:', rect.top + 'px'); // DEBUG

        // Mostra o menu com animação
        setTimeout(() => {
            floatingMenu.classList.add('show');
            console.log('Classe "show" adicionada ao menu'); // DEBUG
            // Desativa flag após abrir completamente
            setTimeout(() => {
                isMenuOpening = false;
            }, 100);
        }, 10);

        return floatingMenu;
    }

    // Função para fechar menu flutuante
    function closeFloatingMenu() {
        if (currentFloatingMenu) {
            currentFloatingMenu.classList.remove('show');
            setTimeout(() => {
                if (currentFloatingMenu) {
                    currentFloatingMenu.remove();
                    currentFloatingMenu = null;
                }
            }, 200);
        }
    }

    // Event listener para submenus na sidebar retraída
    if (sidebar) {
        // Event listener ÚNICO para prevenir collapse E criar menu flutuante
        sidebar.addEventListener('click', function(e) {
            // Procura por elementos com data-toggle-backup (quando retraído) ou data-bs-toggle (quando expandido)
            const target = e.target.closest('[data-toggle-backup], [data-bs-toggle="collapse"]');

            // Se clicou em um item com submenu e a sidebar está retraída
            if (target && sidebar.classList.contains('collapsed') && window.innerWidth > 991.98) {
                // PREVINE TOTALMENTE o Bootstrap
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const submenuId = target.getAttribute('href').substring(1);
                const collapseElement = document.getElementById(submenuId);

                console.log('Clicou em movimentação! submenuId:', submenuId); // DEBUG

                // DESTROI COMPLETAMENTE o Bootstrap Collapse
                if (collapseElement) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
                    if (bsCollapse) {
                        bsCollapse.dispose();
                    }
                    collapseElement.classList.remove('show', 'showing', 'collapsing');
                    collapseElement.style.height = '';
                    collapseElement.style.display = '';
                }

                // Força o link a manter seu estado
                target.setAttribute('aria-expanded', 'false');
                target.classList.add('collapsed');

                // Se já existe um menu flutuante do mesmo submenu, fecha
                if (currentFloatingMenu && currentFloatingMenu.dataset.submenuId === submenuId) {
                    console.log('Fechando menu flutuante existente'); // DEBUG
                    closeFloatingMenu();
                } else {
                    console.log('Criando novo menu flutuante'); // DEBUG
                    // Cria novo menu flutuante
                    currentFloatingMenu = createFloatingMenu(target, submenuId);
                    if (currentFloatingMenu) {
                        currentFloatingMenu.dataset.submenuId = submenuId;
                        console.log('Menu flutuante criado com sucesso!', currentFloatingMenu); // DEBUG
                    } else {
                        console.error('ERRO: createFloatingMenu retornou null!'); // DEBUG
                    }
                }

                // Limpa novamente após processamento
                setTimeout(() => {
                    target.setAttribute('aria-expanded', 'false');
                    target.classList.add('collapsed');
                    if (collapseElement) {
                        collapseElement.classList.remove('show', 'showing', 'collapsing');
                        collapseElement.style.height = '';
                    }
                }, 10);

                return false;
            }
        }, true)

        // MutationObserver para monitorar links de toggle (procura ambos os atributos)
        const initMutationObservers = () => {
            const toggleLinks = sidebar.querySelectorAll('[data-bs-toggle="collapse"], [data-toggle-backup]');
            toggleLinks.forEach(link => {
            const linkObserver = new MutationObserver(function(mutations) {
                if (sidebar.classList.contains('collapsed') && window.innerWidth > 991.98) {
                    mutations.forEach(function(mutation) {
                        // Força aria-expanded="false"
                        if (mutation.attributeName === 'aria-expanded' && link.getAttribute('aria-expanded') === 'true') {
                            link.setAttribute('aria-expanded', 'false');
                        }
                        // Força classe collapsed
                        if (mutation.attributeName === 'class' && !link.classList.contains('collapsed')) {
                            link.classList.add('collapsed');
                        }
                    });
                }
            });

            linkObserver.observe(link, {
                attributes: true,
                attributeFilter: ['aria-expanded', 'class']
            });

            // MutationObserver para o submenu associado
            const submenuId = link.getAttribute('href');
            if (submenuId && submenuId.startsWith('#')) {
                const submenu = document.querySelector(submenuId);
                if (submenu) {
                    const submenuObserver = new MutationObserver(function(mutations) {
                        if (sidebar.classList.contains('collapsed') && window.innerWidth > 991.98) {
                            // Remove IMEDIATAMENTE qualquer classe show/collapsing
                            if (submenu.classList.contains('show') || submenu.classList.contains('collapsing')) {
                                submenu.classList.remove('show', 'showing', 'collapsing');
                                submenu.style.height = '';
                                submenu.style.display = '';
                            }
                        }
                    });

                    submenuObserver.observe(submenu, {
                        attributes: true,
                        attributeFilter: ['class', 'style']
                    });
                }
            }
        });
        };

        // Chama a função para inicializar os observers
        initMutationObservers();
    }

    // Fecha menu flutuante ao clicar fora
    document.addEventListener('click', function(e) {
        // Só fecha se não estiver no processo de abertura
        if (!isMenuOpening && currentFloatingMenu && !e.target.closest('.sidebar-floating-menu') && !e.target.closest('[data-bs-toggle="collapse"]') && !e.target.closest('[data-toggle-backup]')) {
            closeFloatingMenu();
        }
    });

    // Fecha menu flutuante ao redimensionar janela
    window.addEventListener('resize', debounce(function() {
        closeFloatingMenu();
    }, 200));

    // Fecha menu flutuante quando expandir a sidebar E gerencia data-bs-toggle
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const toggleLinks = sidebar.querySelectorAll('[data-bs-toggle="collapse"], [data-toggle-backup]');

                    if (sidebar.classList.contains('collapsed') && window.innerWidth > 991.98) {
                        // Sidebar retraída: REMOVE data-bs-toggle para desabilitar Bootstrap
                        toggleLinks.forEach(link => {
                            if (link.hasAttribute('data-bs-toggle')) {
                                link.setAttribute('data-toggle-backup', link.getAttribute('data-bs-toggle'));
                                link.removeAttribute('data-bs-toggle');
                                console.log('data-bs-toggle removido de:', link); // DEBUG
                            }
                        });
                    } else {
                        // Sidebar expandida: RESTAURA data-bs-toggle
                        closeFloatingMenu();
                        toggleLinks.forEach(link => {
                            if (link.hasAttribute('data-toggle-backup')) {
                                link.setAttribute('data-bs-toggle', link.getAttribute('data-toggle-backup'));
                                link.removeAttribute('data-toggle-backup');
                                console.log('data-bs-toggle restaurado em:', link); // DEBUG
                            }
                        });
                    }
                }
            });
        });

        observer.observe(sidebar, { attributes: true });

        // Aplica a regra imediatamente se a sidebar já estiver retraída ao carregar
        if (sidebar.classList.contains('collapsed') && window.innerWidth > 991.98) {
            const toggleLinks = sidebar.querySelectorAll('[data-bs-toggle="collapse"]');
            toggleLinks.forEach(link => {
                link.setAttribute('data-toggle-backup', link.getAttribute('data-bs-toggle'));
                link.removeAttribute('data-bs-toggle');
                console.log('data-bs-toggle removido no load:', link); // DEBUG
            });
        }
    }
});