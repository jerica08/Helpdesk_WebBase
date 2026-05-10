<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login and staff/admin role
requireLogin();
requireAnyRole(['staff', 'admin']);

// Set content type
header('Content-Type: application/json');

// Get POST data
$ticketId = intval($_POST['ticket_id'] ?? 0);
$note = sanitizeInput($_POST['note'] ?? '');

if (!$ticketId || empty($note)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Load models
require_once '../models/Ticket.php';
$ticketModel = new Ticket();

// Check if ticket exists and user has permission
$ticket = $ticketModel->getTicketById($ticketId);
if (!$ticket) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

// Check permissions
$user = getCurrentUser();
$canAddNote = false;

if ($user['role'] === 'admin') {
    $canAddNote = true;
} elseif ($user['role'] === 'staff') {
    // Staff can add notes to assigned tickets or pending tickets
    $canAddNote = ($ticket['assigned_staff_id'] == $user['id'] || $ticket['status'] === 'pending');
}

if (!$canAddNote) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Add note
if ($ticketModel->addNote($ticketId, $user['id'], $note)) {
    echo json_encode(['success' => true, 'message' => 'Note added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add note']);
}
?>
