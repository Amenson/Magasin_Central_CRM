<?php
/**
 * EMAIL NOTIFICATION HELPER
 * Send email notifications for new messages
 */

class MessagingEmailNotifier {
    private $pdo;
    private $from_email;
    private $from_name;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->from_email = MESSAGING_FROM_EMAIL;
        $this->from_name = MESSAGING_FROM_NAME;
    }
    
    /**
     * Send notification to client about new admin message
     */
    public function notifyClientNewMessage($conversation_id, $message_id) {
        try {
            // Get conversation and message details
            $conv = $this->pdo->prepare(
                "SELECT mc.id, mc.subject, u.email, u.name 
                FROM messages_conversations mc
                JOIN users u ON mc.client_id = u.id
                WHERE mc.id = ?"
            );
            $conv->execute([$conversation_id]);
            $conversation = $conv->fetch(PDO::FETCH_ASSOC);
            
            if (!$conversation || !MESSAGING_SEND_EMAILS) {
                return true;
            }
            
            $subject = 'Réponse: ' . $conversation['subject'];
            $body = $this->buildClientEmailBody($conversation);
            
            return $this->sendEmail($conversation['email'], $conversation['name'], $subject, $body);
        } catch (Exception $e) {
            error_log('Error notifying client: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to admin about new client message
     */
    public function notifyAdminNewMessage($conversation_id, $message_id, $admin_email) {
        try {
            if (!MESSAGING_SEND_EMAILS) {
                return true;
            }
            
            $conv = $this->pdo->prepare(
                "SELECT subject FROM messages_conversations WHERE id = ?"
            );
            $conv->execute([$conversation_id]);
            $conversation = $conv->fetch(PDO::FETCH_ASSOC);
            
            $subject = 'Nouveau message: ' . $conversation['subject'];
            $body = $this->buildAdminEmailBody($conversation);
            
            return $this->sendEmail($admin_email, 'Admin', $subject, $body);
        } catch (Exception $e) {
            error_log('Error notifying admin: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email
     */
    private function sendEmail($to, $name, $subject, $body) {
        $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        return mail(
            $to,
            $subject,
            $body,
            $headers
        );
    }
    
    /**
     * Build client email body
     */
    private function buildClientEmailBody($conversation) {
        $support_url = 'https://' . $_SERVER['HTTP_HOST'] . '/messaging/client/view.php?id=' . $conversation['id'];
        
        $body = "
        <html>
            <body style=\"font-family: Arial, sans-serif;\">
                <div style=\"max-width: 600px; margin: 0 auto;\">
                    <h2>Bonjour {$conversation['name']},</h2>
                    <p>Notre équipe support a répondu à votre message concerné par:</p>
                    <p><strong>{$conversation['subject']}</strong></p>
                    <p>Cliquez ci-dessous pour consulter la réponse:</p>
                    <p><a href=\"$support_url\" style=\"background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;\">
                        Voir la réponse
                    </a></p>
                    <p>Cordialement,<br>L'équipe " . MESSAGING_FROM_NAME . "</p>
                </div>
            </body>
        </html>
        ";
        
        return $body;
    }
    
    /**
     * Build admin email body
     */
    private function buildAdminEmailBody($conversation) {
        $admin_url = 'https://' . $_SERVER['HTTP_HOST'] . '/messaging/admin/view.php?id=' . $conversation['id'];
        
        $body = "
        <html>
            <body style=\"font-family: Arial, sans-serif;\">
                <div style=\"max-width: 600px; margin: 0 auto;\">
                    <h2>Nouveau message client</h2>
                    <p>Sujet: <strong>{$conversation['subject']}</strong></p>
                    <p>Un client a envoyé un nouveau message dans sa conversation support.</p>
                    <p><a href=\"$admin_url\" style=\"background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;\">
                        Voir le message
                    </a></p>
                </div>
            </body>
        </html>
        ";
        
        return $body;
    }
}
?>