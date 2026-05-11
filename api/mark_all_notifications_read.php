<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    require_once '../models/Notification.php';
    $notificationModel = new Notification();
    
    $result = $notificationModel->markAllAsRead($_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
