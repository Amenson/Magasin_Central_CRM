<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

// Redirection si panier vide
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Votre panier est vide.'];
    header('Location: cart.php');
    exit;
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Gestion des messages flash
if (isset($_SESSION['flash'])) {
    echo "<script>showToast('" . addslashes($_SESSION['flash']['message']) . "', '" . $_SESSION['flash']['type'] . "');</script>";
    unset($_SESSION['flash']);
}

// Récupération et calcul du panier (pour affichage et traitement)
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("
    SELECT id, name, price, stock, image 
    FROM products 
    WHERE id IN ($placeholders)
");
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productsById = [];
foreach ($products as $p) {
    $productsById[$p['id']] = $p;
}

$grandTotal = 0;
$validCart = true; // Vérifie si tous les produits existent et stock suffisant
foreach ($_SESSION['cart'] as $id => $quantity) {
    if (!isset($productsById[$id]) || $productsById[$id]['stock'] < $quantity) {
        $validCart = false;
        break;
    }
    $grandTotal += $productsById[$id]['price'] * $quantity;
}

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validation finale stock
    if (!$validCart) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Certains produits sont indisponibles ou en stock insuffisant.'];
        header('Location: checkout.php');
        exit;
    }

    // Récupération infos client (requises)
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($address) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Veuillez remplir correctement tous les champs.'];
        header('Location: checkout.php');
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? null; // Null pour guest

    try {
        $pdo->beginTransaction();

        // Insertion commande
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (user_id, total, customer_name, customer_email, customer_phone, customer_address, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $grandTotal, $name, $email, $phone, $address]);
        $order_id = $pdo->lastInsertId();

        // Insertion items + mise à jour stock
        foreach ($_SESSION['cart'] as $id => $quantity) {
            $product = $productsById[$id];
            $price   = $product['price'];

            // Item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $id, $quantity, $price]);

            // Décremente stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $id]);
        }

// ... (votre code existant pour insertion commande et commit)

$pdo->commit();

// Vidage panier
unset($_SESSION['cart']);

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => "Commande passée avec succès ! Numéro : #$order_id. Procédez au paiement."
];

// Redirection vers la page paiement
header('Location: payment.php?id=' . $order_id);
exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur lors du traitement de votre commande. Veuillez réessayer.'];
        // Log l'erreur en production : error_log($e->getMessage());
        header('Location: checkout.php');
        exit;
    }
}
?>

<div class="container my-5">
    <h1 class="display-6 fw-bold text-center mb-5">
        <i class="bi bi-credit-card"></i> Finaliser votre commande
    </h1>

    <div class="row g-5">
        <!-- Résumé panier -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Récapitulatif de votre panier</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Prix</th>
                                    <th class="text-end">Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $id => $quantity): 
                                    $p = $productsById[$id] ?? null;
                                    if (!$p) continue;
                                    $subtotal = $p['price'] * $quantity;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/placeholder.jpg') ?>"
                                                     class="rounded shadow-sm me-3"
                                                     style="width: 70px; height: 70px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($p['name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $quantity ?></td>
                                        <td class="text-end"><?= number_format($p['price'], 2, ',', ' ') ?> CFA</td>
                                        <td class="text-end fw-bold"><?= number_format($subtotal, 2, ',', ' ') ?> CFA</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire client + validation -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 100px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations de livraison</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['user_name'] ?? '')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['user_email'] ?? '')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Adresse de livraison <span class="text-danger">*</span></label>
                            <textarea name="address" rows="3" class="form-control" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Total</h5>
                            <h5 class="text-primary mb-0"><?= number_format($grandTotal, 2, ',', ' ') ?> CFA</h5>
                        </div>

                        <button type="submit" name="place_order" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check-circle"></i> Confirmer la commande
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="cart.php" class="text-muted small">
                            <i class="bi bi-arrow-left"></i> Retour au panier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>