<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Veuillez saisir un email valide et un mot de passe.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Sécurité : régénérer l'ID de session
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                // Redirection intelligente (retour au panier/checkout si venu de là)
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);

                header("Location: $redirect");
                exit;
            } else {
                $message = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $message = 'Erreur de connexion. Réessayez plus tard.';
            error_log('Erreur login client : ' . $e->getMessage());
        }
    }
}
?>

<div class="page-shell">
    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-box-arrow-in-right text-primary"></i> Connexion</h1>
            <p>Accédez à votre compte <?= htmlspecialchars(SITE_NAME) ?></p>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="card-header"><i class="bi bi-person me-2"></i> Votre compte</div>
                    <div class="p-4">

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   placeholder="votre@email.com" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="••••••••" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2">Pas encore de compte ? 
                            <a href="register.php" class="text-primary fw-bold">Créer un compte</a>
                        </p>
                        <p class="small text-muted">
                            <a href="forgot-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                        </p>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>