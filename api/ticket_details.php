<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login
requireLogin();

// Set content type
header('Content-Type: application/json');

// Get ticket ID
$ticketId = intval($_GET['id'] ?? 0);

if (!$ticketId) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

// Load models
require_once '../models/Ticket.php';
$ticketModel = new Ticket();

// Get ticket details
$ticket = $ticketModel->getTicketById($ticketId);

if (!$ticket) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

// Check permissions based on user role
$user = getCurrentUser();
$canView = false;

if ($user['role'] === 'admin') {
    $canView = true;
} elseif ($user['role'] === 'staff') {
    // Staff can view assigned tickets or pending tickets
    $canView = ($ticket['assigned_staff_id'] == $user['id'] || $ticket['status'] === 'pending');
} elseif ($user['role'] === 'user') {
    // Users can only view their own tickets
    $canView = ($ticket['user_id'] == $user['id']);
}

if (!$canView) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Get ticket notes
$ticket['notes'] = $ticketModel->getTicketNotes($ticketId);

echo json_encode(['success' => true, 'ticket' => $ticket]);
?>
