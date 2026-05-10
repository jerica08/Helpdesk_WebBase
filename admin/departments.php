<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require admin role
requireLogin();
requireRole('admin');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Manage Departments - Admin';

// Load required models
require_once '../models/Department.php';
require_once '../models/User.php';

$departmentModel = new Department();
$userModel = new User();

// Handle department creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_department') {
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'description' => sanitizeInput($_POST['description'])
        ];
        
        $result = $departmentModel->createDepartment($data);
        if ($result['success']) {
            flashMessage('success', 'Department created successfully');
        } else {
            flashMessage('error', $result['message']);
        }
    } elseif ($action === 'update_department') {
        $departmentId = (int)$_POST['department_id'];
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'description' => sanitizeInput($_POST['description'])
        ];
        
        $result = $departmentModel->updateDepartment($departmentId, $data);
        if ($result) {
            flashMessage('success', 'Department updated successfully');
        } else {
            flashMessage('error', 'Failed to update department');
        }
    } elseif ($action === 'delete_department') {
        $departmentId = (int)$_POST['department_id'];
        $result = $departmentModel->deleteDepartment($departmentId);
        if ($result) {
            flashMessage('success', 'Department deleted successfully');
        } else {
            flashMessage('error', 'Failed to delete department. It may have users or tickets assigned.');
        }
    }
    
    header('Location: departments.php');
    exit();
}

// Get all departments
$departments = $departmentModel->getAllDepartments();

// Include header
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <h1 class="page-title">Manage Departments</h1>
        <div class="page-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="openDepartmentModal()">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Staff Count</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No departments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $department): ?>
                                <?php 
                                $staffCount = count($userModel->getStaffUsers($department['id']));
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($department['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($department['description'] ?? 'No description'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $staffCount; ?> staff</span>
                                    </td>
                                    <td><?php echo formatDate($department['created_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editDepartment(<?php echo $department['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewDepartmentUsers(<?php echo $department['id']; ?>)" title="View Users">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <?php if ($staffCount == 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?php echo $department['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalTitle">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="departmentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_department">
                    <input type="hidden" name="department_id" id="departmentId">
                    
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" class="form-control" name="name" id="departmentName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="departmentDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Department Users Modal -->
<div class="modal fade" id="departmentUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentUsersModalTitle">Department Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="departmentUsersList">
                    <!-- Users will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openDepartmentModal() {
    document.getElementById('departmentModalTitle').textContent = 'Add Department';
    document.getElementById('departmentForm').reset();
    document.querySelector('input[name="action"]').value = 'create_department';
}

function editDepartment(departmentId) {
    // Load department data via AJAX
    fetch('../api/get_department.php?department_id=' + departmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const department = data.department;
                document.getElementById('departmentModalTitle').textContent = 'Edit Department';
                document.getElementById('departmentId').value = department.id;
                document.getElementById('departmentName').value = department.name;
                document.getElementById('departmentDescription').value = department.description || '';
                document.querySelector('input[name="action"]').value = 'update_department';
                
                const modal = new bootstrap.Modal(document.getElementById('departmentModal'));
                modal.show();
            } else {
                alert('Error loading department data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading department data');
        });
}

function deleteDepartment(departmentId) {
    if (confirm('Are you sure you want to delete this department?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_department">
            <input type="hidden" name="department_id" value="${departmentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewDepartmentUsers(departmentId) {
    fetch('../api/get_department_users.php?department_id=' + departmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const users = data.users;
                let html = '<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>';
                
                if (users.length === 0) {
                    html += '<tr><td colspan="3" class="text-center">No users in this department</td></tr>';
                } else {
                    users.forEach(user => {
                        html += `<tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><span class="badge bg-${getRoleBadgeClass(user.role)}">${user.role}</span></td>
                        </tr>`;
                    });
                }
                
                html += '</tbody></table></div>';
                document.getElementById('departmentUsersList').innerHTML = html;
                
                const modal = new bootstrap.Modal(document.getElementById('departmentUsersModal'));
                modal.show();
            } else {
                alert('Error loading department users');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading department users');
        });
}

function getRoleBadgeClass(role) {
    switch(role) {
        case 'admin': return 'danger';
        case 'staff': return 'warning';
        case 'user': return 'info';
        default: return 'secondary';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
