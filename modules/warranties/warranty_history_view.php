<?php
/**
 * Visualizador de Histórico de Garantia
 * 
 * Exibe timeline com todas as alterações feitas em uma garantia
 * Mostra: quem alterou, quando, o que mudou (antes/depois)
 * 
 * Parâmetros:
 * - id: ID do produto
 * - modal=true: Se chamado dentro de um modal
 */

require '../../config.php';
require_once '../../includes/warranty_functions.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Obter ID e tipo
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$type = trim($_GET['type'] ?? $_POST['type'] ?? 'product'); // product, machine, warehouse

// Validar tipo
if (!in_array($type, ['product', 'machine', 'warehouse'])) {
    $type = 'product';
}

// Verificar se é modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if ($id <= 0) {
    if ($is_modal) {
        http_response_code(400);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID inválido</div>');
    }
    http_response_code(400);
    exit('ID inválido');
}

try {
    $pdo = getConnection();
    
    // Mapear tipo para tabela e nome
    $type_map = [
        'product' => ['table' => 'products', 'label' => 'Produto'],
        'machine' => ['table' => 'ready_machines', 'label' => 'Máquina'],
        'warehouse' => ['table' => 'warehouse', 'label' => 'Item do Armazém']
    ];
    
    $table = $type_map[$type]['table'];
    $type_label = $type_map[$type]['label'];
    
    // Obter dados do item - usar query separada para evitar SQL injection
    if ($type === 'warehouse') {
        $stmt = $pdo->prepare("SELECT id, name FROM warehouse WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    } elseif ($type === 'machine') {
        $stmt = $pdo->prepare("SELECT id, name FROM ready_machines WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
    }
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        if ($is_modal) {
            http_response_code(404);
            exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($type_label) . ' não encontrado</div>');
        }
        http_response_code(404);
        exit($type_label . ' não encontrado');
    }

    // Obter histórico
    $history = getWarrantyHistory($pdo, $id, 100, $type);

} catch (Exception $e) {
    error_log("Erro ao carregar histórico: " . $e->getMessage());
    if ($is_modal) {
        http_response_code(500);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar histórico: ' . htmlspecialchars($e->getMessage()) . '</div>');
    }
    exit('Erro ao carregar histórico');
}

if (!$is_modal) {
    include '../../includes/header.php';
}
?>

<div class="row mb-3">
    <div class="col-12">
        <h5 class="mb-3">
            <i class="fas fa-history"></i> Histórico de Alterações - <?php echo htmlspecialchars($item['name']); ?>
        </h5>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Nenhuma alteração registrada para este <?php echo strtolower($type_label); ?>
    </div>
<?php else: ?>
    <div class="warranty-history-timeline">
        <?php foreach ($history as $index => $entry): ?>
            <?php
            $old_values = !empty($entry['old_values']) ? json_decode($entry['old_values'], true) : [];
            $new_values = !empty($entry['new_values']) ? json_decode($entry['new_values'], true) : [];
            
            // Cores por ação
            $action_colors = [
                'CREATE' => 'success',
                'UPDATE' => 'info',
                'DELETE' => 'danger',
                'CLAIM' => 'warning'
            ];
            
            $action_labels = [
                'CREATE' => 'Criada',
                'UPDATE' => 'Atualizada',
                'DELETE' => 'Deletada',
                'CLAIM' => 'Acionamento'
            ];
            
            $action_icons = [
                'CREATE' => 'fa-plus-circle',
                'UPDATE' => 'fa-edit',
                'DELETE' => 'fa-trash',
                'CLAIM' => 'fa-exclamation-circle'
            ];
            
            $color = $action_colors[$entry['action']] ?? 'secondary';
            $label = $action_labels[$entry['action']] ?? $entry['action'];
            $icon = $action_icons[$entry['action']] ?? 'fa-circle';
            ?>
            
            <div class="history-entry mb-3">
                <div class="d-flex gap-3">
                    <!-- Timeline dot -->
                    <div class="timeline-marker">
                        <div class="timeline-dot bg-<?php echo $color; ?>">
                            <i class="fas <?php echo $icon; ?> text-white"></i>
                        </div>
                        <?php if ($index < count($history) - 1): ?>
                            <div class="timeline-line"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="timeline-content flex-grow-1 mb-2">
                        <div class="card">
                            <div class="card-header bg-<?php echo $color; ?> text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?php echo $label; ?></strong>
                                        <span class="badge bg-dark ms-2">
                                            <?php echo htmlspecialchars($entry['action']); ?>
                                        </span>
                                    </span>
                                    <small><?php echo formatDatePT($entry['created_at'], true); ?></small>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Informações do usuário -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($entry['user_name'] ?? 'Sistema'); ?>
                                        <?php if ($entry['user_email']): ?>
                                            (<?php echo htmlspecialchars($entry['user_email']); ?>)
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <!-- Mudanças -->
                                <?php if (!empty($old_values) || !empty($new_values)): ?>
                                    <div class="changes-container">
                                        <?php
                                        // Campos de garantia
                                        $warranty_fields = [
                                            'warranty_provider' => 'Fornecedor',
                                            'warranty_start_date' => 'Data de Início',
                                            'warranty_end_date' => 'Data de Término',
                                            'warranty_period_value' => 'Valor do Período',
                                            'warranty_period_unit' => 'Unidade do Período',
                                            'warranty_notes' => 'Observações',
                                            'invoice_number' => 'Número da Nota'
                                        ];

                                        // Obter todos os campos que mudaram
                                        $changed_fields = array_merge(
                                            array_keys($old_values ?? []),
                                            array_keys($new_values ?? [])
                                        );
                                        $changed_fields = array_unique($changed_fields);

                                        if ($entry['action'] === 'CREATE'):
                                            // Para criação, mostrar novos valores
                                            foreach ($changed_fields as $field):
                                                if (!isset($warranty_fields[$field])) continue;
                                                $new_value = $new_values[$field] ?? '';
                                                if (empty($new_value)) continue;
                                                
                                                // Formatar valor
                                                $formatted_value = htmlspecialchars($new_value);
                                                if (strpos($field, 'date') !== false) {
                                                    $formatted_value = formatDatePT($new_value);
                                                } elseif ($field === 'warranty_period_unit') {
                                                    $formatted_value = formatPeriodUnit($new_value);
                                                }
                                                ?>
                                                <div class="change-item mb-2">
                                                    <span class="text-muted"><?php echo $warranty_fields[$field]; ?> definido como</span>
                                                    <strong class="text-dark ms-2"><?php echo $formatted_value; ?></strong>
                                                </div>
                                                <?php
                                            endforeach;
                                        elseif ($entry['action'] === 'UPDATE'):
                                            // Para atualização, mostrar antes/depois
                                            foreach ($changed_fields as $field):
                                                if (!isset($warranty_fields[$field])) continue;
                                                
                                                $old_value = $old_values[$field] ?? '';
                                                $new_value = $new_values[$field] ?? '';
                                                
                                                // Pular se não houver mudança real
                                                if ($old_value === $new_value) continue;
                                                ?>
                                                <div class="change-item mb-3">
                                                    <small class="text-muted">
                                                        <strong><?php echo $warranty_fields[$field]; ?>:</strong>
                                                    </small>
                                                    <div class="row g-2 ps-3 mt-1">
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Antes:</div>
                                                            <div class="ps-2 py-2 bg-danger bg-opacity-10 border-start border-danger rounded">
                                                                <code class="text-danger">
                                                                    <?php 
                                                                    if (strpos($field, 'date') !== false) {
                                                                        echo formatDatePT($old_value);
                                                                    } elseif ($field === 'warranty_period_unit') {
                                                                        echo formatPeriodUnit($old_value);
                                                                    } else {
                                                                        echo htmlspecialchars($old_value ?: '(vazio)');
                                                                    }
                                                                    ?>
                                                                </code>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Depois:</div>
                                                            <div class="ps-2 py-2 bg-success bg-opacity-10 border-start border-success rounded">
                                                                <code class="text-success">
                                                                    <?php 
                                                                    if (strpos($field, 'date') !== false) {
                                                                        echo formatDatePT($new_value);
                                                                    } elseif ($field === 'warranty_period_unit') {
                                                                        echo formatPeriodUnit($new_value);
                                                                    } else {
                                                                        echo htmlspecialchars($new_value ?: '(vazio)');
                                                                    }
                                                                    ?>
                                                                </code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            endforeach;
                                        elseif ($entry['action'] === 'DELETE'):
                                            // Para deleção, mostrar valores removidos
                                            foreach ($old_values as $field => $value):
                                                if (!isset($warranty_fields[$field])) continue;
                                                if (empty($value)) continue;
                                                ?>
                                                <div class="change-item mb-2">
                                                    <small class="text-muted">
                                                        <strong><?php echo $warranty_fields[$field]; ?>:</strong>
                                                    </small>
                                                    <div class="ps-3 py-2 bg-danger bg-opacity-10 border-start border-danger">
                                                        <code class="text-danger">
                                                            <?php 
                                                            if (strpos($field, 'date') !== false) {
                                                                echo formatDatePT($value);
                                                            } elseif ($field === 'warranty_period_unit') {
                                                                echo formatPeriodUnit($value);
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                            ?>
                                                        </code>
                                                    </div>
                                                </div>
                                                <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">Nenhuma mudança de dados registrada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.warranty-history-timeline {
    position: relative;
}

.history-entry {
    display: flex;
}

.timeline-marker {
    position: relative;
    width: 60px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.timeline-dot {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-top: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.timeline-line {
    flex-grow: 1;
    width: 2px;
    background: #dee2e6;
    margin-top: 0;
    min-height: 40px;
}

.timeline-content {
    padding-top: 5px;
}

.change-item {
    padding: 0;
}

.changes-container > div:last-child {
    margin-bottom: 0;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.card-header.bg-success {
    background-color: #198754 !important;
}

.card-header.bg-danger {
    background-color: #dc3545 !important;
}

.card-header.bg-info {
    background-color: #0dcaf0 !important;
}

.card-header.bg-warning {
    background-color: #ffc107 !important;
}

code {
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
    font-size: 12px;
}
</style>

<?php if (!$is_modal): ?>
    <?php include '../../includes/footer.php'; ?>
<?php endif; ?>
