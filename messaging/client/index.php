<?php
/**
 * CLIENT MESSAGING INBOX
 * Main messaging interface for clients
 */

session_start();

// Include configuration
require_once '../../config.php';
require_once '../config/messaging_config.php';
require_once '../classes/MessagingClass.php';
$pageTitle = 'Contact — ' . SITE_NAME;
include '../../includes/header.php';
// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Initialize messaging system
$messaging = new MessagingSystem($pdo, $_SESSION['user_id'], 'client');

// Get pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = MESSAGING_PAGINATION_LIMIT;
$offset = ($page - 1) * $limit;

// Get status filter
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Get conversations
$conversations = $messaging->getConversations($limit, $offset, $status);

// Get unread count
$unread_count = $messaging->getUnreadCount();

// Count total conversations
$count_query = "SELECT COUNT(*) as total FROM messages_conversations WHERE client_id = ?";
if ($status) {
    $count_query .= " AND status = ?";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($status ? [$_SESSION['user_id'], $status] : [$_SESSION['user_id']]);
} else {
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([$_SESSION['user_id']]);
}
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Messagerie - Anon-ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <link rel="icon" href="../../assets/images/logo/favicon.ico" type="image/x-icon">
    <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            
        }
        
        .container {
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .badge {
            background: #e74c3c;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .filters {
            background: white;
            padding: 20px 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #f0f0f0;
            border: 2px solid transparent;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }
        
        .filter-btn.active,
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f8f9ff;
        }
        
        .messages-container {
            background: white;
            padding: 30px;
            min-height: 400px;
        }
        
        .message-item {
            border: 1px solid #e0e0e0;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .message-item:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateX(5px);
            border-color: #667eea;
        }
        
        .message-item.unread {
            background: linear-gradient(135deg, #e8f0ff 0%, #f0e8ff 100%);
            border-left: 4px solid #667eea;
            font-weight: 500;
        }
        
        .message-info {
            flex: 1;
        }
        
        .message-subject {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .message-subject .unread-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .message-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #888;
            margin-top: 8px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .message-status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 15px;
        }
        
        .status-open {
            background: #d4edda;
            color: #155724;
        }
        
        .status-in_progress {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #842029;
        }
        
        .priority-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .priority-urgent {
            background: #f8d7da;
            color: #842029;
        }
        
        .priority-high {
            background: #fff3cd;
            color: #856404;
        }
        
        .priority-medium {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .priority-low {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #667eea;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .footer {
            background: white;
            padding: 20px 30px;
            border-radius: 0 0 10px 10px;
            text-align: center;
            color: #999;
            font-size: 13px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .message-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .message-status-badge {
                margin-right: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="bi bi-envelope"></i> Ma Messagerie</h1>
                <p>Contactez notre support client et suivez vos conversations en cours.</p>
            </div>
            <div class="header-actions">
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?> nouveau(x)</span>
                <?php endif; ?>
                <a href="new.php" class="btn">+ Nouveau Message</a>
            </div>
        </div>
        
        <div class="filters">
            <a href="index.php" class="filter-btn <?php echo !$status ? 'active' : ''; ?>"><i class="bi bi-list"></i> Tous</a>
            <a href="index.php?status=open" class="filter-btn <?php echo $status === 'open' ? 'active' : ''; ?>"><i class="bi bi-lightbulb"></i> Ouvert</a>
            <a href="index.php?status=in_progress" class="filter-btn <?php echo $status === 'in_progress' ? 'active' : ''; ?>"><i class="bi bi-hourglass"></i> En cours</a>
            <a href="index.php?status=closed" class="filter-btn <?php echo $status === 'closed' ? 'active' : ''; ?>"> <i class="bi bi-check-circle"></i>  Fermé</a>
        </div>
        
        <div class="messages-container">
            <?php if (count($conversations) > 0): ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="message-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>" onclick="window.location.href='view.php?id=<?php echo $conv['id']; ?>'">
                        <div class="message-info">
                            <div class="message-subject">
                                <?php echo htmlspecialchars(substr($conv['subject'], 0, 60)); ?>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge">+<?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-meta">
                                <span class="meta-item">👤 <?php echo $conv['admin_name'] ?? 'Support'; ?></span>
                                <span class="meta-item">💬 <?php echo $conv['message_count']; ?> message(s)</span>
                                <span class="meta-item">🕐 <?php echo date('d/m/Y H:i', strtotime($conv['last_message_time'] ?? $conv['created_at'])); ?></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="priority-badge priority-<?php echo $conv['priority']; ?>">
                                <?php echo ucfirst($conv['priority']); ?>
                            </span>
                            <span class="message-status-badge status-<?php echo $conv['status']; ?>">
                                <?php 
                                    $status_labels = [
                                        'open' => '🔵 Ouvert',
                                        'in_progress' => '⏳ En cours',
                                        'closed' => '✅ Fermé',
                                        'archived' => '📦 Archivé'
                                    ];
                                    echo $status_labels[$conv['status']] ?? $conv['status'];
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo $status ? '&status=' . $status : ''; ?>">« Première</a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">‹ Précédente</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php elseif ($i <= $page + 2 && $i >= $page - 2): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">Suivante ›</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $status ? '&status=' . $status : ''; ?>">Dernière »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💭</div>
                    <h3>Aucun message</h3>
                    <p>Vous n'avez aucune conversation pour l'instant.</p>
                    <a href="new.php" class="btn" style="margin-top: 20px;">Commencer une conversation</a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</body>
</html>
<?php include '../../includes/footer.php'; ?>