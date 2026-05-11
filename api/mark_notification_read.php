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

// Get notification ID
$notificationId = intval($_POST['notification_id'] ?? 0);

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    require_once '../models/Notification.php';
    $notificationModel = new Notification();
    
    $result = $notificationModel->markAsRead($notificationId, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
