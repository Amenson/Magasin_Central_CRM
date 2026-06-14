<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('customers');

$success = '';
$error = '';
$user = null;
$user_id = 0;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = 'ID utilisateur invalide.';
} else {
    $user_id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, is_blocked FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'Utilisateur non trouve.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user']) && $user) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;

    $errors = [];
    if ($name === '') $errors[] = 'Le nom est obligatoire.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

    if ($email !== '' && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est deja utilise.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET name = ?, email = ?, phone = ?, address = ?, is_blocked = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, $phone, $address, $is_blocked, $user_id]);

        $success = 'Utilisateur modifie avec succes !';

        $stmt = $pdo->prepare("SELECT id, name, email, phone, address, is_blocked FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = implode('<br>', $errors);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $user) {
    $new_password = 'nouveau_mot_de_passe123';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $user_id]);

    $success = "Mot de passe reinitialise ! Nouveau mot de passe : $new_password (changez-le immediatement)";
}

adminLayoutStart('Modifier le client' . ($user_id ? " #$user_id" : ''), 'customers');
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="pro-card">
    <div class="card-header-pro"><i class="bi bi-person-gear me-2"></i> Client #<?= $user_id ?: '-' ?></div>
    <div class="card-body">
        <?php if ($user): ?>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom complet</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Telephone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Statut du compte</label>
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" name="is_blocked" id="blockSwitch" <?= $user['is_blocked'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="blockSwitch">
                            <?= $user['is_blocked'] ? '<span class="text-danger">Bloque</span>' : '<span class="text-success">Actif</span>' ?>
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Adresse de livraison</label>
                    <textarea name="address" rows="4" class="form-control"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" name="update_user" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                    <a href="manage_users.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </form>

        <hr class="my-4">
        <h5 class="text-danger"><i class="bi bi-key"></i> Reinitialiser le mot de passe</h5>
        <p class="text-muted mb-3">Cela definira un mot de passe temporaire que l'utilisateur devra changer.</p>
        <form method="POST" onsubmit="return confirm('Reinitialiser le mot de passe ?')">
            <button type="submit" name="reset_password" class="btn btn-danger">
                <i class="bi bi-shield-lock"></i> Reinitialiser mot de passe
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php adminLayoutEnd(); ?>
