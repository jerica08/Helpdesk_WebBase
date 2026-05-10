<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login
flashMessage('success', 'You have been logged out successfully.');
header('Location: login.php');
exit();
?>
