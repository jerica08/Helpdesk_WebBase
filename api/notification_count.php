<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login
requireLogin();

try {
    require_once '../models/Notification.php';
    $notificationModel = new Notification();
    
    $count = $notificationModel->getUnreadCount($_SESSION['user_id']);
    
    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>
