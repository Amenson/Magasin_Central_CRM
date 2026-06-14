<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('settings.store');

$success = '';
$fields = [
    'site_name'           => 'Nom du magasin',
    'site_tagline'        => 'Slogan',
    'site_description'    => 'Description (SEO)',
    'site_email'          => 'Email contact',
    'site_phone'          => 'Téléphone',
    'site_whatsapp'       => 'WhatsApp (sans +)',
    'site_address'        => 'Adresse',
    'site_promo_code'     => 'Code promo',
    'currency'            => 'Devise',
    'tax_rate'            => 'Taux TVA (%)',
    'low_stock_threshold' => 'Seuil alerte stock',
    'flooz_number'        => 'Numéro Flooz',
    'tmoney_number'       => 'Numéro TMoney',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $label) {
        $value = trim($_POST[$key] ?? '');
        setStoreSetting($pdo, $key, $value);
    }
    $success = 'Configuration du magasin enregistrée.';
}

$values = [];
foreach (array_keys($fields) as $key) {
    $values[$key] = getSiteConfig($pdo, $key);
}

adminLayoutStart('Configuration magasin', 'settings_store');
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card stat-card">
    <div class="card-body">
        <p class="text-muted">Ces paramètres sont stockés en base de données et complètent la configuration dans <code>config.php</code>. Les valeurs en base ont la priorité pour l'affichage public si vous les chargez dynamiquement.</p>
        <form method="POST" class="row g-3">
            <?php foreach ($fields as $key => $label): ?>
                <div class="col-md-6">
                    <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
                    <?php if ($key === 'site_description'): ?>
                        <textarea name="<?= $key ?>" class="form-control" rows="3"><?= htmlspecialchars($values[$key]) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="<?= $key ?>" class="form-control" value="<?= htmlspecialchars($values[$key]) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer la configuration</button>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card mt-4">
    <div class="card-header bg-white fw-bold">Aperçu identité magasin</div>
    <div class="card-body">
        <h4><?= htmlspecialchars($values['site_name']) ?></h4>
        <p class="text-primary mb-1"><?= htmlspecialchars($values['site_tagline']) ?></p>
        <p class="text-muted"><?= htmlspecialchars($values['site_description']) ?></p>
        <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($values['site_email']) ?> &nbsp;
           <i class="bi bi-telephone"></i> <?= htmlspecialchars($values['site_phone']) ?></p>
        <p><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($values['site_address']) ?></p>
    </div>
</div>

<?php adminLayoutEnd(); ?>
