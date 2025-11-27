<?php
/**
 * MODAL DE GARANTIA PARA EDICAO DE MAQUINA
 * 
 * Versao do modal especifica para a pagina de edicao de maquinas
 */

if (!isset($GLOBALS['is_inside_machine_form'])) {
    return;
}
?>

<!-- ===============================================
     MODAL: GERENCIAR GARANTIA DA MAQUINA (EDICAO)
     =============================================== -->
<div class="modal fade" id="warrantyModalMachineEdit" tabindex="-1" aria-labelledby="warrantyModalMachineEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- HEADER DO MODAL -->
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="warrantyModalMachineEditLabel">
                    <i class="fas fa-shield-alt me-2 text-primary-custom"></i>
                    Editar Garantia da Maquina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- BODY DO MODAL -->
            <div class="modal-body">
                <!-- ALERTA DE AVISO -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Dica:</strong> Configure aqui os dados basicos da garantia da maquina.
                </div>

                <!-- CONTEUDO DE GARANTIA -->
                <div id="warranty-content-machine-edit" class="warranty-details-section">
                    
                    <!-- SECAO 1: INFORMACOES BASICAS -->
                    <div class="warranty-section mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Informacoes Basicas</h6>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="machine_warranty_provider" class="form-label form-label-custom">
                                    <i class="fas fa-building me-1"></i>Fornecedor de Garantia
                                </label>
                                <input type="text" class="form-control form-control-custom" 
                                       id="machine_warranty_provider" 
                                       placeholder="Ex: Samsung, LG, Autorizado">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="machine_warranty_period_value" class="form-label form-label-custom">
                                    <i class="fas fa-hourglass-half me-1"></i>Duracao
                                </label>
                                <input type="number" class="form-control form-control-custom" 
                                       id="machine_warranty_period_value" 
                                       min="1" placeholder="Ex: 12">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="machine_warranty_period_unit" class="form-label form-label-custom">
                                    <i class="fas fa-calendar-alt me-1"></i>Unidade
                                </label>
                                <select class="form-select form-control-custom" id="machine_warranty_period_unit">
                                    <option value="days">Dias</option>
                                    <option value="months" selected>Meses</option>
                                    <option value="years">Anos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER DO MODAL -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
                <button type="button" class="btn btn-primary-custom" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
