<?php
/**
 * SELETOR DE TEMPLATES INLINE - Funcional dentro do Modal de Edição
 * NÃO abre modal aninhado, funciona direto no formulário
 */

require_once 'config.php';
require_once 'includes/warranty_functions.php';

// Obter templates ativos
$templates = getActiveTemplates($pdo);
?>

<?php if (!empty($templates)): ?>

<div class="warranty-templates-container mb-4">
    <div class="card border-info">
        <div class="card-header bg-info text-white" style="cursor: pointer;" onclick="toggleTemplates()">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-file-alt me-2"></i> Templates Disponíveis
                </h6>
                <small>
                    <i class="fas fa-chevron-down me-2" id="templates-toggle-icon"></i>
                    <span id="templates-toggle-text">Expandir</span>
                </small>
            </div>
        </div>
        <div class="card-body" id="templates-body" style="display: none;">
            <small class="text-muted d-block mb-3">
                <i class="fas fa-info-circle"></i> Clique em um template para preencher os campos automaticamente
            </small>
            <div class="row g-2">
                <?php foreach ($templates as $template): ?>
                    <div class="col-md-6 col-lg-4">
                        <button type="button" 
                                class="btn btn-outline-info w-100 template-btn"
                                data-period-value="<?php echo htmlspecialchars($template['period_value'] ?? ''); ?>"
                                data-period-unit="<?php echo htmlspecialchars($template['period_unit'] ?? ''); ?>"
                                data-provider="<?php echo htmlspecialchars($template['warranty_provider'] ?? ''); ?>"
                                data-notes="<?php echo htmlspecialchars($template['warranty_notes'] ?? ''); ?>"
                                onclick="applyWarrantyTemplate(event, this)">
                            
                            <div class="text-start">
                                <div class="fw-bold">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($template['name']); ?>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-hourglass-half"></i> 
                                    <?php echo $template['period_value'] . ' ' . 
                                          ($template['period_unit'] === 'days' ? 'dia(s)' : 
                                           ($template['period_unit'] === 'months' ? 'mês(es)' : 'ano(s)')); ?>
                                </small>
                                <?php if (!empty($template['warranty_provider'])): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($template['warranty_provider']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Toggle para expandir/recolher os templates
 */
function toggleTemplates() {
    const templateBody = document.getElementById('templates-body');
    const toggleIcon = document.getElementById('templates-toggle-icon');
    const toggleText = document.getElementById('templates-toggle-text');
    
    if (templateBody.style.display === 'none' || templateBody.style.display === '') {
        templateBody.style.display = 'block';
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
        toggleText.textContent = 'Recolher';
    } else {
        templateBody.style.display = 'none';
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
        toggleText.textContent = 'Expandir';
    }
}

/**
 * Aplica um template de garantia aos campos do formulário
 * Não abre modal - funciona 100% inline
 */
function applyWarrantyTemplate(event, button) {
    event.preventDefault();
    
    // Obter dados do template
    const periodValue = button.getAttribute('data-period-value');
    const periodUnit = button.getAttribute('data-period-unit');
    const provider = button.getAttribute('data-provider');
    const notes = button.getAttribute('data-notes');
    
    console.log('📋 Aplicando template:', button.querySelector('.fw-bold').innerText);
    
    // Preencher os campos
    const periodInput = document.querySelector('input[name="warranty_period_value"]');
    const unitSelect = document.querySelector('select[name="warranty_period_unit"]');
    const providerInput = document.querySelector('input[name="warranty_provider"]');
    const notesArea = document.querySelector('textarea[name="warranty_notes"]');
    
    if (periodInput) {
        periodInput.value = periodValue;
        periodInput.dispatchEvent(new Event('input', { bubbles: true }));
        periodInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    if (unitSelect) {
        unitSelect.value = periodUnit;
        unitSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    if (providerInput && provider) {
        providerInput.value = provider;
    }
    
    if (notesArea && notes) {
        notesArea.value = notes;
    }
    
    // Triggar cálculo de data final com delay para garantir
    setTimeout(() => {
        const startDateInput = document.querySelector('input[name="warranty_start_date"]');
        if (startDateInput && startDateInput.value) {
            startDateInput.dispatchEvent(new Event('input', { bubbles: true }));
            startDateInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }, 100);
    
    // Feedback visual
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('btn-info');
        btn.classList.add('btn-outline-info');
    });
    
    button.classList.remove('btn-outline-info');
    button.classList.add('btn-info');
    
    // Scroll suave para os campos
    const periodLabel = document.querySelector('label[for="warranty_period_value"]');
    if (periodLabel) {
        periodLabel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    console.log('✅ Template aplicado com sucesso!');
}
</script>

<?php else: ?>
    <div class="alert alert-warning mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Nenhum template de garantia disponível. 
        <a href="warranty_templates.php" class="alert-link">Crie um template</a> para usar aqui.
    </div>
<?php endif; ?>
