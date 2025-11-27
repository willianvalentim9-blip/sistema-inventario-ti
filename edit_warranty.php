<?php
require_once 'config.php';
require_once 'includes/warranty_functions.php';
requireLogin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Garantia do Produto';
$product_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($product_id <= 0) {
    $error_msg = 'ID de produto inválido.';
    
    if ($is_modal) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    
    $_SESSION['flash_message'] = $error_msg;
    $_SESSION['flash_type'] = 'danger';
    header('Location: warranties.php');
    exit;
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, name, warranty_start_date, warranty_end_date, warranty_provider, invoice_number, warranty_notes, warranty_period_value, warranty_period_unit, warranty_ticket_number, warranty_label, warranty_client_name, warranty_supplier_id FROM products WHERE id = ? AND has_warranty = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $error_msg = 'Produto com garantia não encontrado.';
        
        if ($is_modal) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        }
        
        $_SESSION['flash_message'] = $error_msg;
        $_SESSION['flash_type'] = 'danger';
        header('Location: warranties.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar garantia do produto: " . $e->getMessage());
    // Check if the error is due to the missing column 'warranty_notes'
    if (strpos($e->getMessage(), 'warranty_notes') !== false) {
        $_SESSION['flash_message'] = '<strong>Erro de Banco de Dados:</strong> A coluna <code>warranty_notes</code> não foi encontrada na tabela <code>products</code>. Por favor, execute o seguinte comando SQL para adicioná-la: <br><br><code>ALTER TABLE products ADD warranty_notes TEXT;</code>';
        $_SESSION['flash_type'] = 'warning';
    } else {
        $_SESSION['flash_message'] = 'Erro de banco de dados ao carregar a garantia.';
        $_SESSION['flash_type'] = 'danger';
    }
    if (!$is_modal) header('Location: warranties.php');
    exit;
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $old_data = $product;

    // Sanitize and retrieve POST data
    $warranty_provider = trim($_POST['warranty_provider'] ?? '');
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $warranty_start_date = !empty($_POST['warranty_start_date']) ? trim($_POST['warranty_start_date']) : null;
    $warranty_end_date = !empty($_POST['warranty_end_date']) ? trim($_POST['warranty_end_date']) : null;
    $warranty_notes = trim($_POST['warranty_notes'] ?? '');
    $warranty_period_value = !empty($_POST['warranty_period_value']) ? intval($_POST['warranty_period_value']) : null;
    $warranty_period_unit = trim($_POST['warranty_period_unit'] ?? '');
    $warranty_ticket_number = trim($_POST['warranty_ticket_number'] ?? '');
    $warranty_label = trim($_POST['warranty_label'] ?? '');
    $warranty_client_name = trim($_POST['warranty_client_name'] ?? '');
    $warranty_supplier_id = !empty($_POST['warranty_supplier_id']) ? intval($_POST['warranty_supplier_id']) : null;

    // --- VALIDAÇÃO DE CAMPOS OBRIGATÓRIOS ---
    if (empty($warranty_start_date) || empty($warranty_period_value) || empty($warranty_period_unit) || empty($warranty_end_date) || empty($warranty_client_name)) {
        $error_msg = 'Todos os campos obrigatórios devem ser preenchidos: Cliente, Data Inicial, Período, Unidade e Data Final.';
        
        if ($is_modal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit();
        }
        
        $_SESSION['flash_message'] = $error_msg;
        $_SESSION['flash_type'] = 'danger';
        // Retorna à mesma página para exibir o erro
        header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
        exit;
    }
    // --- FIM VALIDAÇÃO ---

    // Validação da Nota Fiscal (se não estiver vazia)
    if (!empty($invoice_number)) {
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE invoice_number = ? AND id != ?");
        $stmt->execute([$invoice_number, $product_id]);
        $existing_product = $stmt->fetch();
        if ($existing_product) {
            $error_msg = "A Nota Fiscal '{$invoice_number}' já está em uso no produto '{$existing_product['name']}' (ID: {$existing_product['id']}). Por favor, verifique os dados.";
            
            if ($is_modal) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_msg]);
                exit();
            }
            
            $_SESSION['flash_message'] = $error_msg;
            $_SESSION['flash_type'] = 'danger';
            header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
            exit;
        }
    }

    // Validation (Start Date > End Date)
    if ($warranty_start_date && $warranty_end_date && strtotime($warranty_start_date) > strtotime($warranty_end_date)) {
        $error_msg = 'A data de início da garantia não pode ser posterior à data de término.';
        
        if ($is_modal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit();
        }
        
        $_SESSION['flash_message'] = $error_msg;
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
        exit;
    }

    // --- VALIDAÇÃO DA CONSISTÊNCIA DO PERÍODO DE GARANTIA NO BACKEND ---
    if ($warranty_start_date && $warranty_period_value && $warranty_period_unit && $warranty_end_date) {
        try {
            $start_date_obj = new DateTime($warranty_start_date);
            $calculated_end_date_obj = clone $start_date_obj; 

            $period_value = intval($warranty_period_value);
            $interval_string = '';

            if ($warranty_period_unit === 'days') {
                $interval_string = "P{$period_value}D";
            } elseif ($warranty_period_unit === 'months') {
                $interval_string = "P{$period_value}M";
            } elseif ($warranty_period_unit === 'years') {
                $interval_string = "P{$period_value}Y";
            }

            if ($interval_string) {
                $calculated_end_date_obj->add(new DateInterval($interval_string));
            }
            
            $calculated_end_date_str = $calculated_end_date_obj->format('Y-m-d');
            
            // Compara a data final calculada com a data final enviada
            if ($warranty_end_date !== $calculated_end_date_str) {
                $_SESSION['flash_message'] = "A Data Final da Garantia (" . (new DateTime($warranty_end_date))->format('d/m/Y') . ") não corresponde ao período de garantia informado ({$warranty_period_value} {$warranty_period_unit}). A data calculada deveria ser " . $calculated_end_date_obj->format('d/m/Y') . ". Por favor, corrija as datas ou o período antes de salvar.";
                $_SESSION['flash_type'] = 'danger';
                header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Erro ao validar as datas da garantia: " . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
            exit;
        }
    }
    // --- FIM DA VALIDAÇÃO ---

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE products SET 
                warranty_provider = ?, 
                invoice_number = ?, 
                warranty_start_date = ?, 
                warranty_end_date = ?, 
                warranty_notes = ?,
                warranty_period_value = ?,
                warranty_period_unit = ?,
                warranty_ticket_number = ?,
                warranty_label = ?,
                warranty_client_name = ?,
                warranty_supplier_id = ?,
                has_warranty = 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $warranty_provider, 
            $invoice_number, 
            $warranty_start_date, 
            $warranty_end_date, 
            $warranty_notes,
            $warranty_period_value,
            $warranty_period_unit,
            $warranty_ticket_number,
            $warranty_label,
            $warranty_client_name,
            $warranty_supplier_id,
            $product_id
        ]);
        
        $new_data_stmt = $pdo->prepare("SELECT id, name, warranty_start_date, warranty_end_date, warranty_provider, invoice_number, warranty_notes, warranty_period_value, warranty_period_unit FROM products WHERE id = ?");
        $new_data_stmt->execute([$product_id]);
        $new_data = $new_data_stmt->fetch();

        logAdminActivity($_SESSION["user_id"], "UPDATE_WARRANTY", "products", $product_id, $old_data, $new_data);

        // Registra no histórico de garantias
        registerWarrantyHistory($pdo, $product_id, 'UPDATE', $old_data, $new_data, $_SESSION["user_id"]);

        $pdo->commit();
        
        $_SESSION['flash_message'] = 'Garantia do produto atualizada com sucesso!';
        $_SESSION['flash_type'] = 'success';
        
        // Se foi um modal, retorna sucesso em JSON
        if ($is_modal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Garantia atualizada com sucesso!']);
            exit();
        }
        
        // Redireciona de volta para a lista de garantias
        header('Location: warranties.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar garantia: " . $e->getMessage());
        $error_msg = 'Erro de banco de dados ao atualizar a garantia: ' . $e->getMessage();
        
        if ($is_modal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit();
        }
        
        $_SESSION['flash_message'] = $error_msg;
        $_SESSION['flash_type'] = 'danger';
        header('Location: edit_warranty.php?id=' . $product_id . ($is_modal ? '&modal=true' : ''));
        exit();
    }
}

// Apenas inclui o header se NÃO for um modal
if (!$is_modal) { include 'includes/header.php'; }

// Se houver uma mensagem de erro/sucesso da submissão anterior, exibe aqui
$form_message = '';
if (isset($_SESSION['flash_message'])) {
    $alert_type = $_SESSION['flash_type'] === 'success' ? 'success' : 'danger';
    $form_message = '<div class="alert alert-'. $alert_type . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['flash_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>

<?php if (!$is_modal): ?>
<div class="container-fluid">
    <div class="card">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-primary-custom"><i class="fas fa-shield-alt me-2"></i> Editar Garantia</h1>
                <a href="warranties.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para Garantias
                </a>
            </div>
<?php endif; ?>

            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <h5 class="card-title mb-0">Produto: <?php echo htmlspecialchars($product['name']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="edit_warranty.php?id=<?php echo $product['id']; ?><?php echo $is_modal ? '&modal=true' : ''; ?>" id="editWarrantyForm">
                        
                        <div id="edit-warranty-error-message" class="mb-3">
                            <?php echo $form_message; ?>
                        </div>

                        <!-- Alerta de item duplicado -->
                        <div id="duplicate-warranty-alert" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>⚠️ Atenção!</strong> Este produto já possui uma garantia registrada com os mesmos dados.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <div id="product-modified-alert" class="alert alert-info alert-dismissible fade show d-none" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ℹ️ Aviso:</strong> Os dados da garantia foram modificados. Clique em "Salvar Alterações" para confirmar.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <!-- Dados do Cliente e Identificação -->
                        <div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;">
                            <i class="fas fa-user me-2"></i> <strong>Informações do Cliente</strong>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="warranty_client_name" class="form-label form-label-custom"><i class="fas fa-user me-1"></i> Nome do Cliente *</label>
                                <input type="text" class="form-control form-control-custom" id="warranty_client_name" name="warranty_client_name" placeholder="Nome completo do cliente" value="<?php echo htmlspecialchars($product['warranty_client_name'] ?? ''); ?>" required>
                                <small class="text-muted">Cliente proprietário do produto com garantia</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="warranty_ticket_number" class="form-label form-label-custom"><i class="fas fa-ticket-alt me-1"></i> Número do Ticket</label>
                                <input type="text" class="form-control form-control-custom" id="warranty_ticket_number" name="warranty_ticket_number" placeholder="Ex: TKT-2025-001234" value="<?php echo htmlspecialchars($product['warranty_ticket_number'] ?? ''); ?>">
                                <small class="text-muted">Número do protocolo ou chamado da garantia</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="warranty_label" class="form-label form-label-custom"><i class="fas fa-tag me-1"></i> Etiqueta da Garantia</label>
                                <input type="text" class="form-control form-control-custom" id="warranty_label" name="warranty_label" placeholder="Ex: GRT-SAMSUNG-2025-001" value="<?php echo htmlspecialchars($product['warranty_label'] ?? ''); ?>">
                                <small class="text-muted">Etiqueta ou código de identificação da garantia</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Fornecedor de Garantia -->
                        <div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;">
                            <i class="fas fa-building me-2"></i> <strong>Fornecedor/Prestador de Garantia</strong>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="warranty_supplier_id" class="form-label form-label-custom"><i class="fas fa-store me-1"></i> Fornecedor Cadastrado</label>
                                <select class="form-select form-control-custom" id="warranty_supplier_id" name="warranty_supplier_id">
                                    <option value="">-- Selecione um fornecedor --</option>
                                </select>
                                <small class="text-muted">Selecione um fornecedor da base de dados</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="warranty_provider" class="form-label form-label-custom"><i class="fas fa-store me-1"></i> Fornecedor/Prestador</label>
                                <input type="text" class="form-control form-control-custom" id="warranty_provider" name="warranty_provider" placeholder="Ex: Samsung, LG, Autorizada..." value="<?php echo htmlspecialchars($product['warranty_provider'] ?? ''); ?>">
                                <small class="text-muted">Nome do fornecedor ou prestador de serviço</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="invoice_number" class="form-label form-label-custom"><i class="fas fa-file-invoice me-1"></i> Nota Fiscal / NF-e</label>
                                <input type="text" class="form-control form-control-custom" id="invoice_number" name="invoice_number" placeholder="Ex: NF 123456789" value="<?php echo htmlspecialchars($product['invoice_number'] ?? ''); ?>">
                                <small class="text-muted">Número da nota fiscal da compra</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Período de Garantia -->
                        <div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;">
                            <i class="fas fa-hourglass-end me-2"></i> <strong>Período de Cobertura</strong>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="warranty_start_date" class="form-label form-label-custom"><i class="fas fa-calendar-alt me-1"></i> Data de Início *</label>
                                <input type="date" class="form-control form-control-custom" id="warranty_start_date" name="warranty_start_date" value="<?php echo htmlspecialchars($product['warranty_start_date'] ?? ''); ?>" required>
                                <small class="text-muted">Quando a garantia inicia</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="warranty_period_value" class="form-label form-label-custom"><i class="fas fa-hourglass-half me-1"></i> Duração *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-custom" id="warranty_period_value" name="warranty_period_value" min="1" placeholder="Ex: 12" value="<?php echo htmlspecialchars($product['warranty_period_value'] ?? ''); ?>" required>
                                    <select class="form-select form-control-custom" id="warranty_period_unit" name="warranty_period_unit">
                                        <option value="days" <?php echo ($product['warranty_period_unit'] ?? '') === 'days' ? 'selected' : ''; ?>>Dias</option>
                                        <option value="months" <?php echo ($product['warranty_period_unit'] ?? 'months') === 'months' ? 'selected' : ''; ?>>Meses</option>
                                        <option value="years" <?php echo ($product['warranty_period_unit'] ?? '') === 'years' ? 'selected' : ''; ?>>Anos</option>
                                    </select>
                                </div>
                                <small class="text-muted">Duração total da cobertura</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="warranty_end_date" class="form-label form-label-custom"><i class="fas fa-calendar-check me-1"></i> Data de Término *</label>
                                <input type="date" class="form-control form-control-custom" id="warranty_end_date" name="warranty_end_date" value="<?php echo htmlspecialchars($product['warranty_end_date'] ?? ''); ?>" required>
                                <div id="warranty-date-warning" class="alert alert-danger small p-2 mt-2 d-none"></div>
                                <small class="text-muted">Calculado automaticamente</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Observações -->
                        <div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;">
                            <i class="fas fa-clipboard me-2"></i> <strong>Detalhes Adicionais</strong>
                        </div>

                        <div class="mb-3">
                            <label for="warranty_notes" class="form-label form-label-custom"><i class="fas fa-sticky-note me-1"></i> Anotações sobre a Garantia</label>
                            <textarea class="form-control form-control-custom" id="warranty_notes" name="warranty_notes" rows="4" placeholder="Descreva detalhes importantes, como:&#10;• Condições e limitações da cobertura&#10;• Exclusões específicas&#10;• Procedimento para acionamento&#10;• Telefone/email para contato&#10;• Observações gerais"><?php echo htmlspecialchars($product['warranty_notes'] ?? ''); ?></textarea>
                            <small class="text-muted">Informações complementares sobre a garantia</small>
                        </div>

                        <!-- ===== TEMPLATES INLINE - SEM MODAL ANINHADO ===== -->
                        <hr class="my-4">
                        <?php include 'warranty_template_selector_inline.php'; ?>
                        <hr class="my-4">

                        <div class="d-flex justify-content-between border-top pt-3 mt-3">
                            <div>
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" id="submitWarrantyBtn" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

<?php if (!$is_modal): ?>
        </div>
    </div>
    </div>
</div>
<?php endif; ?>

<?php 
// Apenas inclui o footer se NÃO for um modal
if (!$is_modal) { include 'includes/footer.php'; }
?>

<script>
// ========================================
// DADOS ORIGINAIS PARA COMPARAÇÃO
// ========================================
const originalData = {
    warranty_provider: '<?php echo htmlspecialchars($product['warranty_provider'] ?? ''); ?>',
    warranty_start_date: '<?php echo htmlspecialchars($product['warranty_start_date'] ?? ''); ?>',
    warranty_period_value: '<?php echo htmlspecialchars($product['warranty_period_value'] ?? ''); ?>',
    warranty_period_unit: '<?php echo htmlspecialchars($product['warranty_period_unit'] ?? ''); ?>',
    warranty_end_date: '<?php echo htmlspecialchars($product['warranty_end_date'] ?? ''); ?>',
    warranty_notes: `<?php echo htmlspecialchars($product['warranty_notes'] ?? ''); ?>`,
    warranty_ticket_number: '<?php echo htmlspecialchars($product['warranty_ticket_number'] ?? ''); ?>',
    warranty_label: '<?php echo htmlspecialchars($product['warranty_label'] ?? ''); ?>',
    warranty_client_name: '<?php echo htmlspecialchars($product['warranty_client_name'] ?? ''); ?>',
    warranty_supplier_id: '<?php echo htmlspecialchars($product['warranty_supplier_id'] ?? ''); ?>'
};

// ========================================
// CARREGA FORNECEDORES DO SERVIDOR
// ========================================
function loadSuppliers() {
    console.log('📦 Carregando fornecedores...');
    const supplierSelect = document.getElementById('warranty_supplier_id');
    
    if (!supplierSelect) {
        console.warn('⚠️ Select de fornecedores não encontrado');
        return;
    }

    fetch('get_warranty_suppliers.php')
        .then(response => {
            if (!response.ok) throw new Error('Erro ao carregar fornecedores');
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                console.log('✅ Fornecedores carregados:', data.data.length);
                
                // Limpa opções anteriores
                supplierSelect.innerHTML = '<option value="">-- Selecione um fornecedor --</option>';
                
                // Adiciona cada fornecedor como opção
                data.data.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.id;
                    option.textContent = supplier.name;
                    
                    // Seleciona se for o fornecedor atual
                    if (supplier.id == originalData.warranty_supplier_id) {
                        option.selected = true;
                    }
                    
                    supplierSelect.appendChild(option);
                });
                
                // Se houver um fornecedor selecionado, atualiza o campo de texto
                if (originalData.warranty_supplier_id) {
                    supplierSelect.value = originalData.warranty_supplier_id;
                }
            } else {
                console.warn('⚠️ Nenhum fornecedor encontrado ou resposta inválida');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar fornecedores:', error);
            supplierSelect.innerHTML = '<option value="">Erro ao carregar fornecedores</option>';
        });
}

// ========================================
// INICIALIZA OS LISTENERS DA GARANTIA
// ========================================
function initializeWarrantyListeners() {
    console.log('🔄 Inicializando listeners de garantia...');
    
    // Pega os elementos do DOM
    const startDateInput = document.getElementById('warranty_start_date');
    const periodValueInput = document.getElementById('warranty_period_value');
    const periodUnitInput = document.getElementById('warranty_period_unit');
    const endDateInput = document.getElementById('warranty_end_date');
    const warningDiv = document.getElementById('warranty-date-warning');
    const providerInput = document.getElementById('warranty_provider');
    const notesInput = document.getElementById('warranty_notes');
    const submitBtn = document.getElementById('submitWarrantyBtn');
    const duplicateAlert = document.getElementById('duplicate-warranty-alert');
    const modifiedAlert = document.getElementById('product-modified-alert');

    if (!startDateInput || !periodValueInput || !periodUnitInput || !endDateInput) {
        console.warn('⚠️ Elementos de garantia não encontrados');
        return;
    }

    // ========================================
    // FUNÇÃO: Verifica se há duplicação
    // ========================================
    function checkForDuplicates() {
        console.log('🔍 Verificando duplicatas...');
        
        const currentData = {
            warranty_provider: providerInput ? providerInput.value : '',
            warranty_start_date: startDateInput.value,
            warranty_period_value: periodValueInput.value,
            warranty_period_unit: periodUnitInput.value,
            warranty_end_date: endDateInput.value,
            warranty_notes: notesInput ? notesInput.value : ''
        };

        // Compara com dados originais
        const isDuplicate = Object.keys(currentData).every(key => {
            return currentData[key] === originalData[key];
        });

        if (isDuplicate && currentData.warranty_start_date && currentData.warranty_period_value) {
            console.warn('⚠️ Dados duplicados detectados!');
            if (duplicateAlert) {
                duplicateAlert.classList.remove('d-none');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
                submitBtn.title = 'Nenhuma alteração foi feita. Modifique os dados antes de salvar.';
            }
            return true;
        } else {
            console.log('✅ Dados modificados detectados');
            if (duplicateAlert) {
                duplicateAlert.classList.add('d-none');
            }
            if (modifiedAlert && (currentData.warranty_start_date && currentData.warranty_period_value)) {
                modifiedAlert.classList.remove('d-none');
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                submitBtn.title = 'Salvar alterações da garantia';
            }
            return false;
        }
    }

    // ========================================
    // FUNÇÃO: Calcula a data final automaticamente
    // ========================================
    function calculateEndDate() {
        console.log('📅 Calculando data final...');
        
        const startDate = startDateInput.value;
        const periodValue = parseInt(periodValueInput.value) || 0;
        const periodUnit = periodUnitInput.value;

        // Verifica se tem dados para calcular
        if (!startDate || periodValue <= 0 || !periodUnit) {
            console.log('❌ Dados insuficientes para calcular');
            return;
        }

        try {
            // Cria a data inicial
            const date = new Date(startDate + 'T00:00:00');
            
            // Soma o período
            if (periodUnit === 'days') {
                date.setDate(date.getDate() + periodValue);
            } else if (periodUnit === 'months') {
                date.setMonth(date.getMonth() + periodValue);
            } else if (periodUnit === 'years') {
                date.setFullYear(date.getFullYear() + periodValue);
            }

            // Formata a data como YYYY-MM-DD
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const calculatedDate = `${year}-${month}-${day}`;
            
            // Define o valor no campo de data final
            endDateInput.value = calculatedDate;
            console.log('✅ Data final calculada:', calculatedDate);
            
            // Valida as datas após cálculo
            validateWarrantyDates();
            
            // Verifica duplicatas após cálculo
            checkForDuplicates();
        } catch (error) {
            console.error('❌ Erro ao calcular data final:', error);
        }
    }

    // ========================================
    // FUNÇÃO: Valida as datas
    // ========================================
    function validateWarrantyDates() {
        console.log('🔍 Validando datas...');
        
        const startDate = startDateInput.value;
        const periodValue = parseInt(periodValueInput.value) || 0;
        const periodUnit = periodUnitInput.value;
        const endDate = endDateInput.value;

        // Se não tem informações, esconde aviso
        if (!startDate || !endDate || periodValue <= 0) {
            if (warningDiv) warningDiv.classList.add('d-none');
            return;
        }

        try {
            // Recalcula a data final esperada
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
            const expectedEndDate = `${year}-${month}-${day}`;

            // Compara com o valor inserido
            if (endDate !== expectedEndDate) {
                if (warningDiv) {
                    const localDateStr = new Date(expectedEndDate + 'T00:00:00').toLocaleDateString('pt-BR');
                    warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> A data final não corresponde ao período. A data calculada seria <strong>${localDateStr}</strong>.`;
                    warningDiv.classList.remove('d-none');
                }
                console.warn('⚠️ Data final inconsistente');
            } else {
                if (warningDiv) warningDiv.classList.add('d-none');
                console.log('✅ Datas consistentes');
            }
        } catch (error) {
            console.error('❌ Erro ao validar datas:', error);
        }
    }

    // ========================================
    // ADICIONA OS EVENT LISTENERS
    // ========================================
    console.log('📌 Adicionando event listeners...');

    startDateInput.addEventListener('change', calculateEndDate);
    periodValueInput.addEventListener('input', calculateEndDate);
    periodValueInput.addEventListener('change', calculateEndDate);
    periodUnitInput.addEventListener('change', calculateEndDate);
    endDateInput.addEventListener('change', validateWarrantyDates);

    // Listeners para verificar duplicatas
    if (providerInput) providerInput.addEventListener('input', checkForDuplicates);
    if (notesInput) notesInput.addEventListener('input', checkForDuplicates);
    startDateInput.addEventListener('change', checkForDuplicates);
    periodValueInput.addEventListener('change', checkForDuplicates);
    periodUnitInput.addEventListener('change', checkForDuplicates);
    endDateInput.addEventListener('change', checkForDuplicates);

    // Listener para mudanças nos novos campos
    const ticketInput = document.getElementById('warranty_ticket_number');
    const labelInput = document.getElementById('warranty_label');
    const clientInput = document.getElementById('warranty_client_name');
    const supplierSelect = document.getElementById('warranty_supplier_id');
    
    if (ticketInput) ticketInput.addEventListener('input', checkForDuplicates);
    if (labelInput) labelInput.addEventListener('input', checkForDuplicates);
    if (clientInput) clientInput.addEventListener('input', checkForDuplicates);
    if (supplierSelect) supplierSelect.addEventListener('change', checkForDuplicates);

    // ========================================
    // INICIALIZA SE JÁ HOUVER DADOS
    // ========================================
    if (startDateInput.value && periodValueInput.value && periodUnitInput.value) {
        console.log('📝 Inicializando com valores existentes...');
        calculateEndDate();
        checkForDuplicates(); // Verifica duplicatas no carregamento
    }

    console.log('✔️ Listeners de garantia inicializados com sucesso!');

    // ========================================
    // INTERCEPTA O SUBMIT DO FORMULÁRIO SE FOR MODAL
    // ========================================
    const form = document.getElementById('editWarrantyForm');
    if (form && document.getElementById('actionModal')) {
        console.log('🔲 Interceptando submit do formulário em modal...');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const actionUrl = this.action;
            
            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(responseText => {
                try {
                    // Tenta fazer parse como JSON
                    const json = JSON.parse(responseText);
                    
                    if (json.success) {
                        // ✅ Sucesso! Mostra mensagem e fecha modal
                        console.log('✅ Garantia salva com sucesso!');
                        
                        // Mostra alerta de sucesso
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Sucesso!</strong> ${json.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.getElementById('actionModalBody').prepend(alert);
                        
                        // Fecha o modal após 1.5 segundos
                        setTimeout(() => {
                            const modalElement = document.getElementById('actionModal');
                            if (modalElement) {
                                const bootstrapModal = bootstrap.Modal.getInstance(modalElement);
                                if (bootstrapModal) {
                                    bootstrapModal.hide();
                                }
                            }
                            // Recarrega a página
                            location.reload();
                        }, 1500);
                    } else {
                        // ❌ Erro retornado
                        console.log('❌ Erro ao salvar:', json.message);
                        const modalBody = document.getElementById('actionModalBody');
                        
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = `
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Erro!</strong> ${json.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        modalBody.prepend(alert);
                    }
                } catch (e) {
                    // Não é JSON, talvez seja HTML (erro do servidor)
                    console.error('❌ Resposta não é JSON:', e);
                    const modalBody = document.getElementById('actionModalBody');
                    modalBody.innerHTML = responseText;
                    
                    // Re-executa os scripts do novo conteúdo
                    Array.from(modalBody.querySelectorAll("script")).forEach(oldScript => {
                        const newScript = document.createElement("script");
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                    initializeWarrantyListeners(); // Re-inicializa listeners
                }
            })
            .catch(error => {
                console.error('❌ Erro ao processar formulário:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro!</strong> Erro ao processar o formulário. Tente novamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.getElementById('actionModalBody').prepend(alert);
            });
            
            return false;
        });
    }
}

// ========================================
// EXECUTAR QUANDO PÁGINA CARREGAR
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 Página carregada, inicializando...');
    loadSuppliers();
    initializeWarrantyListeners();
});

// Se o formulário já existe no DOM (carregamento dinâmico/modal)
if (document.getElementById('editWarrantyForm')) {
    console.log('🔲 Modal detectado, inicializando imediatamente...');
    loadSuppliers();
    initializeWarrantyListeners();
}
</script>