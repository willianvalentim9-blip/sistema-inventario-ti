<?php
/**
 * MODAL INLINE DE GARANTIA PARA CRIAR/EDITAR PRODUTO
 * 
 * Este arquivo é incluído em add_product.php e edit_product.php
 * Fornece um modal otimizado para gerenciar garantias de forma simples
 * 
 * Variáveis esperadas:
 * - $product (opcional) - dados do produto a editar
 * - $product_id (opcional) - ID do produto
 */

// Se for chamado diretamente, retorna vazio
if (!isset($GLOBALS['is_inside_product_form'])) {
    return;
}
?>

<!-- ===============================================
     MODAL: GERENCIAR GARANTIA DO PRODUTO
     =============================================== -->
<div class="modal fade" id="warrantyModalProduct" tabindex="-1" aria-labelledby="warrantyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- HEADER DO MODAL -->
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="warrantyModalLabel">
                    <i class="fas fa-shield-alt me-2 text-primary-custom"></i>
                    Gerenciar Garantia do Produto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- BODY DO MODAL -->
            <div class="modal-body">
                <!-- ALERTA DE AVISO -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Dica:</strong> Configure aqui os dados básicos. Para gerenciar detalhes completos (cliente, ticket, etc), acesse a aba <strong>Garantias</strong>.
                </div>

                <!-- CONTEÚDO DE GARANTIA -->
                <div id="warranty-content-product" class="warranty-details-section">
                    
                    <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
                    <div class="warranty-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Informações Básicas</h6>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_warranty_provider" class="form-label form-label-custom">
                                    <i class="fas fa-store me-1"></i>Fornecedor de Garantia
                                </label>
                                <input type="text" class="form-control form-control-custom" 
                                       id="product_warranty_provider" name="warranty_provider" 
                                       placeholder="Ex: Samsung, LG, Autorizado">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="product_invoice_number" class="form-label form-label-custom">
                                    <i class="fas fa-file-invoice me-1"></i>Nota Fiscal (NF-e)
                                </label>
                                <input type="text" class="form-control form-control-custom" 
                                       id="product_invoice_number" name="invoice_number" 
                                       placeholder="Número da NF">
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO 2: PERÍODO DE GARANTIA -->
                    <div class="warranty-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-calendar-alt me-2 text-success"></i>Período da Garantia</h6>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="product_warranty_start_date" class="form-label form-label-custom">
                                    <i class="fas fa-play-circle me-1"></i>Data de Início
                                </label>
                                <input type="date" class="form-control form-control-custom" 
                                       id="product_warranty_start_date" name="warranty_start_date">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="product_warranty_period_value" class="form-label form-label-custom">
                                    <i class="fas fa-hourglass-half me-1"></i>Duração
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-custom" 
                                           id="product_warranty_period_value" name="warranty_period_value" 
                                           min="1" placeholder="Ex: 12">
                                    <select class="form-select form-control-custom" id="product_warranty_period_unit" 
                                            name="warranty_period_unit">
                                        <option value="days">Dias</option>
                                        <option value="months" selected>Meses</option>
                                        <option value="years">Anos</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="product_warranty_end_date" class="form-label form-label-custom">
                                    <i class="fas fa-flag-checkered me-1"></i>Data de Término
                                </label>
                                <input type="date" class="form-control form-control-custom" 
                                       id="product_warranty_end_date" name="warranty_end_date" 
                                       readonly style="background-color: #f5f5f5;">
                                <small class="text-muted d-block mt-1">Calculado automaticamente</small>
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO 3: OBSERVAÇÕES -->
                    <div class="warranty-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-sticky-note me-2 text-warning"></i>Observações</h6>
                        </div>

                        <div class="mb-3">
                            <label for="product_warranty_notes" class="form-label form-label-custom">
                                <i class="fas fa-edit me-1"></i>Anotações sobre a Garantia
                            </label>
                            <textarea class="form-control form-control-custom" 
                                      id="product_warranty_notes" name="warranty_notes" 
                                      rows="3" placeholder="Detalhes adicionais, condições especiais, etc..."></textarea>
                        </div>
                    </div>

                    <!-- DICA FINAL -->
                    <div class="alert alert-light border border-info mb-0">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1 text-warning"></i>
                            <strong>Nota:</strong> As informações de garantia serão salvas junto com o produto.
                        </small>
                    </div>
                </div>
            </div>

            <!-- FOOTER DO MODAL -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
                <button type="button" class="btn btn-primary-custom" onclick="syncWarrantyDataToForm()">
                    <i class="fas fa-check me-1"></i>Aplicar Dados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===============================================
     SCRIPTS DO MODAL DE GARANTIA
     =============================================== -->
<script>
(function() {
    'use strict';

    // ========================================
    // CONFIGURAÇÃO INICIAL
    // ========================================

    const warrantyContent = document.getElementById('warranty-content-product');
    const warrantyModal = document.getElementById('warrantyModalProduct');

    // Checkbox de garantia do formulário principal
    const productHasWarrantyCheckbox = document.getElementById('has_warranty');

    // ========================================
    // FUNÇÃO: Sincronizar dados do modal com o formulário
    // ========================================
    window.syncWarrantyDataToForm = function() {
        // Copia os dados do modal para os campos do formulário principal
        const fields = [
            'warranty_provider',
            'invoice_number',
            'warranty_start_date',
            'warranty_period_value',
            'warranty_period_unit',
            'warranty_end_date',
            'warranty_notes'
        ];

        fields.forEach(field => {
            const modalField = document.getElementById('product_' + field);
            const formField = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            
            if (modalField && formField) {
                formField.value = modalField.value;
            }
        });

        // Fecha o modal
        const modal = bootstrap.Modal.getInstance(warrantyModal);
        if (modal) {
            modal.hide();
        }

        // Mostra mensagem de sucesso
        showAlert('Dados de garantia aplicados ao formulário!', 'success');
    };

    // ========================================
    // FUNÇÃO: Calcular data final automaticamente
    // ========================================
    function calculateEndDate() {
        const startDate = document.getElementById('product_warranty_start_date').value;
        const periodValue = parseInt(document.getElementById('product_warranty_period_value').value) || 0;
        const periodUnit = document.getElementById('product_warranty_period_unit').value;
        const endDateField = document.getElementById('product_warranty_end_date');

        if (!startDate || periodValue <= 0) {
            endDateField.value = '';
            return;
        }

        try {
            const date = new Date(startDate + 'T00:00:00');
            
            if (periodUnit === 'days') {
                date.setDate(date.getDate() + periodValue);
            } else if (periodUnit === 'months') {
                date.setMonth(date.getMonth() + periodValue);
            } else if (periodUnit === 'years') {
                date.setFullYear(date.getFullYear() + periodValue);
            }

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            endDateField.value = `${year}-${month}-${day}`;
        } catch (error) {
            console.error('Erro ao calcular data:', error);
        }
    }

    // ========================================
    // EVENT LISTENERS
    // ========================================

    // Cálculo automático de data final
    const startDateField = document.getElementById('product_warranty_start_date');
    const periodValueField = document.getElementById('product_warranty_period_value');
    const periodUnitField = document.getElementById('product_warranty_period_unit');

    if (startDateField) {
        startDateField.addEventListener('change', calculateEndDate);
        periodValueField?.addEventListener('input', calculateEndDate);
        periodUnitField?.addEventListener('change', calculateEndDate);
    }

    // Se o modal for aberto e houver dados no formulário, carrega no modal
    if (warrantyModal) {
        warrantyModal.addEventListener('show.bs.modal', function() {
            const fields = [
                'warranty_provider',
                'invoice_number',
                'warranty_start_date',
                'warranty_period_value',
                'warranty_period_unit',
                'warranty_end_date',
                'warranty_notes'
            ];

            fields.forEach(field => {
                const formField = document.querySelector(`[name="${field}"]`);
                const modalField = document.getElementById('product_' + field);

                if (formField && modalField) {
                    modalField.value = formField.value;
                }
            });

            // Pequeno delay para garantir que os campos estão prontos
            setTimeout(calculateEndDate, 100);
        });
    }
})();
</script>

<style>
.warranty-section {
    border-left: 4px solid #e9ecef;
    padding-left: 15px;
}

.warranty-section:hover {
    border-left-color: #0d6efd;
    transition: all 0.3s ease;
}

.section-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.warranty-details-section {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
