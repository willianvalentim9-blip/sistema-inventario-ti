<?php
/**
 * Visualizador de Histórico de Produto
 *
 * Exibe timeline com todas as alterações feitas em um produto
 * Mostra: entradas, saídas, edições, quem alterou, quando
 *
 * Parâmetros:
 * - id: ID do produto
 * - modal=true: Se chamado dentro de um modal
 */

require '../../config.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obter ID do produto
$product_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// Verificar se é modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if ($product_id <= 0) {
    if ($is_modal) {
        http_response_code(400);
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID de produto inválido</div>');
    }
    http_response_code(400);
    exit('Produto inválido');
}

try {
    $pdo = getConnection();

    // Obter dados do produto
    $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        if ($is_modal) {
            http_response_code(404);
            exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Produto não encontrado</div>');
        }
        http_response_code(404);
        exit('Produto não encontrado');
    }

    // Obter histórico de entradas
    $inputs = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                'ENTRADA' as action,
                pi.quantity_added as quantity,
                pi.reason,
                pi.purchase_price,
                pi.sale_price,
                pi.details,
                pi.input_date as action_date,
                u.username,
                u.full_name
            FROM product_inputs pi
            LEFT JOIN users u ON pi.user_id = u.id
            WHERE pi.product_id = ?
        ");
        $stmt->execute([$product_id]);
        $inputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Obter histórico de movimentos (tabela nova)
    $movements = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                UPPER(pm.movement_type) as action,
                pm.quantity,
                pm.reason,
                NULL as purchase_price,
                NULL as sale_price,
                NULL as details,
                pm.movement_date as action_date,
                u.username,
                u.full_name
            FROM product_movements pm
            LEFT JOIN users u ON pm.user_id = u.id
            WHERE pm.product_id = ?
            ORDER BY pm.movement_date DESC
        ");
        $stmt->execute([$product_id]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Obter histórico de saídas
    $outputs = [];
    try {
        $stmt = $pdo->prepare("
            SELECT
                'SAIDA' as action,
                po.quantity_removed as quantity,
                po.reason,
                NULL as purchase_price,
                po.price_at_time as sale_price,
                po.details,
                po.output_date as action_date,
                u.username,
                u.full_name
            FROM product_outputs po
            LEFT JOIN users u ON po.user_id = u.id
            WHERE po.product_id = ?
        ");
        $stmt->execute([$product_id]);
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
                NULL as purchase_price,
                NULL as sale_price,
                al.details,
                al.timestamp as action_date,
                u.username,
                u.full_name,
                al.old_values,
                al.new_values
            FROM admin_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.table_name = 'products'
            AND al.record_id = ?
            AND al.action IN ('CREATE', 'UPDATE', 'DELETE', 'RESTORE')
        ");
        $stmt->execute([$product_id]);
        $admin_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabela não existe, continuar
    }

    // Combinar e ordenar por data
    $history = array_merge($inputs, $movements, $outputs, $admin_actions);
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
            <i class="fas fa-history"></i> Histórico de Movimentações - <?php echo htmlspecialchars($product['name']); ?>
        </h5>
        <p class="text-muted"><strong>Quantidade Atual:</strong> <?php echo $product['quantity']; ?></p>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Nenhuma movimentação registrada para este produto
    </div>
<?php else: ?>
    <div class="product-history-timeline">
        <?php foreach ($history as $index => $entry): ?>
            <?php
            // Cores por ação
            $action_colors = [
                'ENTRADA' => 'success',
                'SAIDA' => 'danger',
                'CREATE' => 'primary',
                'UPDATE' => 'info',
                'DELETE' => 'warning',
                'RESTORE' => 'success'
            ];

            $action_icons = [
                'ENTRADA' => 'fa-arrow-down',
                'SAIDA' => 'fa-arrow-up',
                'CREATE' => 'fa-plus-circle',
                'UPDATE' => 'fa-edit',
                'DELETE' => 'fa-trash',
                'RESTORE' => 'fa-undo'
            ];

            $action_labels = [
                'ENTRADA' => 'Entrada de Estoque',
                'SAIDA' => 'Saída de Estoque',
                'CREATE' => 'Produto Criado',
                'UPDATE' => 'Produto Atualizado',
                'DELETE' => 'Produto Deletado',
                'RESTORE' => 'Produto Restaurado'
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
                                        <?php if ($entry['action'] === 'ENTRADA' || $entry['action'] === 'SAIDA'): ?>
                                            <span class="badge bg-dark ms-2">
                                                <?php echo $entry['action'] === 'ENTRADA' ? '+' : '-'; ?><?php echo $entry['quantity']; ?> unidades
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
                                <?php if ($entry['action'] === 'ENTRADA' || $entry['action'] === 'SAIDA'): ?>
                                    <div class="changes-container">
                                        <div class="change-item mb-3">
                                            <small class="text-muted"><strong>Movimento:</strong></small>
                                            <div class="row g-2 ps-3 mt-1">
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1">Antes:</div>
                                                    <div class="ps-2 py-2 bg-danger bg-opacity-10 border-start border-danger rounded">
                                                        <code class="text-danger">
                                                            <?php echo $entry['action'] === 'ENTRADA' ? '(' . ($entry['quantity'] ?? 0) . ' un)' : '(' . ($entry['quantity'] ?? 0) . ' un)'; ?>
                                                        </code>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1">Depois:</div>
                                                    <div class="ps-2 py-2 bg-success bg-opacity-10 border-start border-success rounded">
                                                        <code class="text-success">
                                                            <?php 
                                                            // Mostra a quantidade com sinal
                                                            if ($entry['action'] === 'ENTRADA') {
                                                                echo '+' . ($entry['quantity'] ?? 0) . ' un';
                                                            } else {
                                                                echo '-' . ($entry['quantity'] ?? 0) . ' un';
                                                            }
                                                            ?>
                                                        </code>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($entry['purchase_price']): ?>
                                        <div class="change-item mb-3">
                                            <small class="text-muted"><strong>Preço de Compra:</strong></small>
                                            <div class="ps-3 py-2 bg-info bg-opacity-10 border-start border-info rounded">
                                                <code class="text-info">R$ <?php echo number_format($entry['purchase_price'], 2, ',', '.'); ?></code>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($entry['sale_price']): ?>
                                        <div class="change-item mb-3">
                                            <small class="text-muted"><strong>Preço de Venda:</strong></small>
                                            <div class="ps-3 py-2 bg-success bg-opacity-10 border-start border-success rounded">
                                                <code class="text-success">R$ <?php echo number_format($entry['sale_price'], 2, ',', '.'); ?></code>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($entry['reason']): ?>
                                        <div class="change-item mb-3">
                                            <small class="text-muted"><strong>Motivo:</strong></small>
                                            <?php 
                                            $reason = htmlspecialchars($entry['reason']);
                                            // Detecta se é movimento de máquina
                                            if (strpos($reason, 'Adicionado à máquina:') !== false) {
                                                // Extrai nome da máquina
                                                preg_match('/Adicionado à máquina: ([^(]+)\s*\(ID:\s*(\d+)\)/', $reason, $matches);
                                                if ($matches) {
                                                    echo '<div class="ps-3 py-2 bg-primary bg-opacity-10 border-start border-primary rounded">';
                                                    echo '<span class="badge bg-primary me-2"><i class="fas fa-microchip me-1"></i>Montagem de Máquina</span>';
                                                    echo '<code class="text-primary">' . trim($matches[1]) . '</code>';
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="ps-3 py-2 bg-secondary bg-opacity-10 border-start border-secondary rounded"><code>' . $reason . '</code></div>';
                                                }
                                            } elseif (strpos($reason, 'Devolvido de:') !== false) {
                                                // Extrai nome da máquina
                                                preg_match('/Devolvido de: ([^(]+)\s*\(([^,]+),\s*ID:\s*(\d+)\)/', $reason, $matches);
                                                if ($matches) {
                                                    echo '<div class="ps-3 py-2 bg-warning bg-opacity-10 border-start border-warning rounded">';
                                                    echo '<span class="badge bg-warning me-2"><i class="fas fa-undo me-1"></i>Devolução de Máquina</span>';
                                                    echo '<code class="text-warning">' . trim($matches[1]) . ' - ' . trim($matches[2]) . '</code>';
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="ps-3 py-2 bg-secondary bg-opacity-10 border-start border-secondary rounded"><code>' . $reason . '</code></div>';
                                                }
                                            } else {
                                                echo '<div class="ps-3 py-2 bg-secondary bg-opacity-10 border-start border-secondary rounded"><code>' . $reason . '</code></div>';
                                            }
                                            ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($entry['details']): ?>
                                        <div class="change-item mb-3">
                                            <small class="text-muted"><strong>Detalhes:</strong></small>
                                            <div class="ps-3 py-2 bg-secondary bg-opacity-10 border-start border-secondary rounded">
                                                <code><?php echo nl2br(htmlspecialchars($entry['details'])); ?></code>
                                            </div>
                                        </div>
                                        <?php endif; ?>
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
                                        'category' => 'Categoria',
                                        'manufacturer' => 'Fabricante',
                                        'model' => 'Modelo',
                                        'quantity' => 'Quantidade',
                                        'min_quantity' => 'Estoque Mínimo',
                                        'max_quantity' => 'Estoque Máximo',
                                        'price' => 'Preço',
                                        'status' => 'Status',
                                        'location' => 'Localização'
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
                                                        if ($field === 'price') {
                                                            echo 'R$ ' . number_format($new_value, 2, ',', '.');
                                                        } else {
                                                            echo htmlspecialchars($new_value);
                                                        }
                                                        ?>
                                                    </strong>
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
                                                                    if ($field === 'price') {
                                                                        echo 'R$ ' . number_format($old_value, 2, ',', '.');
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
                                                                    if ($field === 'price') {
                                                                        echo 'R$ ' . number_format($new_value, 2, ',', '.');
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
                                                            if ($field === 'price') {
                                                                echo 'R$ ' . number_format($value, 2, ',', '.');
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
.product-history-timeline {
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
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Produtos
        </a>
    </div>
<?php endif; ?>

<?php
if (!$is_modal) {
    include '../../includes/footer.php';
}
?>
