<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

// Redirection si pas de commande récente ou ID invalide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID de commande invalide.'];
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Récupération de la commande pour affichage
$stmt = $pdo->prepare("SELECT id, total, status, customer_name, customer_phone FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Commande non trouvée.'];
    header('Location: index.php');
    exit;
}

// Numéros de paiement mobile money (à configurer dans config.php ou base de données)
define('FLOOZ_NUMBER', '99 75 78 11');    // Moov Money (Flooz)
define('TMONEY_NUMBER', '93 81 46 45');   // Togocom (TMoney)

// Message toast
echo "<script>showToast('Veuillez effectuer le paiement pour valider votre commande #$order_id', 'info');</script>";
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <i class="bi bi-credit-card display-1 text-primary"></i>
        <h1 class="display-5 fw-bold mt-3">Paiement de la commande #<?= $order_id ?></h1>
        <p class="lead text-muted">Montant total : <strong class="text-primary fs-4"><?= number_format($order['total'], 0, ',', ' ') ?> FCFA</strong></p>
    </div>

    <div class="row g-5 justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-phone"></i> Paiement par Mobile Money (recommandé)</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info text-center">
                        <strong>Effectuez le paiement du montant exact via Flooz ou TMoney</strong><br>
                        Utilisez la référence : <span class="badge bg-dark fs-5">CMD<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
                    </div>

                    <div class="row g-4">
                        <!-- Flooz (Moov) -->
                        <div class="col-md-6">
                            <div class="card h-100 text-center border-primary">
                                <div class="card-body">
                                    <img src="assets/images/flooz.jpg" alt="Flooz" class="mb-3" style="height: 60px;">
                                    <h5>Flooz (Moov Money)</h5>
                                    <p class="fw-bold fs-4 text-primary"><?= FLOOZ_NUMBER ?></p>
                                    <ol class="text-start small mt-3">
                                        <li>*155*4*1#</li>
                                        <li>Entrez le numéro : <?= FLOOZ_NUMBER ?></li>
                                        <li>Montant : <?= number_format($order['total'], 0, ',', ' ') ?> FCFA</li>
                                        <li>Référence : CMD<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></li>
                                        <li>Validez avec votre code PIN</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- TMoney (Togocom) -->
                        <div class="col-md-6">
                            <div class="card h-100 text-center border-success">
                                <div class="card-body">
                                    <img src="assets/images/tmoney.jpg" alt="TMoney" class="mb-3" style="height: 60px;">
                                    <h5>TMoney (Togocom)</h5>
                                    <p class="fw-bold fs-4 text-success"><?= TMONEY_NUMBER ?></p>
                                    <ol class="text-start small mt-3">
                                        <li>*145*5*1#</li>
                                        <li>Entrez le numéro : <?= TMONEY_NUMBER ?></li>
                                        <li>Montant : <?= number_format($order['total'], 0, ',', ' ') ?> FCFA</li>
                                        <li>Référence : CMD<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></li>
                                        <li>Validez avec votre code PIN</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Important :</strong> Après paiement, nous vérifierons manuellement et mettrons à jour le statut de votre commande (vous recevrez une notification par email/SMS).
                    </div>

                    <div class="text-center mt-4">
                        <a href="order_confirmation.php?id=<?= $order_id ?>" class="btn btn-outline-secondary btn-lg me-3">
                            <i class="bi bi-arrow-left"></i> Retour aux détails
                        </a>
                        <a href="product.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-bag"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
            </div>

            <!-- Option paiement à la livraison (COD) -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-secondary text-white text-center">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Paiement à la livraison</h5>
                </div>
                <div class="card-body text-center">
                    <p>Payez en espèces lors de la réception de votre commande (disponible à Lomé et environs).</p>
                    <p class="text-muted small">Frais supplémentaires possibles selon la zone.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>