<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('orders');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID de commande invalide.'];
    header('Location: dashboard.php');
    exit;
}

$order_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Commande non trouvee.'];
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $validStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];

    if (in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $order_id]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Statut de la commande mis a jour : $newStatus"];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Statut invalide.'];
    }

    header("Location: view_order.php?id=$order_id");
    exit;
}

$statusLabels = [
    'pending' => 'En attente',
    'paid' => 'Payee',
    'shipped' => 'Expediee',
    'delivered' => 'Livree',
    'cancelled' => 'Annulee',
];

adminLayoutStart("Commande #$order_id", 'orders');
?>

<div class="pro-card mb-4">
    <div class="card-header-pro d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-receipt me-2"></i> Commande #<?= $order_id ?></span>
        <a href="dashboard.php#orders" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <h5>Informations client</h5>
                <hr>
                <p><strong>Nom :</strong> <?= htmlspecialchars($order['customer_name'] ?? $order['user_name'] ?? 'Anonyme') ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($order['customer_email'] ?? $order['user_email'] ?? 'N/A') ?></p>
                <p><strong>Telephone :</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
                <p><strong>Adresse :</strong> <?= nl2br(htmlspecialchars($order['customer_address'] ?? 'N/A')) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <h5>Statut actuel</h5>
                <hr>
                <span class="order-status status-<?= htmlspecialchars(strtolower($order['status'])) ?>">
                    <?= htmlspecialchars($statusLabels[$order['status']] ?? ucfirst(str_replace('_', ' ', $order['status']))) ?>
                </span>
                <p class="mt-3"><strong>Date :</strong> <?= date('d/m/Y a H:i', strtotime($order['created_at'])) ?></p>
                <p><strong>Total :</strong> <?= number_format($order['total'], 2, '', ' ') ?> CFA</p>
            </div>
        </div>

        <h5>Articles commandes</h5>
        <hr>
        <div class="admin-table-wrap">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Quantite</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-end">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../<?= htmlspecialchars($item['image'] ?? 'uploads/placeholder.jpg') ?>"
                                             class="rounded shadow-sm me-3"
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             alt="">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                <td class="text-end"><?= number_format($item['price'], 2, '', ' ') ?> CFA</td>
                                <td class="text-end fw-bold"><?= number_format($subtotal, 2, '', ' ') ?> CFA</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-primary">
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end"><?= number_format($order['total'], 2, '', ' ') ?> CFA</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h5 class="mt-4">Validation / Mise a jour du statut</h5>
        <hr>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Nouveau statut</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Mettre a jour le statut
                </button>
            </div>
        </form>
    </div>
</div>

<?php adminLayoutEnd(); ?>
