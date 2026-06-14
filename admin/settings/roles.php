<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

initAdminSession($pdo);
requirePermission('settings.roles');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $label = trim($_POST['label'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = $_POST['permissions'] ?? [];

    if ($label === '') {
        $error = 'Le libellé est obligatoire.';
    } else {
        $permsJson = json_encode(array_values($permissions));
        if ($id > 0) {
            $role = $pdo->prepare("SELECT is_system FROM roles WHERE id = ?");
            $role->execute([$id]);
            $r = $role->fetch(PDO::FETCH_ASSOC);
            if ($r && (int)$r['is_system'] === 1 && !in_array('*', $permissions, true)) {
                $error = 'Le rôle système super admin doit conserver tous les droits.';
            } else {
                $stmt = $pdo->prepare("UPDATE roles SET label=?, description=?, permissions=? WHERE id=?");
                $stmt->execute([$label, $description, $permsJson, $id]);
                $success = 'Rôle mis à jour.';
            }
        } else {
            $name = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label));
            $stmt = $pdo->prepare("INSERT INTO roles (name, label, description, permissions) VALUES (?,?,?,?)");
            try {
                $stmt->execute([$name, $label, $description, $permsJson]);
                $success = 'Rôle créé.';
            } catch (PDOException $e) {
                $error = 'Impossible de créer le rôle (nom peut-être dupliqué).';
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $role = $pdo->prepare("SELECT is_system FROM roles WHERE id = ?");
    $role->execute([$id]);
    $r = $role->fetch(PDO::FETCH_ASSOC);
    if ($r && (int)$r['is_system'] === 1) {
        $error = 'Impossible de supprimer un rôle système.';
    } else {
        $used = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE role_id = ?");
        $used->execute([$id]);
        if ((int)$used->fetchColumn() > 0) {
            $error = 'Ce rôle est assigné à des administrateurs.';
        } else {
            $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
            $success = 'Rôle supprimé.';
        }
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$editRole = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRole = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editRole) {
        $editRole['permissions'] = json_decode($editRole['permissions'], true) ?: [];
    }
}

adminLayoutStart('Rôles et permissions', 'settings_roles');
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold"><?= $editRole ? 'Modifier le rôle' : 'Nouveau rôle' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= (int)($editRole['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Libellé</label>
                        <input type="text" name="label" class="form-control" required value="<?= htmlspecialchars($editRole['label'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editRole['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Permissions</label>
                        <?php
                        $currentPerms = $editRole['permissions'] ?? [];
                        $hasAll = in_array('*', $currentPerms, true);
                        ?>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="*" class="form-check-input" id="perm_all" <?= $hasAll ? 'checked' : '' ?>>
                            <label class="form-check-label" for="perm_all">Accès complet (*)</label>
                        </div>
                        <hr>
                        <?php foreach (ADMIN_PERMISSIONS as $key => $label): ?>
                            <div class="form-check">
                                <input type="checkbox" name="permissions[]" value="<?= $key ?>" class="form-check-input perm-item"
                                    id="perm_<?= $key ?>" <?= (!$hasAll && in_array($key, $currentPerms, true)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="perm_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= $editRole ? 'Enregistrer' : 'Créer' ?></button>
                    <?php if ($editRole): ?><a href="roles.php" class="btn btn-secondary">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card stat-card">
            <div class="card-header bg-white fw-bold">Rôles existants</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Rôle</th><th>Description</th><th>Type</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['label']) ?></strong><br><code class="small"><?= htmlspecialchars($r['name']) ?></code></td>
                            <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
                            <td><?= (int)$r['is_system'] ? '<span class="badge bg-info">Système</span>' : '<span class="badge bg-light text-dark">Personnalisé</span>' ?></td>
                            <td>
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <?php if (!(int)$r['is_system']): ?>
                                    <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce rôle ?')"><i class="bi bi-trash"></i></a>
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

<script>
document.getElementById('perm_all')?.addEventListener('change', function() {
    document.querySelectorAll('.perm-item').forEach(cb => {
        cb.disabled = this.checked;
        if (this.checked) cb.checked = false;
    });
});
</script>

<?php adminLayoutEnd(); ?>
