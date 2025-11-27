<?php
/**
 * SELETOR DE FORNECEDORES DE GARANTIA INLINE - Funcional dentro do Modal de Edição
 * Similar ao seletor de templates, exibe fornecedores como cards clicáveis
 */

require_once 'config.php';

// Obter fornecedores ativos
$pdo = getConnection();
$stmt = $pdo->query("SELECT id, name, email, phone FROM warranty_suppliers WHERE is_active = 1 ORDER BY name ASC");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se há um fornecedor_id selecionado, obter seu nome
$current_supplier_id = isset($product['warranty_supplier_id']) ? $product['warranty_supplier_id'] : null;
$current_supplier_name = '';
if ($current_supplier_id) {
    $stmt = $pdo->prepare("SELECT name FROM warranty_suppliers WHERE id = ?");
    $stmt->execute([$current_supplier_id]);
    $current_supplier = $stmt->fetch();
    $current_supplier_name = $current_supplier ? $current_supplier['name'] : '';
}
?>

<?php if (!empty($suppliers)): ?>

<!-- Mostrar fornecedor atualmente selecionado (se houver) -->
<?php if ($current_supplier_id && $current_supplier_name): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-check-circle me-2"></i>
            <strong>Fornecedor Selecionado:</strong> <?php echo htmlspecialchars($current_supplier_name); ?>
        </div>
        <small>ID: <?php echo htmlspecialchars($current_supplier_id); ?></small>
    </div>
<?php endif; ?>

<div class="warranty-suppliers-container mb-4">
    <div class="card border-success">
        <div class="card-header bg-success text-white" style="cursor: pointer;" onclick="toggleSuppliers()">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-store me-2"></i> Fornecedores Disponíveis
                </h6>
                <small>
                    <i class="fas fa-chevron-down me-2" id="suppliers-toggle-icon"></i>
                    <span id="suppliers-toggle-text">Expandir</span>
                </small>
            </div>
        </div>
        <div class="card-body" id="suppliers-body" style="display: none;">
            <small class="text-muted d-block mb-3">
                <i class="fas fa-info-circle"></i> Clique em um fornecedor para selecioná-lo
            </small>
            <div class="row g-2">
                <?php foreach ($suppliers as $supplier): ?>
                    <div class="col-md-6 col-lg-4">
                        <button type="button" 
                                class="btn btn-outline-success w-100 supplier-btn <?php echo ($current_supplier_id == $supplier['id']) ? 'btn-success' : ''; ?>"
                                data-supplier-id="<?php echo htmlspecialchars($supplier['id']); ?>"
                                data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                                onclick="selectSupplier(event, this)">
                            
                            <div class="text-start">
                                <div class="fw-bold">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($supplier['name']); ?>
                                </div>
                                <?php if (!empty($supplier['email'])): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if (!empty($supplier['phone'])): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['phone']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Opção para limpar seleção -->
            <div class="mt-3 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSupplierSelection()">
                    <i class="fas fa-times me-1"></i> Limpar Seleção
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Toggle para expandir/recolher os fornecedores
 */
function toggleSuppliers() {
    const suppliersBody = document.getElementById('suppliers-body');
    const toggleIcon = document.getElementById('suppliers-toggle-icon');
    const toggleText = document.getElementById('suppliers-toggle-text');
    
    if (suppliersBody.style.display === 'none' || suppliersBody.style.display === '') {
        suppliersBody.style.display = 'block';
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
        toggleText.textContent = 'Recolher';
    } else {
        suppliersBody.style.display = 'none';
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
        toggleText.textContent = 'Expandir';
    }
}

/**
 * Seleciona um fornecedor para a garantia
 */
function selectSupplier(event, button) {
    event.preventDefault();
    
    const supplierId = button.getAttribute('data-supplier-id');
    const supplierName = button.getAttribute('data-supplier-name');
    
    console.log('🏢 Selecionando fornecedor:', supplierName);
    
    // Atualizar o select hidden
    const supplierSelect = document.getElementById('warranty_supplier_id');
    if (supplierSelect) {
        supplierSelect.value = supplierId;
        supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // Atualizar estilos dos buttons
    document.querySelectorAll('.supplier-btn').forEach(btn => {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
    });
    
    button.classList.remove('btn-outline-success');
    button.classList.add('btn-success');
    
    // Mostrar o alerta de fornecedor selecionado
    let alert = document.querySelector('.alert-success');
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'alert alert-success d-flex justify-content-between align-items-center mb-3';
        alert.innerHTML = `
            <div>
                <i class="fas fa-check-circle me-2"></i>
                <strong>Fornecedor Selecionado:</strong> ${supplierName}
            </div>
            <small>ID: ${supplierId}</small>
        `;
        document.querySelector('.warranty-suppliers-container').parentNode.insertBefore(alert, document.querySelector('.warranty-suppliers-container'));
    } else {
        alert.innerHTML = `
            <div>
                <i class="fas fa-check-circle me-2"></i>
                <strong>Fornecedor Selecionado:</strong> ${supplierName}
            </div>
            <small>ID: ${supplierId}</small>
        `;
    }
    
    console.log('✅ Fornecedor selecionado:', supplierName, '(ID:', supplierId + ')');
}

/**
 * Limpa a seleção de fornecedor
 */
function clearSupplierSelection() {
    console.log('🗑️ Limpando seleção de fornecedor');
    
    const supplierSelect = document.getElementById('warranty_supplier_id');
    if (supplierSelect) {
        supplierSelect.value = '';
        supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // Remover estilo de seleção de todos os buttons
    document.querySelectorAll('.supplier-btn').forEach(btn => {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
    });
    
    // Remover o alerta de fornecedor selecionado
    const alert = document.querySelector('.alert-success');
    if (alert) {
        alert.remove();
    }
    
    console.log('✅ Seleção de fornecedor limpa');
}
</script>

<?php else: ?>
    <div class="alert alert-warning mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Nenhum fornecedor de garantia disponível. 
        <a href="warranties.php?tab=suppliers" class="alert-link" target="_blank">Crie um fornecedor</a> para usar aqui.
    </div>
<?php endif; ?>

