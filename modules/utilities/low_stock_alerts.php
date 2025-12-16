<?php
// ========================================
// PÁGINA DE ALERTAS DE ESTOQUE BAIXO
// ========================================

$page_title = "Alertas de Estoque";
require_once '../../config.php';
require_once 'includes/header.php';

requireLogin();

try {
    $pdo = getConnection();
    
    // Busca produtos com estoque baixo
    $low_stock_products = getLowStockProducts('active');
    
    // Busca configurações de alerta por categoria
    $stmt = $pdo->prepare("
        SELECT DISTINCT category FROM products 
        WHERE is_deleted = FALSE OR is_deleted IS NULL
        ORDER BY category
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar alertas de estoque: " . $e->getMessage());
    $low_stock_products = [];
    $categories = [];
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 d-inline-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                Alertas de Estoque Baixo
            </h1>
            <p class="text-muted mt-2">
                Produtos com quantidade abaixo do mínimo definido
            </p>
        </div>
    </div>

    <!-- Cards com Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total de Alertas</h5>
                    <h2 class="display-4 text-warning">
                        <?php echo count($low_stock_products); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Alertas Críticos</h5>
                    <h2 class="display-4 text-danger">
                        <?php echo count(array_filter($low_stock_products, fn($p) => $p['alert_type'] === 'critical')); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Avisos</h5>
                    <h2 class="display-4 text-warning">
                        <?php echo count(array_filter($low_stock_products, fn($p) => $p['alert_type'] === 'warning')); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Necessidade de Ação</h5>
                    <h2 class="display-4 text-info">
                        <?php echo count(array_filter($low_stock_products, fn($p) => $p['quantity'] <= 0)); ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Alertas -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Produtos com Estoque Baixo
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Parabéns!</strong> Todos os produtos estão com estoque adequado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Quantidade Atual</th>
                                        <th>Quantidade Mínima</th>
                                        <th>Diferença</th>
                                        <th>Status</th>
                                        <th>Última Entrada</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_products as $product): 
                                        $difference = $product['min_quantity'] - $product['quantity'];
                                        $badge_class = $product['alert_type'] === 'critical' ? 'bg-danger' : 'bg-warning';
                                        $text_class = $product['alert_type'] === 'critical' ? 'text-danger' : 'text-warning';
                                    ?>
                                        <tr class="<?php echo $product['quantity'] <= 0 ? 'table-danger' : ''; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($product['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="<?php echo $product['quantity'] <= 0 ? 'text-danger fw-bold' : 'fw-bold'; ?>">
                                                    <?php echo $product['quantity']; ?> un
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $product['min_quantity']; ?> un
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    Faltam <?php echo $difference; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($product['alert_type'] === 'critical'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-fire me-1"></i>Crítico
                                                    </span>
                                                <?php elseif ($product['quantity'] <= 0): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="fas fa-exclamation-circle me-1"></i>Fora de Estoque
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Aviso
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($product['last_alert']): ?>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y H:i', strtotime($product['last_alert'])); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="product_stock_in.php?product_id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Realizar entrada de estoque">
                                                    <i class="fas fa-plus-circle me-1"></i>Entrada
                                                </a>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="resolveAlert(<?php echo $product['id']; ?>)"
                                                        title="Marcar alerta como resolvido">
                                                    <i class="fas fa-check me-1"></i>Resolver
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function resolveAlert(productId) {
    const notes = prompt('Adicione uma nota sobre a resolução (opcional):', '');
    
    if (notes !== null) {
        fetch('api/resolve_low_stock_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId, notes: notes })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Alerta resolvido com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao resolver alerta', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao resolver alerta', 'danger');
        });
    }
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<?php
require_once 'includes/footer.php';
?>
