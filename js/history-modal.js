/**
 * Modal de Histórico - Função JavaScript Reutilizável
 *
 * Abre um modal com o histórico de um item (produto, máquina, armazém)
 * Usa Bootstrap 5 Modal
 */

// Criar modal de histórico se não existir
function createHistoryModal() {
    if (document.getElementById('historyModal')) {
        return;
    }

    const modalHTML = `
        <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historyModalLabel">
                            <i class="fas fa-history"></i> Histórico de Movimentações
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="historyModalBody">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando histórico...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Abrir modal de histórico
 * @param {string} url - URL da página de histórico (ex: product_history_view.php?id=1&modal=true)
 * @param {string} title - Título do modal (opcional)
 */
function openHistoryModal(url, title = null) {
    // Criar modal se não existir
    createHistoryModal();

    const modalElement = document.getElementById('historyModal');
    const modalBody = document.getElementById('historyModalBody');
    const modalTitle = document.getElementById('historyModalLabel');

    // Atualizar título se fornecido
    if (title) {
        modalTitle.innerHTML = `<i class="fas fa-history"></i> ${title}`;
    } else {
        modalTitle.innerHTML = '<i class="fas fa-history"></i> Histórico de Movimentações';
    }

    // Mostrar loading
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando histórico...</p>
        </div>
    `;

    // Abrir modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Carregar conteúdo via fetch
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao carregar histórico');
            }
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Erro ao carregar histórico. Tente novamente.
                </div>
            `;
        });
}
