<?php
require_once __DIR__ . '/../config/Database.php';

class Ticket {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new tickets
    public function create($data) {
        try {
            $ticketCode = generateTicketCode();
            
            $this->db->query("INSERT INTO tickets 
                            (ticket_code, user_id, department_id, title, description, priority, status) 
                            VALUES (:ticket_code, :user_id, :department_id, :title, :description, :priority, :status)");
            
            $this->db->bind(':ticket_code', $ticketCode);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->bind(':department_id', $data['department_id']);
            $this->db->bind(':title', $data['title']);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':priority', $data['priority'] ?? 'medium');
            $this->db->bind(':status', 'pending');
            
            if ($this->db->execute()) {
                $ticketId = $this->db->lastInsertId();
                return ['success' => true, 'ticket_id' => $ticketId, 'ticket_code' => $ticketCode];
            } else {
                return ['success' => false, 'message' => 'Failed to create ticket'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get ticket by ID
    public function getTicketById($id) {
        try {
            $this->db->query("SELECT t.*, u.name as user_name, u.email as user_email, 
                            d.name as department_name, s.name as assigned_staff_name 
                            FROM tickets t
                            JOIN users u ON t.user_id = u.id
                            JOIN departments d ON t.department_id = d.id
                            LEFT JOIN users s ON t.assigned_staff_id = s.id
                            WHERE t.id = :id");
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get tickets by user
    public function getTicketsByUser($userId, $limit = null, $offset = 0) {
        try {
            $query = "SELECT t.*, d.name as department_name, s.name as staff_name
                     FROM tickets t
                     LEFT JOIN departments d ON t.department_id = d.id
                     LEFT JOIN users s ON t.assigned_staff_id = s.id
                     WHERE t.user_id = :user_id
                     ORDER BY t.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $this->db->query($query);
            $this->db->bind(':user_id', $userId);
            
            if ($limit) {
                $this->db->bind(':limit', $limit, PDO::PARAM_INT);
                $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get tickets assigned to staff
    public function getTicketsByStaff($staffId, $limit = null, $offset = 0, $departmentId = null) {
        try {
            $query = "SELECT t.*, u.name as user_name, d.name as department_name
                     FROM tickets t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN departments d ON t.department_id = d.id
                     WHERE t.assigned_staff_id = :staff_id";
            
            // If department ID is provided, filter by department
            if ($departmentId) {
                $query .= " AND t.department_id = :department_id";
            }
            
            $query .= " ORDER BY t.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $this->db->query($query);
            $this->db->bind(':staff_id', $staffId);
            
            if ($departmentId) {
                $this->db->bind(':department_id', $departmentId);
            }
            
            if ($limit) {
                $this->db->bind(':limit', $limit, PDO::PARAM_INT);
                $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get tickets for staff (assigned + pending in their department)
    public function getTicketsForStaff($staffId, $departmentId, $limit = null, $offset = 0) {
        try {
            $query = "SELECT t.*, u.name as user_name, d.name as department_name
                     FROM tickets t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN departments d ON t.department_id = d.id
                     WHERE t.department_id = :department_id AND (t.assigned_staff_id = :staff_id OR t.status = 'pending')
                     ORDER BY t.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $this->db->query($query);
            $this->db->bind(':department_id', $departmentId);
            $this->db->bind(':staff_id', $staffId);
            
            if ($limit) {
                $this->db->bind(':limit', $limit, PDO::PARAM_INT);
                $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get all tickets (admin)
    public function getAllTickets($limit = null, $offset = 0) {
        try {
            $query = "SELECT t.*, u.name as user_name, d.name as department_name, s.name as staff_name
                     FROM tickets t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN departments d ON t.department_id = d.id
                     LEFT JOIN users s ON t.assigned_staff_id = s.id
                     ORDER BY t.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $this->db->query($query);
            
            if ($limit) {
                $this->db->bind(':limit', $limit, PDO::PARAM_INT);
                $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Assign ticket to staff
    public function assignToStaff($ticketId, $staffId) {
        try {
            $this->db->query("UPDATE tickets SET assigned_staff_id = :staff_id, status = 'assigned', updated_at = NOW() 
                            WHERE id = :ticket_id");
            $this->db->bind(':staff_id', $staffId);
            $this->db->bind(':ticket_id', $ticketId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Update ticket status
    public function updateStatus($ticketId, $status, $assignedStaffId = null) {
        try {
            $sql = "UPDATE tickets SET status = :status";
            if ($assignedStaffId !== null) {
                $sql .= ", assigned_staff_id = :assigned_staff_id";
            }
            $sql .= " WHERE id = :id";
            
            $this->db->query($sql);
            $this->db->bind(':status', $status);
            $this->db->bind(':id', $ticketId);
            
            if ($assignedStaffId !== null) {
                $this->db->bind(':assigned_staff_id', $assignedStaffId);
            }
            
            return $this->db->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Add note to ticket
    public function addNote($ticketId, $staffId, $note) {
        try {
            $this->db->query("INSERT INTO ticket_notes (ticket_id, staff_id, note) 
                            VALUES (:ticket_id, :staff_id, :note)");
            
            $this->db->bind(':ticket_id', $ticketId);
            $this->db->bind(':staff_id', $staffId);
            $this->db->bind(':note', $note);
            
            return $this->db->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get ticket notes
    public function getTicketNotes($ticketId) {
        try {
            $this->db->query("SELECT tn.*, u.name as staff_name 
                            FROM ticket_notes tn
                            JOIN users u ON tn.staff_id = u.id
                            WHERE tn.ticket_id = :ticket_id
                            ORDER BY tn.created_at ASC");
            $this->db->bind(':ticket_id', $ticketId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get ticket statistics for dashboard
    public function getTicketStats($userId = null, $userRole = null, $departmentId = null) {
        try {
            $stats = [];
            
            if ($userRole === 'user') {
                // User stats
                $this->db->query("SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                    FROM tickets WHERE user_id = :user_id");
                $this->db->bind(':user_id', $userId);
                
            } elseif ($userRole === 'staff') {
                // Staff stats - only from their department
                $this->db->query("SELECT 
                    COUNT(*) as assigned_tickets,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed
                    FROM tickets WHERE department_id = :department_id AND (assigned_staff_id = :staff_id OR status = 'pending')");
                $this->db->bind(':department_id', $departmentId);
                $this->db->bind(':staff_id', $userId);
                
            } else {
                // Admin stats
                $this->db->query("SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                    FROM tickets");
            }
            
            $stats = $this->db->single();
            return $stats ?: [];
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get recent tickets
    public function getRecentTickets($limit = 5, $userId = null, $userRole = null, $departmentId = null) {
        try {
            $sql = "SELECT t.*, d.name as department_name, s.name as assigned_staff_name,
                   u.name as user_name
                   FROM tickets t
                   JOIN departments d ON t.department_id = d.id
                   JOIN users u ON t.user_id = u.id
                   LEFT JOIN users s ON t.assigned_staff_id = s.id";
            
            if ($userRole === 'user') {
                $sql .= " WHERE t.user_id = :user_id";
                $this->db->query($sql . " ORDER BY t.created_at DESC LIMIT :limit");
                $this->db->bind(':user_id', $userId);
            } elseif ($userRole === 'staff') {
                // Staff can only see tickets from their department
                $sql .= " WHERE t.department_id = :department_id AND (t.assigned_staff_id = :staff_id OR t.status = 'pending')";
                $this->db->query($sql . " ORDER BY t.created_at DESC LIMIT :limit");
                $this->db->bind(':department_id', $departmentId);
                $this->db->bind(':staff_id', $userId);
            } else {
                $this->db->query($sql . " ORDER BY t.created_at DESC LIMIT :limit");
            }
            
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get tickets by department
    public function getTicketsByDepartment($departmentId) {
        try {
            $this->db->query("SELECT t.*, u.name as user_name, s.name as assigned_staff_name 
                            FROM tickets t
                            JOIN users u ON t.user_id = u.id
                            LEFT JOIN users s ON t.assigned_staff_id = s.id
                            WHERE t.department_id = :department_id
                            ORDER BY t.created_at DESC");
            $this->db->bind(':department_id', $departmentId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get tickets by status
    public function getTicketsByStatus($status) {
        try {
            $this->db->query("SELECT t.*, u.name as user_name, d.name as department_name, 
                            s.name as assigned_staff_name 
                            FROM tickets t
                            JOIN users u ON t.user_id = u.id
                            JOIN departments d ON t.department_id = d.id
                            LEFT JOIN users s ON t.assigned_staff_id = s.id
                            WHERE t.status = :status
                            ORDER BY t.created_at DESC");
            $this->db->bind(':status', $status);
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Search tickets
    public function searchTickets($searchTerm, $userId = null, $userRole = null) {
        try {
            $sql = "SELECT t.*, u.name as user_name, d.name as department_name, 
                   s.name as assigned_staff_name 
                   FROM tickets t
                   JOIN users u ON t.user_id = u.id
                   JOIN departments d ON t.department_id = d.id
                   LEFT JOIN users s ON t.assigned_staff_id = s.id
                   WHERE (t.title LIKE :search OR t.description LIKE :search OR t.ticket_code LIKE :search)";
            
            if ($userRole === 'user') {
                $sql .= " AND t.user_id = :user_id";
            } elseif ($userRole === 'staff') {
                $sql .= " AND (t.assigned_staff_id = :staff_id OR t.status = 'pending')";
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $this->db->query($sql);
            $this->db->bind(':search', '%' . $searchTerm . '%');
            
            if ($userRole === 'user') {
                $this->db->bind(':user_id', $userId);
            } elseif ($userRole === 'staff') {
                $this->db->bind(':staff_id', $userId);
            }
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Delete ticket (admin only)
    public function deleteTicket($ticketId) {
        try {
            $this->db->beginTransaction();
            
            // Delete ticket notes first
            $this->db->query("DELETE FROM ticket_notes WHERE ticket_id = :ticket_id");
            $this->db->bind(':ticket_id', $ticketId);
            $this->db->execute();
            
            // Delete ticket
            $this->db->query("DELETE FROM tickets WHERE id = :ticket_id");
            $this->db->bind(':ticket_id', $ticketId);
            $result = $this->db->execute();
            
            if ($result) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Ticket deleted successfully'];
            } else {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to delete ticket'];
            }
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get report data
    public function getReportData() {
        try {
            $reports = [];
            
            // Tickets by department
            $this->db->query("SELECT d.name, COUNT(t.id) as ticket_count
                            FROM departments d
                            LEFT JOIN tickets t ON d.id = t.department_id
                            GROUP BY d.id, d.name
                            ORDER BY ticket_count DESC");
            $reports['by_department'] = $this->db->resultSet();
            
            // Tickets by status
            $this->db->query("SELECT status, COUNT(*) as count
                            FROM tickets
                            GROUP BY status");
            $reports['by_status'] = $this->db->resultSet();
            
            // Tickets by priority
            $this->db->query("SELECT priority, COUNT(*) as count
                            FROM tickets
                            GROUP BY priority");
            $reports['by_priority'] = $this->db->resultSet();
            
            // Total tickets
            $this->db->query("SELECT COUNT(*) as total FROM tickets");
            $reports['total_tickets'] = $this->db->single()['total'];
            
            return $reports;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Update ticket
    public function updateTicket($ticketId, $data) {
        try {
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            $setClause = implode(', ', $setClause);
            
            $this->db->query("UPDATE tickets SET $setClause WHERE id = :ticket_id");
            
            // Bind all values
            foreach ($data as $key => $value) {
                $this->db->bind(":$key", $value);
            }
            $this->db->bind(':ticket_id', $ticketId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get ticket count
    public function getTicketCount($userId = null, $userRole = null, $departmentId = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM tickets t";
            
            if ($userId && $userRole === 'user') {
                $query .= " WHERE t.user_id = :user_id";
                $this->db->query($query);
                $this->db->bind(':user_id', $userId);
            } elseif ($userId && $userRole === 'staff') {
                // For staff, count tickets from their department (assigned + pending)
                if ($departmentId) {
                    $query .= " WHERE t.department_id = :department_id AND (t.assigned_staff_id = :staff_id OR t.status = 'pending')";
                    $this->db->query($query);
                    $this->db->bind(':department_id', $departmentId);
                    $this->db->bind(':staff_id', $userId);
                } else {
                    $query .= " WHERE t.assigned_staff_id = :staff_id";
                    $this->db->query($query);
                    $this->db->bind(':staff_id', $userId);
                }
            } else {
                $this->db->query($query);
            }
            
            return $this->db->single()['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // Assign ticket to staff member
    public function assignTicket($ticketId, $staffId) {
        try {
            $this->db->query("UPDATE tickets 
                            SET assigned_staff_id = :staff_id, 
                                status = 'assigned',
                                updated_at = NOW() 
                            WHERE id = :ticket_id");
            
            $this->db->bind(':ticket_id', $ticketId);
            $this->db->bind(':staff_id', $staffId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Add note to ticket
    public function addTicketNote($data) {
        try {
            $this->db->query("INSERT INTO ticket_notes 
                            (ticket_id, user_id, note, is_internal, created_at) 
                            VALUES (:ticket_id, :user_id, :note, :is_internal, NOW())");
            
            $this->db->bind(':ticket_id', $data['ticket_id']);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->bind(':note', $data['note']);
            $this->db->bind(':is_internal', $data['is_internal'] ?? 0);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
