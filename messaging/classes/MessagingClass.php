<?php
/**
 * MESSAGING CLASS
 * Core messaging system functionality
 */

class MessagingSystem {
    private $pdo;
    private $user_id;
    private $user_type; // 'client' or 'admin'
    
    public function __construct($pdo, $user_id, $user_type = 'client') {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }
    
    /**
     * Create new conversation
     */
    public function createConversation($subject, $description, $category = 'other', $priority = 'medium') {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO messages_conversations 
                (client_id, subject, description, category, priority) 
                VALUES (?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $this->user_id,
                $subject,
                $description,
                $category,
                $priority
            ]);
            
            if ($result) {
                $conversation_id = $this->pdo->lastInsertId();
                
                // Send initial message
                $this->sendMessage($conversation_id, $description);
                
                // Notify admins
                $this->notifyAdmins($conversation_id, NOTIFICATION_TYPE['NEW_CONVERSATION']);
                
                return $conversation_id;
            }
            return false;
        } catch (Exception $e) {
            error_log('Error creating conversation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user conversations
     */
    public function getConversations($limit = 20, $offset = 0, $status = null) {
        try {
            $query = "SELECT mc.*, 
                      (SELECT COUNT(*) FROM messages_content 
                       WHERE conversation_id = mc.id AND read_status = 0 
                       AND sender_type = ? 
                       AND sender_id != ?) as unread_count,
                      u.name as client_name,
                      a.username as admin_name,
                      (SELECT COUNT(*) FROM messages_content 
                       WHERE conversation_id = mc.id) as message_count,
                      (SELECT MAX(created_at) FROM messages_content 
                       WHERE conversation_id = mc.id) as last_message_time
                      FROM messages_conversations mc
                      LEFT JOIN users u ON mc.client_id = u.id
                      LEFT JOIN admins a ON mc.admin_id = a.id
                      WHERE mc.client_id = ?";
            
            $params = ['client', $this->user_id, $this->user_id];
            
            if ($status) {
                $query .= " AND mc.status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY mc.updated_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting conversations: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get admin conversations
     */
    public function getAdminConversations($limit = 20, $offset = 0, $filters = []) {
        try {
            $query = "SELECT mc.*, 
                      (SELECT COUNT(*) FROM messages_content 
                       WHERE conversation_id = mc.id AND read_status = 0) as unread_count,
                      u.name as client_name,
                      (SELECT COUNT(*) FROM messages_content 
                       WHERE conversation_id = mc.id) as message_count,
                      (SELECT MAX(created_at) FROM messages_content 
                       WHERE conversation_id = mc.id) as last_message_time
                      FROM messages_conversations mc
                      LEFT JOIN users u ON mc.client_id = u.id
                      WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $query .= " AND mc.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $query .= " AND mc.priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['category'])) {
                $query .= " AND mc.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['assigned_to_me'])) {
                $query .= " AND mc.admin_id = ?";
                $params[] = $this->user_id;
            }
            
            if (!empty($filters['unassigned'])) {
                $query .= " AND mc.admin_id IS NULL";
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (mc.subject LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $query .= " ORDER BY mc.priority DESC, mc.updated_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting admin conversations: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get single conversation
     */
    public function getConversation($conversation_id) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT mc.*, 
                u.name as client_name, u.email as client_email,
                a.username as admin_name
                FROM messages_conversations mc
                LEFT JOIN users u ON mc.client_id = u.id
                LEFT JOIN admins a ON mc.admin_id = a.id
                WHERE mc.id = ?"
            );
            $stmt->execute([$conversation_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting conversation: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get conversation messages
     */
    public function getMessages($conversation_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT mc.*, 
                IF(mc.sender_type = 'client', u.name, a.username) as sender_name,
                (SELECT COUNT(*) FROM messages_attachments WHERE message_id = mc.id) as attachment_count
                FROM messages_content mc
                LEFT JOIN users u ON mc.sender_type = 'client' AND mc.sender_id = u.id
                LEFT JOIN admins a ON mc.sender_type = 'admin' AND mc.sender_id = a.id
                WHERE mc.conversation_id = ?
                ORDER BY mc.created_at ASC
                LIMIT ? OFFSET ?"
            );
            $stmt->execute([$conversation_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting messages: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send message
     */
    public function sendMessage($conversation_id, $message, $attachments = []) {
        try {
            // Verify access to conversation
            if (!$this->canAccessConversation($conversation_id)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO messages_content 
                (conversation_id, sender_id, sender_type, message, attachment_count) 
                VALUES (?, ?, ?, ?, ?)"
            );
            
            $attachment_count = count($attachments);
            
            $result = $stmt->execute([
                $conversation_id,
                $this->user_id,
                $this->user_type,
                $message,
                $attachment_count
            ]);
            
            if ($result) {
                $message_id = $this->pdo->lastInsertId();
                
                // Handle attachments
                if (!empty($attachments)) {
                    $this->addAttachments($message_id, $attachments);
                }
                
                // Update conversation timestamp
                $this->updateConversationTimestamp($conversation_id);
                
                // Mark as read
                $this->markConversationAsRead($conversation_id);
                
                // Notify recipient
                $this->notifyMessageReceived($conversation_id, $message_id);
                
                return $message_id;
            }
            return false;
        } catch (Exception $e) {
            error_log('Error sending message: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark conversation as read
     */
    public function markConversationAsRead($conversation_id) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE messages_content 
                SET read_status = 1 
                WHERE conversation_id = ? AND sender_id != ? AND read_status = 0"
            );
            return $stmt->execute([$conversation_id, $this->user_id]);
        } catch (Exception $e) {
            error_log('Error marking as read: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update conversation status
     */
    public function updateConversationStatus($conversation_id, $status) {
        try {
            // Admin only action
            if ($this->user_type !== 'admin') {
                return false;
            }
            
            $closed_at = ($status === 'closed') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $this->pdo->prepare(
                "UPDATE messages_conversations 
                SET status = ?, closed_at = ?
                WHERE id = ?"
            );
            return $stmt->execute([$status, $closed_at, $conversation_id]);
        } catch (Exception $e) {
            error_log('Error updating conversation status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign conversation to admin
     */
    public function assignConversation($conversation_id, $admin_id) {
        try {
            // Admin only action
            if ($this->user_type !== 'admin') {
                return false;
            }
            
            $stmt = $this->pdo->prepare(
                "UPDATE messages_conversations 
                SET admin_id = ?, assigned_at = NOW(), status = 'in_progress'
                WHERE id = ?"
            );
            
            $result = $stmt->execute([$admin_id, $conversation_id]);
            
            if ($result) {
                // Notify assigned admin
                $this->notifyAdmins($conversation_id, NOTIFICATION_TYPE['ASSIGNMENT'], $admin_id);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Error assigning conversation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add attachments to message
     */
    public function addAttachments($message_id, $files) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO messages_attachments 
                (message_id, original_filename, saved_filename, file_size, file_type, file_path, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            foreach ($files as $file) {
                $stmt->execute([
                    $message_id,
                    $file['original_name'],
                    $file['saved_name'],
                    $file['size'],
                    $file['type'],
                    $file['path'],
                    $this->user_id
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error adding attachments: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get message attachments
     */
    public function getAttachments($message_id) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM messages_attachments WHERE message_id = ?"
            );
            $stmt->execute([$message_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting attachments: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rate conversation (client only)
     */
    public function rateConversation($conversation_id, $rating, $comment = '') {
        try {
            if ($this->user_type !== 'client') {
                return false;
            }
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO messages_ratings 
                (conversation_id, client_id, rating, comment) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = ?, comment = ?"
            );
            
            return $stmt->execute([
                $conversation_id,
                $this->user_id,
                $rating,
                $comment,
                $rating,
                $comment
            ]);
        } catch (Exception $e) {
            error_log('Error rating conversation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread count for user
     */
    public function getUnreadCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM messages_content mc
                     INNER JOIN messages_conversations conv ON mc.conversation_id = conv.id
                     WHERE mc.read_status = 0 AND mc.sender_id != ?";
            
            if ($this->user_type === 'client') {
                $query .= " AND conv.client_id = ?";
            } else {
                $query .= " AND conv.admin_id = ?";
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id, $this->user_id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Error getting unread count: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Private helper: Verify access to conversation
     */
    private function canAccessConversation($conversation_id) {
        try {
            $query = "SELECT id FROM messages_conversations WHERE id = ?";
            
            if ($this->user_type === 'client') {
                $query .= " AND client_id = ?";
                $params = [$conversation_id, $this->user_id];
            } else {
                $query .= " AND (admin_id = ? OR admin_id IS NULL)";
                $params = [$conversation_id, $this->user_id];
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Private helper: Update conversation timestamp
     */
    private function updateConversationTimestamp($conversation_id) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE messages_conversations SET updated_at = NOW() WHERE id = ?"
            );
            return $stmt->execute([$conversation_id]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Private helper: Notify admins of new conversation or message
     */
    private function notifyAdmins($conversation_id, $notification_type, $specific_admin_id = null) {
        try {
            if ($notification_type === NOTIFICATION_TYPE['ASSIGNMENT']) {
                // Notify specific admin
                $stmt = $this->pdo->prepare(
                    "INSERT INTO messages_notifications 
                    (admin_id, conversation_id, type) 
                    VALUES (?, ?, ?)"
                );
                $stmt->execute([$specific_admin_id, $conversation_id, $notification_type]);
            } else {
                // Notify all admins
                $stmt = $this->pdo->prepare(
                    "SELECT id FROM admins WHERE id != ?"
                );
                $stmt->execute([$this->user_id]);
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($admins as $admin) {
                    $insert = $this->pdo->prepare(
                        "INSERT INTO messages_notifications 
                        (admin_id, conversation_id, type) 
                        VALUES (?, ?, ?)"
                    );
                    $insert->execute([$admin['id'], $conversation_id, $notification_type]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error notifying admins: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Private helper: Notify message received
     */
    private function notifyMessageReceived($conversation_id, $message_id) {
        try {
            // Get conversation
            $conv = $this->getConversation($conversation_id);
            
            if (!$conv) return false;
            
            // Determine recipient
            if ($this->user_type === 'client') {
                // Notify assigned admin
                if ($conv['admin_id']) {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO messages_notifications 
                        (admin_id, conversation_id, message_id, type) 
                        VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $conv['admin_id'],
                        $conversation_id,
                        $message_id,
                        NOTIFICATION_TYPE['NEW_MESSAGE']
                    ]);
                }
            } else {
                // Notify client (not applicable here, handled via email)
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error notifying message: ' . $e->getMessage());
            return false;
        }
    }
}
?>