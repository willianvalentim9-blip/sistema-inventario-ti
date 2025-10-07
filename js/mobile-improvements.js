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
    document.getElementById('confirmationModal')?.remove();

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

    confirmBtn.addEventListener('click', () => {
        config.onConfirm();
        confirmationModal.hide();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        modalElement.remove();
    });

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
});