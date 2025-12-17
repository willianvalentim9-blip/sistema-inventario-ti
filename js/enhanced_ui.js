/**
 * ==========================================================
 * enhanced_ui.js
 * * Contém scripts para melhorias de interface do usuário,
 * como modais de confirmação, upload de logo, etc.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function() {
    // NOTA: A funcionalidade de upload de logo foi movida para settings.php
    // para evitar duplicação de código e conflitos de event listeners.
    // A lógica agora está centralizada em modules/utilities/settings.php (linha 439+)
});


/**
 * Função para exibir um modal de confirmação genérico e personalizável.
 * Exemplo de uso:
 * showConfirmation({
 * title: 'Confirmar Ação',
 * message: 'Você tem certeza que deseja prosseguir?',
 * details: 'Esta ação não pode ser desfeita.',
 * type: 'warning', // 'danger', 'info', 'success'
 * confirmText: 'Sim, Prosseguir',
 * cancelText: 'Cancelar',
 * onConfirm: () => { console.log('Ação confirmada!'); },
 * onCancel: () => { console.log('Ação cancelada.'); }
 * });
 */
function showConfirmation(options) {
    // Desabilita todos os tooltips ativos para evitar sobreposição
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => tooltip.remove());

    // Define valores padrão para as opções
    const config = {
        title: options.title || 'Confirmação',
        message: options.message || 'Você tem certeza?',
        details: options.details || '',
        type: options.type || 'info',
        confirmText: options.confirmText || 'Confirmar',
        cancelText: options.cancelText || 'Cancelar',
        confirmClass: options.confirmClass || `btn-${options.type || 'primary'}`,
        onConfirm: options.onConfirm || (() => {}),
        onCancel: options.onCancel || (() => {})
    };

    // Ícones e cores baseados no tipo
    const typeMap = {
        'danger': { icon: 'fa-exclamation-triangle', color: 'text-danger' },
        'warning': { icon: 'fa-exclamation-circle', color: 'text-warning' },
        'info': { icon: 'fa-info-circle', color: 'text-info' },
        'success': { icon: 'fa-check-circle', color: 'text-success' }
    };
    const typeInfo = typeMap[config.type] || typeMap['info'];

    // Remove qualquer modal de confirmação existente para evitar duplicação
    const existingModal = document.getElementById('confirmationModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Cria o HTML do modal
    const modalHTML = `
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title ${typeInfo.color}" id="confirmationModalLabel">
                            <i class="fas ${typeInfo.icon} me-2"></i>
                            ${config.title}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>${config.message}</p>
                        ${config.details ? `<div class="alert alert-light small p-2 mt-3">${config.details}</div>` : ''}
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelConfirmBtn">${config.cancelText}</button>
                        <button type="button" class="btn ${config.confirmClass}" id="execConfirmBtn">${config.confirmText}</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Adiciona o modal ao corpo da página
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalElement = document.getElementById('confirmationModal');
    const confirmationModal = new bootstrap.Modal(modalElement);
    const execBtn = document.getElementById('execConfirmBtn');
    const cancelBtn = document.getElementById('cancelConfirmBtn');

    // Função para limpar e remover o modal
    const cleanup = () => {
        confirmationModal.hide();
    };

    // Adiciona os listeners
    execBtn.addEventListener('click', () => {
        config.onConfirm();
        cleanup();
    });

    cancelBtn.addEventListener('click', () => {
        config.onCancel();
        cleanup();
    });
    
    // Remove o elemento do DOM depois que o modal for totalmente escondido
    modalElement.addEventListener('hidden.bs.modal', event => {
        modalElement.remove();
    });

    // Mostra o modal
    confirmationModal.show();
}