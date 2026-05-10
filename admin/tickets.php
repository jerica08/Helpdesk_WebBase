<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require admin role
requireLogin();
requireRole('admin');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Manage Tickets - Admin';

// Load required models
require_once '../models/Ticket.php';
require_once '../models/User.php';
require_once '../models/Department.php';

$ticketModel = new Ticket();
$userModel = new User();
$departmentModel = new Department();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get filters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$department = $_GET['department'] ?? '';
$search = $_GET['search'] ?? '';

// Get tickets
$tickets = $ticketModel->getAllTickets($limit, $offset);
$totalTickets = $ticketModel->getTicketCount();
$totalPages = ceil($totalTickets / $limit);

// Get departments for filter
$departments = $departmentModel->getAllDepartments();

// Include header
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <h1 class="page-title">Manage Tickets</h1>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="exportData('tickets')">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ticket code or title...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="">All Priority</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="tickets.php" class="btn btn-secondary">Clear</a>
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
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No tickets found</td>
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
                                    <td>
                                        <?php if ($ticket['staff_name']): ?>
                                            <?php echo htmlspecialchars($ticket['staff_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                    <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                    <td><?php echo formatDate($ticket['created_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTicket(<?php echo $ticket['id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editTicket(<?php echo $ticket['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
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
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&department=<?php echo urlencode($department); ?>&search=<?php echo urlencode($search); ?>">
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

// Edit ticket function (placeholder)
function editTicket(ticketId) {
    // This would open an edit modal or redirect to edit page
    viewTicket(ticketId); // For now, just view the ticket
}
</script>

<?php require_once '../includes/footer.php'; ?>
