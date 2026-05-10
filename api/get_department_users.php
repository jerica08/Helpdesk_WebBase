<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login and admin role
requireLogin();
requireRole('admin');

// Get department ID
$departmentId = $_GET['department_id'] ?? '';

if (empty($departmentId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit();
}

try {
    require_once '../models/User.php';
    $userModel = new User();
    
    $users = $userModel->getUsersByDepartment($departmentId);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
