<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require user role
requireLogin();
requireRole('user');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Create Ticket';

// Load required models
require_once '../models/Department.php';
$departmentModel = new Department();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Check if POST data is received
    if (empty($_POST)) {
        flashMessage('error', 'No data received. Please try again.');
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $department_id = intval($_POST['department_id'] ?? 0);
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        
        // Validate inputs
        if (empty($title) || empty($description) || empty($department_id)) {
            flashMessage('error', 'Please fill in all required fields');
        } else {
            require_once '../models/Ticket.php';
            $ticketModel = new Ticket();
            
            $ticketData = [
                'user_id' => $_SESSION['user_id'],
                'department_id' => $department_id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority
            ];
            
            $result = $ticketModel->create($ticketData);
            
            if ($result['success']) {
                flashMessage('success', 'Ticket created successfully! Ticket ID: ' . $result['ticket_code']);
                header('Location: tickets.php');
                exit();
            } else {
                flashMessage('error', $result['message']);
            }
        }
    }
}

// Get departments
$departments = $departmentModel->getAllDepartments();

// Include header
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="form-container">
            <h3>Create New Ticket</h3>
            <p class="text-muted">Fill in the details below to submit a support ticket.</p>
            
            <form method="POST" action="create-ticket.php" id="createTicketForm">
                <div class="form-section">
                    <div class="mb-3">
                        <label for="title" class="form-label">Ticket Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        <small class="form-text text-muted">Brief description of your issue</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Provide detailed information about your issue</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department *</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority *</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : 'selected'; ?>>Medium</option>
                                <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                            </select>
                            <small class="form-text text-muted">
                                Low: General questions<br>
                                Medium: Issues affecting productivity<br>
                                High: Urgent issues requiring immediate attention
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                    <a href="tickets.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createTicketForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const departmentId = document.getElementById('department_id').value;
        const priority = document.getElementById('priority').value;
        
        // Validation
        if (!title) {
            alert('Please enter a ticket title');
            document.getElementById('title').focus();
            return false;
        }
        
        if (!description) {
            alert('Please enter a description');
            document.getElementById('description').focus();
            return false;
        }
        
        if (!departmentId) {
            alert('Please select a department');
            document.getElementById('department_id').focus();
            return false;
        }
        
        if (!priority) {
            alert('Please select a priority level');
            document.getElementById('priority').focus();
            return false;
        }
        
        // Submit the form
        this.submit();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
