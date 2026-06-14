<?php
session_start();
require_once '../config.php';

// Redirection si déjà connecté
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'Veuillez remplir tous les champs.';
    } else {
        try {
            require_once __DIR__ . '/includes/migrate.php';
            runCrmMigration($pdo);

            $stmt = $pdo->prepare("
                SELECT a.id, a.username, a.password, a.is_active, r.label AS role_label, r.permissions
                FROM admins a
                LEFT JOIN roles r ON r.id = a.role_id
                WHERE a.username = ? LIMIT 1
            ");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && (int)($admin['is_active'] ?? 1) === 1 && password_verify($password, $admin['password'])) {

                session_regenerate_id(true);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role_label'] = $admin['role_label'] ?? 'Administrateur';
                $perms = json_decode($admin['permissions'] ?? '["*"]', true);
                $_SESSION['admin_permissions'] = is_array($perms) ? $perms : ['*'];

                $pdo->prepare("UPDATE admins SET last_login_at = NOW() WHERE id = ?")->execute([$admin['id']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $message = 'Nom d’utilisateur ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $message = 'Erreur de connexion. Réessayez plus tard.';
            error_log('Erreur login admin : ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-auth-page">
    <div class="admin-auth-card">
        <div class="auth-logo"><i class="bi bi-shield-lock-fill"></i></div>
        <h2 class="text-center fw-bold mb-1">Administration</h2>
        <p class="text-center text-muted mb-4"><?= htmlspecialchars(SITE_NAME) ?></p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
              <input type="text" name="username" class="form-control" placeholder="Nom d'utilisateur" 
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter
            </button>
        </form>
        <p class="text-center text-muted small mt-4 mb-0">© <?= date('Y') ?> <?= SITE_NAME ?></p>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>