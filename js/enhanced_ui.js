/**
 * ==========================================================
 * enhanced_ui.js
 * * Contém scripts para melhorias de interface do usuário,
 * como modais de confirmação, upload de logo, etc.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function() {

    /**
     * Lógica para upload e remoção do logo da empresa na página de configurações.
     */
    const setupLogoUpload = () => {
        const logoInput = document.getElementById('logo-input');
        const removeBtn = document.getElementById('remove-logo-btn');

        // Função para fazer o upload do logo via AJAX
        const uploadLogo = (file) => {
            const formData = new FormData();
            formData.append('logo', file);
            
            // Mostra um alerta de carregamento
            if (window.showAlert) {
                showAlert('Enviando novo logo...', 'loading', { persistent: true, duration: 0 });
            }

            fetch('update_logo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recarrega a página para exibir o novo logo e mensagem de sucesso
                    location.reload(); 
                } else {
                    if (window.showAlert) {
                        showAlert(data.message || 'Erro ao enviar a imagem.', 'error');
                    }
                }
            })
            .catch(error => {
                if (window.showAlert) {
                    showAlert('Erro de comunicação. Tente novamente.', 'error');
                }
                console.error('Error:', error);
            });
        };

        // ===============================================
        // *** CÓDIGO DE CORREÇÃO ADICIONADO AQUI ***
        // ===============================================
        // Função para remover o logo via AJAX
        const removeLogo = () => {
            // Usa o modal de confirmação, se disponível
            if (window.showConfirmation) {
                showConfirmation({
                    title: 'Remover Logo',
                    message: 'Tem certeza que deseja remover o logo da empresa?',
                    type: 'danger',
                    confirmText: 'Sim, Remover',
                    onConfirm: () => {
                        performLogoRemoval();
                    }
                });
            } else {
                // Fallback para um confirm padrão do navegador
                if (confirm('Tem certeza que deseja remover o logo da empresa?')) {
                    performLogoRemoval();
                }
            }
        };

        const performLogoRemoval = () => {
            const formData = new FormData();
            formData.append('remove_logo', '1');

            if (window.showAlert) {
                showAlert('Removendo logo...', 'loading', { persistent: true, duration: 0 });
            }

            fetch('update_logo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    if (window.showAlert) {
                        showAlert(data.message || 'Erro ao remover o logo.', 'error');
                    }
                }
            })
            .catch(error => {
                if (window.showAlert) {
                    showAlert('Erro de comunicação. Tente novamente.', 'error');
                }
                console.error('Error:', error);
            });
        };
        // ===============================================
        // *** FIM DO CÓDIGO DE CORREÇÃO ***
        // ===============================================

        // Adiciona os event listeners aos elementos
        if (logoInput) {
            logoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    uploadLogo(this.files[0]);
                }
            });
        }

        if (removeBtn) {
            // A linha abaixo estava faltando ou incorreta
            removeBtn.addEventListener('click', removeLogo);
        }
    };

    // Inicializa a funcionalidade de upload de logo se estivermos na página de configurações
    if (document.getElementById('settings-form')) {
        setupLogoUpload();
    }
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