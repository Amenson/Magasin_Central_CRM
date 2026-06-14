<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('contacts');

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = trim($_POST['action']);
    $contactId = (int)($_POST['contact_id'] ?? 0);
    
    // Mark as read
    if ($action === 'mark_read' && $contactId > 0) {
        $stmt = $pdo->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
        echo json_encode(['success' => $stmt->execute([$contactId])]);
    }
    // Update status
    elseif ($action === 'update_status' && $contactId > 0) {
        $newStatus = trim($_POST['new_status'] ?? '');
        $validStatuses = ['new', 'read', 'replied', 'resolved', 'spam'];
        
        if (in_array($newStatus, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
            echo json_encode(['success' => $stmt->execute([$newStatus, $contactId])]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        }
    }
    // Update priority
    elseif ($action === 'update_priority' && $contactId > 0) {
        $newPriority = trim($_POST['new_priority'] ?? '');
        $validPriorities = ['low', 'medium', 'high'];
        
        if (in_array($newPriority, $validPriorities)) {
            $stmt = $pdo->prepare("UPDATE contacts SET priority = ? WHERE id = ?");
            echo json_encode(['success' => $stmt->execute([$newPriority, $contactId])]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Priorité invalide']);
        }
    }
    // Delete contact
    elseif ($action === 'delete' && $contactId > 0) {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        echo json_encode(['success' => $stmt->execute([$contactId])]);
    }
    
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$priorityFilter = $_GET['priority'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if ($priorityFilter !== 'all') {
    $where[] = "priority = ?";
    $params[] = $priorityFilter;
}
if ($categoryFilter !== 'all') {
    $where[] = "category = ?";
    $params[] = $categoryFilter;
}
if (!empty($searchQuery)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchPattern = "%$searchQuery%";
    $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT id, name, email, subject, status, priority, category, created_at, message
    FROM contacts
    $whereClause
    ORDER BY 
        CASE WHEN priority = 'high' THEN 0 WHEN priority = 'medium' THEN 1 ELSE 2 END,
        CASE WHEN status = 'new' THEN 0 WHEN status = 'read' THEN 1 ELSE 2 END,
        created_at DESC
");
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status
$statusCounts = [];
$statusCounts['all'] = $pdo->query("SELECT COUNT(*) as count FROM contacts")->fetch()['count'];
$statusCounts['new'] = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'")->fetch()['count'];
$statusCounts['read'] = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'read'")->fetch()['count'];
$statusCounts['replied'] = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'replied'")->fetch()['count'];
$statusCounts['resolved'] = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'resolved'")->fetch()['count'];
$statusCounts['spam'] = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'spam'")->fetch()['count'];

adminLayoutStart('Gestion des Contacts', 'contacts');
?>

<div class="mb-4">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="?status=all" class="badge <?= $statusFilter === 'all' ? 'bg-primary' : 'bg-secondary' ?> p-2">
            Tous (<?= $statusCounts['all'] ?>)
        </a>
        <a href="?status=new" class="badge <?= $statusFilter === 'new' ? 'bg-danger' : 'bg-secondary' ?> p-2">
            Nouveaux (<?= $statusCounts['new'] ?>)
        </a>
        <a href="?status=read" class="badge <?= $statusFilter === 'read' ? 'bg-info' : 'bg-secondary' ?> p-2">
            Lus (<?= $statusCounts['read'] ?>)
        </a>
        <a href="?status=replied" class="badge <?= $statusFilter === 'replied' ? 'bg-success' : 'bg-secondary' ?> p-2">
            Répondus (<?= $statusCounts['replied'] ?>)
        </a>
        <a href="?status=resolved" class="badge <?= $statusFilter === 'resolved' ? 'bg-success' : 'bg-secondary' ?> p-2">
            Résolus (<?= $statusCounts['resolved'] ?>)
        </a>
        <a href="?status=spam" class="badge <?= $statusFilter === 'spam' ? 'bg-dark' : 'bg-secondary' ?> p-2">
            Spam (<?= $statusCounts['spam'] ?>)
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Chercher nom, email, sujet..." value="<?= htmlspecialchars($searchQuery) ?>">
        </div>
        <div class="col-md-3">
            <select name="priority" class="form-select">
                <option value="all">Toutes les priorités</option>
                <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>Haute</option>
                <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Basse</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="all">Toutes les catégories</option>
                <option value="order" <?= $categoryFilter === 'order' ? 'selected' : '' ?>>Commande</option>
                <option value="product" <?= $categoryFilter === 'product' ? 'selected' : '' ?>>Produit</option>
                <option value="delivery" <?= $categoryFilter === 'delivery' ? 'selected' : '' ?>>Livraison</option>
                <option value="payment" <?= $categoryFilter === 'payment' ? 'selected' : '' ?>>Paiement</option>
                <option value="other" <?= $categoryFilter === 'other' ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrer</button>
        </div>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 5%">ID</th>
                    <th style="width: 20%">Contact</th>
                    <th style="width: 25%">Sujet</th>
                    <th style="width: 10%">Catégorie</th>
                    <th style="width: 10%">Priorité</th>
                    <th style="width: 10%">Statut</th>
                    <th style="width: 12%">Date</th>
                    <th style="width: 8%">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> Aucun contact trouvé
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                <tr class="contact-row <?= $c['status'] === 'new' ? 'table-light' : '' ?>" data-contact-id="<?= $c['id'] ?>">
                    <td><strong>#<?= $c['id'] ?></strong></td>
                    <td>
                        <div><strong><?= htmlspecialchars($c['name']) ?></strong></div>
                        <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(substr($c['subject'], 0, 50)) ?><?= strlen($c['subject']) > 50 ? '...' : '' ?></td>
                    <td>
                        <span class="badge bg-secondary">
                            <?php
                            $categories = ['order' => 'Commande', 'product' => 'Produit', 'delivery' => 'Livraison', 'payment' => 'Paiement', 'other' => 'Autre'];
                            echo $categories[$c['category']] ?? 'Autre';
                            ?>
                        </span>
                    </td>
                    <td>
                        <select class="form-select form-select-sm priority-select" data-contact-id="<?= $c['id'] ?>">
                            <option value="low" <?= $c['priority'] === 'low' ? 'selected' : '' ?>>Basse</option>
                            <option value="medium" <?= $c['priority'] === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="high" <?= $c['priority'] === 'high' ? 'selected' : '' ?>>Haute</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-contact-id="<?= $c['id'] ?>">
                            <option value="new" <?= $c['status'] === 'new' ? 'selected' : '' ?>>Nouveau</option>
                            <option value="read" <?= $c['status'] === 'read' ? 'selected' : '' ?>>Lu</option>
                            <option value="replied" <?= $c['status'] === 'replied' ? 'selected' : '' ?>>Répondu</option>
                            <option value="resolved" <?= $c['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option>
                            <option value="spam" <?= $c['status'] === 'spam' ? 'selected' : '' ?>>Spam</option>
                        </select>
                    </td>
                    <td><small><?= date('d/m/y H:i', strtotime($c['created_at'])) ?></small></td>
                    <td>
                        <a href="view_contact.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm" title="Voir le détail"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-danger btn-sm delete-contact" data-contact-id="<?= $c['id'] ?>" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="toastContainer"></div>

<script>
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} toast-notification`;
    toast.innerHTML = `<strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}`;
    document.getElementById('toastContainer').appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 4000);
}

// Status change
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const contactId = this.dataset.contactId;
        const newStatus = this.value;
        
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_status&contact_id=${contactId}&new_status=${newStatus}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(`Statut du contact #${contactId} mis à jour`);
            } else {
                showToast(data.message || 'Erreur', 'danger');
                location.reload();
            }
        })
        .catch(() => {
            showToast('Erreur connexion', 'danger');
            location.reload();
        });
    });
});

// Priority change
document.querySelectorAll('.priority-select').forEach(select => {
    select.addEventListener('change', function() {
        const contactId = this.dataset.contactId;
        const newPriority = this.value;
        
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_priority&contact_id=${contactId}&new_priority=${newPriority}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(`Priorité du contact #${contactId} mise à jour`);
            } else {
                showToast(data.message || 'Erreur', 'danger');
                location.reload();
            }
        })
        .catch(() => {
            showToast('Erreur connexion', 'danger');
            location.reload();
        });
    });
});

// Delete contact
document.querySelectorAll('.delete-contact').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
            const contactId = this.dataset.contactId;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&contact_id=${contactId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Contact supprimé');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Erreur', 'danger');
                }
            })
            .catch(() => showToast('Erreur connexion', 'danger'));
        }
    });
});
</script>

<?php adminLayoutEnd(); ?>
