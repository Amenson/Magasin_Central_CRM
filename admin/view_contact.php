<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('contacts');

$contactId = (int)($_GET['id'] ?? 0);

if ($contactId <= 0) {
    header('Location: manage_contacts.php');
    exit;
}

// Fetch contact
$stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
$stmt->execute([$contactId]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    header('Location: manage_contacts.php');
    exit;
}

// Mark as read if it's new
if ($contact['status'] === 'new') {
    $pdo->prepare("UPDATE contacts SET status = 'read' WHERE id = ?")->execute([$contactId]);
    $contact['status'] = 'read';
}

// Handle notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $notes = trim($_POST['admin_notes'] ?? '');
    $status = trim($_POST['status'] ?? $contact['status']);
    
    $validStatuses = ['new', 'read', 'replied', 'resolved', 'spam'];
    if (!in_array($status, $validStatuses)) {
        $status = $contact['status'];
    }
    
    $stmt = $pdo->prepare("UPDATE contacts SET admin_notes = ?, status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$notes, $status, $contactId])) {
        $contact['admin_notes'] = $notes;
        $contact['status'] = $status;
        $message = 'Notes mises à jour avec succès';
        $messageType = 'success';
    } else {
        $message = 'Erreur lors de la mise à jour';
        $messageType = 'danger';
    }
}

adminLayoutStart('Détail du Contact #' . $contactId, 'contacts');
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Détails du contact</h5>
                <span class="badge bg-<?= match($contact['status']) {
                    'new' => 'danger',
                    'read' => 'info',
                    'replied' => 'success',
                    'resolved' => 'success',
                    'spam' => 'dark',
                    default => 'secondary'
                } ?>">
                    <?= match($contact['status']) {
                        'new' => 'Nouveau',
                        'read' => 'Lu',
                        'replied' => 'Répondu',
                        'resolved' => 'Résolu',
                        'spam' => 'Spam',
                        default => 'Inconnu'
                    } ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nom</label>
                            <div class="fs-5 fw-bold"><?= htmlspecialchars($contact['name']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Email</label>
                            <div><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a></div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Téléphone</label>
                            <div><?= $contact['phone'] ? htmlspecialchars($contact['phone']) : '<em class="text-muted">Non fourni</em>' ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Date de soumission</label>
                            <div><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Catégorie</label>
                            <div>
                                <span class="badge bg-secondary">
                                    <?php
                                    $categories = ['order' => 'Commande', 'product' => 'Produit', 'delivery' => 'Livraison', 'payment' => 'Paiement', 'other' => 'Autre'];
                                    echo $categories[$contact['category']] ?? 'Autre';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Priorité</label>
                            <div>
                                <span class="badge bg-<?= match($contact['priority']) {
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    'low' => 'success',
                                    default => 'secondary'
                                } ?>">
                                    <?= match($contact['priority']) {
                                        'high' => 'Haute',
                                        'medium' => 'Moyenne',
                                        'low' => 'Basse',
                                        default => 'Inconnue'
                                    } ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label text-muted small">Sujet</label>
                    <div class="fs-5 fw-bold mb-2"><?= htmlspecialchars($contact['subject']) ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small">Message</label>
                    <div class="p-3 rounded" style="background: var(--surface-muted); white-space: pre-wrap; word-break: break-word;">
                        <?= htmlspecialchars($contact['message']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Notes et Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select name="status" id="status" class="form-select">
                            <option value="new" <?= $contact['status'] === 'new' ? 'selected' : '' ?>>Nouveau</option>
                            <option value="read" <?= $contact['status'] === 'read' ? 'selected' : '' ?>>Lu</option>
                            <option value="replied" <?= $contact['status'] === 'replied' ? 'selected' : '' ?>>Répondu</option>
                            <option value="resolved" <?= $contact['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option>
                            <option value="spam" <?= $contact['status'] === 'spam' ? 'selected' : '' ?>>Spam</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Notes internes</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="6" placeholder="Ajoutez des notes internes..."><?= htmlspecialchars($contact['admin_notes'] ?? '') ?></textarea>
                        <small class="form-text text-muted">Ces notes ne sont visibles que par l'équipe admin</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="save_notes" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer
                        </button>
                        <a href="manage_contacts.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>

                <hr>

                <div class="small text-muted">
                    <p class="mb-2"><strong>ID:</strong> #<?= $contact['id'] ?></p>
                    <p class="mb-2"><strong>Créé:</strong> <?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></p>
                    <p class="mb-0"><strong>Dernier accès:</strong> <?= date('d/m/Y H:i', strtotime($contact['updated_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Actions rapides</h6>
            </div>
            <div class="card-body">
                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bi bi-reply"></i> Répondre par email
                </a>
                <a href="tel:<?= htmlspecialchars($contact['phone'] ?? '') ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2" <?= empty($contact['phone']) ? 'disabled' : '' ?>>
                    <i class="bi bi-telephone"></i> Appeler
                </a>
                <button class="btn btn-outline-danger btn-sm w-100" onclick="if(confirm('Supprimer ce contact ?')) { fetch('manage_contacts.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=delete&contact_id=<?= $contactId ?>'}).then(r => r.json()).then(() => location.href='manage_contacts.php'); }">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
