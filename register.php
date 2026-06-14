<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Le nom est obligatoire.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if (empty($password) || strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';

    // Vérification email unique
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déjà utilisé.';
        }
    }

    // Inscription si pas d'erreurs
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, phone, address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $hash, $phone, $address]);

            $message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            $messageType = 'success';

            // Optionnel : connexion automatique après inscription
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de l\'inscription.';
            error_log('Erreur inscription : ' . $e->getMessage());
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
    }
}
?>

<div class="page-shell">
    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-person-plus text-primary"></i> Inscription</h1>
            <p>Rejoignez <?= htmlspecialchars(SITE_NAME) ?> en quelques secondes</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="card-header"><i class="bi bi-person-plus me-2"></i> Créer un compte</div>
                    <div class="p-4">

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                   placeholder="Votre nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   placeholder="votre@email.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mot de passe <span class="text-danger">*</span> (min. 8 caractères)</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="••••••••" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                                   placeholder="90 12 34 56">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Adresse de livraison</label>
                            <textarea name="address" rows="3" class="form-control" 
                                      placeholder="Votre adresse complète"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-person-check me-2"></i> S'inscrire
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Déjà un compte ? 
                            <a href="login.php" class="text-primary fw-bold">Se connecter</a>
                        </p>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>