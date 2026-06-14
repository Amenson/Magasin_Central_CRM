<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : SITE_NAME . ' — ' . SITE_TAGLINE ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars(SITE_DESCRIPTION) ?>">
    <meta name="keywords" content="<?= htmlspecialchars(SITE_KEYWORDS) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="icon" href="assets/images/logo/favicon.ico" type="image/x-icon">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="brand-icon"><i class="bi bi-shop"></i></span>
            <span class="brand-text"><?= htmlspecialchars(SITE_NAME) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i> Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php#products"><i class="bi bi-grid me-1"></i> Produits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php"><i class="bi bi-envelope me-1"></i> Contact</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="my-orders.php"><i class="bi bi-bag-check me-1"></i> Commandes</a>
                </li>
                <?php endif; ?>
                <li class="nav-item position-relative">
                    <a class="nav-link" href="cart.php">
                        <i class="bi bi-cart3 fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-cart">
                            <?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>
                        </span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle me-1"></i> Compte</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm ms-lg-2" href="logout.php">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-nav-primary btn-sm ms-lg-2" href="register.php">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="site-main flex-grow-1">
