<?php
// ========================================
// FORMULÁRIO DE BAIXA DE MÁQUINA (COM QUANTIDADE)
// ========================================
require_once '../../config.php';
requireLogin();

$machine_id = intval($_GET['id'] ?? 0);
$machine = null;
$error_message = '';

if ($machine_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, name, status, sale_price, quantity FROM ready_machines WHERE id = ?");
        $stmt->execute([$machine_id]);
        $machine = $stmt->fetch();
        if (!$machine) {
            $error_message = 'Máquina não encontrada.';
        } elseif ($machine['quantity'] <= 0) {
            $error_message = 'Esta máquina não possui estoque para dar baixa.';
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

<form id="machineStockOutForm" action="update_machine_status.php" method="POST" autocomplete="off">
    <div id="machineStockOutMessage" class="mb-3"></div>
    <input type="hidden" name="machine_id" value="<?php echo $machine['id']; ?>">
    <div class="alert alert-secondary">
        <h6 class="mb-0 text-primary-custom"><?php echo htmlspecialchars($machine['name']); ?></h6>
        <small class="text-muted">Estoque Atual: <?php echo $machine['quantity']; ?></small>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="quantity" class="form-label form-label-custom">Quantidade para Baixa*</label>
            <input type="number" class="form-control form-control-custom" id="quantity" name="quantity" required min="1" max="<?php echo $machine['quantity']; ?>" value="1">
        </div>
        <div class="col-md-6 mb-3">
            <label for="reason" class="form-label form-label-custom">Motivo da Baixa*</label>
            <select class="form-select form-control-custom" id="reason" name="reason" required>
                <option value="" selected disabled>Selecione...</option>
                <option value="Venda">Venda</option>
                <option value="Descarte">Descarte</option>
                <option value="Uso Interno">Uso Interno</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
    </div>
    <div id="sale_price_group" class="mb-3" style="display: none;">
        <label for="final_sale_price" class="form-label form-label-custom">Preço Final de Venda (R$)</label>
        <input type="text" class="form-control form-control-custom" id="final_sale_price" name="final_sale_price" value="<?php echo number_format($machine['sale_price'], 2, ',', '.'); ?>">
    </div>
    <div class="mb-3">
        <label for="notes" class="form-label form-label-custom">Observações</label>
        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="2" placeholder="Detalhes da venda, cliente, etc."></textarea>
    </div>
    <div class="d-flex justify-content-end border-top pt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="submitMachineStockOut"><i class="fas fa-check me-1"></i>Confirmar Baixa</button>
    </div>
</form>

<script>
document.getElementById('reason').addEventListener('change', function() {
    document.getElementById('sale_price_group').style.display = (this.value === 'Venda') ? 'block' : 'none';
});

const machineStockOutForm = document.getElementById('machineStockOutForm');
if (machineStockOutForm) {
    machineStockOutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = document.getElementById('submitMachineStockOut');
        const originalButtonHtml = submitButton.innerHTML;
        const messageDiv = document.getElementById('machineStockOutMessage');

        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        submitButton.disabled = true;
        messageDiv.innerHTML = '';

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const modal = bootstrap.Modal.getInstance(machineStockOutForm.closest('.modal'));
            
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
}
</script>