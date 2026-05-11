<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require staff role
requireLogin();
requireRole('staff');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Staff Dashboard';

// Load required models
require_once '../models/Ticket.php';
$ticketModel = new Ticket();

// Get staff statistics - filtered by department
$stats = $ticketModel->getTicketStats($_SESSION['user_id'], 'staff', $_SESSION['department_id']);

// Get recent tickets - filtered by department
$recentTickets = $ticketModel->getRecentTickets(5, $_SESSION['user_id'], 'staff', $_SESSION['department_id']);

// Include header
require_once '../includes/header.php';
?>

<!-- Dashboard Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-4">
        <div class="dashboard-card primary">
            <div class="card-icon text-primary">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="card-number"><?php echo $stats['assigned_tickets'] ?? 0; ?></div>
            <div class="card-label">Assigned Tickets</div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="dashboard-card info">
            <div class="card-icon text-info">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="card-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
            <div class="card-label">In Progress</div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="dashboard-card success">
            <div class="card-icon text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-number"><?php echo $stats['completed'] ?? 0; ?></div>
            <div class="card-label">Completed</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-flex gap-2">
                    <a href="assigned-tickets.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View Assigned Tickets
                    </a>
                    <a href="../user/tickets.php" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View All Tickets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Tickets for Assignment -->
<div class="table-container mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Pending Tickets</h3>
        <a href="assigned-tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    
    <?php
    // Get pending tickets - filtered by department
    require_once '../models/Ticket.php';
    $pendingTickets = $ticketModel->getTicketsByStatus('pending');
    $pendingTickets = array_filter($pendingTickets, function($ticket) {
        return empty($ticket['assigned_staff_id']) && $ticket['department_id'] == $_SESSION['department_id'];
    });
    $pendingTickets = array_slice($pendingTickets, 0, 5);
    ?>
    
    <?php if (!empty($pendingTickets)): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
                        <th>Submitted By</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingTickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <?php echo htmlspecialchars($ticket['title']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['department_name']); ?></td>
                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                            <td><?php echo formatDate($ticket['created_at']); ?></td>
                            <td>
                                <button class="action-btn view" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn edit" onclick="assignTicketToMe(<?php echo $ticket['id']; ?>)">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
            <p class="text-muted">No pending tickets available for assignment.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Assigned Tickets -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Recent Assigned Tickets</h3>
        <a href="assigned-tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    
    <?php if (!empty($recentTickets)): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
                        <th>Submitted By</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <?php echo htmlspecialchars($ticket['title']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['department_name']); ?></td>
                            <td><?php echo getStatusBadge($ticket['status']); ?></td>
                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                            <td><?php echo formatDate($ticket['created_at']); ?></td>
                            <td>
                                <button class="action-btn view" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($ticket['status'] === 'assigned' && (!isset($ticket['assigned_staff_id']) || $ticket['assigned_staff_id'] == $_SESSION['user_id'])): ?>
                                    <button class="action-btn edit" onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'in_progress')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">No tickets assigned to you yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Set global variables for JavaScript
window.userRole = '<?php echo $_SESSION['user_role']; ?>';
window.userId = '<?php echo $_SESSION['user_id']; ?>';

// View ticket details
function viewTicket(ticketId) {
    fetch('../api/ticket_details.php?ticket_id=' + ticketId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ticketModalBody').innerHTML = data.html;
                document.getElementById('ticketModalFooter').innerHTML = data.footer;
                var ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'));
                ticketModal.show();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Assign ticket to self
function assignTicketToMe(ticketId) {
    if (confirm('Are you sure you want to assign this ticket to yourself?')) {
        const formData = new FormData();
        formData.append('ticket_id', ticketId);

        fetch('../api/assign_ticket.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error assigning ticket: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error assigning ticket');
        });
    }
}

// Update ticket status
function updateTicketStatus(ticketId, status) {
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('status', status);

    fetch('../api/update_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating ticket: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating ticket');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
