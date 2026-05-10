<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user has staff or admin role
if (!hasAnyRole(['staff', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
    exit;
}

// Check if ticket_id is provided
if (!isset($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
    exit;
}

$ticketId = (int)$_POST['ticket_id'];
$staffId = $_SESSION['user_id'];

try {
    // Load Ticket model
    require_once '../models/Ticket.php';
    $ticketModel = new Ticket();

    // Get ticket details
    $ticket = $ticketModel->getTicketById($ticketId);

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }

    // Check if ticket is already assigned to someone else
    if (!empty($ticket['assigned_staff_id']) && $ticket['assigned_staff_id'] != $staffId) {
        echo json_encode(['success' => false, 'message' => 'Ticket is already assigned to another staff member']);
        exit;
    }

    // Assign ticket to current user
    $result = $ticketModel->assignTicket($ticketId, $staffId);

    if ($result) {
        // Add a note about the assignment
        $noteData = [
            'ticket_id' => $ticketId,
            'user_id' => $staffId,
            'note' => 'Ticket assigned to ' . $_SESSION['user_name'],
            'is_internal' => 1
        ];
        $ticketModel->addTicketNote($noteData);
        
        echo json_encode(['success' => true, 'message' => 'Ticket assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign ticket']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
