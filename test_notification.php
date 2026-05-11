<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Start session
session_start();

// Require login
requireLogin();

require_once 'models/Notification.php';
$notificationModel = new Notification();

echo "<h2>Notification System Test</h2>";

// Show current user info
echo "<h3>Current User:</h3>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "User Name: " . $_SESSION['user_name'] . "<br>";
echo "User Role: " . $_SESSION['user_role'] . "<br>";

// Show unread notification count
$unreadCount = $notificationModel->getUnreadCount($_SESSION['user_id']);
echo "<h3>Unread Notifications: $unreadCount</h3>";

// Show recent notifications
$notifications = $notificationModel->getUserNotifications($_SESSION['user_id'], 10);

echo "<h3>Recent Notifications:</h3>";
if (empty($notifications)) {
    echo "No notifications found<br>";
} else {
    echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
    echo "<tr><th>ID</th><th>Message</th><th>Type</th><th>Ticket Code</th><th>Read</th><th>Created</th></tr>";
    
    foreach ($notifications as $notification) {
        $readStatus = $notification['is_read'] ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$notification['id']}</td>";
        echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>{$notification['ticket_code']}</td>";
        echo "<td>$readStatus</td>";
        echo "<td>{$notification['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test creating a notification
echo "<h3>Test Creating Notification:</h3>";
if (isset($_GET['test']) && $_GET['test'] === 'create') {
    $testMessage = "Test notification created at " . date('Y-m-d H:i:s');
    $result = $notificationModel->create($_SESSION['user_id'], 1, $testMessage, 'status_update');
    
    if ($result) {
        echo "✅ Test notification created successfully!<br>";
        echo "<a href='test_notification.php'>Refresh to see it</a>";
    } else {
        echo "❌ Failed to create test notification";
    }
} else {
    echo "<a href='test_notification.php?test=create'>Create Test Notification</a>";
}

echo "<h3>Test Marking All as Read:</h3>";
if (isset($_GET['test']) && $_GET['test'] === 'markread') {
    $result = $notificationModel->markAllAsRead($_SESSION['user_id']);
    
    if ($result) {
        echo "✅ All notifications marked as read!<br>";
        echo "<a href='test_notification.php'>Refresh to see changes</a>";
    } else {
        echo "❌ Failed to mark notifications as read";
    }
} else {
    echo "<a href='test_notification.php?test=markread'>Mark All as Read</a>";
}
?>
