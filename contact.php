<?php
session_start();
require_once 'config.php';
$pageTitle = 'Contact — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="container contact-page">
    <div class="page-header">
        <h1>Contactez-nous</h1>
        <p>Notre équipe vous répond sous 24h</p>
    </div>

    <div class="promo-bar rounded mb-4">
        🎉 -15% avec le code <strong><?= SITE_PROMO_CODE ?></strong>
    </div>

    <div class="contact-card">
        <h2 class="fw-bold mb-2">Comment pouvons-nous vous aider ?</h2>
        <p class="text-muted mb-4">Commandes, produits, livraison — choisissez votre canal préféré.</p>

        <div class="d-flex flex-column gap-3 mb-4">
            <a href="mailto:<?= SITE_EMAIL ?>" class="contact-option email">
                <i class="bi bi-envelope"></i> <?= SITE_EMAIL ?>
            </a>
            <a href="tel:<?= preg_replace('/\s+/', '', SITE_PHONE) ?>" class="contact-option phone">
                <i class="bi bi-telephone"></i> <?= SITE_PHONE ?>
            </a>
            <a href="https://wa.me/<?= SITE_WHATSAPP ?>" class="contact-option chat" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Discuter sur WhatsApp
            </a>
        </div>

        <div class="p-3 rounded" style="background:var(--surface-muted)">
            <p class="mb-2"><strong><?= htmlspecialchars(SITE_NAME) ?></strong> — <?= htmlspecialchars(SITE_TAGLINE) ?></p>
            <p class="mb-1 small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(SITE_ADDRESS) ?></p>
            <p class="mb-0 small text-muted">Vos données restent confidentielles.</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
