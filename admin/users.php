<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Require admin role
requireLogin();
requireRole('admin');

// Set base path for navigation
$basePath = './';

// Set page title
$pageTitle = 'Manage Users - Admin';

// Load required models
require_once '../models/User.php';
require_once '../models/Department.php';

$userModel = new User();
$departmentModel = new Department();

// Handle user creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'bulk_create') {
        $emails = array_filter(array_map('trim', explode("\n", $_POST['emails'] ?? '')));
        $role = $_POST['role'] ?? 'user';
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data = [
                    'name' => explode('@', $email)[0],
                    'email' => $email,
                    'password' => substr(md5(time() . rand()), 0, 8),
                    'role' => $role,
                    'department_id' => $departmentId
                ];
                
                $result = $userModel->register($data);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
        
        flashMessage('success', "Created {$successCount} users successfully. {$errorCount} failed.");
        
    } elseif ($action === 'bulk_delete') {
        $userIds = explode(',', $_POST['user_ids'] ?? '');
        $successCount = 0;
        
        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId != $_SESSION['user_id']) {
                if ($userModel->deleteUser($userId)) {
                    $successCount++;
                }
            }
        }
        
        flashMessage('success', "Deleted {$successCount} users successfully.");
        
    } elseif ($action === 'create_user') {
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'email' => sanitizeInput($_POST['email']),
            'password' => $_POST['password'],
            'role' => $_POST['role'],
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null
        ];
        
        $result = $userModel->register($data);
        if ($result['success']) {
            flashMessage('success', 'User created successfully');
        } else {
            flashMessage('error', $result['message']);
        }
        
        // Redirect to refresh the page and show new user
        header('Location: users.php');
        exit();
    } elseif ($action === 'update_user') {
        $userId = (int)$_POST['user_id'];
        $data = [
            'name' => sanitizeInput($_POST['name']),
            'email' => sanitizeInput($_POST['email']),
            'role' => $_POST['role'],
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $result = $userModel->updateUser($userId, $data);
        if ($result) {
            flashMessage('success', 'User updated successfully');
        } else {
            flashMessage('error', 'Failed to update user');
        }
    } elseif ($action === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        if ($userId == $_SESSION['user_id']) {
            flashMessage('error', 'You cannot delete your own account');
        } else {
            $result = $userModel->deleteUser($userId);
            if ($result) {
                flashMessage('success', 'User deleted successfully');
            } else {
                flashMessage('error', 'Failed to delete user');
            }
        }
    }
    
    header('Location: users.php');
    exit();
}

// Get all users from model
$users = $userModel->getAllUsers();

$departments = $departmentModel->getAllDepartments();

// Apply filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$departmentFilter = $_GET['department'] ?? '';

// Apply filters only if values are provided
if (!empty($search)) {
    $users = array_filter($users, function($user) use ($search) {
        return stripos($user['name'], $search) !== false || stripos($user['email'], $search) !== false;
    });
    $users = array_values($users); // Reset array keys
}

if (!empty($roleFilter)) {
    $users = array_filter($users, function($user) use ($roleFilter) {
        return $user['role'] === $roleFilter;
    });
    $users = array_values($users); // Reset array keys
}

if (!empty($departmentFilter)) {
    $users = array_filter($users, function($user) use ($departmentFilter) {
        return $user['department_id'] == $departmentFilter;
    });
    $users = array_values($users); // Reset array keys
}

// Include header
require_once '../includes/header.php';
?>



<div class="content-header">
    <h1 class="page-title">Manage Users</h1>
    <div class="page-actions">
        <!-- Add User form is below -->
    </div>
</div>

    <!-- Add User Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New User</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="user">User</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Users</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search by name or email...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo ($_GET['role'] ?? '') === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="staff" <?php echo ($_GET['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo ($_GET['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo ($_GET['department'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="users.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div> 
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">All Users</h5>
                <div>
                    <span class="text-muted">Total: <?php echo count($users); ?> users</span>
                </div>
            </div>
            
                        
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-danger">No users found in database</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'None'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete User">
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

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetails">
                    <!-- User details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="editUserName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="editUserEmail" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password" id="editUserPassword">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" id="editUserRole" required>
                            <option value="user">User</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id" id="editUserDepartment">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

    
     



<script>
function viewUser(userId) {
    // Load user data via AJAX
    fetch('../api/get_user.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const detailsHtml = `
                    <div class="row">
                        <div class="col-sm-4"><strong>ID:</strong></div>
                        <div class="col-sm-8">${user.id}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Name:</strong></div>
                        <div class="col-sm-8">${user.name}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Email:</strong></div>
                        <div class="col-sm-8">${user.email}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Role:</strong></div>
                        <div class="col-sm-8"><span class="badge bg-primary">${user.role}</span></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Department:</strong></div>
                        <div class="col-sm-8">${user.department_name || 'None'}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Created:</strong></div>
                        <div class="col-sm-8">${new Date(user.created_at).toLocaleDateString()}</div>
                    </div>
                `;
                document.getElementById('userDetails').innerHTML = detailsHtml;
                
                var viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                viewModal.show();
            } else {
                alert('Error loading user data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user data');
        });
}

function editUser(userId) {
    // Load user data via AJAX
    fetch('../api/get_user.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('editUserId').value = user.id;
                document.getElementById('editUserName').value = user.name;
                document.getElementById('editUserEmail').value = user.email;
                document.getElementById('editUserRole').value = user.role;
                document.getElementById('editUserDepartment').value = user.department_id || '';
                document.getElementById('editUserPassword').value = '';
                
                var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            } else {
                alert('Error loading user data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user data');
        });
}

function saveUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    fetch('users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving user');
    });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function getRoleBadgeClass(role) {
    switch(role) {
        case 'admin': return 'danger';
        case 'staff': return 'warning';
        case 'user': return 'info';
        default: return 'secondary';
    }
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllHeader').checked;
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = selectAll);
    updateBulkActions();
}

// Update bulk actions visibility
function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (checkedBoxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-block';
        bulkDeleteBtn.textContent = `Delete Selected (${checkedBoxes.length})`;
    } else {
        bulkDeleteBtn.style.display = 'none';
    }
}

// Bulk delete users
function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (confirm(`Are you sure you want to delete ${userIds.length} user(s)?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_delete">
            <input type="hidden" name="user_ids" value="${userIds.join(',')}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Export users
function exportUsers() {
    window.open('api/export_users.php', '_blank');
}

// Bulk user creation
function createBulkUsers() {
    const emails = document.getElementById('bulkEmails').value.split('\n').filter(email => email.trim());
    const role = document.getElementById('bulkRole').value;
    const departmentId = document.getElementById('bulkDepartment').value;
    
    if (emails.length === 0) {
        alert('Please enter at least one email address');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'bulk_create');
    formData.append('emails', emails.join('\n'));
    formData.append('role', role);
    formData.append('department_id', departmentId);
    
    fetch('users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating users');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
