<?php
session_start();
require_once 'config.php'; // Connexion PDO
include 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Gestion des messages d'alerte
$alerts = [];

if (isset($_GET['added']) && $_GET['added'] == 1) {
    $alerts[] = ['type' => 'success', 'message' => 'Produit ajouté au panier avec succès !'];
}

if (isset($_GET['rated']) && $_GET['rated'] == 1) {
    $alerts[] = ['type' => 'success', 'message' => 'Merci pour votre avis !'];
}

// Gestion ajout au panier
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = max(1, (int)$_POST['quantity']);

    if ($product['stock'] <= 0) {
        $errorMessage = 'Ce produit est en rupture de stock.';
    } elseif ($quantity > $product['stock']) {
        $errorMessage = "Quantité demandée supérieure au stock disponible ({$product['stock']}).";
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] += $quantity;
        } else {
            $_SESSION['cart'][$id] = $quantity;
        }

        header("Location: product.php?id=$id&added=1");
        exit;
    }

    if ($errorMessage) {
        $alerts[] = ['type' => 'danger', 'message' => $errorMessage];
    }
}

// Gestion de la note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("UPDATE products SET rating_total = rating_total + ?, rating_count = rating_count + 1 WHERE id = ?");
        $stmt->execute([$rating, $id]);

        header("Location: product.php?id=$id&rated=1");
        exit;
    } else {
        $alerts[] = ['type' => 'danger', 'message' => 'Veuillez sélectionner une note valide.'];
    }
}

// Calcul de la note moyenne du produit principal
$ratingCount = $product['rating_count'] ?? 0;
$averageRating = $ratingCount > 0 ? $product['rating_total'] / $ratingCount : 0;
$averageRounded = round($averageRating, 1);
$fullStars = floor($averageRating);
$hasHalfStar = ($averageRating - $fullStars >= 0.5);

// Calcul du stock status
$stockStatus = $product['stock'] > 0 ? 'En stock' : 'Rupture de stock';
$stockBadgeClass = $product['stock'] > 0 ? 'bg-success' : 'bg-danger';
$stockWarning = ($product['stock'] > 0 && $product['stock'] <= 5) ? "Plus que {$product['stock']} en stock !" : '';
$disableAddToCart = $product['stock'] <= 0;

// Recommandations intelligentes
$related = [];
if (!empty($product['category'])) {
    $stmtRelated = $pdo->prepare("
        SELECT * FROM products 
        WHERE category = ? 
          AND id != ? 
          AND stock > 0 
        ORDER BY (rating_total / NULLIF(rating_count, 0)) DESC, rating_count DESC, RAND()
        LIMIT 4
    ");
    $stmtRelated->execute([$product['category'], $id]);
    $related = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="container my-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <?php if (!empty($product['category'])): ?>
            <li class="breadcrumb-item"><a href="products.php?category=<?= urlencode($product['category']) ?>">
                <?= htmlspecialchars(ucfirst($product['category'])) ?>
            </a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
    </ol>
</nav>

<div class="container my-5">
    <!-- Messages d'alerte -->
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
            <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endforeach; ?>

    <div class="row g-5">
        <!-- Image -->
        <div class="col-lg-6">
            <div class="product-image rounded overflow-hidden shadow-sm">
                <img src="<?= htmlspecialchars($product['image'] ?? '/img/placeholder.jpg') ?>"
                     class="img-fluid w-100"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     id="mainProductImage">
            </div>
        </div>

        <!-- Infos -->
        <div class="col-lg-6">
            <h1 class="display-6 fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Note du produit -->
            <div class="mb-4">
                <?php if ($ratingCount > 0): ?>
                    <div class="d-flex align-items-center">
                        <?php for ($i = 0; $i < $fullStars; $i++): ?>
                            <i class="bi bi-star-fill text-warning fs-4"></i>
                        <?php endfor; ?>
                        <?php if ($hasHalfStar): ?>
                            <i class="bi bi-star-half text-warning fs-4"></i>
                        <?php endif; ?>
                        <?php for ($i = 0; $i < (5 - ceil($averageRating)); $i++): ?>
                            <i class="bi bi-star text-warning fs-4"></i>
                        <?php endfor; ?>
                        <span class="ms-3 fw-bold text-dark"><?= $averageRounded ?> / 5</span>
                        <span class="ms-2 text-muted">(<?= $ratingCount ?> avis)</span>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun avis pour le moment. Soyez le premier à noter ce produit !</p>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center mb-3">
                <span class="badge <?= $stockBadgeClass ?> fs-6 px-3 py-2"><?= $stockStatus ?></span>
                <?php if ($stockWarning): ?>
                    <span class="ms-3 text-warning fw-bold"><?= $stockWarning ?></span>
                <?php endif; ?>
            </div>

            <p class="lead fs-3 text-primary fw-bold mb-4">
                <?= number_format($product['price'], 0, ',', ' ') ?> CFA
            </p>

            <p class="text-muted mb-4">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>

            <!-- Formulaire de notation avec étoiles interactives -->
            <form method="post" class="mb-4" id="ratingForm">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <label class="fw-bold mb-0">Votre note :</label>
                    <div id="rating-stars" class="d-flex">
                        <i class="bi bi-star rating-star" data-value="1"></i>
                        <i class="bi bi-star rating-star" data-value="2"></i>
                        <i class="bi bi-star rating-star" data-value="3"></i>
                        <i class="bi bi-star rating-star" data-value="4"></i>
                        <i class="bi bi-star rating-star" data-value="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="selected-rating" value="5">
                    <button type="submit" name="submit_rating" class="btn btn-outline-primary">Envoyer</button>
                </div>
            </form>

            <!-- Formulaire ajout panier -->
            <form method="post" id="addToCartForm">
                <div class="row align-items-end g-3 mb-4">
                    <div class="col-auto">
                        <label for="quantity" class="form-label fw-bold">Quantité</label>
                        <div class="input-group" style="width: 150px;">
                            <button class="btn btn-outline-secondary" type="button" id="decrement">-</button>
                            <input type="number"
                                   name="quantity"
                                   id="quantity"
                                   class="form-control text-center"
                                   value="1"
                                   min="1"
                                   max="<?= $product['stock'] ?>"
                                   <?= $disableAddToCart ? 'disabled' : '' ?>
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" id="increment">+</button>
                        </div>
                    </div>

                    <div class="col-auto">
                        <button type="submit"
                                name="add_to_cart"
                                class="btn btn-primary btn-lg px-5"
                                <?= $disableAddToCart ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus"></i> Ajouter au panier
                        </button>
                    </div>
                </div>
            </form>

            <!-- Infos supplémentaires -->
            <div class="border-top pt-4">
                <small class="text-muted">
                    <i class="bi bi-truck"></i> Livraison rapide partout au Togo<br>
                    <i class="bi bi-shield-check"></i> Paiement sécurisé • Retour sous 7 jours
                </small>
            </div>
        </div>
    </div>

    <!-- Recommandations intelligentes -->
    <?php if (!empty($related)): ?>
        <section class="my-5 py-5 border-top">
            <h3 class="fw-bold mb-4 text-center">
                Produits populaires dans "<?= htmlspecialchars(ucfirst($product['category'])) ?>"
            </h3>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php foreach ($related as $rel): 
                    $relAvg = $rel['rating_count'] > 0 ? $rel['rating_total'] / $rel['rating_count'] : 0;
                    $relRounded = round($relAvg, 1);
                    $relFull = floor($relAvg);
                    $relHalf = ($relAvg - $relFull >= 0.5);
                ?>
                    <div class="col">
                        <div class="card h-100 product-card shadow-sm border-0 hover-shadow">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($rel['image'] ?? '/img/placeholder.jpg') ?>"
                                     class="card-img-top"
                                     alt="<?= htmlspecialchars($rel['name']) ?>"
                                     loading="lazy">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($rel['name']) ?></h5>

                                <?php if ($rel['rating_count'] > 0): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <?php for ($i = 0; $i < $relFull; $i++): ?>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        <?php endfor; ?>
                                        <?php if ($relHalf): ?>
                                            <i class="bi bi-star-half text-warning"></i>
                                        <?php endif; ?>
                                        <?php for ($i = 0; $i < (5 - $relFull - ($relHalf ? 1 : 0)); $i++): ?>
                                            <i class="bi bi-star text-warning"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2 text-muted small"><?= $relRounded ?> (<?= $rel['rating_count'] ?> avis)</span>
                                    </div>
                                <?php endif; ?>

                                <p class="fw-bold fs-5 text-primary mb-3 mt-auto">
                                    <?= number_format($rel['price'], 0, ',', ' ') ?> CFA
                                </p>
                                <a href="product.php?id=<?= $rel['id'] ?>" class="btn btn-outline-primary">
                                    Voir le produit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- JavaScript -->
<script>
    // Gestion quantité
    const quantityInput = document.getElementById('quantity');
    const incrementBtn = document.getElementById('increment');
    const decrementBtn = document.getElementById('decrement');
    const maxStock = <?= $product['stock'] ?>;

    if (incrementBtn && decrementBtn) {
        incrementBtn.addEventListener('click', () => {
            let val = parseInt(quantityInput.value);
            if (val < maxStock) {
                quantityInput.value = val + 1;
            }
        });

        decrementBtn.addEventListener('click', () => {
            let val = parseInt(quantityInput.value);
            if (val > 1) {
                quantityInput.value = val - 1;
            }
        });
    }

    // Système d'étoiles interactives pour la notation
    const ratingStars = document.querySelectorAll('.rating-star');
    const hiddenRatingInput = document.getElementById('selected-rating');
    let currentRating = 5; // Par défaut 5 étoiles

    function updateStars(rating) {
        ratingStars.forEach(star => {
            const value = parseInt(star.dataset.value);
            if (value <= rating) {
                star.classList.replace('bi-star', 'bi-star-fill');
                star.classList.add('filled');
            } else {
                star.classList.replace('bi-star-fill', 'bi-star');
                star.classList.remove('filled');
            }
        });
    }

    ratingStars.forEach(star => {
        star.addEventListener('mouseover', () => {
            updateStars(star.dataset.value);
        });

        star.addEventListener('click', () => {
            currentRating = parseInt(star.dataset.value);
            hiddenRatingInput.value = currentRating;
            updateStars(currentRating);
        });
    });

    const ratingContainer = document.getElementById('rating-stars');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', () => {
            updateStars(currentRating);
        });
    }

    // Initialisation
    updateStars(currentRating);

    // Zoom sur l'image
    const mainImage = document.getElementById('mainProductImage');
    const productImageContainer = document.querySelector('.product-image');

    if (productImageContainer && mainImage) {
        productImageContainer.addEventListener('mousemove', (e) => {
            const { left, top, width, height } = productImageContainer.getBoundingClientRect();
            const x = (e.clientX - left) / width * 100;
            const y = (e.clientY - top) / height * 100;
            mainImage.style.transformOrigin = `${x}% ${y}%`;
        });

        productImageContainer.addEventListener('mouseenter', () => {
            mainImage.style.transform = 'scale(2)';
            productImageContainer.style.cursor = 'zoom-in';
        });

        productImageContainer.addEventListener('mouseleave', () => {
            mainImage.style.transform = 'scale(1)';
        });
    }
</script>

<style>
    .product-image {
        overflow: hidden;
    }
    .product-image img {
        transition: transform 0.4s ease;
    }
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 32px rgba(0,0,0,0.12) !important;
    }
    .hover-shadow {
        transition: all 0.3s ease;
    }

    /* Style des étoiles de notation */
    .rating-star {
        font-size: 2.5rem;
        cursor: pointer;
        color: #ccc;
        transition: color 0.2s ease;
    }
    .rating-star.filled {
        color: #ffc107;
    }
</style>

<?php include 'includes/footer.php'; ?>