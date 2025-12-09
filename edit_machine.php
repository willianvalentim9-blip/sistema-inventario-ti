<?php
// ========================================
// PÁGINA DE EDIÇÃO DE MÁQUINA OTIMIZADA (VERSÃO COMPLETA)
// Baseada em add_machine.php com funcionalidades completas
// ========================================
require_once 'config.php';
require_once 'includes/machine_components_functions.php';
requireLogin();

$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';
$page_title = 'Editar Máquina';
$machine_id = intval($_GET["id"] ?? 0);
$GLOBALS['is_inside_machine_form'] = true;

if ($machine_id <= 0) {
    if (!$is_modal) header('Location: ready_machines.php');
    exit('ID de máquina inválido.');
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();

    // IMPORTANTE: Guarda o estado anterior de has_warranty para decidir redirecionamento
    $had_warranty_before = isset($machine['has_warranty']) && $machine['has_warranty'] == 1;

    if (!$machine) {
        if (!$is_modal) header('Location: ready_machines.php');
        exit('Máquina não encontrada.');
    }

    // Busca componentes existentes da máquina
    $existing_components = getMachineComponents($pdo, $machine_id);
    
    // Se há componentes vinculados, preenche os campos da máquina
    if (!empty($existing_components)) {
        foreach ($existing_components as $comp) {
            $component_type = $comp['component_type'];
            $product_name = $comp['name'];
            
            // Mapeia component_type para field name
            switch ($component_type) {
                case 'CPU':
                    $machine['processor'] = $product_name;
                    break;
                case 'RAM':
                    $machine['memory'] = $product_name;
                    break;
                case 'HDD':
                    // HDD pode ter múltiplos - adiciona em nova linha
                    if (!empty($machine['storage'])) {
                        $machine['storage'] .= "\n" . $product_name;
                    } else {
                        $machine['storage'] = $product_name;
                    }
                    break;
                case 'GPU':
                    $machine['graphics'] = $product_name;
                    break;
                case 'Motherboard':
                    $machine['motherboard'] = $product_name;
                    break;
                case 'PSU':
                    $machine['power_supply'] = $product_name;
                    break;
                case 'Case':
                    $machine['case_type'] = $product_name;
                    break;
            }
        }
    }

} catch (PDOException $e) {
    if (!$is_modal) header('Location: ready_machines.php');
    exit('Erro de banco de dados.');
}

// Variáveis para controle de mensagens
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $specifications = trim($_POST["specifications"] ?? "");
    $processor = trim($_POST["processor"] ?? "");
    $memory = trim($_POST["memory"] ?? "");
    $storage = trim($_POST["storage"] ?? "");
    $graphics = trim($_POST["graphics"] ?? "");
    $motherboard = trim($_POST["motherboard"] ?? "");
    $power_supply = trim($_POST["power_supply"] ?? "");
    $case_type = trim($_POST["case_type"] ?? "");
    $serial_number = trim($_POST["serial_number"] ?? "") ?: null;
    $barcode = trim($_POST["barcode"] ?? "") ?: null;
    $qr_code = trim($_POST["qr_code"] ?? "") ?: null;
    $sale_price = floatval(str_replace(",", ".", $_POST["sale_price"] ?? 0));
    $cost_price = floatval(str_replace(",", ".", $_POST["cost_price"] ?? 0));
    $status = trim($_POST["status"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $notes = trim($_POST["notes"] ?? "");
    $windows_10_compatible = isset($_POST["windows_10_compatible"]) ? 1 : 0;
    $windows_11_compatible = isset($_POST["windows_11_compatible"]) ? 1 : 0;
    $has_warranty = isset($_POST["has_warranty"]) ? 1 : 0;
    $warranty_provider = trim($_POST["machine_warranty_provider"] ?? '');
    $warranty_period_value = !empty($_POST["machine_warranty_period_value"]) ? intval($_POST["machine_warranty_period_value"]) : null;
    $warranty_period_unit = trim($_POST["machine_warranty_period_unit"] ?? '');
    $image_to_save = trim($_POST["uploaded_image"] ?? $machine["image"]);
    $machine_components_json = trim($_POST['machine_components'] ?? '{}');
    
    // DEBUG: Log do JSON de componentes recebido
    error_log("DEBUG: machine_components recebido = " . var_export($machine_components_json, true));
    error_log("DEBUG: POST completo = " . var_export($_POST, true));
    
    /**
     * LÓGICA: Se usuário apagou um campo que tinha componente vinculado,
     * removemos o componente do JSON
     */
    $componentsData = json_decode($machine_components_json, true) ?? [];
    
    // Mapa de campo -> tipo de componente
    $fieldComponentMap = [
        'processor' => 'CPU',
        'memory' => 'RAM',
        'graphics' => 'GPU',
        'motherboard' => 'Motherboard',
        'power_supply' => 'PSU',
        'case_type' => 'Case'
    ];
    
    error_log("DEBUG: Verificando mudanças nos campos de componentes");
    error_log("DEBUG: Componentes antes: " . var_export($componentsData, true));
    
    // Verifica cada campo
    foreach ($fieldComponentMap as $fieldName => $componentType) {
        $newValue = $$fieldName; // Pega o valor atual do POST (ex: $processor, $memory)
        $oldValue = $machine[$fieldName] ?? ''; // Valor antigo da máquina
        
        error_log("DEBUG: Verificando {$fieldName}: antigo='{$oldValue}' novo='{$newValue}'");
        
        // Se campo foi limpo (estava preenchido, agora está vazio)
        if (!empty($oldValue) && empty($newValue)) {
            error_log("❌ Campo {$fieldName} foi limpo - removendo componente {$componentType}");
            unset($componentsData[$componentType]);
        }
        // Se campo foi preenchido (mesmo que manualmente)
        elseif (!empty($newValue) && !isset($componentsData[$componentType])) {
            error_log("✅ Campo {$fieldName} foi preenchido - mantendo para edição manual");
        }
    }
    
    // Para HDD (pode ter múltiplos), lógica especial
    $oldStorage = $machine['storage'] ?? '';
    $newStorage = $storage;
    if (!empty($oldStorage) && empty($newStorage)) {
        error_log("❌ Campo storage foi limpo - removendo componentes HDD");
        unset($componentsData['HDD']);
    }
    
    // Reconstrói JSON com os componentes atualizados
    $machine_components_json = json_encode($componentsData);
    error_log("DEBUG: Componentes após ajuste: " . $machine_components_json);

    if (empty($name)) {
        $error_message = 'O nome da máquina é obrigatório.';
    } else {
        try {
            $pdo->beginTransaction();

            // Atualiza dados básicos da máquina
            $stmt = $pdo->prepare("
                UPDATE ready_machines SET
                    name = ?,
                    description = ?,
                    image = ?,
                    specifications = ?,
                    processor = ?,
                    memory = ?,
                    storage = ?,
                    graphics = ?,
                    motherboard = ?,
                    power_supply = ?,
                    case_type = ?,
                    serial_number = ?,
                    barcode = ?,
                    qr_code = ?,
                    sale_price = ?,
                    cost_price = ?,
                    status = ?,
                    location = ?,
                    notes = ?,
                    windows_10_compatible = ?,
                    windows_11_compatible = ?,
                    has_warranty = ?,
                    warranty_provider = ?,
                    warranty_period_value = ?,
                    warranty_period_unit = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute([
                $name, $description, $image_to_save, $specifications,
                $processor, $memory, $storage, $graphics,
                $motherboard, $power_supply, $case_type,
                $serial_number, $barcode, $qr_code,
                $sale_price ?: null, $cost_price ?: null,
                $status, $location, $notes,
                $windows_10_compatible, $windows_11_compatible,
                $has_warranty,
                $has_warranty ? $warranty_provider : null,
                $has_warranty ? $warranty_period_value : null,
                $has_warranty ? $warranty_period_unit : null,
                $machine_id
            ]);

            // Log detalhado com valores antigos e novos
            $old_values = [
                'name' => $machine['name'],
                'description' => $machine['description'],
                'processor' => $machine['processor'],
                'memory' => $machine['memory'],
                'storage' => $machine['storage'],
                'graphics' => $machine['graphics'],
                'motherboard' => $machine['motherboard'],
                'power_supply' => $machine['power_supply'],
                'case_type' => $machine['case_type'],
                'serial_number' => $machine['serial_number'],
                'barcode' => $machine['barcode'],
                'qr_code' => $machine['qr_code'],
                'sale_price' => $machine['sale_price'],
                'cost_price' => $machine['cost_price'],
                'status' => $machine['status'],
                'location' => $machine['location'],
                'notes' => $machine['notes'],
                'windows_10_compatible' => $machine['windows_10_compatible'],
                'windows_11_compatible' => $machine['windows_11_compatible'],
                'has_warranty' => $machine['has_warranty'],
                'warranty_provider' => $machine['warranty_provider'],
                'warranty_period_value' => $machine['warranty_period_value'],
                'warranty_period_unit' => $machine['warranty_period_unit']
            ];

            $new_values = [
                'name' => $name,
                'description' => $description,
                'processor' => $processor,
                'memory' => $memory,
                'storage' => $storage,
                'graphics' => $graphics,
                'motherboard' => $motherboard,
                'power_supply' => $power_supply,
                'case_type' => $case_type,
                'serial_number' => $serial_number,
                'barcode' => $barcode,
                'qr_code' => $qr_code,
                'sale_price' => $sale_price ?: null,
                'cost_price' => $cost_price ?: null,
                'status' => $status,
                'location' => $location,
                'notes' => $notes,
                'windows_10_compatible' => $windows_10_compatible,
                'windows_11_compatible' => $windows_11_compatible,
                'has_warranty' => $has_warranty,
                'warranty_provider' => $has_warranty ? $warranty_provider : null,
                'warranty_period_value' => $has_warranty ? $warranty_period_value : null,
                'warranty_period_unit' => $has_warranty ? $warranty_period_unit : null
            ];

            logAdminActivity($_SESSION["user_id"], 'UPDATE_MACHINE', 'ready_machines', $machine_id, $old_values, $new_values);

            // Gerencia componentes se houver mudanças
            if (!empty($machine_components_json) && $machine_components_json !== '{}') {
                try {
                    // Primeiro, devolve estoque dos componentes anteriores
                    removeMachineComponents($pdo, $machine_id, true, 'Atualização de componentes');

                    // Salva novos componentes
                    saveMachineComponents($pdo, $machine_id, $machine_components_json);

                    // Deduz estoque dos novos componentes
                    deductProductsStock($pdo, $machine_id, 1);

                    logAdminActivity($_SESSION["user_id"], 'UPDATE_MACHINE_COMPONENTS', 'machine_products', $machine_id,
                        'Machine ' . $machine_id . ' components updated');

                } catch (Exception $e) {
                    throw new Exception("Erro ao atualizar componentes: " . $e->getMessage());
                }
            }

            $pdo->commit();

            $_SESSION['flash_message'] = 'Máquina atualizada com sucesso!';
            $_SESSION['flash_type'] = 'success';

            // LÓGICA INTELIGENTE DE REDIRECIONAMENTO:
            if (!$had_warranty_before && $has_warranty == 1) {
                // Novo cadastro de garantia - vai para a página de garantias
                header('Location: warranties.php?tab=machines');
                exit();
            }

            // Atualização normal - volta para máquinas
            header('Location: ready_machines.php');
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Erro de banco de dados: ' . $e->getMessage();
            error_log("Erro ao atualizar máquina: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = $e->getMessage();
            error_log("Erro ao atualizar máquina: " . $e->getMessage());
        }
    }
}

if (!$is_modal) {
    include 'includes/header.php';
}

// Se houver uma mensagem de erro/sucesso
$form_message = '';
if (!empty($error_message)) {
    $form_message = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($error_message) . '</div>';
} elseif (isset($_SESSION['flash_message'])) {
    $alert_type = $_SESSION['flash_type'] === 'success' ? 'success' : 'danger';
    $form_message = '<div class="alert alert-'. $alert_type . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

$image_path = 'uploads/machines/' . htmlspecialchars($machine['image'] ?? '');
$image_exists = !empty($machine['image']) && file_exists($image_path);

// Prepara JSON de componentes existentes para o JavaScript
$existing_components_json = '{}';
if (!empty($existing_components)) {
    $components_map = [];
    foreach ($existing_components as $comp) {
        $component_type = $comp['component_type'];

        // Para armazenamento (HDD), permite múltiplos
        if ($component_type === 'HDD') {
            if (!isset($components_map[$component_type])) {
                $components_map[$component_type] = [];
            }
            $components_map[$component_type][] = [
                'id' => $comp['product_id'],
                'name' => $comp['name']
            ];
        } else {
            $components_map[$component_type] = [
                'id' => $comp['product_id'],
                'name' => $comp['name']
            ];
        }
    }
    $existing_components_json = json_encode($components_map);
}

?>

<!-- Link CSS para Media Upload -->
<link rel="stylesheet" href="CSS/media-upload.css">
<!-- Link CSS para Modal de Componentes -->
<link rel="stylesheet" href="CSS/machine-components-modal.css">

<style>
/* SISTEMA SIMPLIFICADO DE COMPONENTES - SEM MODAL CASCATA */

/* Estilo dos chips de produtos vinculados */
.component-chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
    min-height: 20px;
}

.component-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    transition: all 0.2s ease;
}

.component-chip:hover {
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.5);
    transform: translateY(-1px);
}

.component-chip .btn-remove-chip {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    font-size: 0.7rem;
    transition: background 0.2s;
}

.component-chip .btn-remove-chip:hover {
    background: rgba(255,255,255,0.4);
}

/* Indicador visual nos campos */
.campo-com-chip {
    border-left: 3px solid #28a745 !important;
}
</style>

<?php if (!$is_modal): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom"><i class="fas fa-desktop me-2"></i>Editar Máquina</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="ready_machines.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php echo $form_message; ?>

<div class="<?php echo $is_modal ? '' : 'card card-custom'; ?>">
    <?php if (!$is_modal): ?>
    <div class="card-header card-header-custom"><i class="fas fa-desktop me-2"></i>Informações da Máquina</div>
    <?php endif; ?>

    <div class="<?php echo $is_modal ? '' : 'card-body'; ?>">
        <form method="POST" action="edit_machine.php?id=<?php echo $machine['id']; ?><?php echo $is_modal ? '&modal=true' : ''; ?>" enctype="multipart/form-data" id="editMachineForm">

            <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
            <div class="row">
                <div class="col-md-<?php echo $is_modal ? '8' : '6'; ?>">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h5>

                    <div class="mb-3">
                        <label for="name" class="form-label form-label-custom">Nome da Máquina *</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name"
                               value="<?php echo htmlspecialchars($machine['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label form-label-custom">Descrição</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="3"><?php echo htmlspecialchars($machine["description"]); ?></textarea>
                    </div>

                    <?php if (!$is_modal): ?>
                    <div class="mb-3">
                        <label for="specifications" class="form-label form-label-custom">Especificações Gerais</label>
                        <textarea class="form-control form-control-custom" id="specifications" name="specifications" rows="4"><?php echo htmlspecialchars($machine["specifications"]); ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-<?php echo $is_modal ? '4' : '6'; ?>">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-qrcode me-2"></i>Códigos e Status</h5>

                    <?php if (!$is_modal): ?>
                    <div class="mb-3">
                        <label for="serial_number" class="form-label form-label-custom">Número de Série</label>
                        <input type="text" class="form-control form-control-custom" id="serial_number" name="serial_number"
                               value="<?php echo htmlspecialchars($machine['serial_number']); ?>">
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="barcode" class="form-label form-label-custom">Código de Barras</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="barcode" name="barcode"
                                   value="<?php echo htmlspecialchars($machine['barcode']); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()" title="Gerar código automaticamente">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="barcode">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="qr_code" class="form-label form-label-custom">Código QR</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="qr_code" name="qr_code"
                                   value="<?php echo htmlspecialchars($machine['qr_code']); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="generateQRCode()" title="Gerar código QR automaticamente">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" data-open-scanner data-target-input="qr_code">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <?php if (!$is_modal): ?>
                    <div class="mb-3">
                        <label for="location" class="form-label form-label-custom">Localização</label>
                        <input type="text" class="form-control form-control-custom" id="location" name="location"
                               value="<?php echo htmlspecialchars($machine['location']); ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$is_modal): ?>
            <hr class="my-4">

            <!-- SEÇÃO 2: PREÇOS E STATUS -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-dollar-sign me-2"></i>Preços e Controle</h5>
                    <div class="mb-3">
                        <label for="sale_price" class="form-label form-label-custom">Preço de Venda (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="sale_price" name="sale_price"
                               min="0" step="0.01" value="<?php echo number_format($machine['sale_price'], 2, '.', ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cost_price" class="form-label form-label-custom">Preço de Custo (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="cost_price" name="cost_price"
                               min="0" step="0.01" value="<?php echo number_format($machine['cost_price'] ?? 0, 2, '.', ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label form-label-custom">Status</label>
                        <select class="form-select form-control-custom" id="status" name="status">
                            <option value="available" <?php if($machine['status'] === 'available') echo 'selected'; ?>>Disponível para Venda</option>
                            <option value="sold" <?php if($machine['status'] === 'sold') echo 'selected'; ?>>Vendida</option>
                            <option value="reserved" <?php if($machine['status'] === 'reserved') echo 'selected'; ?>>Reservada</option>
                            <option value="maintenance" <?php if($machine['status'] === 'maintenance') echo 'selected'; ?>>Em Manutenção</option>
                            <option value="testing" <?php if($machine['status'] === 'testing') echo 'selected'; ?>>Em Teste</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-sticky-note me-2"></i>Observações</h5>
                    <div class="mb-3">
                        <label for="notes" class="form-label form-label-custom">Anotações Gerais</label>
                        <textarea class="form-control form-control-custom" id="notes" name="notes" rows="7"><?php echo htmlspecialchars($machine['notes']); ?></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <?php else: ?>

            <!-- Campos simplificados para o modal -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sale_price" class="form-label form-label-custom">Preço de Venda (R$)</label>
                        <input type="number" class="form-control form-control-custom" id="sale_price" name="sale_price"
                               min="0" step="0.01" value="<?php echo number_format($machine['sale_price'], 2, '.', ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label form-label-custom">Status</label>
                        <select class="form-select form-control-custom" id="status" name="status">
                            <option value="available" <?php if($machine['status'] === 'available') echo 'selected'; ?>>Disponível</option>
                            <option value="sold" <?php if($machine['status'] === 'sold') echo 'selected'; ?>>Vendida</option>
                            <option value="reserved" <?php if($machine['status'] === 'reserved') echo 'selected'; ?>>Reservada</option>
                            <option value="maintenance" <?php if($machine['status'] === 'maintenance') echo 'selected'; ?>>Manutenção</option>
                            <option value="testing" <?php if($machine['status'] === 'testing') echo 'selected'; ?>>Em Teste</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr class="my-3">
            <?php endif; ?>

            <!-- SEÇÃO 3: IMAGEM (somente se não for modal ou em página completa) -->
            <?php if (!$is_modal): ?>
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-camera me-2"></i>Imagem da Máquina</h5>
                    <div class="mb-3">
                        <div class="media-upload-container" id="machine-upload-container">
                            <div class="media-upload-placeholder" style="<?php echo $image_exists ? 'display: none;' : ''; ?>">
                                <div class="media-upload-placeholder-content">
                                    <span class="media-upload-placeholder-icon">
                                        <i class="fas fa-desktop"></i>
                                    </span>
                                    <div class="media-upload-placeholder-title">Adicionar Imagem</div>
                                    <div class="media-upload-placeholder-subtitle">Escolha uma fonte</div>
                                    <div class="media-upload-actions">
                                        <button type="button" class="media-upload-btn" data-action="gallery">
                                            <i class="fas fa-images"></i>
                                            Galeria
                                        </button>
                                        <button type="button" class="media-upload-btn" data-action="camera">
                                            <i class="fas fa-camera"></i>
                                            Câmera
                                        </button>
                                    </div>
                                    <div class="media-upload-drag-hint">
                                        <i class="fas fa-hand-point-up"></i>
                                        Ou arraste aqui
                                    </div>
                                </div>
                            </div>
                            <div class="media-upload-preview" style="<?php echo !$image_exists ? 'display: none;' : ''; ?>">
                                <img class="media-upload-preview-image" src="<?php echo $image_exists ? $image_path : ''; ?>" alt="Preview">
                                <div class="media-upload-preview-overlay">
                                    <button type="button" class="media-upload-action-btn" data-action="change" title="Trocar imagem">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <button type="button" class="media-upload-action-btn danger" data-action="remove" title="Remover imagem">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($machine["image"]); ?>">
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <?php else: ?>
            <!-- Upload completo para modal (PADRÃO PRODUTO) -->
            <div class="mb-3">
                <label class="form-label form-label-custom"><i class="fas fa-camera me-1"></i>Imagem</label>
                <div class="media-upload-container" id="machine-upload-container-modal" style="min-height: 200px;">
                    <div class="media-upload-placeholder" style="<?php echo $image_exists ? 'display: none;' : ''; ?>">
                        <div class="media-upload-placeholder-content">
                            <span class="media-upload-placeholder-icon">
                                <i class="fas fa-desktop"></i>
                            </span>
                            <div class="media-upload-placeholder-title">Adicionar Imagem</div>
                            <div class="media-upload-placeholder-subtitle">Escolha uma fonte</div>
                            <div class="media-upload-actions">
                                <button type="button" class="media-upload-btn" data-action="gallery">
                                    <i class="fas fa-images"></i>
                                    Galeria
                                </button>
                                <button type="button" class="media-upload-btn" data-action="camera">
                                    <i class="fas fa-camera"></i>
                                    Câmera
                                </button>
                            </div>
                            <div class="media-upload-drag-hint">
                                <i class="fas fa-hand-point-up"></i>
                                Ou arraste aqui
                            </div>
                        </div>
                    </div>
                    <div class="media-upload-preview" style="<?php echo !$image_exists ? 'display: none;' : ''; ?>">
                        <img class="media-upload-preview-image" src="<?php echo $image_exists ? $image_path : ''; ?>" alt="Preview">
                        <div class="media-upload-preview-overlay">
                            <button type="button" class="media-upload-action-btn" data-action="change" title="Trocar imagem">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button type="button" class="media-upload-action-btn danger" data-action="remove" title="Remover imagem">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <!-- CORREÇÃO CRÍTICA: Inputs file/camera que estavam faltando -->
                    <input type="file" id="media-file-input-machine-modal" accept="image/*" style="display: none;">
                    <input type="file" id="media-camera-input-machine-modal" accept="image/*" capture="environment" style="display: none;">
                </div>
                <input type="hidden" id="uploaded_image" name="uploaded_image" value="<?php echo htmlspecialchars($machine["image"]); ?>">
            </div>

            <hr class="my-3">
            <?php endif; ?>

            <!-- SEÇÃO 4: COMPONENTES -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-microchip me-2"></i>Componentes de Hardware</h5>

                    <div class="mb-3">
                        <label for="processor" class="form-label form-label-custom">
                            Processador <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="processor" name="processor"
                                   value="<?php echo htmlspecialchars($machine['processor']); ?>"
                                   placeholder="Ex: Intel Core i7-9700K">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="CPU" data-component-name="Processador" data-category="CPU" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="CPU-chips" class="component-chips-container mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="memory" class="form-label form-label-custom">
                            Memória RAM <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="memory" name="memory"
                                   value="<?php echo htmlspecialchars($machine['memory']); ?>"
                                   placeholder="Ex: 16GB DDR4 3200MHz">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="RAM" data-component-name="Memória RAM" data-category="RAM" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="RAM-chips" class="component-chips-container mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="storage" class="form-label form-label-custom">
                            Armazenamento <small class="text-muted">(múltiplos itens permitidos)</small>
                        </label>
                        <div class="input-group">
                            <textarea class="form-control form-control-custom" id="storage" name="storage"
                                      style="height: auto; min-height: 60px; resize: vertical; font-size: 0.9rem;"
                                      placeholder="Ex: SSD 500GB&#10;HDD 2TB"><?php echo htmlspecialchars($machine['storage']); ?></textarea>
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="HDD" data-component-name="Armazenamento" data-category="HDD,SSD,NVMe" title="Buscar no estoque" style="align-self: flex-start;">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="HDD-chips" class="component-chips-container mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="graphics" class="form-label form-label-custom">
                            Placa de Vídeo <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="graphics" name="graphics"
                                   value="<?php echo htmlspecialchars($machine['graphics']); ?>"
                                   placeholder="Ex: NVIDIA RTX 3060 Ti">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="GPU" data-component-name="Placa de Vídeo" data-category="GPU" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="GPU-chips" class="component-chips-container mt-2"></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-cog me-2"></i>Outros Componentes</h5>

                    <div class="mb-3">
                        <label for="motherboard" class="form-label form-label-custom">
                            Placa Mãe <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="motherboard" name="motherboard"
                                   value="<?php echo htmlspecialchars($machine['motherboard']); ?>"
                                   placeholder="Ex: ASUS ROG STRIX B550-F">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="Motherboard" data-component-name="Placa Mãe" data-category="Motherboard" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="Motherboard-chips" class="component-chips-container mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="power_supply" class="form-label form-label-custom">
                            Fonte de Alimentação <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="power_supply" name="power_supply"
                                   value="<?php echo htmlspecialchars($machine['power_supply']); ?>"
                                   placeholder="Ex: Corsair 650W 80+ Gold">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="PSU" data-component-name="Fonte" data-category="PSU" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="PSU-chips" class="component-chips-container mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="case_type" class="form-label form-label-custom">
                            Gabinete <small class="text-muted">(digite ou busque)</small>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" id="case_type" name="case_type"
                                   value="<?php echo htmlspecialchars($machine['case_type']); ?>"
                                   placeholder="Ex: NZXT H510">
                            <button type="button" class="btn btn-primary btn-add-component-modal" data-component-key="Case" data-component-name="Gabinete" data-category="Case" title="Buscar no estoque">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        <div id="Case-chips" class="component-chips-container mt-2"></div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- SEÇÃO 5: SISTEMA OPERACIONAL E GARANTIA -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-laptop-code me-2"></i>Sistema Operacional</h5>

                    <div class="mb-3">
                        <label for="operating_system" class="form-label form-label-custom">Sistema Operacional Instalado</label>
                        <select class="form-select form-control-custom" id="operating_system" name="operating_system">
                            <option value="" <?php if(empty($machine['operating_system'])) echo 'selected'; ?>>Sem Sistema Operacional</option>
                            <optgroup label="Windows">
                                <option value="Windows 11 Pro" <?php if($machine['operating_system'] == 'Windows 11 Pro') echo 'selected'; ?>>Windows 11 Pro</option>
                                <option value="Windows 11 Home" <?php if($machine['operating_system'] == 'Windows 11 Home') echo 'selected'; ?>>Windows 11 Home</option>
                                <option value="Windows 10 Pro" <?php if($machine['operating_system'] == 'Windows 10 Pro') echo 'selected'; ?>>Windows 10 Pro</option>
                                <option value="Windows 10 Home" <?php if($machine['operating_system'] == 'Windows 10 Home') echo 'selected'; ?>>Windows 10 Home</option>
                                <option value="Windows Server 2022" <?php if($machine['operating_system'] == 'Windows Server 2022') echo 'selected'; ?>>Windows Server 2022</option>
                                <option value="Windows Server 2019" <?php if($machine['operating_system'] == 'Windows Server 2019') echo 'selected'; ?>>Windows Server 2019</option>
                            </optgroup>
                            <optgroup label="Linux">
                                <option value="Ubuntu 24.04 LTS" <?php if($machine['operating_system'] == 'Ubuntu 24.04 LTS') echo 'selected'; ?>>Ubuntu 24.04 LTS</option>
                                <option value="Ubuntu 22.04 LTS" <?php if($machine['operating_system'] == 'Ubuntu 22.04 LTS') echo 'selected'; ?>>Ubuntu 22.04 LTS</option>
                                <option value="Ubuntu 20.04 LTS" <?php if($machine['operating_system'] == 'Ubuntu 20.04 LTS') echo 'selected'; ?>>Ubuntu 20.04 LTS</option>
                                <option value="Debian 12" <?php if($machine['operating_system'] == 'Debian 12') echo 'selected'; ?>>Debian 12</option>
                                <option value="Debian 11" <?php if($machine['operating_system'] == 'Debian 11') echo 'selected'; ?>>Debian 11</option>
                                <option value="Fedora Workstation" <?php if($machine['operating_system'] == 'Fedora Workstation') echo 'selected'; ?>>Fedora Workstation</option>
                                <option value="CentOS Stream" <?php if($machine['operating_system'] == 'CentOS Stream') echo 'selected'; ?>>CentOS Stream</option>
                                <option value="Red Hat Enterprise Linux" <?php if($machine['operating_system'] == 'Red Hat Enterprise Linux') echo 'selected'; ?>>Red Hat Enterprise Linux</option>
                                <option value="Linux Mint" <?php if($machine['operating_system'] == 'Linux Mint') echo 'selected'; ?>>Linux Mint</option>
                                <option value="Arch Linux" <?php if($machine['operating_system'] == 'Arch Linux') echo 'selected'; ?>>Arch Linux</option>
                            </optgroup>
                            <optgroup label="macOS">
                                <option value="macOS Sonoma" <?php if($machine['operating_system'] == 'macOS Sonoma') echo 'selected'; ?>>macOS Sonoma</option>
                                <option value="macOS Ventura" <?php if($machine['operating_system'] == 'macOS Ventura') echo 'selected'; ?>>macOS Ventura</option>
                                <option value="macOS Monterey" <?php if($machine['operating_system'] == 'macOS Monterey') echo 'selected'; ?>>macOS Monterey</option>
                            </optgroup>
                            <optgroup label="Outro">
                                <option value="FreeBSD" <?php if($machine['operating_system'] == 'FreeBSD') echo 'selected'; ?>>FreeBSD</option>
                                <option value="Chrome OS" <?php if($machine['operating_system'] == 'Chrome OS') echo 'selected'; ?>>Chrome OS</option>
                                <option value="Outro" <?php if($machine['operating_system'] == 'Outro') echo 'selected'; ?>>Outro</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary-custom mb-3"><i class="fas fa-shield-alt me-2"></i>Garantia</h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="machine_has_warranty"
                                       name="has_warranty" value="1" <?php echo ($machine['has_warranty'] ?? 0) ? 'checked' : ''; ?>
                                       style="width: 3em; height: 1.5em;">
                                <label class="form-check-label" for="machine_has_warranty">
                                    <strong>Com Garantia</strong>
                                </label>
                            </div>
                            <a href="warranties.php" class="btn btn-sm btn-outline-info" id="editMachineWarrantyBtn"
                               <?php echo ($machine['has_warranty'] ?? 0) ? '' : 'style="display: none;"'; ?>>
                                <i class="fas fa-shield-alt me-1"></i>Editar Garantia
                            </a>
                        </div>

                        <!-- CAMPOS OCULTOS DE GARANTIA -->
                        <input type="hidden" id="edit_warranty_provider" name="machine_warranty_provider" value="<?php echo htmlspecialchars($machine['warranty_provider'] ?? ''); ?>">
                        <input type="hidden" id="edit_warranty_period_value" name="machine_warranty_period_value" value="<?php echo htmlspecialchars($machine['warranty_period_value'] ?? ''); ?>">
                        <input type="hidden" id="edit_warranty_period_unit" name="machine_warranty_period_unit" value="<?php echo htmlspecialchars($machine['warranty_period_unit'] ?? 'months'); ?>">

                        <!-- RESUMO VISUAL -->
                        <div id="warranty-summary-machine" class="alert alert-light border border-info p-2 mt-2 <?php echo ($machine['has_warranty'] ?? 0) ? '' : 'd-none'; ?>">
                            <small class="text-muted d-block mb-1"><i class="fas fa-shield-alt me-1 text-info"></i>Garantia:</small>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark" id="summary-provider-machine"><?php echo htmlspecialchars($machine['warranty_provider'] ?? 'Não informado'); ?></span>
                                <span class="badge bg-light text-dark" id="summary-period-machine"><?php echo htmlspecialchars(($machine['warranty_period_value'] ?? '-') . ' ' . ($machine['warranty_period_unit'] ?? 'meses')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Campo hidden para armazenar componentes em JSON -->
            <input type="hidden" id="machine-components-json" name="machine_components" value="{}">

            <div class="d-flex justify-content-<?php echo $is_modal ? 'end' : 'between'; ?>">
                <?php if (!$is_modal): ?>
                <div>
                    <a href="ready_machines.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancelar</a>
                </div>
                <?php endif; ?>
                <div>
                    <?php if ($is_modal): ?>
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i>Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($is_modal): ?>
<!-- Fallback para modal: carregar scripts essenciais -->
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/custom.js"></script>
<?php endif; ?>

<script src="js/media-upload.js"></script>
<script src="js/simple-component-search.js"></script>
<script src="js/machine-components-integration.js"></script>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SISTEMA COMPLETO COM CHIPS VISUAIS -->
<!-- ═══════════════════════════════════════════════════════════ -->

<script>
console.log('═══════════════════════════════════════════════════════════');
console.log('✅ EDIT MACHINE - VERSÃO COM CHIPS VISUAIS');
console.log('Sistema: Componentes com chips visuais completos');
console.log('═══════════════════════════════════════════════════════════');

// Dados existentes - componentes já salvos
const existingComponentsData = <?php echo $existing_components_json; ?>;

console.log('📦 Componentes existentes carregados:', existingComponentsData);

/**
 * Inicializa os chips dos componentes existentes
 */
function initializeExistingChips() {
    console.log('🔄 Inicializando chips dos componentes existentes...');

    if (!existingComponentsData || Object.keys(existingComponentsData).length === 0) {
        console.log('⚠️ Nenhum componente existente para inicializar');
        return;
    }

    // Aguarda o carregamento das funções do machine-components-integration.js
    if (typeof addProductChip === 'undefined') {
        console.log('⏳ Aguardando addProductChip...');
        setTimeout(initializeExistingChips, 100);
        return;
    }

    let chipsCreated = 0;

    for (const [componentType, componentData] of Object.entries(existingComponentsData)) {
        console.log(`📍 Processando ${componentType}:`, componentData);

        // Se é array (múltiplos itens - HDD, RAM)
        if (Array.isArray(componentData)) {
            componentData.forEach(item => {
                try {
                    addProductChip(componentType, item.name, item.id);
                    chipsCreated++;
                    console.log(`  ✅ Chip criado: ${item.name} (ID: ${item.id})`);
                } catch (error) {
                    console.error(`  ❌ Erro ao criar chip para ${componentType}:`, error);
                }
            });
        }
        // Se é objeto único
        else if (componentData && componentData.id) {
            try {
                addProductChip(componentType, componentData.name, componentData.id);
                chipsCreated++;
                console.log(`  ✅ Chip criado: ${componentData.name} (ID: ${componentData.id})`);
            } catch (error) {
                console.error(`  ❌ Erro ao criar chip para ${componentType}:`, error);
            }
        }
    }

    console.log(`✅ ${chipsCreated} chip(s) inicializado(s) com sucesso`);

    // Atualiza o JSON no campo hidden
    setTimeout(() => {
        if (typeof buildAndUpdateComponentsJSON !== 'undefined') {
            buildAndUpdateComponentsJSON();
            console.log('✅ JSON de componentes atualizado');
        }
    }, 100);
}

// Aguarda o DOM carregar e inicializa os chips
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initializeExistingChips, 500);
    });
} else {
    setTimeout(initializeExistingChips, 500);
}
</script>

<script>
// ========================================
// INICIALIZAÇÃO SIMPLES
// ========================================
const hasWarrantyCheckbox = document.getElementById('machine_has_warranty');
const editWarrantyBtn = document.getElementById('editMachineWarrantyBtn');
const warrantySummaryEdit = document.getElementById('warranty-summary-machine');

// Garantia
if (editWarrantyBtn) {
    editWarrantyBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const modal = document.getElementById('warrantyModalMachineEdit');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    });
}

if (hasWarrantyCheckbox) {
    hasWarrantyCheckbox.addEventListener('change', function() {
        if (this.checked) {
            updateWarrantySummaryMachine();
            if (editWarrantyBtn) editWarrantyBtn.style.display = 'inline-block';
            if (warrantySummaryEdit) warrantySummaryEdit.classList.remove('d-none');
        } else {
            if (editWarrantyBtn) editWarrantyBtn.style.display = 'none';
            if (warrantySummaryEdit) warrantySummaryEdit.classList.add('d-none');
        }
    });
}

console.log('✅ Edit Machine carregado');

// ========================================
// INICIALIZAÇÃO DO MEDIA UPLOAD (COMPATÍVEL COM MODAL)
// ========================================
(function() {
    function initMachineMediaUpload() {
        if (!document.getElementById('machine-upload-container-modal')) {
            return; // Elemento não existe
        }

        if (typeof MediaUploadManager === 'undefined') {
            console.log('⏳ MediaUploadManager não carregado, tentando carregar...');

            // Verifica se o script já está na página
            if (!document.querySelector('script[src*="media-upload.js"]')) {
                const script = document.createElement('script');
                script.src = 'js/media-upload.js';
                script.onload = function() {
                    console.log('✅ media-upload.js carregado dinamicamente');
                    initMachineMediaUpload(); // Tenta novamente
                };
                script.onerror = function() {
                    console.error('❌ Erro ao carregar media-upload.js');
                };
                document.head.appendChild(script);
            } else {
                // Script já existe, aguarda um pouco e tenta novamente
                setTimeout(initMachineMediaUpload, 100);
            }
            return;
        }

        // MediaUploadManager existe, inicializa
        try {
            const machineUploadManager = new MediaUploadManager({
                containerId: 'machine-upload-container-modal',
                fileInputId: 'media-file-input-machine-modal',
                cameraInputId: 'media-camera-input-machine-modal',
                hiddenInputId: 'uploaded_image',
                itemType: 'machine'
            });
            window.machineUploadManager = machineUploadManager;
            console.log('✅ Media Upload inicializado para máquina (modal)');
        } catch (error) {
            console.error('❌ Erro ao inicializar MediaUploadManager:', error);
        }
    }

    // Tenta inicializar imediatamente
    if (document.readyState === 'loading') {
        // DOM ainda carregando, aguarda
        document.addEventListener('DOMContentLoaded', initMachineMediaUpload);
    } else {
        // DOM já carregado, inicializa agora
        setTimeout(initMachineMediaUpload, 50);
    }
})();

// ========================================
// FUNÇÕES AUXILIARES
// ========================================
function updateWarrantySummaryMachine() {
    const provider = document.getElementById('edit_warranty_provider')?.value || 'Não informado';
    const periodValue = document.getElementById('edit_warranty_period_value')?.value || '-';
    const periodUnit = document.getElementById('edit_warranty_period_unit')?.value || 'months';

    const unitLabels = {
        'days': 'dias',
        'months': 'meses',
        'years': 'anos'
    };
    const unitLabel = unitLabels[periodUnit] || periodUnit;

    const summaryProvider = document.getElementById('summary-provider-machine');
    const summaryPeriod = document.getElementById('summary-period-machine');

    if (summaryProvider) summaryProvider.textContent = provider;
    if (summaryPeriod) summaryPeriod.textContent = periodValue + ' ' + unitLabel;
}

function showStockConfirmationModal(components, callback) {
    let componentsHtml = '';
    const componentsList = [];

    for (const [type, product] of Object.entries(components)) {
        if (Array.isArray(product)) {
            product.forEach(p => {
                componentsList.push({type, ...p});
            });
        } else {
            componentsList.push({type, ...product});
        }
    }

    componentsHtml = componentsList.map(item => `
        <div class="alert alert-warning alert-sm mb-2" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>${item.type}:</strong> ${item.productName || item.name}
        </div>
    `).join('');

    const modalHtml = `
        <div class="modal fade" id="stockConfirmationModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-box me-2"></i>Confirmar Atualização de Estoque
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            <strong>Você está atualizando os componentes desta máquina:</strong>
                        </p>
                        <div class="components-list mb-3">
                            ${componentsHtml}
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Atenção:</strong> Os componentes anteriores serão <strong>DEVOLVIDOS</strong> ao estoque e estes novos serão <strong>DEDUZIDOS</strong>.
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmStockDeduction" required>
                            <label class="form-check-label" for="confirmStockDeduction">
                                Eu confirmo a atualização de estoque
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmStockBtn" disabled>
                            <i class="fas fa-check me-1"></i>Confirmar e Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const scrollPosition = window.scrollY;

    const oldModal = document.getElementById('stockConfirmationModal');
    if (oldModal) oldModal.remove();

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = modalHtml;
    document.body.appendChild(tempDiv.firstElementChild);

    const modal = new bootstrap.Modal(document.getElementById('stockConfirmationModal'));
    const confirmCheckbox = document.getElementById('confirmStockDeduction');
    const confirmBtn = document.getElementById('confirmStockBtn');

    confirmCheckbox.addEventListener('change', function() {
        confirmBtn.disabled = !this.checked;
    });

    confirmBtn.addEventListener('click', function() {
        modal.hide();
        callback(true);
    });

    document.getElementById('stockConfirmationModal').addEventListener('hidden.bs.modal', function() {
        window.scrollTo(0, scrollPosition);
        callback(false);
    }, { once: true });

    modal.show();

    setTimeout(() => {
        window.scrollTo(0, scrollPosition);
    }, 100);
}

function generateBarcode() {
    const timestamp = Date.now();
    const barcode = '789' + timestamp.toString().substring(timestamp.toString().length - 9);
    document.getElementById('barcode').value = barcode;
    if (typeof showAlert === 'function') {
        showAlert('Código de barras gerado.', 'info');
    }
}

function generateQRCode() {
    const qrCode = 'MACHINE-' + Date.now();
    document.getElementById('qr_code').value = qrCode;
    if (typeof showAlert === 'function') {
        showAlert('Código QR gerado.', 'info');
    }
}

/**
 * Limpa um campo de componente (valor e productId)
 */
function clearComponentField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.value = '';
        field.removeAttribute('data-product-id');
        
        // Feedback visual
        if (typeof showToast === 'function') {
            showToast('Campo limpo');
        }
    }
}

// A função buildAndUpdateComponentsJSON vem do machine-components-integration.js
// Não precisamos duplicar essa função aqui

/**
 * Valida estoque dos componentes selecionados
 * Faz chamada AJAX para verificar se algum produto ficará com estoque 0
 */
function validateComponentsStock(componentsData) {
    return new Promise((resolve) => {
        if (!componentsData || Object.keys(componentsData).length === 0) {
            resolve(true); // Sem componentes, segue normalmente
            return;
        }
        
        // Faz requisição AJAX para validar estoque
        fetch('api/validate_component_stock.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(componentsData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Sem problemas de estoque
                resolve(true);
            } else if (data.warning) {
                // Há produtos que ficarão com estoque 0
                const message = data.message + '\n\nDeseja continuar mesmo assim?';
                if (confirm(message)) {
                    resolve(true);
                } else {
                    resolve(false);
                }
            } else {
                // Erro na validação
                alert('Erro ao validar estoque: ' + data.message);
                resolve(false);
            }
        })
        .catch(error => {
            console.error('Erro na validação de estoque:', error);
            resolve(true); // Continua mesmo assim
        });
    });
}

/**
 * Event listener para o formulário de edição de máquina
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('📖 DOMContentLoaded disparado para edit_machine');

    const form = document.getElementById('editMachineForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // O buildAndUpdateComponentsJSON já foi chamado pelos eventos anteriores
            // O campo hidden machine-components-json já está preenchido
            console.log('🚀 Formulário sendo submetido');

            // Verifica se há JSON no campo hidden
            const hiddenField = document.getElementById('machine-components-json');
            if (hiddenField) {
                console.log('📤 JSON que será enviado:', hiddenField.value);
            }

            // Deixa o formulário submeter normalmente
        });
    } else {
        console.warn('⚠️ Formulário editMachineForm não encontrado');
    }
});
</script>

<?php
if (!$is_modal) {
    include 'includes/warranty_modal_edit_machine.php';
    include 'includes/footer.php';
}
?>
