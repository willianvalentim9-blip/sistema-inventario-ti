<?php
// ========================================
// FORMULÁRIO DE ENTRADA DE ESTOQUE DE MÁQUINA (PARA MODAL)
// ========================================
require_once 'config.php';
requireLogin();

$machine_id = intval($_GET['id'] ?? 0);
$machine = null;
$error_message = '';

if ($machine_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, name, quantity FROM ready_machines WHERE id = ?");
        $stmt->execute([$machine_id]);
        $machine = $stmt->fetch();

        if (!$machine) {
            $error_message = 'Máquina não encontrada.';
        }
    } catch (PDOException $e) {
        $error_message = 'Erro ao buscar a máquina: ' . $e->getMessage();
    }
} else {
    $error_message = 'ID da máquina inválido.';
}

if ($error_message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    exit;
}
?>

<form id="machineStockInForm" action="give_machine_stock_in.php" method="POST" autocomplete="off">
    <div id="stockInMessageMachine" class="mb-3"></div>
    
    <input type="hidden" name="machine_id" value="<?php echo $machine['id']; ?>">

    <div class="alert alert-secondary">
        <div class="d-flex justify-content-between">
            <div>
                <h6 class="mb-0 text-primary-custom"><?php echo htmlspecialchars($machine['name']); ?></h6>
            </div>
            <div>
                Estoque Atual: 
                <span class="badge bg-primary fs-6"><?php echo $machine['quantity']; ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="quantity_machine" class="form-label form-label-custom">Quantidade para Entrada*</label>
            <input type="number" class="form-control form-control-custom" id="quantity_machine" name="quantity" required min="1" value="1">
        </div>
        <div class="col-md-6 mb-3">
            <label for="reason_machine" class="form-label form-label-custom">Motivo da Entrada*</label>
            <select class="form-select form-control-custom" id="reason_machine" name="reason" required>
                <option value="" selected disabled>Selecione um motivo...</option>
                <option value="Compra de Fornecedor">Compra de Fornecedor</option>
                <option value="Devolução de Cliente">Devolução de Cliente</option>
                <option value="Ajuste de Inventário">Ajuste de Inventário</option>
                <option value="Montagem Finalizada">Montagem Finalizada</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
    </div>
    
    <div id="additional-info-container-machine" class="mb-3"></div>

    <div class="mb-3">
        <label for="notes_machine" class="form-label form-label-custom">Observações</label>
        <textarea class="form-control form-control-custom" id="notes_machine" name="notes" rows="3" placeholder="Detalhes adicionais, como nome do fornecedor, Nº da NF, etc."></textarea>
    </div>
    
    <div class="d-flex justify-content-end border-top pt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" id="submitMachineStockIn">
            <i class="fas fa-check me-1"></i>
            Confirmar Entrada
        </button>
    </div>
</form>

<script>
    const machineStockInForm = document.getElementById('machineStockInForm');
    
    if (machineStockInForm) {
        machineStockInForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('submitMachineStockIn');
            const originalButtonHtml = submitButton.innerHTML;
            
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            submitButton.disabled = true;

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const modal = bootstrap.Modal.getInstance(machineStockInForm.closest('.modal'));
                
                if (data.success) {
                    modal.hide();
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'danger');
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                showAlert(`Erro de comunicação: ${error.message}`, 'danger');
                submitButton.innerHTML = originalButtonHtml;
                submitButton.disabled = false;
            });
        });

        const reasonSelectMachine = document.getElementById('reason_machine');
        const additionalInfoContainerMachine = document.getElementById('additional-info-container-machine');
        const updateAdditionalFieldsMachine = () => {
            additionalInfoContainerMachine.innerHTML = '';
            const reason = reasonSelectMachine.value;
            let fieldHtml = '';

            switch(reason) {
                case 'Compra de Fornecedor':
                    fieldHtml = `<label for="details_field_machine" class="form-label form-label-custom">Fornecedor / Nº da Nota Fiscal</label><input type="text" class="form-control form-control-custom" id="details_field_machine" name="details_field" placeholder="Ex: InfoDistribuidora, NF #5678">`;
                    break;
                case 'Devolução de Cliente':
                    fieldHtml = `<label for="details_field_machine" class="form-label form-label-custom">Cliente / Nº da Venda Original</label><input type="text" class="form-control form-control-custom" id="details_field_machine" name="details_field" placeholder="Ex: João Silva, Venda #1234">`;
                    break;
            }
            if (fieldHtml) additionalInfoContainerMachine.innerHTML = fieldHtml;
        };
        reasonSelectMachine.addEventListener('change', updateAdditionalFieldsMachine);
    }
</script>