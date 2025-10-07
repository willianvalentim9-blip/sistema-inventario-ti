<?php
// ========================================
// FORMULÁRIO DE ENTRADA DE ESTOQUE (PARA MODAL)
// ========================================
require_once 'config.php';
requireLogin();

$product_id = intval($_GET['id'] ?? 0);
$product = null;
$error_message = '';

if ($product_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, name, quantity, category, price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $error_message = 'Produto não encontrado.';
        }
    } catch (PDOException $e) {
        $error_message = 'Erro ao buscar o produto: ' . $e->getMessage();
    }
} else {
    $error_message = 'ID do produto inválido.';
}

if ($error_message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    exit;
}
?>

<form id="stockInForm" action="give_stock_in.php" method="POST" autocomplete="off">
    <div id="stockInMessage" class="mb-3"></div>
    
    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

    <div class="alert alert-secondary">
        <div class="d-flex justify-content-between">
            <div>
                <h6 class="mb-0 text-primary-custom"><?php echo htmlspecialchars($product['name']); ?></h6>
                <small class="text-muted"><?php echo htmlspecialchars($product['category']); ?></small>
            </div>
            <div>
                Estoque Atual: 
                <span class="badge bg-primary fs-6"><?php echo $product['quantity']; ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="quantity" class="form-label form-label-custom">Quantidade para Entrada*</label>
            <input type="number" class="form-control form-control-custom" id="quantity" name="quantity" required min="1" value="1">
        </div>
        <div class="col-md-6 mb-3">
            <label for="reason" class="form-label form-label-custom">Motivo da Entrada*</label>
            <select class="form-select form-control-custom" id="reason" name="reason" required>
                <option value="" selected disabled>Selecione um motivo...</option>
                <option value="Compra de Fornecedor">Compra de Fornecedor</option>
                <option value="Devolução de Cliente">Devolução de Cliente</option>
                <option value="Ajuste de Inventário">Ajuste de Inventário</option>
                <option value="Produção Interna">Produção Interna</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
    </div>
    
    <div id="additional-info-container-in" class="mb-3"></div>

    <div class="mb-3" id="cost_price_group" style="display: none;">
        <label for="unit_price" class="form-label form-label-custom">Preço de Custo (Unitário)</label>
        <input type="text" class="form-control form-control-custom" id="unit_price" name="unit_price" placeholder="R$ 0,00" value="<?php echo number_format($product['price'] ?? 0, 2, ',', '.'); ?>">
    </div>

    <div class="mb-3">
        <label for="notes" class="form-label form-label-custom">Observações</label>
        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="3" placeholder="Detalhes adicionais, como nome do fornecedor, Nº da NF, etc."></textarea>
    </div>
    
    <div class="d-flex justify-content-end border-top pt-3">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" id="submitStockIn">
            <i class="fas fa-check me-1"></i>
            Confirmar Entrada
        </button>
    </div>
</form>

<script>
    const stockInForm = document.getElementById('stockInForm');
    
    if (stockInForm) {
        stockInForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('submitStockIn');
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
                const modal = bootstrap.Modal.getInstance(stockInForm.closest('.modal'));
                
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

        const reasonSelectIn = document.getElementById('reason');
        const additionalInfoContainerIn = document.getElementById('additional-info-container-in');
        const costPriceGroup = document.getElementById('cost_price_group');

        const updateAdditionalFieldsIn = () => {
            additionalInfoContainerIn.innerHTML = '';
            const reason = reasonSelectIn.value;
            let fieldHtml = '';

            // Mostra o campo de preço apenas se for compra
            if (reason === 'Compra de Fornecedor') {
                costPriceGroup.style.display = 'block';
            } else {
                costPriceGroup.style.display = 'none';
            }

            switch(reason) {
                case 'Compra de Fornecedor':
                    fieldHtml = `<label for="details_field" class="form-label form-label-custom">Fornecedor / Nº da Nota Fiscal</label><input type="text" class="form-control form-control-custom" id="details_field" name="details_field" placeholder="Ex: InfoDistribuidora, NF #5678">`;
                    break;
                case 'Devolução de Cliente':
                    fieldHtml = `<label for="details_field" class="form-label form-label-custom">Cliente / Nº da Venda Original</label><input type="text" class="form-control form-control-custom" id="details_field" name="details_field" placeholder="Ex: João Silva, Venda #1234">`;
                    break;
            }
            if (fieldHtml) additionalInfoContainerIn.innerHTML = fieldHtml;
        };
        reasonSelectIn.addEventListener('change', updateAdditionalFieldsIn);
        // Chama a função uma vez para garantir que o estado inicial esteja correto
        updateAdditionalFieldsIn();
    }
</script>