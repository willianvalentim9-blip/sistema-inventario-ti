<?php
/**
 * MODAL DE GARANTIA PARA EDIÇÃO DE WAREHOUSE
 *
 * Versão do modal específica para a página de edição de warehouse
 */

if (!isset($GLOBALS['is_inside_warehouse_form'])) {
    return;
}
?>

<!-- ===============================================
     MODAL: GERENCIAR GARANTIA DO WAREHOUSE (EDIÇÃO)
     =============================================== -->
<div class="modal fade" id="warrantyModalWarehouseEdit" tabindex="-1" aria-labelledby="warrantyModalLabelWarehouse" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- HEADER DO MODAL -->
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="warrantyModalLabelWarehouse">
                    <i class="fas fa-shield-alt me-2 text-primary-custom"></i>
                    Editar Garantia do Item do Armazém
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- BODY DO MODAL -->
            <div class="modal-body">
                <!-- ALERTA DE AVISO -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Dica:</strong> Configure aqui os dados de garantia do item do armazém.
                </div>

                <!-- CONTEÚDO DE GARANTIA -->
                <div id="warranty-content-warehouse-edit" class="warranty-details-section">

                    <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
                    <div class="warranty-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Informações Básicas</h6>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="warehouse_warranty_provider" class="form-label form-label-custom">
                                    <i class="fas fa-store me-1"></i>Fornecedor de Garantia
                                </label>
                                <input type="text" class="form-control form-control-custom"
                                       id="warehouse_warranty_provider"
                                       placeholder="Ex: Samsung, LG, Autorizado">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="warehouse_invoice_number" class="form-label form-label-custom">
                                    <i class="fas fa-file-invoice me-1"></i>Nota Fiscal (NF-e)
                                </label>
                                <input type="text" class="form-control form-control-custom"
                                       id="warehouse_invoice_number"
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
                                <label for="warehouse_warranty_start_date" class="form-label form-label-custom">
                                    <i class="fas fa-play-circle me-1"></i>Data de Início
                                </label>
                                <input type="date" class="form-control form-control-custom"
                                       id="warehouse_warranty_start_date">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="warehouse_warranty_period_value" class="form-label form-label-custom">
                                    <i class="fas fa-hourglass-half me-1"></i>Duração
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-custom"
                                           id="warehouse_warranty_period_value"
                                           min="1" placeholder="Ex: 12">
                                    <select class="form-select form-control-custom" id="warehouse_warranty_period_unit">
                                        <option value="days">Dias</option>
                                        <option value="months" selected>Meses</option>
                                        <option value="years">Anos</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="warehouse_warranty_end_date" class="form-label form-label-custom">
                                    <i class="fas fa-flag-checkered me-1"></i>Data de Término
                                </label>
                                <input type="date" class="form-control form-control-custom"
                                       id="warehouse_warranty_end_date"
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
                            <label for="warehouse_warranty_notes" class="form-label form-label-custom">
                                <i class="fas fa-edit me-1"></i>Anotações sobre a Garantia
                            </label>
                            <textarea class="form-control form-control-custom"
                                      id="warehouse_warranty_notes"
                                      rows="3" placeholder="Detalhes adicionais, condições especiais, etc..."></textarea>
                        </div>
                    </div>

                    <!-- DICA FINAL -->
                    <div class="alert alert-light border border-info mb-0">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1 text-warning"></i>
                            <strong>Nota:</strong> As informações de garantia serão salvas junto com o item do armazém.
                        </small>
                    </div>
                </div>
            </div>

            <!-- FOOTER DO MODAL -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
                <button type="button" class="btn btn-primary-custom" onclick="syncWarrantyDataEditWarehouse()">
                    <i class="fas fa-check me-1"></i>Aplicar Dados
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ========================================
    // SINCRONIZAR DADOS DO MODAL COM FORMULÁRIO
    // ========================================
    window.syncWarrantyDataEditWarehouse = function() {
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
            const modalField = document.getElementById('warehouse_' + field);
            const formField = document.getElementById(field);

            if (modalField && formField) {
                formField.value = modalField.value;
            }
        });

        // Fecha o modal
        const modal = document.getElementById('warrantyModalWarehouseEdit');
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }

        // Atualiza o resumo
        if (typeof updateWarrantySummaryWarehouse === 'function') {
            updateWarrantySummaryWarehouse();
        }

        if (typeof showAlert === 'function') {
            showAlert('Dados de garantia aplicados!', 'success');
        }
    };

    // ========================================
    // CALCULAR DATA FINAL AUTOMATICAMENTE
    // ========================================
    function calculateEndDateWarehouseEdit() {
        const startDate = document.getElementById('warehouse_warranty_start_date')?.value;
        const periodValue = parseInt(document.getElementById('warehouse_warranty_period_value')?.value) || 0;
        const periodUnit = document.getElementById('warehouse_warranty_period_unit')?.value;
        const endDateField = document.getElementById('warehouse_warranty_end_date');

        if (!startDate || periodValue <= 0 || !endDateField) {
            if (endDateField) endDateField.value = '';
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
    const startDateFieldWarehouse = document.getElementById('warehouse_warranty_start_date');
    const periodValueFieldWarehouse = document.getElementById('warehouse_warranty_period_value');
    const periodUnitFieldWarehouse = document.getElementById('warehouse_warranty_period_unit');

    if (startDateFieldWarehouse) {
        startDateFieldWarehouse.addEventListener('change', calculateEndDateWarehouseEdit);
        periodValueFieldWarehouse?.addEventListener('input', calculateEndDateWarehouseEdit);
        periodUnitFieldWarehouse?.addEventListener('change', calculateEndDateWarehouseEdit);
    }

    // Calcular ao abrir o modal
    const modalWarehouse = document.getElementById('warrantyModalWarehouseEdit');
    if (modalWarehouse) {
        modalWarehouse.addEventListener('show.bs.modal', function() {
            // Carregar dados dos campos do formulário para o modal
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
                const modalField = document.getElementById('warehouse_' + field);
                const formField = document.getElementById(field);

                if (modalField && formField) {
                    modalField.value = formField.value || '';
                }
            });

            // Pequeno delay para garantir que os campos estão prontos
            setTimeout(calculateEndDateWarehouseEdit, 100);
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
