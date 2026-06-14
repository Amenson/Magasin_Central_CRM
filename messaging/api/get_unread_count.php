<?php
/**
 * API: GET UNREAD COUNT
 * Get number of unread messages for current user
 */

header('Content-Type: application/json');
session_start();

require_once '../../../config.php';
require_once '../../config/messaging_config.php';
require_once '../../classes/MessagingClass.php';

$response = ['success' => false, 'count' => 0];

try {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        throw new Exception('Non authentifié');
    }
    
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    $user_type = isset($_SESSION['user_id']) ? 'client' : 'admin';
    
    $messaging = new MessagingSystem($pdo, $user_id, $user_type);
    $count = $messaging->getUnreadCount();
    
    $response['success'] = true;
    $response['count'] = $count;
    
} catch (Exception $e) {
    http_response_code(400);
}

echo json_encode($response);
?>