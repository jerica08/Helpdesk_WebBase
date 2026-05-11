<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="../rmmc.logo.png" alt="RMMC Logo" class="sidebar-logo-small">
                <h4 class="text-white ms-2">Helpdesk</h4>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo $basePath; ?>dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- User Menu Items -->
                <li class="user-menu">
                    <a href="<?php echo $basePath; ?>tickets.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i>
                        <span>My Tickets</span>
                    </a>
                </li>
                <?php if (!hasRole('admin')): ?>
                <li class="user-menu">
                    <a href="<?php echo $basePath; ?>create-ticket.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'create-ticket.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>
                        <span>Create Ticket</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('staff')): ?>
                <!-- Staff Menu Items -->
                <li class="staff-menu">
                    <a href="<?php echo $basePath; ?>assigned-tickets.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'assigned-tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Assigned Tickets</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <!-- Admin Menu Items -->
                <li class="admin-menu">
                    <a href="<?php echo $basePath; ?>tickets.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Tickets</span>
                    </a>
                </li>
                <li class="admin-menu">
                    <a href="<?php echo $basePath; ?>users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="admin-menu">
                    <a href="<?php echo $basePath; ?>departments.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Departments</span>
                    </a>
                </li>
                <li class="admin-menu">
                    <a href="<?php echo $basePath; ?>reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle fa-2x"></i>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    </div>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="header-actions">
                    <?php 
                    // Add notification bell for users
                    if ($_SESSION['user_role'] === 'user'): 
                        require_once '../models/Notification.php';
                        $notificationModel = new Notification();
                        $unreadCount = $notificationModel->getUnreadCount($_SESSION['user_id']);
                    ?>
                    <div class="notification-dropdown">
                        <button class="btn btn-outline-primary btn-sm position-relative" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div id="notificationDropdown" class="notification-menu" style="display: none;">
                            <div class="notification-header">
                                <h6>Notifications</h6>
                                <?php if ($unreadCount > 0): ?>
                                    <a href="#" onclick="markAllAsRead()" class="mark-all-read">Mark all as read</a>
                                <?php endif; ?>
                            </div>
                            <div class="notification-list">
                                <?php 
                                $notifications = $notificationModel->getUserNotifications($_SESSION['user_id'], 5);
                                if (empty($notifications)): 
                                ?>
                                    <div class="no-notifications">No new notifications</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
                                             onclick="viewNotification(<?php echo $notification['id']; ?>, <?php echo $notification['ticket_id']; ?>)">
                                            <div class="notification-content">
                                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                <div class="notification-meta">
                                                    <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                                                    <span class="ticket-code"><?php echo htmlspecialchars($notification['ticket_code']); ?></span>
                                                </div>
                                            </div>
                                            <?php if (!$notification['is_read']): ?>
                                                <div class="notification-dot"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="notification-footer">
                                <a href="../user/notifications.php">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </header>

            <div class="content-body">
                <?php echo displayFlashMessages(); ?>

<script>
// Notification functionality
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// Close notifications when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const button = event.target.closest('.notification-dropdown button');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// Mark notification as read and view ticket
function viewNotification(notificationId, ticketId) {
    // Mark as read via AJAX
    fetch('../api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread styling
            const notificationElement = document.querySelector(`[onclick="viewNotification(${notificationId}, ${ticketId})"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
                const dot = notificationElement.querySelector('.notification-dot');
                if (dot) dot.remove();
            }
            
            // Update notification count
            updateNotificationCount();
        }
    });
    
    // Redirect to ticket view
    window.location.href = '../user/ticket-details.php?id=' + ticketId;
}

// Mark all notifications as read
function markAllAsRead() {
    fetch('../api/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove all unread styling
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                const dot = item.querySelector('.notification-dot');
                if (dot) dot.remove();
            });
            
            // Update notification count
            updateNotificationCount();
        }
    });
}

// Update notification count in header
function updateNotificationCount() {
    fetch('../api/notification_count.php')
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.notification-dropdown .badge');
        if (badge) {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    });
}
</script>
