<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('settings.admins');

$success = '';
$error = '';

// Suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION['admin_id']) {
        $error = 'Vous ne pouvez pas supprimer votre propre compte.';
    } else {
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
        $success = 'Administrateur supprimé.';
    }
}

// Ajout / modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int)($_POST['role_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($username === '' || $roleId <= 0) {
        $error = 'Nom d\'utilisateur et rôle obligatoires.';
    } else {
        $check = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);
        if ($check->fetch()) {
            $error = 'Ce nom d\'utilisateur existe déjà.';
        } elseif ($id > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET username=?, email=?, password=?, role_id=?, is_active=? WHERE id=?");
                $stmt->execute([$username, $email, $hash, $roleId, $isActive, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET username=?, email=?, role_id=?, is_active=? WHERE id=?");
                $stmt->execute([$username, $email, $roleId, $isActive, $id]);
            }
            $success = 'Administrateur mis à jour.';
        } else {
            if ($password === '') {
                $error = 'Mot de passe obligatoire pour un nouvel admin.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role_id, is_active) VALUES (?,?,?,?,?)");
                $stmt->execute([$username, $email, $hash, $roleId, $isActive]);
                $success = 'Administrateur créé.';
            }
        }
    }
}

$roles = $pdo->query("SELECT id, label FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$admins = $pdo->query("
    SELECT a.id, a.username, a.email, a.is_active, a.created_at, a.last_login_at, r.label AS role_label
    FROM admins a
    LEFT JOIN roles r ON r.id = a.role_id
    ORDER BY a.id
")->fetchAll(PDO::FETCH_ASSOC);

$editAdmin = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
}

adminLayoutStart('Utilisateurs administrateurs', 'settings_admins');
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold"><?= $editAdmin ? 'Modifier admin' : 'Nouvel administrateur' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= (int)($editAdmin['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($editAdmin['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editAdmin['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe <?= $editAdmin ? '(laisser vide pour conserver)' : '' ?></label>
                        <input type="password" name="password" class="form-control" <?= $editAdmin ? '' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ((int)($editAdmin['role_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= ($editAdmin['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Compte actif</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= $editAdmin ? 'Enregistrer' : 'Créer' ?></button>
                    <?php if ($editAdmin): ?><a href="admins.php" class="btn btn-secondary">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Liste des administrateurs</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($a['username']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($a['email'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['role_label'] ?? '—') ?></td>
                            <td><span class="badge <?= $a['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $a['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
                            <td><?= $a['last_login_at'] ? date('d/m/Y H:i', strtotime($a['last_login_at'])) : '—' ?></td>
                            <td>
                                <a href="?edit=<?= $a['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <?php if ((int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
                                    <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet admin ?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
