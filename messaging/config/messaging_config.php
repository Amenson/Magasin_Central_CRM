<?php
/**
 * MESSAGING SYSTEM CONFIGURATION
 * Professional messaging system for Anon-ecommerce
 */

// Database connection (use from main config.php)
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../../config.php';
}

// Messaging System Constants
define('MESSAGING_UPLOAD_DIR', __DIR__ . '/../../uploads/messages/');
define('MESSAGING_MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('MESSAGING_ALLOWED_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip']);
define('MESSAGING_PAGINATION_LIMIT', 20);
define('MESSAGING_AUTO_ASSIGN_ADMIN', true);
define('MESSAGING_RESPONSE_TIMEOUT', 24); // Hours
define('MESSAGING_CLOSE_INACTIVE_DAYS', 30); // Days

// Email Settings for Notifications
define('MESSAGING_SEND_EMAILS', true);
define('MESSAGING_ADMIN_EMAIL', 'admin@anon-ecommerce.com');
define('MESSAGING_FROM_EMAIL', 'noreply@anon-ecommerce.com');
define('MESSAGING_FROM_NAME', 'Anon-ecommerce Support');

// Ensure upload directory exists
if (!is_dir(MESSAGING_UPLOAD_DIR)) {
    mkdir(MESSAGING_UPLOAD_DIR, 0755, true);
}

// Message statuses
const MESSAGE_STATUS = [
    'OPEN' => 'open',
    'IN_PROGRESS' => 'in_progress',
    'CLOSED' => 'closed',
    'ARCHIVED' => 'archived'
];

// Message priorities
const MESSAGE_PRIORITY = [
    'LOW' => 'low',
    'MEDIUM' => 'medium',
    'HIGH' => 'high',
    'URGENT' => 'urgent'
];

// Message categories
const MESSAGE_CATEGORY = [
    'ORDER' => 'order',
    'PRODUCT' => 'product',
    'DELIVERY' => 'delivery',
    'RETURN' => 'return',
    'TECHNICAL' => 'technical',
    'OTHER' => 'other'
];

// Notification types
const NOTIFICATION_TYPE = [
    'NEW_MESSAGE' => 'new_message',
    'NEW_CONVERSATION' => 'new_conversation',
    'ASSIGNMENT' => 'assignment',
    'RATING' => 'rating'
];
?>