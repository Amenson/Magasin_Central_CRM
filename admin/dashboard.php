<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('dashboard');

// Comptage commandes par statut pour le graphique
$statusCounts = [
    'pending'   => 0,
    'paid'      => 0,
    'shipped'   => 0,
    'cancelled' => 0,
];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
}

// Récupérer tous les produits
$stmt = $pdo->query("SELECT id, name, price, stock, image, category FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les commandes avec infos client
$stmt = $pdo->prepare("
    SELECT o.id, o.total, o.status, o.created_at, o.user_id,
           u.name AS client_name, u.email AS client_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion AJAX pour mise à jour du statut (même page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    $validStatuses = ['pending', 'paid', 'shipped', 'cancelled'];
    if ($orderId > 0 && in_array($newStatus, $validStatuses)) {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $success = $updateStmt->execute([$newStatus, $orderId]);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    }
    exit;
}

adminLayoutStart('Tableau de bord', 'dashboard');
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card stat-primary text-center p-3">
            <div class="stat-label">Produits</div>
            <div class="stat-value text-primary"><?= count($products) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-success text-center p-3">
            <div class="stat-label">Commandes</div>
            <div class="stat-value text-success"><?= count($orders) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-info text-center p-3">
            <div class="stat-label">Payées</div>
            <div class="stat-value" style="color:var(--brand-accent)"><?= $statusCounts['paid'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-warning text-center p-3">
            <div class="stat-label">En attente</div>
            <div class="stat-value text-warning"><?= $statusCounts['pending'] ?? 0 ?></div>
        </div>
    </div>
</div>

<?php if (adminHasPermission('products')): ?>
<div class="admin-table-wrap mb-4">
    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam"></i> Produits</h5>
        <div class="d-flex gap-2">
            <input type="text" id="searchProduct" class="form-control form-control-sm" style="max-width:220px" placeholder="Rechercher...">
            <a href="add_product.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Ajouter</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="productsTable">
            <thead><tr><th>ID</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><img src="../<?= htmlspecialchars($p['image'] ?? 'uploads/placeholder.jpg') ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($p['category'] ?? '—')) ?></td>
                    <td><?= number_format($p['price'], 0, ',', ' ') ?> CFA</td>
                    <td><span class="badge <?= $p['stock'] <= 5 ? 'bg-danger' : 'bg-secondary' ?>"><?= $p['stock'] ?></span></td>
                    <td>
                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (adminHasPermission('orders')): ?>
<div class="admin-table-wrap mb-4" id="orders">
    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check"></i> Commandes</h5>
        <select id="statusFilter" class="form-select form-select-sm" style="max-width:180px">
            <option value="all">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="paid">Payée</option>
            <option value="shipped">Expédiée</option>
            <option value="cancelled">Annulée</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="ordersTable">
            <thead><tr><th>ID</th><th>Client</th><th>Total</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr data-status="<?= strtolower($o['status']) ?>">
                    <td>#<?= $o['id'] ?></td>
                    <td><strong><?= htmlspecialchars($o['client_name'] ?? 'Anonyme') ?></strong><br><small class="text-muted"><?= htmlspecialchars($o['client_email'] ?? '') ?></small></td>
                    <td><?= number_format($o['total'], 0, ',', ' ') ?> CFA</td>
                    <td>
                        <select class="form-select form-select-sm badge-status status-<?= strtolower($o['status']) ?> status-select" data-order-id="<?= $o['id'] ?>" data-current="<?= strtolower($o['status']) ?>">
                            <option value="pending" <?= strtolower($o['status']) === 'pending' ? 'selected' : '' ?>>En attente</option>
                            <option value="paid" <?= strtolower($o['status']) === 'paid' ? 'selected' : '' ?>>Payée</option>
                            <option value="shipped" <?= strtolower($o['status']) === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                            <option value="cancelled" <?= strtolower($o['status']) === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
                        </select>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td><a href="view_order.php?id=<?= $o['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-center mb-3"><i class="bi bi-pie-chart"></i> Répartition par statut</h5>
        <canvas id="statusChart" height="80"></canvas>
    </div>
</div>

<div id="toastContainer"></div>

<script>
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} toast-notification`;
    toast.innerHTML = `<strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}`;
    document.getElementById('toastContainer').appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 4000);
}
document.getElementById('searchProduct')?.addEventListener('keyup', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none';
    });
});
document.getElementById('statusFilter')?.addEventListener('change', function() {
    document.querySelectorAll('#ordersTable tbody tr').forEach(r => {
        r.style.display = (this.value === 'all' || r.dataset.status === this.value) ? '' : 'none';
    });
});
const ctx = document.getElementById('statusChart');
if (ctx) {
    const totalCommands = <?= array_sum($statusCounts) ?>;
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Payées', 'Expédiées', 'Annulées'],
            datasets: [{ data: [<?= $statusCounts['pending'] ?? 0 ?>, <?= $statusCounts['paid'] ?? 0 ?>, <?= $statusCounts['shipped'] ?? 0 ?>, <?= $statusCounts['cancelled'] ?? 0 ?>], backgroundColor: ['#f59e0b','#10b981','#06b6d4','#ef4444'], borderWidth: 0 }]
        },
        options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });
    const statusMap = { pending: 0, paid: 1, shipped: 2, cancelled: 3 };
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId, newStatus = this.value, row = this.closest('tr'), oldStatus = row.dataset.status;
            if (oldStatus === newStatus) return;
            this.disabled = true;
            fetch('update_order_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: orderId, new_status: newStatus }) })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    row.dataset.status = newStatus;
                    this.className = `form-select form-select-sm badge-status status-${newStatus} status-select`;
                    if (statusMap[oldStatus] !== undefined) { chart.data.datasets[0].data[statusMap[oldStatus]]--; chart.data.datasets[0].data[statusMap[newStatus]]++; chart.update(); }
                    showToast(`Commande #${orderId} mise à jour`);
                } else { showToast(data.message || 'Erreur', 'danger'); this.value = oldStatus; }
            }).catch(() => { showToast('Erreur connexion', 'danger'); this.value = oldStatus; })
            .finally(() => { this.disabled = false; });
        });
    });
}
</script>

<?php adminLayoutEnd(); ?>