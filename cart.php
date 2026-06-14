<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

// Gestion des messages flash (pour toasts)
$flash = $_SESSION['cart_flash'] ?? null;
unset($_SESSION['cart_flash']);

if ($flash) {
    echo "<script>showToast('" . addslashes($flash['message']) . "', '" . $flash['type'] . "');</script>";
}

// Gestion des actions POST (sécurisé)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['id'])) {
        $id = (int)$_POST['id'];

        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart_flash'] = ['type' => 'danger', 'message' => 'Produit introuvable dans le panier.'];
            header('Location: cart.php');
            exit;
        }

        switch ($_POST['action']) {
            case 'remove':
                unset($_SESSION['cart'][$id]);
                $_SESSION['cart_flash'] = ['type' => 'success', 'message' => 'Produit supprimé du panier.'];
                break;

            case 'update':
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));

                // Vérification stock
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $available = $stmt->fetchColumn() ?: 0;

                if ($quantity > $available) {
                    $quantity = $available;
                    $_SESSION['cart_flash'] = ['type' => 'warning', 'message' => "Quantité limitée au stock disponible ($available)."];
                }

                $_SESSION['cart'][$id] = $quantity;
                $_SESSION['cart_flash'] = ['type' => 'success', 'message' => 'Panier mis à jour.'];
                break;
        }

        if (empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }

        header('Location: cart.php');
        exit;
    }
}

// Récupération des articles du panier
$cart = $_SESSION['cart'] ?? [];
$empty = empty($cart);
$grandTotal = 0;

if (!$empty) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productsById = [];
    foreach ($products as $p) {
        $productsById[$p['id']] = $p;
    }
}
?>

<div class="page-shell">
<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-cart3 text-primary"></i> Mon panier</h1>
        <?php if (!$empty): ?><p><?= array_sum($cart) ?> article(s)</p><?php endif; ?>
    </div>

    <?php if ($empty): ?>
        <div class="empty-state">
            <i class="bi bi-bag-x display-1 text-muted mb-4"></i>
            <h3 class="text-muted">Votre panier est vide</h3>
            <p class="text-muted mb-4">Ajoutez des produits pour continuer vos achats.</p>
            <a href="product.php" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-shop"></i> Découvrir les produits
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Articles -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-center">Quantité</th>
                                        <th class="text-end">Prix</th>
                                        <th class="text-end">Sous-total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart as $id => $qty): 
                                        $p = $productsById[$id] ?? null;
                                        if (!$p) {
                                            unset($_SESSION['cart'][$id]);
                                            continue;
                                        }
                                        $subtotal = $p['price'] * $qty;
                                        $grandTotal += $subtotal;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/placeholder.jpg') ?>"
                                                         class="rounded me-3 shadow-sm"
                                                         style="width: 80px; height: 80px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($p['name']) ?></h6>
                                                        <?php if ($p['stock'] < $qty): ?>
                                                            <small class="text-danger">Stock insuffisant</small>
                                                        <?php elseif ($p['stock'] <= 5): ?>
                                                            <small class="text-warning">Stock faible (<?= $p['stock'] ?>)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <form method="POST" class="d-inline-flex align-items-center">
                                                    <input type="hidden" name="id" value="<?= $id ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <div class="input-group" style="width: 140px;">
                                                        <button type="submit" name="quantity" value="<?= $qty - 1 ?>" class="btn btn-outline-secondary <?= $qty <= 1 ? 'disabled' : '' ?>">-</button>
                                                        <input type="text" class="form-control text-center" value="<?= $qty ?>" readonly>
                                                        <button type="submit" name="quantity" value="<?= $qty + 1 ?>" class="btn btn-outline-secondary <?= $qty >= $p['stock'] ? 'disabled' : '' ?>">+</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="text-end"><?= number_format($p['price'], 2, '', ' ') ?> CFA</td>
                                            <td class="text-end fw-bold"><?= number_format($subtotal, 2, '', ' ') ?> CFA</td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Supprimer cet article ?')">
                                                    <input type="hidden" name="id" value="<?= $id ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Récapitulatif</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Sous-total</span>
                            <strong><?= number_format($grandTotal, 2, '', ' ') ?> CFA</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <h5>Total</h5>
                            <h5 class="text-primary"><?= number_format($grandTotal, 2, '', ' ') ?> CFA</h5>
                        </div>
                        <a href="checkout.php" class="btn btn-success btn-lg w-100 mb-3">
                            <i class="bi bi-lock"></i> Valider la commande
                        </a>
                        <a href="product.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<?php include 'includes/footer.php'; ?>