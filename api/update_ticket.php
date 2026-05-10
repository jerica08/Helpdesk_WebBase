<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require login
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$ticketId = $_POST['ticket_id'] ?? '';
$status = $_POST['status'] ?? '';
$assignedStaffId = $_POST['assigned_staff_id'] ?? null;

// Handle staff assignment (when only ticket_id and assigned_staff_id are provided)
if ($assignedStaffId && empty($status)) {
    $status = 'assigned';
}

// Validate input
if (empty($ticketId) || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate status
$validStatuses = ['pending', 'assigned', 'in_progress', 'resolved', 'closed'];
if (!in_array($status, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    require_once '../models/Ticket.php';
    $ticketModel = new Ticket();
    
    // Check if user has permission to update this ticket
    $user = getCurrentUser();
    $ticket = $ticketModel->getTicketById($ticketId);
    
    if (!$ticket) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit();
    }
    
    // Check permissions
    if ($user['role'] === 'user' && $ticket['user_id'] != $user['id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    if ($user['role'] === 'staff' && $ticket['assigned_staff_id'] != $user['id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You can only update tickets assigned to you']);
        exit();
    }
    
    // Update ticket
    $updateData = [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($assignedStaffId && ($user['role'] === 'admin' || $user['role'] === 'staff')) {
        $updateData['assigned_staff_id'] = $assignedStaffId;
    }
    
    $result = $ticketModel->updateTicket($ticketId, $updateData);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update ticket']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
