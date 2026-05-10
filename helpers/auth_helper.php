<?php
// Authentication Helper Functions

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'department_id' => $_SESSION['department_id']
        ];
    }
    return null;
}

function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

function hasAnyRole($roles) {
    $user = getCurrentUser();
    return $user && in_array($user['role'], $roles);
}

function requireRole($role) {
    if (!hasRole($role)) {
        $_SESSION['error'] = 'Access denied. Insufficient permissions.';
        header('Location: ../auth/logout.php');
        exit();
    }
}

function requireAnyRole($roles) {
    if (!hasAnyRole($roles)) {
        $_SESSION['error'] = 'Access denied. Insufficient permissions.';
        header('Location: ../auth/logout.php');
        exit();
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: ../auth/login.php');
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function displayFlashMessages() {
    $types = ['success', 'error', 'warning', 'info'];
    $output = '';
    
    foreach ($types as $type) {
        $message = getFlashMessage($type);
        if ($message) {
            $alertClass = $type === 'error' ? 'danger' : $type;
            $output .= '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
            $output .= htmlspecialchars($message);
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $output .= '</div>';
        }
    }
    
    return $output;
}

function formatDate($date, $format = 'M d, Y h:i A') {
    return date($format, strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="status-badge status-pending">Pending</span>',
        'assigned' => '<span class="status-badge status-assigned">Assigned</span>',
        'in_progress' => '<span class="status-badge status-in-progress">In Progress</span>',
        'resolved' => '<span class="status-badge status-resolved">Resolved</span>',
        'closed' => '<span class="status-badge status-closed">Closed</span>'
    ];
    
    return $badges[$status] ?? '<span class="status-badge status-unknown">Unknown</span>';
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="priority-badge priority-low">Low</span>',
        'medium' => '<span class="priority-badge priority-medium">Medium</span>',
        'high' => '<span class="priority-badge priority-high">High</span>'
    ];
    
    return $badges[$priority] ?? '<span class="priority-badge priority-unknown">Unknown</span>';
}

function generateTicketCode() {
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return 'TK' . $date . $random;
}

function logActivity($user_id, $action, $description) {
    // This can be implemented later for audit trail
    error_log("User ID: $user_id, Action: $action, Description: $description");
}
?>
