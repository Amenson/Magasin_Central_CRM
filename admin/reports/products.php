<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('reports');

[$dateFrom, $dateTo, $fromDate, $toDate] = reportDateFilter();
$lowStock = (int)getSiteConfig($pdo, 'low_stock_threshold');

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.category, p.stock, p.price,
           COALESCE(SUM(oi.quantity), 0) AS qty_sold,
           COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY revenue DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalProducts = count($products);
$lowStockCount = count(array_filter($products, fn($p) => (int)$p['stock'] <= $lowStock));
$totalStockValue = array_sum(array_map(fn($p) => (float)$p['price'] * (int)$p['stock'], $products));
$top5 = array_slice($products, 0, 5);

adminLayoutStart('Rapport produits', 'reports_products');
reportFilterForm('products');
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Produits catalogue</div>
            <div class="h3 mb-0"><?= $totalProducts ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Stock critique (≤ <?= $lowStock ?>)</div>
            <div class="h3 mb-0 text-danger"><?= $lowStockCount ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center p-3">
            <div class="text-muted small">Valeur stock</div>
            <div class="h5 mb-0"><?= formatMoney($totalStockValue) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Top 5 produits (CA période)</div>
            <div class="card-body"><canvas id="productsChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Performance produits</div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>Produit</th><th>Catégorie</th><th>Stock</th><th>Vendus</th><th>CA</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr class="<?= (int)$p['stock'] <= $lowStock ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($p['category'] ?? '—')) ?></td>
                            <td><span class="badge <?= (int)$p['stock'] <= $lowStock ? 'bg-danger' : 'bg-secondary' ?>"><?= (int)$p['stock'] ?></span></td>
                            <td><?= (int)$p['qty_sold'] ?></td>
                            <td><?= formatMoney((float)$p['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('productsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($top5, 'name')) ?>,
        datasets: [{
            label: 'CA FCFA',
            data: <?= json_encode(array_map('floatval', array_column($top5, 'revenue'))) ?>,
            backgroundColor: '#6366f1'
        }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});
</script>

<?php adminLayoutEnd(); ?>
