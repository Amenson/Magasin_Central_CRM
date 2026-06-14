<?php
/**
 * API: DOWNLOAD ATTACHMENT
 * Secure file download with access verification
 */

session_start();

require_once '../../../config.php';
require_once '../../config/messaging_config.php';
require_once '../../classes/MessagingClass.php';

try {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        throw new Exception('Non authentifié');
    }
    
    if (!isset($_GET['id'])) {
        throw new Exception('ID de pièce jointe manquant');
    }
    
    $attachment_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    
    // Get attachment info
    $stmt = $pdo->prepare(
        "SELECT ma.*, mc.conversation_id 
        FROM messages_attachments ma
        JOIN messages_content mc ON ma.message_id = mc.id
        WHERE ma.id = ?"
    );
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        throw new Exception('Pièce jointe non trouvée');
    }
    
    // Verify access
    $conv = $pdo->prepare(
        "SELECT client_id, admin_id FROM messages_conversations WHERE id = ?"
    );
    $conv->execute([$attachment['conversation_id']]);
    $conversation = $conv->fetch(PDO::FETCH_ASSOC);
    
    $is_client = isset($_SESSION['user_id']) && $conversation['client_id'] == $user_id;
    $is_admin = isset($_SESSION['admin_id']) && $conversation['admin_id'] == $user_id;
    
    if (!$is_client && !$is_admin) {
        throw new Exception('Accès refusé');
    }
    
    // Download file
    if (!file_exists($attachment['file_path'])) {
        throw new Exception('Fichier non trouvé');
    }
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . filesize($attachment['file_path']));
    
    readfile($attachment['file_path']);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo 'Erreur: ' . $e->getMessage();
}
?>