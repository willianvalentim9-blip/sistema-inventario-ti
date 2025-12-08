<?php
// ========================================
// DASHBOARD PRINCIPAL DO SISTEMA
// ========================================
// Esta página exibe a visão geral do sistema e estatísticas

// Inclui o arquivo de configuração
require_once __DIR__ . '/config.php';

// Verifica se o usuário está logado
requireLogin();

// Define variáveis para o template
$page_title = 'Dashboard';

// ========================================
// BUSCA DADOS PARA ESTATÍSTICAS
// ========================================
try {
    $pdo = getConnection();
    
    // Conta total de produtos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = FALSE OR is_deleted IS NULL");
    $total_products = $stmt->fetch()['total'];
    
    // Conta produtos por status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM products WHERE is_deleted = FALSE OR is_deleted IS NULL GROUP BY status");
    $products_by_status = $stmt->fetchAll();
    
    // Conta produtos com estoque baixo usando a coluna min_quantity
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE quantity > 0 AND quantity <= min_quantity AND min_quantity > 0 AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $low_stock_products = $stmt->fetch()['total'];
    
    // Conta produtos sem estoque
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE quantity = 0 AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $out_of_stock_products = $stmt->fetch()['total'];
    
    // Conta máquinas prontas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE is_deleted = FALSE OR is_deleted IS NULL");
    $total_machines = $stmt->fetch()['total'];
    
    // Conta usuários (apenas para admin)
    $total_users = 0;
    if (isAdmin()) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt->fetch()['total'];
    }
    
    // Produtos adicionados recentemente (últimos 7 dias)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $recent_products = $stmt->fetch()['total'];
    
    // Últimos produtos adicionados (5 mais recentes)
    $stmt = $pdo->query("SELECT name, category, created_at FROM products WHERE is_deleted = FALSE OR is_deleted IS NULL ORDER BY created_at DESC LIMIT 5");
    $latest_products = $stmt->fetchAll();
    
    // Produtos mais caros
    $stmt = $pdo->query("SELECT name, price FROM products WHERE price IS NOT NULL AND (is_deleted = FALSE OR is_deleted IS NULL) ORDER BY price DESC LIMIT 5");
    $expensive_products = $stmt->fetchAll();
    
    // ALTERADO: Query para buscar itens vendidos com o valor total da venda
    $stmt = $pdo->query("
        (SELECT 
            po.product_name as name, 
            po.output_date as date, 
            'Produto' as type, 
            u.username,
            (po.unit_price * po.quantity_removed) as sale_value
         FROM product_outputs po 
         LEFT JOIN users u ON po.user_id = u.id 
         WHERE po.reason = 'Venda' AND po.unit_price IS NOT NULL)
        UNION ALL
        (SELECT 
            mo.machine_name as name, 
            mo.output_date as date, 
            'Máquina' as type, 
            u.username,
            (mo.final_sale_price * mo.quantity_removed) as sale_value
         FROM machine_outputs mo 
         LEFT JOIN users u ON mo.user_id = u.id 
         WHERE mo.reason = 'Venda' AND mo.final_sale_price IS NOT NULL)
        ORDER BY date DESC
        LIMIT 5
    ");
    $recently_sold_items = $stmt->fetchAll();
    
    // Dados para gráfico de movimentações (últimos 30 dias)
    $stmt = $pdo->query("
        SELECT 
            DATE(movement_date) as date,
            movement_type,
            COUNT(*) as count,
            SUM(quantity) as total_quantity
        FROM product_movements 
        WHERE movement_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(movement_date), movement_type
        ORDER BY date DESC
    ");
    $movement_data = $stmt->fetchAll();
    
    // Produtos com alertas de estoque
    $stmt = $pdo->query("
        SELECT name, quantity, min_quantity, max_quantity
        FROM products 
        WHERE (is_deleted = FALSE OR is_deleted IS NULL)
          AND ((quantity <= min_quantity AND min_quantity > 0) 
               OR (quantity >= max_quantity AND max_quantity > 0))
        ORDER BY (quantity - min_quantity)
        LIMIT 10
    ");
    $stock_alerts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do dashboard: " . $e->getMessage());
    $total_products = $low_stock_products = $out_of_stock_products = $total_machines = $total_users = $recent_products = 0;
    $products_by_status = $expensive_products = $recently_sold_items = [];
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-tachometer-alt me-2"></i>
        Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        </div>
</div>

<div class="alert alert-info alert-custom mb-4" role="alert">
    <i class="fas fa-user-circle me-2"></i>
    <strong>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</strong>
    Último acesso em <?php echo date('d/m/Y H:i'); ?>
    <?php if (isAdmin()): ?>
        <span class="badge bg-warning text-dark ms-2">Administrador</span>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-custom border-left-primary h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary-custom text-uppercase mb-1">
                            Total de Produtos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_products); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-primary-custom"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-custom border-left-warning h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Estoque Baixo
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($low_stock_products); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-custom border-left-danger h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Sem Estoque
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($out_of_stock_products); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-custom border-left-success h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Máquinas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_machines); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-desktop fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header card-header-custom">
                <i class="fas fa-clock me-2"></i>
                Últimos Itens Adicionados
            </div>
            <div class="card-body">
                <?php if (!empty($latest_products)): ?>
                    <?php foreach ($latest_products as $product): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                            <div>
                                <div class="fw-bold text-truncate" style="max-width: 150px;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </small>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d/m H:i', strtotime($product['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-3">
                        <a href="products.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>
                            Ver Todos
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nenhum produto cadastrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header card-header-custom">
                <i class="fas fa-hand-holding-usd me-2"></i>
                Itens Vendidos Recentemente
            </div>
            <div class="card-body">
                <?php if (!empty($recently_sold_items)): ?>
                    <?php foreach ($recently_sold_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                            <div>
                                <div class="fw-bold text-truncate" style="max-width: 150px;">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>
                                <small class="badge <?php echo $item['type'] === 'Produto' ? 'bg-info' : 'bg-success'; ?>">
                                    <?php echo htmlspecialchars($item['type']); ?>
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['username'] ?? 'Sistema'); ?>
                                </small>
                            </div>

                            <div class="text-center">
                                <?php if (isset($item['sale_value']) && $item['sale_value'] > 0): ?>
                                    <strong class="text-success">
                                        <?php echo 'R$ ' . number_format($item['sale_value'], 2, ',', '.'); ?>
                                    </strong>
                                <?php endif; ?>
                            </div>

                            <small class="text-muted text-end" style="min-width: 50px;">
                                <?php echo date('d/m', strtotime($item['date'])); ?><br>
                                <?php echo date('H:i', strtotime($item['date'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                     <div class="text-center text-muted py-3">
                        <i class="fas fa-receipt fa-2x mb-2"></i>
                        <p>Nenhuma venda registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header card-header-custom">
                <i class="fas fa-dollar-sign me-2"></i>
                Produtos Mais Caros
            </div>
            <div class="card-body">
                <?php if (!empty($expensive_products)): ?>
                    <?php foreach ($expensive_products as $product): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-truncate" style="max-width: 200px;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </span>
                            <strong class="text-success">
                                <?php echo 'R$ ' . number_format($product['price'], 2, ',', '.'); ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Nenhum produto com preço cadastrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-bolt me-2"></i>
                Ações Rápidas
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="add_product.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            Adicionar Produto
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="scanner.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-qrcode me-2"></i>
                            Scanner QR/Código
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="ready_machines.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-desktop me-2"></i>
                            Máquinas
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="products.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-boxes me-2"></i>
                            Ver Todos Produtos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-chart-line me-2"></i>
                Movimentações dos Últimos 30 Dias
            </div>
            <div class="card-body">
                <canvas id="movementChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Alertas de Estoque
            </div>
            <div class="card-body">
                <?php if (empty($stock_alerts)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p>Nenhum alerta de estoque!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($stock_alerts as $alert): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($alert['name']); ?></h6>
                                        <small class="text-muted">
                                            Atual: <?php echo $alert['quantity']; ?> | 
                                            Min: <?php echo $alert['min_quantity']; ?> | 
                                            Max: <?php echo $alert['max_quantity']; ?>
                                        </small>
                                    </div>
                                    <span class="badge <?php 
                                        echo $alert['quantity'] <= $alert['min_quantity'] ? 'bg-danger' : 'bg-warning'; 
                                    ?>">
                                        <?php echo $alert['quantity'] <= $alert['min_quantity'] ? 'Baixo' : 'Alto'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="export_csv.php?type=low_stock" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-download me-1"></i>
                            Exportar Alertas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-cogs me-2"></i>
                Informações do Sistema
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Estatísticas Gerais:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Total de Usuários:</strong> <?php echo $total_users; ?></li>
                            <li><strong>Produtos Adicionados (7 dias):</strong> <?php echo $recent_products; ?></li>
                            <li><strong>Versão do Sistema:</strong> 1.0</li>
                            <li><strong>Última Atualização:</strong> <?php echo date('d/m/Y'); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Ações Administrativas:</h6>
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Use o menu lateral para acessar as funcionalidades administrativas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// ========================================
// FUNÇÕES AUXILIARES PARA O DASHBOARD
// ========================================

function getStatusBadgeClass($status) {
    $classes = [
        'available' => 'bg-success',
        'in_use' => 'bg-warning text-dark',
        'defective' => 'bg-danger',
        'maintenance' => 'bg-info'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'available' => 'Disponível',
        'in_use' => 'Em Uso',
        'defective' => 'Defeituoso',
        'maintenance' => 'Manutenção'
    ];
    return $texts[$status] ?? ucfirst($status);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualiza automaticamente as estatísticas a cada 5 minutos
    setInterval(function() {
        // Aqui poderia implementar uma atualização AJAX das estatísticas
        console.log('Verificando atualizações...');
    }, 300000); // 5 minutos
    
    // Adiciona tooltips aos cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ========================================
// GRÁFICO DE MOVIMENTAÇÕES
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('movementChart').getContext('2d');
    
    // Dados do PHP para JavaScript
    const movementData = <?php echo json_encode($movement_data); ?>;
    
    // Processa os dados para o gráfico
    const dates = [];
    const entradas = [];
    const saidas = [];
    const ajustes = [];
    
    // Últimos 30 dias
    for (let i = 29; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        dates.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        
        // Busca dados para esta data
        const dayData = movementData.filter(item => item.date === dateStr);
        
        entradas.push(dayData.find(item => item.movement_type === 'entrada')?.total_quantity || 0);
        saidas.push(dayData.find(item => item.movement_type === 'saida')?.total_quantity || 0);
        ajustes.push(dayData.find(item => item.movement_type === 'ajuste')?.total_quantity || 0);
    }
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Entradas',
                    data: entradas,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Saídas',
                    data: saidas,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Ajustes',
                    data: ajustes,
                    borderColor: 'rgb(255, 205, 86)',
                    backgroundColor: 'rgba(255, 205, 86, 0.2)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            }
        }
    });
});
</script>