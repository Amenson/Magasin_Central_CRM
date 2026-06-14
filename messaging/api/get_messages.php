<?php
/**
 * API: GET MESSAGES
 * Fetch messages for a conversation (with pagination)
 */

header('Content-Type: application/json');
session_start();

require_once '../../../config.php';
require_once '../../config/messaging_config.php';
require_once '../../classes/MessagingClass.php';

$response = ['success' => false, 'data' => []];

try {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        throw new Exception('Non authentifié');
    }
    
    if (!isset($_GET['conversation_id'])) {
        throw new Exception('ID de conversation manquant');
    }
    
    $conv_id = (int)$_GET['conversation_id'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : MESSAGING_PAGINATION_LIMIT;
    $offset = ($page - 1) * $limit;
    
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    $user_type = isset($_SESSION['user_id']) ? 'client' : 'admin';
    
    $messaging = new MessagingSystem($pdo, $user_id, $user_type);
    
    $messages = $messaging->getMessages($conv_id, $limit, $offset);
    
    foreach ($messages as &$msg) {
        $msg['attachments'] = $messaging->getAttachments($msg['id']);
    }
    
    $response['success'] = true;
    $response['data'] = $messages;
    $response['count'] = count($messages);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>