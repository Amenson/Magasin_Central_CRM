<?php
/**
 * CREATE NEW MESSAGE
 * Interface for clients to start a new conversation
 */

session_start();

require_once '../../config.php';
require_once '../config/messaging_config.php';
require_once '../classes/MessagingClass.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$messaging = new MessagingSystem($pdo, $_SESSION['user_id'], 'client');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'other');
    $priority = trim($_POST['priority'] ?? 'medium');
    
    if (!$subject || !$description) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else if (strlen($subject) < 5) {
        $error = 'Le sujet doit contenir au moins 5 caractères.';
    } else if (strlen($description) < 20) {
        $error = 'La description doit contenir au moins 20 caractères.';
    } else {
        $conversation_id = $messaging->createConversation($subject, $description, $category, $priority);
        
        if ($conversation_id) {
            $success = 'Conversation créée avec succès! Vous serez redirigé...';
            header('Refresh: 2; url=view.php?id=' . $conversation_id);
        } else {
            $error = 'Erreur lors de la création de la conversation. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Message - Anon-ecommerce</title>
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
            max-width: 700px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            color: white;
        }
        
        .card-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
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
        
        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        button,
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
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
        
        .btn-cancel {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #e0e0e0;
        }
        
        .form-help {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📝 Nouveau Message</h1>
                <p>Contactez notre équipe support pour toute question ou problème</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="new.php">
                    <div class="form-group">
                        <label for="subject">
                            Sujet <span class="required">*</span>
                        </label>
                        <input type="text" id="subject" name="subject" required 
                               placeholder="Ex: Problème avec ma commande" 
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                               maxlength="255">
                        <div class="form-help">Minimum 5 caractères</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">
                            Description <span class="required">*</span>
                        </label>
                        <textarea id="description" name="description" required 
                                  placeholder="Décrivez votre problème ou question en détail..."
                                  maxlength="5000"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-help">Minimum 20 caractères</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Catégorie</label>
                            <select id="category" name="category">
                                <option value="order" <?php echo (isset($_POST['category']) && $_POST['category'] === 'order') ? 'selected' : ''; ?>>Commande</option>
                                <option value="product" <?php echo (isset($_POST['category']) && $_POST['category'] === 'product') ? 'selected' : ''; ?>>Produit</option>
                                <option value="delivery" <?php echo (isset($_POST['category']) && $_POST['category'] === 'delivery') ? 'selected' : ''; ?>>Livraison</option>
                                <option value="return" <?php echo (isset($_POST['category']) && $_POST['category'] === 'return') ? 'selected' : ''; ?>>Retour</option>
                                <option value="technical" <?php echo (isset($_POST['category']) && $_POST['category'] === 'technical') ? 'selected' : ''; ?>>Problème Technique</option>
                                <option value="other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'other') ? 'selected' : 'selected'; ?>>Autre</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priorité</label>
                            <select id="priority" name="priority">
                                <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Basse</option>
                                <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : 'selected'; ?>>Moyenne</option>
                                <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>Haute</option>
                                <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-cancel">Annuler</a>
                        <button type="submit" class="btn btn-submit">Envoyer le Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>