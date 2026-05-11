<?php
require_once __DIR__ . '/../config/Database.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create a new notification
    public function create($userId, $ticketId, $message, $type = 'status_update') {
        try {
            $this->db->query("INSERT INTO notifications (user_id, ticket_id, message, type) 
                            VALUES (:user_id, :ticket_id, :message, :type)");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':ticket_id', $ticketId);
            $this->db->bind(':message', $message);
            $this->db->bind(':type', $type);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get notifications for a user
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        try {
            $sql = "SELECT n.*, t.ticket_code, t.title as ticket_title 
                    FROM notifications n
                    JOIN tickets t ON n.ticket_id = t.id
                    WHERE n.user_id = :user_id";
            
            if ($unreadOnly) {
                $sql .= " AND n.is_read = FALSE";
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT :limit";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get unread notification count for a user
    public function getUnreadCount($userId) {
        try {
            $this->db->query("SELECT COUNT(*) as count FROM notifications 
                            WHERE user_id = :user_id AND is_read = FALSE");
            $this->db->bind(':user_id', $userId);
            
            $result = $this->db->single();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // Mark notification as read
    public function markAsRead($notificationId, $userId) {
        try {
            $this->db->query("UPDATE notifications SET is_read = TRUE 
                            WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $notificationId);
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Mark all notifications as read for a user
    public function markAllAsRead($userId) {
        try {
            $this->db->query("UPDATE notifications SET is_read = TRUE 
                            WHERE user_id = :user_id");
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Create notification for ticket status update
    public function createStatusUpdateNotification($ticketId, $newStatus) {
        try {
            // Get ticket details
            $this->db->query("SELECT t.user_id, t.ticket_code, t.title, u.name as user_name 
                            FROM tickets t 
                            JOIN users u ON t.user_id = u.id 
                            WHERE t.id = :ticket_id");
            $this->db->bind(':ticket_id', $ticketId);
            $ticket = $this->db->single();
            
            if (!$ticket) return false;
            
            // Create appropriate message based on status
            $message = $this->getStatusMessage($ticket['ticket_code'], $newStatus);
            $type = ($newStatus === 'resolved') ? 'resolved' : 'status_update';
            
            return $this->create($ticket['user_id'], $ticketId, $message, $type);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Create notification for ticket assignment
    public function createAssignmentNotification($ticketId, $assignedStaffId) {
        try {
            // Get ticket and staff details
            $this->db->query("SELECT t.user_id, t.ticket_code, t.title, u.name as user_name, s.name as staff_name 
                            FROM tickets t 
                            JOIN users u ON t.user_id = u.id 
                            JOIN users s ON t.assigned_staff_id = s.id 
                            WHERE t.id = :ticket_id");
            $this->db->bind(':ticket_id', $ticketId);
            $ticket = $this->db->single();
            
            if (!$ticket) return false;
            
            $message = "Your ticket {$ticket['ticket_code']} has been assigned to {$ticket['staff_name']}";
            
            return $this->create($ticket['user_id'], $ticketId, $message, 'assignment');
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get appropriate message for status update
    private function getStatusMessage($ticketCode, $status) {
        switch ($status) {
            case 'pending':
                return "Your ticket {$ticketCode} is pending review";
            case 'assigned':
                return "Your ticket {$ticketCode} has been assigned to a staff member";
            case 'in_progress':
                return "Your ticket {$ticketCode} is now being worked on";
            case 'resolved':
                return "Your ticket {$ticketCode} has been resolved";
            case 'closed':
                return "Your ticket {$ticketCode} has been closed";
            default:
                return "Your ticket {$ticketCode} status has been updated to {$status}";
        }
    }
}
?>
