<?php
/**
 * Seletor de Templates de Garantia - Modal Reutilizável
 * 
 * Este arquivo é chamado via openActionModal() para permitir
 * seleção e aplicação de templates de garantia em formulários
 * 
 * Parâmetros GET:
 * - modal=true : Indica que está sendo chamado em contexto modal
 * 
 * Retorna dados do template selecionado via JavaScript
 */

require 'config.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

// Obter conexão com o banco de dados
$pdo = getConnection();
$templates = getActiveTemplates($pdo); // Agora a variável $pdo existe

// Verificar se é chamada modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

?>

<!-- Se não for modal, incluir header/footer -->
<?php if (!$is_modal): ?>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
<?php endif; ?>

<div id="templateSelectorContainer">
    <h5 class="mb-3">
        <i class="fas fa-file-invoice"></i> Selecione um Template de Garantia
    </h5>

    <?php if (empty($templates)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nenhum template disponível
        </div>
    <?php else: ?>
        <div class="row g-2">
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6">
                    <button class="btn btn-outline-primary w-100 p-3 text-start template-selector" 
                            data-template-id="<?php echo $template['id']; ?>"
                            data-template-name="<?php echo htmlspecialchars($template['name']); ?>"
                            data-warranty-provider="<?php echo htmlspecialchars($template['warranty_provider'] ?? ''); ?>"
                            data-warranty-period-value="<?php echo $template['warranty_period_value']; ?>"
                            data-warranty-period-unit="<?php echo $template['warranty_period_unit']; ?>"
                            data-warranty-notes="<?php echo htmlspecialchars($template['warranty_notes'] ?? ''); ?>">
                        
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                            <small class="badge bg-success">Selecionar</small>
                        </div>

                        <?php if ($template['description']): ?>
                            <small class="text-muted d-block mb-2">
                                <?php echo htmlspecialchars($template['description']); ?>
                            </small>
                        <?php endif; ?>

                        <div class="template-info small">
                            <div class="text-dark">
                                <i class="fas fa-calendar"></i> 
                                <strong><?php echo $template['warranty_period_value']; ?> 
                                <?php echo formatPeriodUnit($template['warranty_period_unit']); ?></strong>
                            </div>
                            <?php if ($template['warranty_provider']): ?>
                                <div class="text-dark">
                                    <i class="fas fa-building"></i> 
                                    <?php echo htmlspecialchars($template['warranty_provider']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3 alert alert-light">
            <i class="fas fa-lightbulb"></i>
            <strong>Dica:</strong> Clique em um template para usar seus valores no formulário. 
            Você pode editá-los depois se necessário.
        </div>
    <?php endif; ?>
</div>

<?php if (!$is_modal): ?>
    </div>
    <?php include 'includes/footer.php'; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Selecionar templates
    const templateButtons = document.querySelectorAll('.template-selector');
    
    templateButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Obter dados do template
            const templateData = {
                id: this.getAttribute('data-template-id'),
                name: this.getAttribute('data-template-name'),
                warranty_provider: this.getAttribute('data-warranty-provider'),
                warranty_period_value: this.getAttribute('data-warranty-period-value'),
                warranty_period_unit: this.getAttribute('data-warranty-period-unit'),
                warranty_notes: this.getAttribute('data-warranty-notes')
            };
            
            // Aplicar template ao formulário pai
            applyTemplateToForm(templateData);
            
            // Fechar modal se estiver aberto
            const modal = document.getElementById('actionModal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });
});

/**
 * Aplica os valores do template ao formulário de garantia
 * Procura pelo formulário de garantia no DOM
 */
function applyTemplateToForm(templateData) {
    // Tentar encontrar o formulário nos contextos possíveis:
    // 1. Em um modal (editWarrantyForm)
    // 2. Na página principal (add_productForm, etc)
    
    let form = document.getElementById('editWarrantyForm') || 
               document.getElementById('addProductForm') ||
               document.querySelector('form[data-warranty-form]');
    
    if (!form) {
        // Se não encontrar formulário específico, procurar no formulário ativo
        form = document.querySelector('form:visible, #actionModal form');
    }
    
    if (!form) {
        console.warn('Formulário de garantia não encontrado');
        alert('Não foi possível encontrar o formulário de garantia. Tente novamente.');
        return;
    }
    
    // Preencher campos conhecidos
    const fields = {
        'warranty_provider': templateData.warranty_provider,
        'warranty_period_value': templateData.warranty_period_value,
        'warranty_period_unit': templateData.warranty_period_unit,
        'warranty_notes': templateData.warranty_notes
    };
    
    let fieldsUpdated = 0;
    for (const [fieldName, fieldValue] of Object.entries(fields)) {
        const input = form.querySelector(`[name="${fieldName}"]`);
        if (input) {
            input.value = fieldValue;
            
            // Disparar evento de mudança para validação/cálculos
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
            
            fieldsUpdated++;
        }
    }
    
    if (fieldsUpdated > 0) {
        // Mostrar notificação de sucesso
        showTemplateAppliedMessage(templateData.name);
        
        // Se houver função de cálculo de data final, chamar
        if (typeof calculateEndDate === 'function') {
            calculateEndDate();
        }
    } else {
        console.warn('Nenhum campo de garantia foi encontrado no formulário');
        alert('Nenhum campo de garantia foi encontrado no formulário.');
    }
}

/**
 * Mostra mensagem de template aplicado
 */
function showTemplateAppliedMessage(templateName) {
    // Criar toast de sucesso
    const toastHtml = `
        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle"></i> 
                    Template "<strong>${templateName}</strong>" aplicado com sucesso!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Inserir e mostrar
    const container = document.body;
    const toastElement = document.createElement('div');
    toastElement.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastElement.style.zIndex = '99999';
    toastElement.innerHTML = toastHtml;
    
    container.appendChild(toastElement);
    const toast = new bootstrap.Toast(toastElement.querySelector('.toast'));
    toast.show();
    
    // Remover após desaparecer
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>

<style>
.template-selector {
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
}

.template-selector:hover {
    border-color: #0d6efd;
    background-color: #f8f9ff;
}

.template-selector.selected {
    border-color: #198754;
    background-color: #f0fdf4;
}

.template-info {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.template-info > div {
    margin: 0.25rem 0;
}
</style>
