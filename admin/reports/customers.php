<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('reports');

[$dateFrom, $dateTo, $fromDate, $toDate] = reportDateFilter();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$newClients = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total), 0) AS total_spent,
           MAX(o.created_at) AS last_order
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.created_at BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 50
");
$stmt->execute([$dateFrom, $dateTo]);
$topClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, MAX(o.created_at) AS last_order
    FROM users u
    INNER JOIN orders o ON o.user_id = u.id
    GROUP BY u.id
    HAVING MAX(o.created_at) < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY last_order ASC
    LIMIT 20
");
$stmt->execute();
$inactiveClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalClients = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$clientsWithOrders = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE user_id IS NOT NULL")->fetchColumn();

adminLayoutStart('Rapport clients', 'reports_customers');
reportFilterForm('customers');
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Total clients</div>
            <div class="h3 mb-0"><?= $totalClients ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Nouveaux (période)</div>
            <div class="h3 mb-0 text-primary"><?= $newClients ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Clients actifs (avec commande)</div>
            <div class="h3 mb-0 text-success"><?= $clientsWithOrders ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Top clients (période)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Client</th><th>Email</th><th>Commandes</th><th>CA</th><th>Dernière cmd</th></tr></thead>
                    <tbody>
                    <?php foreach ($topClients as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= (int)$c['order_count'] ?></td>
                            <td><?= formatMoney((float)$c['total_spent']) ?></td>
                            <td><?= $c['last_order'] ? date('d/m/Y', strtotime($c['last_order'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold text-warning">Clients inactifs (+30 jours)</div>
            <div class="list-group list-group-flush">
                <?php if (empty($inactiveClients)): ?>
                    <div class="list-group-item text-muted">Aucun client inactif</div>
                <?php else: ?>
                    <?php foreach ($inactiveClients as $c): ?>
                        <div class="list-group-item">
                            <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small><br>
                            <small>Dernière cmd : <?= date('d/m/Y', strtotime($c['last_order'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
