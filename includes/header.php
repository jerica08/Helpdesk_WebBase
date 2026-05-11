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
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </header>

            <div class="content-body">
                <?php echo displayFlashMessages(); ?>
