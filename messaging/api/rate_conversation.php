<?php
/**
 * API: RATE CONVERSATION
 * Allow clients to rate conversations after closing
 */

header('Content-Type: application/json');
session_start();

require_once '../../../config.php';
require_once '../../config/messaging_config.php';
require_once '../../classes/MessagingClass.php';

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Vous devez être connecté');
    }
    
    if (!isset($_POST['conversation_id']) || !isset($_POST['rating'])) {
        throw new Exception('Paramètres manquants');
    }
    
    $conv_id = (int)$_POST['conversation_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception('La note doit être entre 1 et 5');
    }
    
    $messaging = new MessagingSystem($pdo, $_SESSION['user_id'], 'client');
    
    if ($messaging->rateConversation($conv_id, $rating, $comment)) {
        $response['success'] = true;
        $response['message'] = 'Merci d\'avoir valué cette conversation!';
    } else {
        throw new Exception('Erreur lors de l\'enregistrement de la notation');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>