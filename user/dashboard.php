<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require user role
requireLogin();
requireRole('user');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'User Dashboard';

// Load required models
require_once '../models/Ticket.php';
$ticketModel = new Ticket();

// Get user statistics
$stats = $ticketModel->getTicketStats($_SESSION['user_id'], 'user');

// Get recent tickets
$recentTickets = $ticketModel->getRecentTickets(5, $_SESSION['user_id'], 'user');

// Include header
require_once '../includes/header.php';
?>

<!-- Dashboard Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="dashboard-card primary">
            <div class="card-icon text-primary">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="card-number"><?php echo $stats['total_tickets'] ?? 0; ?></div>
            <div class="card-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card warning">
            <div class="card-icon text-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-number"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="card-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card info">
            <div class="card-icon text-info">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="card-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
            <div class="card-label">In Progress</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card success">
            <div class="card-icon text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-number"><?php echo $stats['resolved'] ?? 0; ?></div>
            <div class="card-label">Resolved</div>
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
                    <a href="create-ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Ticket
                    </a>
                    <a href="tickets.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View All Tickets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tickets -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Recent Tickets</h3>
        <a href="tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    
    <?php if (!empty($recentTickets)): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
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
                            <td><?php echo htmlspecialchars($ticket['department_name']); ?></td>
                            <td><?php echo getStatusBadge($ticket['status']); ?></td>
                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                            <td><?php echo formatDate($ticket['created_at']); ?></td>
                            <td>
                                <button class="action-btn view" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">No tickets found. <a href="create-ticket.php">Create your first ticket</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
