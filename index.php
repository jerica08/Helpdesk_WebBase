<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

// Get user role and redirect to appropriate dashboard
$user = getCurrentUser();
switch ($user['role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'staff':
        header('Location: staff/dashboard.php');
        break;
    case 'user':
        header('Location: user/dashboard.php');
        break;
    default:
        header('Location: auth/logout.php');
        break;
}
?>
