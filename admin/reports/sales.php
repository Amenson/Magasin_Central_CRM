<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('reports');

[$dateFrom, $dateTo, $fromDate, $toDate] = reportDateFilter();

// KPI ventes
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_orders,
           COALESCE(SUM(total), 0) AS revenue,
           COALESCE(AVG(total), 0) AS avg_order
    FROM orders
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$dateFrom, $dateTo]);
$kpi = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total),0) AS amount FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$dateFrom, $dateTo]);
$byStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$revenue = 0;
$cancelled = 0;
foreach ($byStatus as $row) {
    if (isCancelledStatus($row['status'])) {
        $cancelled += (int)$row['cnt'];
    } elseif (isRevenueStatus($row['status']) || !isCancelledStatus($row['status'])) {
        $revenue += (float)$row['amount'];
    }
}

$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS day_revenue
    FROM orders
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT o.id, o.total, o.status, o.created_at,
           COALESCE(o.customer_name, u.name, 'Anonyme') AS client_name,
           o.customer_email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT 100
");
$stmt->execute([$dateFrom, $dateTo]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

adminLayoutStart('Rapport des ventes', 'reports_sales');
reportFilterForm('sales');
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Commandes</div>
            <div class="h3 mb-0 text-primary"><?= (int)$kpi['total_orders'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Chiffre d'affaires</div>
            <div class="h5 mb-0 text-success"><?= formatMoney((float)$revenue) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Panier moyen</div>
            <div class="h5 mb-0"><?= formatMoney((float)$kpi['avg_order']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Annulées</div>
            <div class="h3 mb-0 text-danger"><?= $cancelled ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Évolution des ventes</div>
            <div class="card-body"><canvas id="salesChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Par statut</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Statut</th><th>Nb</th><th>Montant</th></tr></thead>
                    <tbody>
                    <?php foreach ($byStatus as $s): ?>
                        <tr>
                            <td><?= orderStatusLabel($s['status']) ?></td>
                            <td><?= (int)$s['cnt'] ?></td>
                            <td><?= formatMoney((float)$s['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-header bg-white fw-bold">Dernières commandes (100 max)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Statut</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['client_name']) ?><br><small class="text-muted"><?= htmlspecialchars($o['customer_email'] ?? '') ?></small></td>
                    <td><?= formatMoney((float)$o['total']) ?></td>
                    <td><span class="badge bg-secondary"><?= orderStatusLabel($o['status']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($daily, 'day')) ?>,
        datasets: [{
            label: 'CA (FCFA)',
            data: <?= json_encode(array_map('floatval', array_column($daily, 'day_revenue'))) ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,.15)',
            fill: true,
            tension: .3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<?php adminLayoutEnd(); ?>
