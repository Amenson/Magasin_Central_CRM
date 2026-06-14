<?php
/**
 * API: GET CONVERSATIONS
 * Fetch user's conversations with optional filters
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
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : MESSAGING_PAGINATION_LIMIT;
    $offset = ($page - 1) * $limit;
    
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    $user_type = isset($_SESSION['user_id']) ? 'client' : 'admin';
    
    $messaging = new MessagingSystem($pdo, $user_id, $user_type);
    
    if ($user_type === 'client') {
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $conversations = $messaging->getConversations($limit, $offset, $status);
    } else {
        $filters = [
            'status' => isset($_GET['status']) ? $_GET['status'] : '',
            'priority' => isset($_GET['priority']) ? $_GET['priority'] : '',
            'category' => isset($_GET['category']) ? $_GET['category'] : '',
            'search' => isset($_GET['search']) ? $_GET['search'] : ''
        ];
        $conversations = $messaging->getAdminConversations($limit, $offset, $filters);
    }
    
    $response['success'] = true;
    $response['data'] = $conversations;
    $response['count'] = count($conversations);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>