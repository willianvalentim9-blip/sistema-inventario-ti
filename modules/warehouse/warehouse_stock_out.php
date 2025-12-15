<?php
// ========================================
// FORMULÁRIO DE BAIXA DE ESTOQUE WAREHOUSE (PARA MODAL)
// ========================================
require_once '../../config.php';
requireLogin();

$warehouse_id = intval($_GET['id'] ?? 0);
$item = null;
$error_message = '';

if ($warehouse_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, name, quantity, category, price FROM warehouse WHERE id = ?");
        $stmt->execute([$warehouse_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $error_message = 'Item do warehouse não encontrado.';
        } elseif ($item['quantity'] <= 0) {
            $error_message = 'Este item não possui estoque para dar baixa.';
        }
    } catch (PDOException $e) {
        $error_message = 'Erro ao buscar o item: ' . $e->getMessage();
    }
} else {
    $error_message = 'ID do item inválido.';
}

if ($error_message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    exit;
}
?>

<form id="stockOutForm" action="give_warehouse_stock_out.php" method="POST" autocomplete="off">
    <div id="stockOutMessage" class="mb-3"></div>
    
    <input type="hidden" name="warehouse_id" value="<?php echo $item['id']; ?>">
    <input type="hidden" name="ajax" value="1">

    <div class="alert alert-secondary">
        <div class="d-flex justify-content-between">
            <div>
                <h6 class="mb-0 text-primary-custom"><?php echo htmlspecialchars($item['name']); ?></h6>
                <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
            </div>
            <div>
                Estoque Atual: 
                <span class="badge bg-primary fs-6"><?php echo $item['quantity']; ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="quantity" class="form-label form-label-custom">Quantidade para Baixa*</label>
            <input type="number" class="form-control form-control-custom" id="quantity" name="quantity" required min="1" max="<?php echo $item['quantity']; ?>" value="1">
        </div>
        <div class="col-md-6 mb-3">
            <label for="reason" class="form-label form-label-custom">Motivo da Baixa*</label>
            <select class="form-select form-control-custom" id="reason" name="reason" required>
                <option value="" selected disabled>Selecione um motivo...</option>
                <option value="Venda">Venda</option>
                <option value="Uso Interno">Uso Interno</option>
                <option value="Descarte por Defeito">Descarte por Defeito</option>
                <option value="Devolução ao Fornecedor">Devolução ao Fornecedor</option>
                <option value="Perda ou Roubo">Perda ou Roubo</option>
                <option value="Ajuste de Inventário">Ajuste de Inventário</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
    </div>
    
    <div id="additional-info-container" class="mb-3"></div>

    <div class="mb-3" id="sale_price_group">
        <label for="unit_price" class="form-label form-label-custom">Preço de Venda (Unitário)</label>
        <input type="text" class="form-control form-control-custom" id="unit_price" name="unit_price" placeholder="R$ 0,00" value="<?php echo number_format($item['price'] ?? 0, 2, ',', '.'); ?>">
    </div>

    <div class="mb-3">
        <label for="notes" class="form-label form-label-custom">Observações</label>
        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="3" placeholder="Detalhes adicionais, como nome do cliente, etc."></textarea>
    </div>
    
    <div class="d-flex justify-content-end border-top pt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="submitStockOut">
            <i class="fas fa-check me-1"></i>
            Confirmar Baixa
        </button>
    </div>
</form>

<script>
    const stockOutForm = document.getElementById('stockOutForm');
    
    if (stockOutForm) {
        stockOutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('submitStockOut');
            const originalButtonHtml = submitButton.innerHTML;
            const messageDiv = document.getElementById('stockOutMessage');

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
                const modal = bootstrap.Modal.getInstance(stockOutForm.closest('.modal'));

                if (data.success) {
                    modal.hide();

                    // ========================================
                    // EXIBIR AVISO SE ESTOQUE FICOU ABAIXO DO MÍNIMO
                    // ========================================
                    if (data.warning && data.warning.trim() !== '') {
                        // Mostra o alerta de sucesso primeiro
                        showAlert(data.message, 'success');

                        // Depois de 500ms mostra o warning
                        setTimeout(() => {
                            showAlert(data.warning, 'warning', 8000); // 8 segundos para ler
                        }, 500);

                        // Recarrega após 9 segundos (tempo para ler ambos os alertas)
                        setTimeout(() => location.reload(), 9000);
                    } else {
                        // Sem warning, comportamento normal
                        showAlert(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    }
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

        const reasonSelect = document.getElementById('reason');
        const additionalInfoContainer = document.getElementById('additional-info-container');
        const updateAdditionalFields = () => {
            additionalInfoContainer.innerHTML = '';
            const reason = reasonSelect.value;
            let fieldHtml = '';

            switch(reason) {
                case 'Venda':
                    fieldHtml = `<label for="details_field" class="form-label form-label-custom">Cliente / Nº da Venda</label><input type="text" class="form-control form-control-custom" id="details_field" name="details_field" placeholder="Ex: João Silva, Venda #1234">`;
                    break;
                case 'Uso Interno':
                    fieldHtml = `<label for="details_field" class="form-label form-label-custom">Departamento / Funcionário</label><input type="text" class="form-control form-control-custom" id="details_field" name="details_field" placeholder="Ex: TI, Suporte N2">`;
                    break;
            }
            if (fieldHtml) additionalInfoContainer.innerHTML = fieldHtml;
        };
        reasonSelect.addEventListener('change', updateAdditionalFields);
    }
</script>
