<?php
require_once __DIR__ . '/../config/Database.php';

class Department {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Get all departments
    public function getAllDepartments() {
        try {
            $this->db->query("SELECT * FROM departments ORDER BY name");
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get department by ID
    public function getDepartmentById($id) {
        try {
            $this->db->query("SELECT * FROM departments WHERE id = :id");
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Create new department
    public function create($data) {
        try {
            // Check if department name already exists
            $this->db->query("SELECT id FROM departments WHERE name = :name");
            $this->db->bind(':name', $data['name']);
            
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Department name already exists'];
            }
            
            $this->db->query("INSERT INTO departments (name, description) 
                            VALUES (:name, :description)");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':description', $data['description']);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Department created successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to create department'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update department
    public function update($id, $data) {
        try {
            // Check if department name already exists (excluding current department)
            $this->db->query("SELECT id FROM departments WHERE name = :name AND id != :id");
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':id', $id);
            
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Department name already exists'];
            }
            
            $this->db->query("UPDATE departments 
                            SET name = :name, description = :description 
                            WHERE id = :id");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Department updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update department'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Delete department
    public function delete($id) {
        try {
            // Check if department has users
            $this->db->query("SELECT COUNT(*) as count FROM users WHERE department_id = :id");
            $this->db->bind(':id', $id);
            $userCount = $this->db->single()['count'];
            
            // Check if department has tickets
            $this->db->query("SELECT COUNT(*) as count FROM tickets WHERE department_id = :id");
            $this->db->bind(':id', $id);
            $ticketCount = $this->db->single()['count'];
            
            if ($userCount > 0 || $ticketCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete department with existing users or tickets'];
            }
            
            $this->db->query("DELETE FROM departments WHERE id = :id");
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Department deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete department'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get department statistics
    public function getDepartmentStats($departmentId) {
        try {
            $this->db->query("SELECT 
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                COUNT(u.id) as user_count
                FROM departments d
                LEFT JOIN tickets t ON d.id = t.department_id
                LEFT JOIN users u ON d.id = u.department_id
                WHERE d.id = :id
                GROUP BY d.id");
            $this->db->bind(':id', $departmentId);
            return $this->db->single();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get departments with ticket counts
    public function getDepartmentsWithTicketCounts() {
        try {
            $this->db->query("SELECT d.*, COUNT(t.id) as ticket_count
                            FROM departments d
                            LEFT JOIN tickets t ON d.id = t.department_id
                            GROUP BY d.id, d.name, d.description, d.created_at, d.updated_at
                            ORDER BY ticket_count DESC, d.name");
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get departments with user counts
    public function getDepartmentsWithUserCounts() {
        try {
            $this->db->query("SELECT d.*, COUNT(u.id) as user_count
                            FROM departments d
                            LEFT JOIN users u ON d.id = u.department_id
                            GROUP BY d.id, d.name, d.description, d.created_at, d.updated_at
                            ORDER BY user_count DESC, d.name");
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Create department
    public function createDepartment($data) {
        try {
            // Check if department name already exists
            $this->db->query("SELECT id FROM departments WHERE name = :name");
            $this->db->bind(':name', $data['name']);
            
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Department name already exists'];
            }
            
            // Insert department
            $this->db->query("INSERT INTO departments (name, description) 
                            VALUES (:name, :description)");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':description', $data['description'] ?? null);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Department created successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to create department'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update department
    public function updateDepartment($departmentId, $data) {
        try {
            // Check if department name already exists (excluding current department)
            $this->db->query("SELECT id FROM departments WHERE name = :name AND id != :id");
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':id', $departmentId);
            
            if ($this->db->single()) {
                return false;
            }
            
            // Update department
            $this->db->query("UPDATE departments SET name = :name, description = :description 
                            WHERE id = :id");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':description', $data['description'] ?? null);
            $this->db->bind(':id', $departmentId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Delete department
    public function deleteDepartment($departmentId) {
        try {
            // Check if department has users
            $this->db->query("SELECT COUNT(*) as count FROM users WHERE department_id = :id");
            $this->db->bind(':id', $departmentId);
            $userCount = $this->db->single()['count'];
            
            if ($userCount > 0) {
                return false; // Cannot delete department with users
            }
            
            // Check if department has tickets
            $this->db->query("SELECT COUNT(*) as count FROM tickets WHERE department_id = :id");
            $this->db->bind(':id', $departmentId);
            $ticketCount = $this->db->single()['count'];
            
            if ($ticketCount > 0) {
                return false; // Cannot delete department with tickets
            }
            
            // Delete department
            $this->db->query("DELETE FROM departments WHERE id = :id");
            $this->db->bind(':id', $departmentId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
