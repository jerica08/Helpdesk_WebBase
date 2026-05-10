<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login and admin role
requireLogin();
requireRole('admin');

// Get user ID
$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

try {
    require_once '../models/User.php';
    $userModel = new User();
    
    $user = $userModel->getUserById($userId);
    
    if ($user) {
        // Remove password from response
        unset($user['password']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
