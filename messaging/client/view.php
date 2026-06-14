<?php
/**
 * VIEW CONVERSATION
 * Display conversation messages and allow replies
 */

session_start();

require_once '../../config.php';
require_once '../config/messaging_config.php';
require_once '../classes/MessagingClass.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$messaging = new MessagingSystem($pdo, $_SESSION['user_id'], 'client');
$conv_id = (int)$_GET['id'];
$error = '';
$success = '';

// Get conversation
$conversation = $messaging->getConversation($conv_id);

if (!$conversation || $conversation['client_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

// Mark as read
$messaging->markConversationAsRead($conv_id);

// Get messages
$messages = $messaging->getMessages($conv_id);

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    
    if (!$message) {
        $error = 'Veuillez entrer un message.';
    } else {
        if ($messaging->sendMessage($conv_id, $message)) {
            $success = 'Message envoyé avec succès!';
            // Refresh messages
            $messages = $messaging->getMessages($conv_id);
        } else {
            $error = 'Erreur lors de l\'envoi du message.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($conversation['subject']); ?> - Messagerie</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-left h1 {
            color: #333;
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .header-meta {
            font-size: 13px;
            color: #888;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-open { background: #d4edda; color: #155724; }
        .status-in_progress { background: #cfe2ff; color: #084298; }
        .status-closed { background: #f8d7da; color: #842029; }
        
        .messages-area {
            background: white;
            padding: 30px;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
        }
        
        .message.admin .message-bubble {
            margin-left: auto;
            order: -1;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .message.admin .message-avatar {
            background: #27ae60;
        }
        
        .message-bubble {
            flex: 1;
            max-width: 70%;
        }
        
        .bubble-header {
            font-weight: 600;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .bubble-content {
            background: #f5f5f5;
            padding: 12px 15px;
            border-radius: 8px;
            word-wrap: break-word;
            line-height: 1.5;
            color: #333;
        }
        
        .message.admin .bubble-content {
            background: #d4edda;
        }
        
        .bubble-time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
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
        
        .form-area {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
        }
        
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-back {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            display: inline-block;
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-back:hover {
            background: #e0e0e0;
        }
        
        .conversation-closed {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            color: #842029;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><?php echo htmlspecialchars($conversation['subject']); ?></h1>
                <div class="header-meta">
                    Support: <strong><?php echo $conversation['admin_name'] ?? 'Non assigné'; ?></strong>
                    | Créé le <?php echo date('d/m/Y H:i', strtotime($conversation['created_at'])); ?>
                </div>
            </div>
            <div style="text-align: right;">
                <span class="status-badge status-<?php echo $conversation['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $conversation['status'])); ?>
                </span>
                <br>
                <a href="index.php" class="btn-back">← Retour</a>
            </div>
        </div>
        
        <div class="messages-area">
            <?php if (count($messages) === 0): ?>
                <div style="text-align: center; color: #999; padding: 40px 20px;">
                    <p>Aucun message pour l'instant. Commencez la conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_type']; ?>">
                        <div class="message-avatar">
                            <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                        </div>
                        <div class="message-bubble">
                            <div class="bubble-header"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                            <div class="bubble-content"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            <div class="bubble-time"><?php echo date('d/m H:i', strtotime($msg['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="form-area">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($conversation['status'] === 'closed'): ?>
                <div class="conversation-closed">
                    🔒 Cette conversation est fermée. Vous ne pouvez plus envoyer de messages.
                </div>
            <?php else: ?>
                <form method="POST" action="view.php?id=<?php echo $conv_id; ?>">
                    <div class="form-group">
                        <textarea name="message" placeholder="Tapez votre message ici..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Envoyer le Message</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        document.querySelector('.messages-area').scrollTop = document.querySelector('.messages-area').scrollHeight;
    </script>
</body>
</html>