<?php
/**
 * API: SEND MESSAGE
 * Handle message sending via AJAX or form submission
 */

header('Content-Type: application/json');
session_start();

require_once '../../../config.php';
require_once '../../config/messaging_config.php';
require_once '../../classes/MessagingClass.php';

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        throw new Exception('Non authentifié');
    }
    
    if (!isset($_POST['conversation_id']) || !isset($_POST['message'])) {
        throw new Exception('Paramètres manquants');
    }
    
    $conv_id = (int)$_POST['conversation_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    $user_type = isset($_SESSION['user_id']) ? 'client' : 'admin';
    
    if (!$message) {
        throw new Exception('Le message ne peut pas être vide');
    }
    
    $messaging = new MessagingSystem($pdo, $user_id, $user_type);
    
    // Handle file uploads
    $attachments = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['name'] as $key => $filename) {
            $file_error = $_FILES['attachments']['error'][$key];
            $file_size = $_FILES['attachments']['size'][$key];
            $file_tmp = $_FILES['attachments']['tmp_name'][$key];
            
            if ($file_error === 0) {
                // Validate file size
                if ($file_size > MESSAGING_MAX_FILE_SIZE) {
                    throw new Exception('Le fichier ' . $filename . ' dépasse la taille maximale');
                }
                
                // Validate file type
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($file_ext, MESSAGING_ALLOWED_TYPES)) {
                    throw new Exception('Type de fichier non autorisé: ' . $file_ext);
                }
                
                // Save file
                $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
                $file_path = MESSAGING_UPLOAD_DIR . $new_filename;
                
                if (!move_uploaded_file($file_tmp, $file_path)) {
                    throw new Exception('Erreur lors du téléchargement');
                }
                
                $attachments[] = [
                    'original_name' => $filename,
                    'saved_name' => $new_filename,
                    'size' => $file_size,
                    'type' => $file_ext,
                    'path' => $file_path
                ];
            }
        }
    }
    
    // Send message
    $message_id = $messaging->sendMessage($conv_id, $message, $attachments);
    
    if (!$message_id) {
        throw new Exception('Erreur lors de l\'envoi du message');
    }
    
    $response['success'] = true;
    $response['message'] = 'Message envoyé avec succès';
    $response['message_id'] = $message_id;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>