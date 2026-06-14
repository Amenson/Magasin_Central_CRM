<?php
function adminBasePath(): string
{
    if (str_contains($_SERVER['SCRIPT_NAME'], '/reports/') || str_contains($_SERVER['SCRIPT_NAME'], '/settings/')) {
        return '../';
    }
    return '';
}

function adminAssetPath(): string
{
    return adminBasePath() === '../' ? '../../assets/css/' : '../assets/css/';
}

function adminLayoutStart(string $title, string $activeMenu = 'dashboard'): void
{
    $base = adminBasePath();
    $assets = adminAssetPath();
    $roleLabel = htmlspecialchars($_SESSION['admin_role_label'] ?? 'Admin');
    $username = htmlspecialchars($_SESSION['admin_username'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assets ?>theme.css">
    <link rel="stylesheet" href="<?= $assets ?>admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="<?= $base ?>../assets/images/logo/favicon.ico" type="image/x-icon">
</head>
<body>
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="brand">
            <span class="brand-icon"><i class="bi bi-shop"></i></span>
            <?= SITE_NAME ?>
        </div>
        <nav class="py-2">
            <?php if (adminHasPermission('dashboard')): ?>
            <a href="<?= $base ?>dashboard.php" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
            <?php endif; ?>
            <?php if (adminHasPermission('products')): ?>
            <a href="<?= $base ?>add_product.php" class="<?= $activeMenu === 'products' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Produits
            </a>
            <?php endif; ?>
            <?php if (adminHasPermission('orders')): ?>
            <a href="<?= $base ?>dashboard.php#orders" class="<?= $activeMenu === 'orders' ? 'active' : '' ?>">
                <i class="bi bi-cart-check"></i> Commandes
            </a>
            <?php endif; ?>
            <?php if (adminHasPermission('customers')): ?>
            <a href="<?= $base ?>manage_users.php" class="<?= $activeMenu === 'customers' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Clients
            </a>
            <?php endif; ?>

            <?php if (adminHasPermission('reports')): ?>
            <div class="nav-section">Rapports</div>
            <a href="<?= $base ?>reports/sales.php" class="<?= $activeMenu === 'reports_sales' ? 'active' : '' ?>"><i class="bi bi-graph-up"></i> Ventes</a>
            <a href="<?= $base ?>reports/customers.php" class="<?= $activeMenu === 'reports_customers' ? 'active' : '' ?>"><i class="bi bi-person-lines-fill"></i> Clients</a>
            <a href="<?= $base ?>reports/products.php" class="<?= $activeMenu === 'reports_products' ? 'active' : '' ?>"><i class="bi bi-bar-chart"></i> Produits</a>
            <?php endif; ?>

              <div class="nav-section">Messagerie</div>
            <a href="../messaging/admin/index.php"><i class="bi bi-envelope"></i> Messagerie</a>
             <a href="../messaging/admin/view.php"><i class="bi bi-eye"></i> Voir les messages</a>
            
            

            <?php if (adminHasPermission('settings.admins') || adminHasPermission('settings.roles') || adminHasPermission('settings.store')): ?>
            <div class="nav-section">Paramètres</div>
            <?php if (adminHasPermission('settings.admins')): ?>
            <a href="<?= $base ?>settings/admins.php" class="<?= $activeMenu === 'settings_admins' ? 'active' : '' ?>"><i class="bi bi-shield-lock"></i> Admins</a>
            <?php endif; ?>
            <?php if (adminHasPermission('settings.roles')): ?>
            <a href="<?= $base ?>settings/roles.php" class="<?= $activeMenu === 'settings_roles' ? 'active' : '' ?>"><i class="bi bi-key"></i> Rôles</a>
            <?php endif; ?>
            <?php if (adminHasPermission('settings.store')): ?>
            <a href="<?= $base ?>settings/store.php" class="<?= $activeMenu === 'settings_store' ? 'active' : '' ?>"><i class="bi bi-gear"></i> Magasin</a>
            <?php endif; ?>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="h5 mb-0"><?= htmlspecialchars($title) ?></h1>
            <div class="d-flex align-items-center gap-3">
                <span class="badge rounded-pill" style="background:var(--gradient-hero)"><?= $roleLabel ?></span>
                <span class="text-muted small d-none d-md-inline"><i class="bi bi-person"></i> <?= $username ?></span>
                <a href="<?= $base ?>logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Déconnexion</span>
                </a>
            </div>
        </header>
        <main class="admin-content">
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>
    <?php
}

function adminLayoutEnd(): void
{
    ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= adminBasePath() ?>assets/js/admin.js"></script>
</body>
</html>
    <?php
}

function reportFilterForm(string $exportType): void
{
    [$from, $to, $fromDate, $toDate] = reportDateFilter();
    $canExport = adminHasPermission('reports.export');
    ?>
    <div class="filter-bar">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Du</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Au</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
            </div>
            <?php if ($canExport): ?>
            <div class="col-md-3 text-md-end">
                <a href="export.php?type=<?= urlencode($exportType) ?>&date_from=<?= urlencode($fromDate) ?>&date_to=<?= urlencode($toDate) ?>" class="btn btn-success">
                    <i class="bi bi-download"></i> Exporter CSV
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <?php
}
