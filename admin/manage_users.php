<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('customers');

$success = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn() > 0) {
        $error = 'Impossible de supprimer : cet utilisateur a des commandes.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $success = $stmt->execute([$userId]) ? 'Utilisateur supprimé.' : 'Erreur suppression.';
    }
}

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalPages = max(1, ceil((int)$countStmt->fetchColumn() / $perPage));

$stmt = $pdo->prepare("SELECT id, name, email, phone, address, created_at FROM users $where ORDER BY created_at DESC LIMIT :offset, :perPage");
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param, PDO::PARAM_STR);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

adminLayoutStart('Gestion des clients', 'customers');
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="filter-bar">
    <form method="GET" class="row g-2">
        <div class="col-md-9"><input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Rechercher</button></div>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Tél.</th><th>Inscription</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Aucun client</td></tr>
            <?php else: foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): $qs = $search ? '&search=' . urlencode($search) : ''; ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php adminLayoutEnd(); ?>
