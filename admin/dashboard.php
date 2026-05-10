<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require admin role
requireLogin();
requireRole('admin');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Admin Dashboard';

// Load required models
require_once '../models/Ticket.php';
require_once '../models/User.php';
require_once '../models/Department.php';

$ticketModel = new Ticket();
$userModel = new User();
$departmentModel = new Department();

// Get admin statistics
$ticketStats = $ticketModel->getTicketStats();
$userStats = $userModel->getUserStats();

// Get recent data
$recentTickets = $ticketModel->getRecentTickets(5);
$recentUsers = array_slice($userModel->getAllUsers(), 0, 5);

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
            <div class="card-number"><?php echo $ticketStats['total_tickets'] ?? 0; ?></div>
            <div class="card-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card warning">
            <div class="card-icon text-warning">
                <i class="fas fa-eye"></i>
            </div>
            <div class="card-number"><?php echo $ticketStats['pending_review'] ?? 0; ?></div>
            <div class="card-label">Pending Review</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card info">
            <div class="card-icon text-info">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="card-number"><?php echo $ticketStats['assigned'] ?? 0; ?></div>
            <div class="card-label">Assigned</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="dashboard-card success">
            <div class="card-icon text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-number"><?php echo $ticketStats['resolved'] ?? 0; ?></div>
            <div class="card-label">Resolved</div>
        </div>
    </div>
</div>

<!-- System Overview -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">User Statistics</h5>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $userStats['total'] ?? 0; ?></div>
                        <small class="text-muted">Total Users</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $userStats['by_role']['staff'] ?? 0; ?></div>
                        <small class="text-muted">Staff</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $userStats['by_role']['user'] ?? 0; ?></div>
                        <small class="text-muted">Students/Faculty</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Department Statistics</h5>
                <div class="row text-center">
                    <?php
                    $departments = $departmentModel->getAllDepartments();
                    $deptCount = count($departments);
                    ?>
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $deptCount; ?></div>
                        <small class="text-muted">Departments</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $userModel->getUsersByRole('staff') ? count($userModel->getUsersByRole('staff')) : 0; ?></div>
                        <small class="text-muted">Active Staff</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 mb-0"><?php echo $ticketStats['resolved'] ?? 0; ?></div>
                        <small class="text-muted">Resolved Today</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="users.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="departments.php" class="btn btn-info">
                        <i class="fas fa-building"></i> Manage Departments
                    </a>
                    <a href="all-tickets.php" class="btn btn-warning">
                        <i class="fas fa-list"></i> View All Tickets
                    </a>
                    <a href="reports.php" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tickets - Full Width -->
<div class="col-12 mb-4">
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
                            <th>User</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                                <td class="text-truncate" style="max-width: 300px;">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['department_name']); ?></td>
                                <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                <td>
                                    <button class="action-btn view" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit" onclick="editTicket(<?php echo $ticket['id']; ?>)">
                                        <i class="fas fa-edit"></i>
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
                <p class="text-muted">No tickets found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Set global variables for JavaScript
window.userRole = '<?php echo $_SESSION['user_role']; ?>';
window.userId = '<?php echo $_SESSION['user_id']; ?>';

// Edit ticket function (placeholder)
function editTicket(ticketId) {
    // This would open an edit modal or redirect to edit page
    viewTicket(ticketId); // For now, just view the ticket
}
</script>

<?php require_once '../includes/footer.php'; ?>
