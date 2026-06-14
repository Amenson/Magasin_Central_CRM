<?php
session_start();
require_once 'config.php';
include 'includes/header.php';

// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Veuillez vous connecter pour accéder à votre profil.'];
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération des infos de base (toujours existantes)
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Récupération optionnelle phone/address (tolérant aux colonnes manquantes)
$user['phone'] = '';
$user['address'] = '';

try {
    $stmt = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $extra = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($extra) {
        $user['phone'] = $extra['phone'] ?? '';
        $user['address'] = $extra['address'] ?? '';
    }
} catch (PDOException $e) {
    // Colonnes phone/address n'existent pas → on les ignore
}

$message = '';
$messageType = 'danger';

// Traitement mise à jour profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];
    if (empty($name)) $errors[] = 'Le nom est obligatoire.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    
    // Vérification email unique (sauf pour l'utilisateur actuel)
    if (!empty($email) && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déjà utilisé par un autre compte.';
        }
    }

    if (empty($errors)) {
        // Construction dynamique de la requête UPDATE (tolérant aux colonnes manquantes)
        $updateFields = ['name = ?', 'email = ?'];
        $params = [$name, $email];

        // Ajouter phone si colonne existe
        try {
            $pdo->query("SELECT phone FROM users LIMIT 1");
            $updateFields[] = 'phone = ?';
            $params[] = $phone;
        } catch (PDOException $e) {
            // Colonne inexistante → ignorée
        }

        // Ajouter address si colonne existe
        try {
            $pdo->query("SELECT address FROM users LIMIT 1");
            $updateFields[] = 'address = ?';
            $params[] = $address;
        } catch (PDOException $e) {
            // Colonne inexistante → ignorée
        }

        $params[] = $user_id; // WHERE id = ?

        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $message = 'Profil mis à jour avec succès !';
        $messageType = 'success';

        // Mise à jour session et variables locales
        $_SESSION['user_name'] = $name;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['address'] = $address;
    } else {
        $message = implode('<br>', $errors);
    }
}

// Traitement changement mot de passe (inchangé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = 'Tous les champs sont obligatoires.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current_password, $hash)) {
            $errors[] = 'Mot de passe actuel incorrect.';
        }
    }

    if (empty($errors)) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);

        $message = 'Mot de passe changé avec succès !';
        $messageType = 'success';
    } else {
        $message = implode('<br>', $errors);
    }
}

// Affichage toast si message
if ($message) {
    echo "<script>showToast('" . addslashes($message) . "', '$messageType');</script>";
}
?>

<div class="container my-5">
    <h1 class="display-6 fw-bold text-center mb-5">
        <i class="bi bi-person-circle me-2"></i> Mon Profil
    </h1>

    <div class="row g-5 justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Informations personnelles</h5>
                </div>
                <div class="card-body">
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
                                <label class="form-label fw-bold">Téléphone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Adresse de livraison</label>
                                <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Changer le mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Confirmer nouveau mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="bi bi-shield-lock"></i> Changer le mot de passe
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="my-orders.php" class="btn btn-outline-primary me-3">
                    <i class="bi bi-list-check"></i> Mes commandes
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house"></i> Retour à l'accueil
                </a>
            </div>
             <div class="account-section">
                <h5 class="mt-5 mb-3"><i class="bi bi-chat-dots"></i> Messagerie</h5>
                <p>Accédez à votre messagerie pour contacter notre support client et suivre vos conversations en cours.</p>
                <a href="messaging/client/index.php" class="btn btn-outline-success">
                    <i class="bi bi-chat"></i> Accéder à la messagerie
                </a>

             </div>
             
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>