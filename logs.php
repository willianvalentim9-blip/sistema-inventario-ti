<?php
require_once 'config.php';
requireAdmin(); // Apenas administradores podem acessar esta página

$page_title = 'Logs do Sistema';

$logs = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT sl.*, u.username FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar logs do sistema: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-clipboard-list me-2"></i>
        Logs do Sistema
    </h1>
</div>

<div class="card card-custom mb-4">
    <div class="card-header card-header-custom">
        <i class="fas fa-history me-2"></i>
        Últimas Atividades do Sistema
    </div>
    <div class="card-body">
        <?php if (!empty($logs)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Tabela</th>
                            <th>ID do Registro</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['record_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nenhum log de sistema encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

