<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require admin role
requireLogin();
requireRole('admin');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');

// Load models
require_once '../models/User.php';
$userModel = new User();

// Get all users
$users = $userModel->getAllUsers();

// Create CSV output
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Department', 'Created At']);

// Add user data
foreach ($users as $user) {
    fputcsv($output, [
        $user['id'],
        $user['name'],
        $user['email'],
        $user['role'],
        $user['department_name'] ?? 'None',
        $user['created_at']
    ]);
}

fclose($output);
?>
