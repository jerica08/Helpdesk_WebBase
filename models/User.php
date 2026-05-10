<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Register new user
    public function register($data) {
        try {
            // Check if email already exists
            $this->db->query("SELECT id FROM users WHERE email = :email");
            $this->db->bind(':email', $data['email']);
            
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $this->db->query("INSERT INTO users (name, email, password, role, department_id) 
                            VALUES (:name, :email, :password, :role, :department_id)");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':role', $data['role'] ?? 'user');
            $this->db->bind(':department_id', $data['department_id'] ?? null);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User registered successfully'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Login user
    public function login($email, $password) {
        try {
            $this->db->query("SELECT * FROM users WHERE email = :email");
            $this->db->bind(':email', $email);
            
            $user = $this->db->single();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                
                // Update last login
                $this->updateLastLogin($user['id']);
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update last login timestamp
    private function updateLastLogin($userId) {
        $this->db->query("UPDATE users SET updated_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $userId);
        $this->db->execute();
    }
    
    // Get user by ID
    public function getUserById($id) {
        try {
            $this->db->query("SELECT u.*, d.name as department_name 
                            FROM users u 
                            LEFT JOIN departments d ON u.department_id = d.id 
                            WHERE u.id = :id");
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get all users (admin only)
    public function getAllUsers() {
        try {
            $this->db->query("SELECT * FROM users ORDER BY name");
            $users = $this->db->resultSet();
            
            // Add department names
            foreach ($users as &$user) {
                if (!empty($user['department_id'])) {
                    $this->db->query("SELECT name FROM departments WHERE id = :id");
                    $this->db->bind(':id', $user['department_id']);
                    $dept = $this->db->single();
                    $user['department_name'] = $dept ? $dept['name'] : null;
                } else {
                    $user['department_name'] = null;
                }
            }
            
            return $users;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get users by role
    public function getUsersByRole($role) {
        try {
            $this->db->query("SELECT u.*, d.name as department_name 
                            FROM users u 
                            LEFT JOIN departments d ON u.department_id = d.id 
                            WHERE u.role = :role 
                            ORDER BY u.name");
            $this->db->bind(':role', $role);
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get staff members
    public function getStaff() {
        return $this->getUsersByRole('staff');
    }
    
    // Update user
    public function updateUser($id, $data) {
        try {
            $setClause = [];
            foreach ($data as $key => $value) {
                if ($key === 'password' && !empty($value)) {
                    $setClause[] = "$key = :$key";
                } elseif ($key !== 'password') {
                    $setClause[] = "$key = :$key";
                }
            }
            $setClause = implode(', ', $setClause);
            
            $this->db->query("UPDATE users SET $setClause WHERE id = :id");
            
            // Bind all values
            foreach ($data as $key => $value) {
                if ($key === 'password' && !empty($value)) {
                    $hashedPassword = password_hash($value, PASSWORD_DEFAULT);
                    $this->db->bind(":$key", $hashedPassword);
                } elseif ($key !== 'password') {
                    $this->db->bind(":$key", $value);
                }
            }
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Update password
    public function updatePassword($id, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->query("UPDATE users SET password = :password WHERE id = :id");
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Delete user
    public function deleteUser($id) {
        try {
            // Check if user has tickets
            $this->db->query("SELECT COUNT(*) as count FROM tickets WHERE user_id = :id OR assigned_staff_id = :id");
            $this->db->bind(':id', $id);
            $result = $this->db->single();
            
            if ($result['count'] > 0) {
                return false;
            }
            
            $this->db->query("DELETE FROM users WHERE id = :id");
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Change password (for logged in user)
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password
            $this->db->query("SELECT password FROM users WHERE id = :id");
            $this->db->bind(':id', $userId);
            $user = $this->db->single();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            if ($this->updatePassword($userId, $newPassword)) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to change password'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get user statistics
    public function getUserStats() {
        try {
            $stats = [];
            
            // Total users
            $this->db->query("SELECT COUNT(*) as total FROM users");
            $stats['total'] = $this->db->single()['total'];
            
            // Users by role
            $this->db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $roles = $this->db->resultSet();
            foreach ($roles as $role) {
                $stats['by_role'][$role['role']] = $role['count'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get staff users
    public function getStaffUsers($departmentId = null) {
        try {
            $query = "SELECT u.*, d.name as department_name
                     FROM users u
                     LEFT JOIN departments d ON u.department_id = d.id
                     WHERE u.role = 'staff'";
            
            if ($departmentId) {
                $query .= " AND u.department_id = :department_id";
            }
            
            $query .= " ORDER BY u.name";
            
            $this->db->query($query);
            
            if ($departmentId) {
                $this->db->bind(':department_id', $departmentId);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get users by department
    public function getUsersByDepartment($departmentId) {
        try {
            $this->db->query("SELECT * FROM users WHERE department_id = :department_id ORDER BY name");
            $this->db->bind(':department_id', $departmentId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
