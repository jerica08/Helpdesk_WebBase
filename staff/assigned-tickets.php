<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require staff role
requireLogin();
requireRole('staff');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Assigned Tickets - Staff';

// Load required models
require_once '../models/Ticket.php';
$ticketModel = new Ticket();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get filters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';

// Get assigned tickets
$tickets = $ticketModel->getTicketsByStaff($_SESSION['user_id'], $limit, $offset);
$totalTickets = $ticketModel->getTicketCount($_SESSION['user_id'], 'staff');
$totalPages = ceil($totalTickets / $limit);

// Apply filters if set
if ($status) {
    $tickets = array_filter($tickets, function($ticket) use ($status) {
        return $ticket['status'] === $status;
    });
}
if ($priority) {
    $tickets = array_filter($tickets, function($ticket) use ($priority) {
        return $ticket['priority'] === $priority;
    });
}

// Include header
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <h1 class="page-title">Assigned Tickets</h1>
        <div class="page-actions">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="">All Priority</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="assigned-tickets.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket Code</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No tickets assigned to you</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['ticket_code']); ?></span>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                            <?php echo htmlspecialchars($ticket['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['department_name']); ?></td>
                                    <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                    <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                    <td><?php echo formatDate($ticket['created_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTicket(<?php echo $ticket['id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($ticket['status'] === 'assigned'): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'in_progress')" title="Start Work">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($ticket['status'] === 'in_progress'): ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'resolved')" title="Mark Resolved">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Set global variables for JavaScript
window.userRole = '<?php echo $_SESSION['user_role']; ?>';
window.userId = '<?php echo $_SESSION['user_id']; ?>';

// Assign ticket to self
function assignTicketToMe(ticketId) {
    if (confirm('Are you sure you want to assign this ticket to yourself?')) {
        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('assigned_staff_id', <?php echo $_SESSION['user_id']; ?>);
        
        fetch('../api/update_ticket.php', {
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
</script>

<?php require_once '../includes/footer.php'; ?>
