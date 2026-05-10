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
    require_once '../models/Department.php';
    $departmentModel = new Department();
    
    $department = $departmentModel->getDepartmentById($departmentId);
    
    if ($department) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'department' => $department]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Department not found']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
