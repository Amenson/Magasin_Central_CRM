<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

// Redirection si pas d'ID ou ID invalide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID de commande invalide.'];
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Récupération de la commande
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name AS user_name, u.email AS user_email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Commande non trouvée.'];
    header('Location: index.php');
    exit;
}

// Optionnel : Vérification que la commande appartient à l'utilisateur connecté
if (isset($_SESSION['user_id']) && $order['user_id'] !== $_SESSION['user_id']) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Accès refusé à cette commande.'];
    header('Location: index.php');
    exit;
}

// Récupération des articles de la commande
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message flash (succès de la commande)
if (isset($_SESSION['flash'])) {
    echo "<script>showToast('" . addslashes($_SESSION['flash']['message']) . "', '" . $_SESSION['flash']['type'] . "');</script>";
    unset($_SESSION['flash']);
} else {
    // Message par défaut si pas de flash
    echo "<script>showToast('Commande confirmée avec succès ! Merci pour votre achat.', 'success');</script>";
}
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <i class="bi bi-check-circle-fill text-success display-1"></i>
        <h1 class="display-5 fw-bold mt-3">Merci pour votre commande !</h1>
        <p class="lead text-muted">Votre commande a été enregistrée avec succès.</p>
    </div>

    <div class="row g-5">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        Commande #<?= $order_id ?> 
                        <span class="badge bg-light text-dark ms-2"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="bi bi-person"></i> Informations client</h5>
                            <hr>
                            <p><strong>Nom :</strong> <?= htmlspecialchars($order['customer_name'] ?? $order['user_name'] ?? 'Anonyme') ?></p>
                            <p><strong>Email :</strong> <?= htmlspecialchars($order['customer_email'] ?? $order['user_email'] ?? 'N/A') ?></p>
                            <p><strong>Téléphone :</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
                            <p><strong>Adresse :</strong> <?= nl2br(htmlspecialchars($order['customer_address'] ?? 'N/A')) ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5><i class="bi bi-calendar"></i> Détails commande</h5>
                            <hr>
                            <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                            <p><strong>Total payé :</strong> <span class="fs-4 text-success fw-bold"><?= number_format($order['total'], 0, ',', ' ') ?> CFA</span></p>
                        </div>
                    </div>

                    <h5><i class="bi bi-bag-check"></i> Articles commandés</h5>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Quantité</th>
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
                                                <img src="<?= htmlspecialchars($item['image'] ?? 'uploads/placeholder.jpg') ?>"
                                                     class="rounded shadow-sm me-3"
                                                     style="width: 70px; height: 70px; object-fit: cover;">
                                                <span><?= htmlspecialchars($item['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end"><?= number_format($item['price'], 0, ',', ' ') ?> CFA</td>
                                        <td class="text-end fw-bold"><?= number_format($subtotal, 0, ',', ' ') ?> CFA</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success">
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end fs-5"><?= number_format($order['total'], 0, ',', ' ') ?> CFA</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h5><i class="bi bi-info-circle"></i> Prochaines étapes</h5>
                        <p>
                            Nous préparons votre commande. Vous recevrez un email de confirmation bientôt.<br>
                            <strong>Paiement :</strong> Effectuez le paiement par Flooz/TMoney au numéro indiqué par email ou contactez-nous.<br>
                            Suivi : Revenez sur votre compte pour voir le statut de votre commande.
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <a href="payment.php?id=<?= $order_id ?>" class="btn btn-warning btn-lg px-5 me-3">
                        <i class="bi bi-credit-card"></i> Payer maintenant
                         </a>
                        <a href="product.php" class="btn btn-primary btn-lg px-5 me-3">
                            <i class="bi bi-bag"></i> Continuer mes achats
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="my-orders.php" class="btn btn-outline-primary btn-lg px-5">
                                <i class="bi bi-list-check"></i> Mes commandes
                            </a>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>