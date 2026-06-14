<?php
/**
 * ADMIN VIEW CONVERSATION
 * Admin interface for managing conversations
 */

session_start();

require_once '../../config.php';
require_once '../config/messaging_config.php';
require_once '../classes/MessagingClass.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$messaging = new MessagingSystem($pdo, $_SESSION['admin_id'], 'admin');
$conv_id = (int)$_GET['id'];
$error = '';
$success = '';

// Get conversation
$conversation = $messaging->getConversation($conv_id);

if (!$conversation) {
    header('Location: index.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                $message = trim($_POST['message'] ?? '');
                if (!$message) {
                    $error = 'Veuillez entrer un message.';
                } else {
                    if ($messaging->sendMessage($conv_id, $message)) {
                        $success = 'Message envoyé avec succès!';
                        $messages = $messaging->getMessages($conv_id);
                    } else {
                        $error = 'Erreur lors de l\'envoi.';
                    }
                }
                break;
                
            case 'change_status':
                $new_status = trim($_POST['status'] ?? '');
                if ($messaging->updateConversationStatus($conv_id, $new_status)) {
                    $success = 'Statut mis à jour.';
                    $conversation = $messaging->getConversation($conv_id);
                } else {
                    $error = 'Erreur lors de la mise à jour.';
                }
                break;
                
            case 'assign':
                $admin_id = (int)$_POST['admin_id'];
                if ($messaging->assignConversation($conv_id, $admin_id)) {
                    $success = 'Conversation assignée.';
                    $conversation = $messaging->getConversation($conv_id);
                } else {
                    $error = 'Erreur lors de l\'assignation.';
                }
                break;
        }
    }
}

// Get messages
$messages = $messaging->getMessages($conv_id);

// Mark as read
$messaging->markConversationAsRead($conv_id);

// Get all admins for assignment
try {
    $stmt = $pdo->prepare("SELECT id, username FROM admins ORDER BY username");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $admins = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($conversation['subject']); ?> - Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .admin-header a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 20px;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #842029;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .messages-area {
            height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .message.admin {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
        }
        
        .message.admin .message-content {
            background: #27ae60;
            color: white;
        }
        
        .message.client .message-content {
            background: #e0e0e0;
            color: #333;
        }
        
        .message-bubble {
            padding: 12px 15px;
            border-radius: 8px;
            word-wrap: break-word;
            line-height: 1.5;
        }
        
        .message-info {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
            color: #2c3e50;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .sidebar-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-item:last-child {
            border-bottom: none;
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
        
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .message-content {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><?php echo htmlspecialchars(substr($conversation['subject'], 0, 50)); ?></h1>
        <a href="index.php">← Retour</a>
    </div>
    
    <div class="container">
        <div class="main-grid">
            <!-- Messages Column -->
            <div>
                <div class="card">
                    <div class="card-header">
                        💬 Conversation avec <?php echo htmlspecialchars($conversation['client_name']); ?>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <div class="messages-area">
                            <?php if (count($messages) === 0): ?>
                                <div style="text-align: center; color: #999; padding: 40px 20px;">
                                    <p>Aucun message pour l'instant.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message <?php echo $msg['sender_type']; ?>">
                                        <div class="message-content">
                                            <div class="message-bubble">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>
                                            <div class="message-info">
                                                <?php echo htmlspecialchars($msg['sender_name']); ?> • <?php echo date('d/m H:i', strtotime($msg['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($conversation['status'] !== 'closed'): ?>
                            <form method="POST" action="view.php?id=<?php echo $conv_id; ?>">
                                <input type="hidden" name="action" value="send_message">
                                <div class="form-group">
                                    <textarea name="message" placeholder="Tapez votre réponse..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">📨 Envoyer</button>
                            </form>
                        <?php else: ?>
                            <div style="background: #f8d7da; padding: 15px; border-radius: 5px; text-align: center; color: #842029;">
                                🔒 Cette conversation est fermée.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Conversation Info -->
                <div class="card">
                    <div class="card-header">📋 Informations</div>
                    <div class="card-body">
                        <div class="sidebar-info">
                            <div class="info-item">
                                <strong>Statut:</strong>
                                <span class="badge badge-status-<?php echo $conversation['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $conversation['status'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Priorité:</strong>
                                <span class="badge badge-priority-<?php echo $conversation['priority']; ?>">
                                    <?php echo ucfirst($conversation['priority']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Catégorie:</strong>
                                <span><?php echo ucfirst(str_replace('_', ' ', $conversation['category'])); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Client:</strong>
                                <span><?php echo htmlspecialchars($conversation['client_email']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Créée:</strong>
                                <span><?php echo date('d/m/Y H:i', strtotime($conversation['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Assignée à:</strong>
                                <span><?php echo $conversation['admin_name'] ?? 'Non assignée'; ?></span>
                            </div>
                        </div>
                        
                        <!-- Change Status -->
                        <form method="POST" action="view.php?id=<?php echo $conv_id; ?>" style="margin-bottom: 15px;">
                            <input type="hidden" name="action" value="change_status">
                            <div class="form-group">
                                <label>Changer le statut</label>
                                <select name="status" onchange="this.form.submit();">
                                    <option value="open" <?php echo $conversation['status'] === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                                    <option value="in_progress" <?php echo $conversation['status'] === 'in_progress' ? 'selected' : ''; ?>>En Cours</option>
                                    <option value="closed" <?php echo $conversation['status'] === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                                    <option value="archived" <?php echo $conversation['status'] === 'archived' ? 'selected' : ''; ?>>Archivé</option>
                                </select>
                            </div>
                        </form>
                        
                        <!-- Assign To -->
                        <?php if (!empty($admins)): ?>
                            <form method="POST" action="view.php?id=<?php echo $conv_id; ?>">
                                <input type="hidden" name="action" value="assign">
                                <div class="form-group">
                                    <label>Assigner à un admin</label>
                                    <select name="admin_id" onchange="this.form.submit();">
                                        <option value="">-- Sélectionner --</option>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>" <?php echo $conversation['admin_id'] == $admin['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Client Info -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">👤 Client</div>
                    <div class="card-body">
                        <div class="sidebar-info">
                            <div class="info-item">
                                <strong>Nom:</strong>
                                <span><?php echo htmlspecialchars($conversation['client_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong>
                                <span style="word-break: break-all;"><?php echo htmlspecialchars($conversation['client_email']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        document.querySelector('.messages-area').scrollTop = document.querySelector('.messages-area').scrollHeight;
    </script>
</body>
</html>