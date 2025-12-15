<?php
/**
 * Visualizador de Histórico de Item do Armazém
 *
 * Exibe timeline com todas as alterações feitas em um item do armazém
 * Mostra: entradas, saídas, edições, quem alterou, quando
 *
 * Parâmetros:
 * - id: ID do item do armazém
 * - modal=true: Se chamado dentro de um modal
 */

require '../../config.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Verificar permissão (apenas administrativo)
if ($_SESSION['user_role'] !== 'administrativo' && $_SESSION['user_role'] !== 'admin') {
    if (isset($_GET['modal']) && $_GET['modal'] === 'true') {
        http_response_code(403);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Acesso negado</div>');
    }
    header('Location: ../../dashboard.php');
    exit;
}

// Obter ID do item
$warehouse_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// Verificar se é modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if ($warehouse_id <= 0) {
    if ($is_modal) {
        http_response_code(400);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID inválido</div>');
    }
    http_response_code(400);
    exit('ID inválido');
}

try {
    $pdo = getConnection();

    // Obter dados do item
    $stmt = $pdo->prepare("SELECT id, name, category, quantity FROM warehouse WHERE id = ?");
    $stmt->execute([$warehouse_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        if ($is_modal) {
            http_response_code(404);
            exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Item não encontrado</div>');
        }
        http_response_code(404);
        exit('Item não encontrado');
    }

    // Obter histórico de movimentações
    $movements = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                wm.movement_type as action,
                wm.quantity,
                wm.reason,
                wm.details,
                wm.movement_date as action_date,
                u.username,
                u.full_name
            FROM warehouse_movements wm
            LEFT JOIN users u ON wm.user_id = u.id
            WHERE wm.warehouse_id = ?
            ORDER BY wm.movement_date DESC
        ");
        $stmt->execute([$warehouse_id]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Obter histórico de entradas
    $inputs = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                'ENTRADA' as action,
                wi.quantity as quantity,
                wi.reason,
                wi.details,
                wi.input_date as action_date,
                u.username,
                u.full_name
            FROM warehouse_inputs wi
            LEFT JOIN users u ON wi.user_id = u.id
            WHERE wi.warehouse_id = ?
        ");
        $stmt->execute([$warehouse_id]);
        $inputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Obter histórico de saídas
    $outputs = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                'SAIDA' as action,
                wo.quantity as quantity,
                wo.reason,
                wo.details,
                wo.output_date as action_date,
                u.username,
                u.full_name
            FROM warehouse_outputs wo
            LEFT JOIN users u ON wo.user_id = u.id
            WHERE wo.warehouse_id = ?
        ");
        $stmt->execute([$warehouse_id]);
        $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Obter histórico de admin logs
    $admin_actions = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                al.action,
                NULL as quantity,
                NULL as reason,
                al.details,
                al.timestamp as action_date,
                u.username,
                u.full_name,
                al.old_values,
                al.new_values
            FROM admin_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.table_name = 'warehouse'
            AND al.record_id = ?
            AND al.action IN ('CREATE', 'UPDATE', 'DELETE', 'RESTORE')
        ");
        $stmt->execute([$warehouse_id]);
        $admin_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Combinar e ordenar por data
    $history = array_merge($movements, $inputs, $outputs, $admin_actions);
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
            <i class="fas fa-history"></i> Histórico de Movimentações - <?php echo htmlspecialchars($item['name']); ?>
        </h5>
        <p class="text-muted">
            <strong>Categoria:</strong> <?php echo htmlspecialchars($item['category']); ?> |
            <strong>Quantidade Atual:</strong> <?php echo $item['quantity']; ?>
        </p>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Nenhuma movimentação registrada para este item
    </div>
<?php else: ?>
    <div class="warehouse-history-timeline">
        <?php foreach ($history as $index => $entry): ?>
            <?php
            // Cores por ação
            $action_colors = [
                'ENTRADA' => 'success',
                'entrada' => 'success',
                'SAIDA' => 'danger',
                'saida' => 'danger',
                'CREATE' => 'primary',
                'UPDATE' => 'info',
                'DELETE' => 'warning',
                'RESTORE' => 'success',
                'restauracao' => 'success'
            ];

            $action_icons = [
                'ENTRADA' => 'fa-arrow-down',
                'entrada' => 'fa-arrow-down',
                'SAIDA' => 'fa-arrow-up',
                'saida' => 'fa-arrow-up',
                'CREATE' => 'fa-plus-circle',
                'UPDATE' => 'fa-edit',
                'DELETE' => 'fa-trash',
                'RESTORE' => 'fa-undo',
                'restauracao' => 'fa-undo'
            ];

            $action_labels = [
                'ENTRADA' => 'Entrada de Estoque',
                'entrada' => 'Entrada de Estoque',
                'SAIDA' => 'Saída de Estoque',
                'saida' => 'Saída de Estoque',
                'CREATE' => 'Item Criado',
                'UPDATE' => 'Item Atualizado',
                'DELETE' => 'Item Deletado',
                'RESTORE' => 'Item Restaurado',
                'restauracao' => 'Item Restaurado'
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
                                        <?php if ($entry['action'] === 'ENTRADA' || $entry['action'] === 'entrada' || $entry['action'] === 'SAIDA' || $entry['action'] === 'saida'): ?>
                                            <span class="badge bg-dark ms-2">
                                                <?php echo in_array($entry['action'], ['ENTRADA', 'entrada']) ? '+' : '-'; ?><?php echo $entry['quantity']; ?> unidades
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

                                <!-- Detalhes da movimentação -->
                                <?php if ($entry['action'] === 'ENTRADA' || $entry['action'] === 'entrada' || $entry['action'] === 'SAIDA' || $entry['action'] === 'saida'): ?>
                                    <div class="changes-container">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Quantidade:</strong></small>
                                                    <span class="badge bg-<?php echo in_array($entry['action'], ['ENTRADA', 'entrada']) ? 'success' : 'danger'; ?> fs-6">
                                                        <?php echo in_array($entry['action'], ['ENTRADA', 'entrada']) ? '+' : '-'; ?><?php echo $entry['quantity']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <?php if ($entry['reason']): ?>
                                            <div class="col-md-6">
                                                <div class="change-item">
                                                    <small class="text-muted d-block mb-1"><strong>Motivo:</strong></small>
                                                    <span><?php echo htmlspecialchars($entry['reason']); ?></span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Detalhes de alteração (UPDATE) -->
                                <?php if (!empty($entry['old_values']) || !empty($entry['new_values'])): ?>
                                    <?php
                                    $old_values = $entry['old_values'] ? json_decode($entry['old_values'], true) : [];
                                    $new_values = $entry['new_values'] ? json_decode($entry['new_values'], true) : [];
                                    ?>
                                    <?php if (!empty($old_values) || !empty($new_values)): ?>
                                        <div class="changes-container mt-3">
                                            <strong class="d-block mb-3">Alterações:</strong>
                                            <div class="row g-3">
                                                <?php
                                                $all_keys = array_unique(array_merge(array_keys($old_values), array_keys($new_values)));
                                                foreach ($all_keys as $key):
                                                    if ($key === 'updated_at') continue;
                                                    $old = $old_values[$key] ?? null;
                                                    $new = $new_values[$key] ?? null;
                                                    if ($old !== $new):
                                                ?>
                                                    <div class="col-md-6">
                                                        <div class="change-item">
                                                            <small class="text-muted d-block mb-1"><strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong></small>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <span class="badge bg-light text-dark">Antes:</span><br>
                                                                    <span class="text-danger"><?php echo htmlspecialchars($old ?: '(vazio)'); ?></span>
                                                                </div>
                                                                <div class="col-6">
                                                                    <span class="badge bg-light text-dark">Depois:</span><br>
                                                                    <span class="text-success"><?php echo htmlspecialchars($new ?: '(vazio)'); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Detalhes gerais -->
                                <?php if ($entry['details']): ?>
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-1"><strong>Detalhes:</strong></small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($entry['details'])); ?></p>
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

<?php if (!$is_modal): ?>
    <div class="mt-3">
        <a href="warehouse.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Armazém
        </a>
    </div>
<?php endif; ?>

<style>
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

<?php
if (!$is_modal) {
    include '../../includes/footer.php';
}
?>
