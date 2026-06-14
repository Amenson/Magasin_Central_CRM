<?php
session_start();
require_once 'config.php'; // votre connexion PDO
include 'includes/header.php';

// Paramètres de pagination
$perPage = 12;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

// Paramètres de tri (whitelist pour sécurité)
$sortOptions = [
    'newest'     => 'created_at DESC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'name'       => 'name ASC',
];
$sort = $_GET['sort'] ?? 'newest';
$orderBy = $sortOptions[$sort] ?? 'created_at DESC';

// Catégories autorisées (slug => nom affiché)
$categoryMap = [
    'electronique' => 'Électronique',
    'mode'        => 'Mode',
    'maison'      => 'Maison',
    'loisirs'     => 'Loisirs',
];
$categoryIcons = [
    'electronique' => 'phone',
    'mode'        => 'shirt',
    'maison'      => 'house',
    'loisirs'     => 'controller',
];
$categorySlug = $_GET['category'] ?? '';
$categoryName = $categoryMap[$categorySlug] ?? '';

// Recherche
$search = trim($_GET['search'] ?? '');

// Construction sécurisée de la clause WHERE et des paramètres
// Construction sécurisée de la clause WHERE et des paramètres (PARAMÈTRES NOMMÉS)
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE (name LIKE :search OR description LIKE :search2)";
    $params['search']  = "%$search%";
    $params['search2'] = "%$search%";
}

if ($categorySlug !== '' && $categoryName !== '') {
    $where .= $where ? " AND category = :category" : "WHERE category = :category";
    $params['category'] = $categorySlug;
}


// Requête COUNT pour pagination
$countSql = "SELECT COUNT(*) FROM products " . $where;
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalProducts = $stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalProducts / $perPage));

// Requête produits principaux (liste paginée)
$sql = "SELECT * FROM products 
        $where 
        ORDER BY $orderBy 
        LIMIT :offset, :perPage";

$stmt = $pdo->prepare($sql);

// Bind des paramètres dynamiques (search, category)
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}

// Bind pagination
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);

$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Requête produits vedettes (8 plus récents, indépendants des filtres)
$featuredSql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8";
$stmtFeatured = $pdo->prepare($featuredSql);
$stmtFeatured->execute();
$featuredProducts = $stmtFeatured->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour afficher une carte produit (évite la duplication de code)
function displayProductCard($product) {
    ?>
    <div class="col">
        <div class="card h-100 product-card shadow-sm border-0 hover-shadow">
            <div class="position-relative">
                <img src="<?= htmlspecialchars($product['image'] ?? '/img/placeholder.jpg') ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     loading="lazy">
                <?php if (isset($product['stock']) && $product['stock'] <= 0): ?>
                    <span class="position-absolute top-0 end-0 badge bg-danger m-2">Rupture</span>
                <?php elseif (isset($product['stock']) && $product['stock'] <= 5): ?>
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Stock faible !</span>
                <?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text text-muted flex-grow-1 small">
                    <?= htmlspecialchars(substr($product['description'] ?? '', 0, 80)) ?>...
                </p>
                <div class="mt-auto">
                    <p class="product-price mb-2">
                        <?= number_format($product['price'], 0, ',', ' ') ?> CFA
                    </p>
                    <a href="product.php?id=<?= (int)$product['id'] ?>" class="btn btn-primary w-100">
                        Voir le produit
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Construction de la query string pour pagination (préserve les filtres)
$queryParams = array_filter([
    'search'   => $search ?: null,
    'sort'     => $sort,
    'category' => $categorySlug ?: null,
]);
$queryString = http_build_query($queryParams);
$baseUrl = $queryString ? '?' . $queryString . '&' : '?';
?>
<!-- Promo -->
<div class="promo-bar">
    🎉 -15% sur votre première commande — code <strong><?= SITE_PROMO_CODE ?></strong>
</div>

<!-- Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="hero-title"><?= htmlspecialchars(SITE_HERO_TITLE) ?></h1>
                <p class="hero-subtitle mt-3"><?= htmlspecialchars(SITE_HERO_TEXT) ?></p>
                <div class="hero-cta">
                    <a href="#products" class="btn btn-primary btn-lg me-2"><i class="bi bi-grid me-1"></i> Voir les produits</a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">Nous contacter</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/newletter.png" class="img-fluid" alt="<?= htmlspecialchars(SITE_NAME) ?>" style="max-height:340px">
            </div>
        </div>
    </div>
</section>

<!-- Catégories -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center">Nos catégories</h2>
        <p class="section-subtitle text-center">Parcourez par univers</p>
        <div class="row g-4">
            <?php foreach ($categoryMap as $slug => $name): ?>
            <div class="col-6 col-md-3">
                <a href="?category=<?= $slug ?>" class="text-decoration-none">
                    <div class="category-card <?= $categorySlug === $slug ? 'active' : '' ?>">
                        <div class="cat-icon"><i class="bi bi-<?= $categoryIcons[$slug] ?>"></i></div>
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($name) ?></h6>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Produits -->
<section class="py-5 bg-white" id="products">
<div class="container">
    <h2 class="section-title text-center"><?= $categoryName ? htmlspecialchars($categoryName) : 'Nos produits' ?></h2>
    <p class="section-subtitle text-center">Qualité et prix accessibles</p>

    <!-- Barre de recherche + tri (un seul formulaire) -->
    <form method="GET" class="row g-3 align-items-center justify-content-between mb-4">
        <?php if ($categorySlug): ?>
            <input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>">
        <?php endif; ?>
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="col-md-4">
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nom A → Z</option>
            </select>
        </div>
    </form>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center py-5">
            <h4>Aucun produit trouvé</h4>
            <p>Essayez d'autres mots-clés ou modifiez les filtres.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <?= displayProductCard($product) ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination produits" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>page=<?= $page-1 ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end   = min($totalPages, $page + $range);
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>page=<?= $page+1 ?>" aria-label="Suivant">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
</section>

<!-- Avantages -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3"><div class="feature-box text-center"><div class="feature-icon"><i class="bi bi-truck"></i></div><h6 class="fw-bold">Livraison rapide</h6><p class="text-muted small mb-0">Partout au Togo</p></div></div>
            <div class="col-md-3"><div class="feature-box text-center"><div class="feature-icon"><i class="bi bi-shield-check"></i></div><h6 class="fw-bold">Paiement sécurisé</h6><p class="text-muted small mb-0">Flooz & TMoney</p></div></div>
            <div class="col-md-3"><div class="feature-box text-center"><div class="feature-icon"><i class="bi bi-arrow-repeat"></i></div><h6 class="fw-bold">Retour facile</h6><p class="text-muted small mb-0">Sous 7 jours</p></div></div>
            <div class="col-md-3"><div class="feature-box text-center"><div class="feature-icon"><i class="bi bi-headset"></i></div><h6 class="fw-bold">Support 24/7</h6><p class="text-muted small mb-0"><?= SITE_PHONE ?></p></div></div>
        </div>
    </div>
</section>

<!-- Vedettes -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title text-center">Produits vedettes</h2>
        <p class="section-subtitle text-center">Les coups de cœur du moment</p>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <?= displayProductCard($product) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center">Pourquoi choisir <?= htmlspecialchars(SITE_NAME) ?> ?</h2>
        <div class="faq-container mx-auto" style="max-width:720px">
        <div class="faq-item">
            <div class="faq-question">Quels types de produits vendez-vous ?</div>
            <div class="faq-answer">
                Nous proposons une large gamme de produits dans les catégories électronique, mode, maison et loisirs.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Puis-je payer à la livraison ?</div>
            <div class="faq-answer">
                Oui, le paiement à la livraison est disponible partout au Togo. Sélectionnez cette option lors du paiement.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Si le produit ne me convient pas, que faire ?</div>
            <div class="faq-answer">
                Retour gratuit sous 7 jours pour les produits non utilisés dans leur emballage d'origine. Connectez-vous à votre compte pour initier le retour.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Comment vous contacter en cas de besoin ?</div>
            <div class="faq-answer">
                Notre service client est disponible 24/7 par téléphone au <?= SITE_PHONE ?>, par e-mail à <?= SITE_EMAIL ?> ou via le chat en direct.
            </div>
        </div>
    </div>
</section>

<div class="whatsapp-bubble">
    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=Bonjour" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg class="whatsapp-icon" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.884 3.488"/></svg>
    </a>
</div>

<script>
    // Accordion FAQ (une seule section ouverte à la fois)
    document.querySelectorAll('.faq-question').forEach(item => {
        item.addEventListener('click', () => {
            const answer = item.nextElementSibling;
            const isActive = answer.classList.contains('show');

            // Fermer toutes les réponses
            document.querySelectorAll('.faq-answer').forEach(ans => ans.classList.remove('show'));
            document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));

            // Ouvrir la section cliquée si elle n'était pas ouverte
            if (!isActive) {
                answer.classList.add('show');
                item.classList.add('active');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>