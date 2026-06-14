<?php
/**
 * ADMIN MESSAGING DASHBOARD
 * Central hub for managing all conversations
 */

session_start();

require_once '../../config.php';
require_once '../config/messaging_config.php';
require_once '../classes/MessagingClass.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../admin/');
    exit;
}

$messaging = new MessagingSystem($pdo, $_SESSION['admin_id'], 'admin');

// Get filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = MESSAGING_PAGINATION_LIMIT;
$offset = ($page - 1) * $limit;

$filters = [
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'priority' => isset($_GET['priority']) ? $_GET['priority'] : '',
    'category' => isset($_GET['category']) ? $_GET['category'] : '',
    'assigned_to_me' => isset($_GET['assigned_to_me']) ? true : false,
    'unassigned' => isset($_GET['unassigned']) ? true : false,
    'search' => isset($_GET['search']) ? $_GET['search'] : ''
];

// Get conversations
$conversations = $messaging->getAdminConversations($limit, $offset, $filters);

// Get statistics
try {
    $stats = [];
    
    // Total conversations
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations");
    $stmt->execute();
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Open conversations
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations WHERE status = 'open'");
    $stmt->execute();
    $stats['open'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // In progress
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations WHERE status = 'in_progress'");
    $stmt->execute();
    $stats['in_progress'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Closed
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations WHERE status = 'closed'");
    $stmt->execute();
    $stats['closed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Unassigned
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations WHERE admin_id IS NULL AND status IN ('open', 'in_progress')");
    $stmt->execute();
    $stats['unassigned'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Urgent
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_conversations WHERE priority = 'urgent' AND status IN ('open', 'in_progress')");
    $stmt->execute();
    $stats['urgent'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $stats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'closed' => 0, 'unassigned' => 0, 'urgent' => 0];
}

// Count total filtered
$count_query = "SELECT COUNT(*) as total FROM messages_conversations WHERE 1=1";
$params = [];

if ($filters['status']) {
    $count_query .= " AND status = ?";
    $params[] = $filters['status'];
}
if ($filters['priority']) {
    $count_query .= " AND priority = ?";
    $params[] = $filters['priority'];
}
if ($filters['category']) {
    $count_query .= " AND category = ?";
    $params[] = $filters['category'];
}
if ($filters['assigned_to_me']) {
    $count_query .= " AND admin_id = ?";
    $params[] = $_SESSION['admin_id'];
}
if ($filters['unassigned']) {
    $count_query .= " AND admin_id IS NULL";
}
if ($filters['search']) {
    $count_query .= " AND (subject LIKE ? OR subject LIKE ? OR subject LIKE ?)";
    $search = '%' . $filters['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Messagerie - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="icon" href="/assets/images/logo/favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-card.open .stat-number { color: #27ae60; }
        .stat-card.in-progress .stat-number { color: #3498db; }
        .stat-card.closed .stat-number { color: #e74c3c; }
        .stat-card.unassigned .stat-number { color: #f39c12; }
        .stat-card.urgent .stat-number { color: #c0392b; }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .conversations-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .conversation-row {
            border-bottom: 1px solid #e0e0e0;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
        }
        
        .conversation-row:hover {
            background: #f9f9f9;
            transform: translateX(5px);
        }
        
        .conversation-subject {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .conversation-client {
            color: #666;
            font-size: 13px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-status-open { background: #d4edda; color: #155724; }
        .badge-status-in_progress { background: #cfe2ff; color: #084298; }
        .badge-status-closed { background: #f8d7da; color: #842029; }
        
        .badge-priority-low { background: #e2e3e5; color: #383d41; }
        .badge-priority-medium { background: #d1ecf1; color: #0c5460; }
        .badge-priority-high { background: #fff3cd; color: #856404; }
        .badge-priority-urgent { background: #f8d7da; color: #842029; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
            font-size: 13px;
        }
        
        .pagination a:hover {
            background: #3498db;
            color: white;
        }
        
        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>⚙️ Tableau de Bord Messagerie</h1>
        <p>Gérez les conversations clients en temps réel</p>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Conversations</div>
            </div>
            <div class="stat-card open">
                <div class="stat-number"><?php echo $stats['open']; ?></div>
                <div class="stat-label">Ouvertes</div>
            </div>
            <div class="stat-card in-progress">
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">En Cours</div>
            </div>
            <div class="stat-card closed">
                <div class="stat-number"><?php echo $stats['closed']; ?></div>
                <div class="stat-label">Fermées</div>
            </div>
            <div class="stat-card unassigned">
                <div class="stat-number"><?php echo $stats['unassigned']; ?></div>
                <div class="stat-label">Non Assignées</div>
            </div>
            <div class="stat-card urgent">
                <div class="stat-number"><?php echo $stats['urgent']; ?></div>
                <div class="stat-label">Urgentes</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="index.php">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Rechercher</label>
                        <input type="text" id="search" name="search" placeholder="Client, sujet..." 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="">Tous</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>En Cours</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priorité</label>
                        <select id="priority" name="priority">
                            <option value="">Tous</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Basse</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Moyenne</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>Haute</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Catégorie</label>
                        <select id="category" name="category">
                            <option value="">Tous</option>
                            <option value="order" <?php echo $filters['category'] === 'order' ? 'selected' : ''; ?>>Commande</option>
                            <option value="product" <?php echo $filters['category'] === 'product' ? 'selected' : ''; ?>>Produit</option>
                            <option value="delivery" <?php echo $filters['category'] === 'delivery' ? 'selected' : ''; ?>>Livraison</option>
                            <option value="return" <?php echo $filters['category'] === 'return' ? 'selected' : ''; ?>>Retour</option>
                            <option value="technical" <?php echo $filters['category'] === 'technical' ? 'selected' : ''; ?>>Technique</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                    <a href="index.php" class="btn btn-secondary">↺ Réinitialiser</a>
                </div>
            </form>
        </div>
        
        <!-- Conversations List -->
        <div class="conversations-list">
            <?php if (count($conversations) > 0): ?>
                <div style="display: none;">
                    <div class="conversation-row" style="padding: 15px; background: #f0f0f0; font-weight: bold; font-size: 13px;">
                        <div>Sujet</div>
                        <div>Client</div>
                        <div>Statut</div>
                        <div>Priorité</div>
                        <div>Mis à jour</div>
                        <div>Actions</div>
                    </div>
                </div>
                
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-row" onclick="window.location.href='view.php?id=<?php echo $conv['id']; ?>'">
                        <div>
                            <div class="conversation-subject"><?php echo htmlspecialchars(substr($conv['subject'], 0, 50)); ?></div>
                            <div class="conversation-client">📧 <?php echo htmlspecialchars($conv['client_name']); ?></div>
                        </div>
                        <div><?php echo htmlspecialchars(substr($conv['client_name'], 0, 20)); ?></div>
                        <div>
                            <span class="badge badge-status-<?php echo $conv['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $conv['status'])); ?>
                            </span>
                        </div>
                        <div>
                            <span class="badge badge-priority-<?php echo $conv['priority']; ?>">
                                <?php echo ucfirst($conv['priority']); ?>
                            </span>
                        </div>
                        <div>
                            <small><?php echo date('d/m H:i', strtotime($conv['updated_at'])); ?></small>
                        </div>
                        <div>
                            <a href="view.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary" onclick="event.stopPropagation();">→</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo http_build_query(array_filter($filters)); ?>">« Première</a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters)); ?>">‹ Précédente</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php elseif ($i <= $page + 2 && $i >= $page - 2): ?>
                                <a href="?page=<?php echo $i; ?><?php echo http_build_query(array_filter($filters)); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters)); ?>">Suivante ›</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo http_build_query(array_filter($filters)); ?>">Dernière »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune conversation trouvée.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>