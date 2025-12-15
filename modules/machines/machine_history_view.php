<?php
/**
 * Visualizador de Histórico de Máquina
 *
 * Exibe timeline com todas as alterações feitas em uma máquina
 * Mostra: montagens, desmontagens, edições, quem alterou, quando
 *
 * Parâmetros:
 * - id: ID da máquina
 * - modal=true: Se chamado dentro de um modal
 */

require '../../config.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obter ID da máquina
$machine_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// Verificar se é modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if ($machine_id <= 0) {
    if ($is_modal) {
        http_response_code(400);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID de máquina inválido</div>');
    }
    http_response_code(400);
    exit('Máquina inválida');
}

try {
    $pdo = getConnection();

    // Obter dados da máquina
    $stmt = $pdo->prepare("SELECT id, name, status FROM ready_machines WHERE id = ?");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        if ($is_modal) {
            http_response_code(404);
            exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Máquina não encontrada</div>');
        }
        http_response_code(404);
        exit('Máquina não encontrada');
    }

    // Obter histórico de componentes adicionados
    $stmt = $pdo->prepare("
        SELECT
            'COMPONENTE_ADICIONADO' as action,
            p.name as component_name,
            mp.quantity,
            mp.created_at as action_date,
            NULL as username,
            NULL as full_name,
            mp.component_type as details
        FROM machine_products mp
        LEFT JOIN products p ON mp.product_id = p.id
        WHERE mp.machine_id = ?
    ");
    $stmt->execute([$machine_id]);
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter histórico de entrada de máquinas
    $stmt = $pdo->prepare("
        SELECT
            'ENTRADA' as action,
            NULL as component_name,
            mi.quantity_added as quantity,
            mi.input_date as action_date,
            u.username,
            u.full_name,
            mi.reason as details,
            NULL as old_values,
            NULL as new_values
        FROM machine_inputs mi
        LEFT JOIN users u ON mi.user_id = u.id
        WHERE mi.machine_id = ?
    ");
    $stmt->execute([$machine_id]);
    $inputs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter histórico de movimentação de status
    $stmt = $pdo->prepare("
        SELECT
            'MOVIMENTACAO_STATUS' as action,
            NULL as component_name,
            NULL as quantity,
            mm.movement_date as action_date,
            u.username,
            u.full_name,
            CONCAT('Status: ', COALESCE(mm.old_status, 'N/A'), ' → ', mm.new_status, ' | ', COALESCE(mm.details, '')) as details,
            NULL as old_values,
            NULL as new_values
        FROM machine_movements mm
        LEFT JOIN users u ON mm.user_id = u.id
        WHERE mm.machine_id = ?
    ");
    $stmt->execute([$machine_id]);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter histórico de saída de máquinas
    $stmt = $pdo->prepare("
        SELECT
            'SAIDA' as action,
            NULL as component_name,
            mo.quantity_removed as quantity,
            mo.output_date as action_date,
            u.username,
            u.full_name,
            mo.reason as details,
            NULL as old_values,
            NULL as new_values
        FROM machine_outputs mo
        LEFT JOIN users u ON mo.user_id = u.id
        WHERE mo.machine_id = ?
    ");
    $stmt->execute([$machine_id]);
    $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter histórico de admin logs (criação, edição, exclusão)
    $stmt = $pdo->prepare("
        SELECT
            al.action,
            NULL as component_name,
            NULL as quantity,
            al.details,
            al.timestamp as action_date,
            u.username,
            u.full_name,
            al.old_values,
            al.new_values
        FROM admin_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.table_name = 'ready_machines'
        AND al.record_id = ?
        AND al.action IN ('CREATE', 'UPDATE_MACHINE', 'UPDATE_MACHINE_COMPONENTS', 'DELETE', 'RESTORE')
    ");
    $stmt->execute([$machine_id]);
    $admin_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combinar e ordenar por data
    $history = array_merge($components, $inputs, $movements, $outputs, $admin_actions);
    usort($history, function($a, $b) {
        return strtotime($b['action_date']) - strtotime($a['action_date']);
    });

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
            <i class="fas fa-history"></i> Histórico de Alterações - <?php echo htmlspecialchars($machine['name']); ?>
        </h5>
        <p class="text-muted"><strong>Status:</strong> <?php echo htmlspecialchars($machine['status']); ?></p>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Nenhuma alteração registrada para esta máquina
    </div>
<?php else: ?>
    <div class="machine-history-timeline">
        <?php foreach ($history as $index => $entry): ?>
            <?php
            // Cores por ação
            $action_colors = [
                'COMPONENTE_ADICIONADO' => 'success',
                'COMPONENTE_REMOVIDO' => 'danger',
                'ENTRADA' => 'primary',
                'MOVIMENTACAO_STATUS' => 'warning',
                'SAIDA' => 'danger',
                'CREATE' => 'primary',
                'UPDATE_MACHINE' => 'info',
                'UPDATE_MACHINE_COMPONENTS' => 'success',
                'DELETE' => 'warning',
                'RESTORE' => 'success'
            ];

            $action_icons = [
                'COMPONENTE_ADICIONADO' => 'fa-plus-circle',
                'COMPONENTE_REMOVIDO' => 'fa-minus-circle',
                'ENTRADA' => 'fa-inbox',
                'MOVIMENTACAO_STATUS' => 'fa-exchange-alt',
                'SAIDA' => 'fa-share',
                'CREATE' => 'fa-desktop',
                'UPDATE_MACHINE' => 'fa-edit',
                'UPDATE_MACHINE_COMPONENTS' => 'fa-cogs',
                'DELETE' => 'fa-trash',
                'RESTORE' => 'fa-undo'
            ];

            $action_labels = [
                'COMPONENTE_ADICIONADO' => 'Componente Adicionado',
                'COMPONENTE_REMOVIDO' => 'Componente Removido',
                'ENTRADA' => 'Máquina Entrada',
                'MOVIMENTACAO_STATUS' => 'Alteração de Status',
                'SAIDA' => 'Máquina Saída',
                'CREATE' => 'Máquina Criada',
                'UPDATE_MACHINE' => 'Máquina Atualizada',
                'UPDATE_MACHINE_COMPONENTS' => 'Componentes Atualizados',
                'DELETE' => 'Máquina Deletada',
                'RESTORE' => 'Máquina Restaurada'
            ];

            $color = $action_colors[$entry['action']] ?? 'secondary';
            $icon = $action_icons[$entry['action']] ?? 'fa-circle';
            $label = $action_labels[$entry['action']] ?? $entry['action'];
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
                                        <?php if ($entry['component_name']): ?>
                                            <span class="badge bg-dark ms-2">
                                                <?php echo htmlspecialchars($entry['component_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <small>
                                        <?php
                                        $date = new DateTime($entry['action_date']);
                                        echo $date->format('d/m/Y H:i');
                                        ?>
                                    </small>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Informações do usuário -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($entry['full_name'] ?: $entry['username'] ?: 'Sistema'); ?>
                                    </small>
                                </div>

                                <!-- Detalhes do componente -->
                                <?php if ($entry['action'] === 'COMPONENTE_ADICIONADO' || $entry['action'] === 'COMPONENTE_REMOVIDO'): ?>
                                    <div class="changes-container">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Componente:</strong></small>
                                                    <span class="text-dark"><?php echo htmlspecialchars($entry['component_name']); ?></span>
                                                </div>
                                            </div>

                                            <?php if ($entry['quantity']): ?>
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Quantidade:</strong></small>
                                                    <span class="badge bg-<?php echo $entry['action'] === 'COMPONENTE_ADICIONADO' ? 'success' : 'danger'; ?> fs-6">
                                                        <?php echo $entry['quantity']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($entry['details']): ?>
                                            <div class="col-12">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Notas:</strong></small>
                                                    <span class="text-dark"><?php echo nl2br(htmlspecialchars($entry['details'])); ?></span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Detalhes de Entrada/Saída -->
                                <?php if ($entry['action'] === 'ENTRADA' || $entry['action'] === 'SAIDA'): ?>
                                    <div class="changes-container">
                                        <div class="row g-3">
                                            <?php if ($entry['quantity']): ?>
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Quantidade:</strong></small>
                                                    <span class="badge bg-<?php echo $entry['action'] === 'ENTRADA' ? 'success' : 'danger'; ?> fs-6">
                                                        <?php echo $entry['quantity']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($entry['details']): ?>
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Motivo:</strong></small>
                                                    <span class="text-dark"><?php echo htmlspecialchars($entry['details']); ?></span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Detalhes de Movimentação de Status -->
                                <?php if ($entry['action'] === 'MOVIMENTACAO_STATUS'): ?>
                                    <div class="changes-container">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Alteração:</strong></small>
                                                    <span class="text-dark"><?php echo htmlspecialchars($entry['details']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Mudanças de CREATE/UPDATE/DELETE -->
                                <?php if (!empty($entry['old_values']) || !empty($entry['new_values'])): ?>
                                    <?php
                                    $old_values = $entry['old_values'] ? json_decode($entry['old_values'], true) : [];
                                    $new_values = $entry['new_values'] ? json_decode($entry['new_values'], true) : [];

                                    $field_labels = [
                                        'name' => 'Nome',
                                        'description' => 'Descrição',
                                        'processor' => 'Processador',
                                        'memory' => 'Memória',
                                        'storage' => 'Armazenamento',
                                        'graphics' => 'Gráficos',
                                        'quantity' => 'Quantidade',
                                        'sale_price' => 'Preço de Venda',
                                        'status' => 'Status',
                                        'serial_number' => 'Número de Série',
                                        'windows_10_compatible' => 'Windows 10',
                                        'windows_11_compatible' => 'Windows 11'
                                    ];

                                    $changed_fields = array_unique(array_merge(
                                        array_keys($old_values),
                                        array_keys($new_values)
                                    ));
                                    ?>

                                    <div class="changes-container">
                                        <?php if ($entry['action'] === 'CREATE'): ?>
                                            <!-- Para criação, mostrar novos valores -->
                                            <?php foreach ($changed_fields as $field): ?>
                                                <?php if (!isset($field_labels[$field])) continue; ?>
                                                <?php $new_value = $new_values[$field] ?? ''; ?>
                                                <?php if (empty($new_value) && $new_value !== '0') continue; ?>

                                                <div class="change-item mb-2">
                                                    <span class="text-muted"><?php echo $field_labels[$field]; ?> definido como</span>
                                                    <strong class="text-dark ms-2">
                                                        <?php
                                                        if ($field === 'sale_price') {
                                                            echo 'R$ ' . number_format($new_value, 2, ',', '.');
                                                        } elseif (strpos($field, 'compatible') !== false) {
                                                            echo $new_value ? 'Sim' : 'Não';
                                                        } else {
                                                            echo htmlspecialchars($new_value);
                                                        }
                                                        ?>
                                                    </strong>
                                                </div>
                                            <?php endforeach; ?>

                                        <?php elseif ($entry['action'] === 'UPDATE_MACHINE'): ?>
                                            <!-- Para atualização de máquina, mostrar antes/depois -->
                                            <?php foreach ($changed_fields as $field): ?>
                                                <?php if (!isset($field_labels[$field])) continue; ?>
                                                <?php
                                                $old_value = $old_values[$field] ?? '';
                                                $new_value = $new_values[$field] ?? '';
                                                if ($old_value === $new_value) continue;
                                                ?>

                                                <div class="change-item mb-3">
                                                    <small class="text-muted">
                                                        <strong><?php echo $field_labels[$field]; ?>:</strong>
                                                    </small>
                                                    <div class="row g-2 ps-3 mt-1">
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Antes:</div>
                                                            <div class="ps-2 py-2 bg-danger bg-opacity-10 border-start border-danger rounded">
                                                                <code class="text-danger">
                                                                    <?php
                                                                    if ($field === 'sale_price') {
                                                                        echo 'R$ ' . number_format($old_value, 2, ',', '.');
                                                                    } elseif (strpos($field, 'compatible') !== false) {
                                                                        echo $old_value ? 'Sim' : 'Não';
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
                                                                    if ($field === 'sale_price') {
                                                                        echo 'R$ ' . number_format($new_value, 2, ',', '.');
                                                                    } elseif (strpos($field, 'compatible') !== false) {
                                                                        echo $new_value ? 'Sim' : 'Não';
                                                                    } else {
                                                                        echo htmlspecialchars($new_value ?: '(vazio)');
                                                                    }
                                                                    ?>
                                                                </code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                        <?php elseif ($entry['action'] === 'UPDATE'): ?>
                                            <!-- Para atualização, mostrar antes/depois -->
                                            <?php foreach ($changed_fields as $field): ?>
                                                <?php if (!isset($field_labels[$field])) continue; ?>
                                                <?php
                                                $old_value = $old_values[$field] ?? '';
                                                $new_value = $new_values[$field] ?? '';
                                                if ($old_value === $new_value) continue;
                                                ?>

                                                <div class="change-item mb-3">
                                                    <small class="text-muted">
                                                        <strong><?php echo $field_labels[$field]; ?>:</strong>
                                                    </small>
                                                    <div class="row g-2 ps-3 mt-1">
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Antes:</div>
                                                            <div class="ps-2 py-2 bg-danger bg-opacity-10 border-start border-danger rounded">
                                                                <code class="text-danger">
                                                                    <?php
                                                                    if ($field === 'sale_price') {
                                                                        echo 'R$ ' . number_format($old_value, 2, ',', '.');
                                                                    } elseif (strpos($field, 'compatible') !== false) {
                                                                        echo $old_value ? 'Sim' : 'Não';
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
                                                                    if ($field === 'sale_price') {
                                                                        echo 'R$ ' . number_format($new_value, 2, ',', '.');
                                                                    } elseif (strpos($field, 'compatible') !== false) {
                                                                        echo $new_value ? 'Sim' : 'Não';
                                                                    } else {
                                                                        echo htmlspecialchars($new_value ?: '(vazio)');
                                                                    }
                                                                    ?>
                                                                </code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                        <?php elseif ($entry['action'] === 'DELETE'): ?>
                                            <!-- Para deleção, mostrar valores removidos -->
                                            <?php foreach ($old_values as $field => $value): ?>
                                                <?php if (!isset($field_labels[$field])) continue; ?>
                                                <?php if (empty($value) && $value !== '0') continue; ?>

                                                <div class="change-item mb-2">
                                                    <small class="text-muted">
                                                        <strong><?php echo $field_labels[$field]; ?>:</strong>
                                                    </small>
                                                    <div class="ps-3 py-2 bg-danger bg-opacity-10 border-start border-danger">
                                                        <code class="text-danger">
                                                            <?php
                                                            if ($field === 'sale_price') {
                                                                echo 'R$ ' . number_format($value, 2, ',', '.');
                                                            } elseif (strpos($field, 'compatible') !== false) {
                                                                echo $value ? 'Sim' : 'Não';
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                            ?>
                                                        </code>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
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
.machine-history-timeline {
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

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

code {
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
    font-size: 12px;
}
</style>

<?php if (!$is_modal): ?>
    <div class="mt-3">
        <a href="ready_machines.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Máquinas
        </a>
    </div>
<?php endif; ?>

<?php
if (!$is_modal) {
    include '../../includes/footer.php';
}
?>
