// Global variables
let currentUser = null;
let currentPage = 'dashboard';

// API Base URL
const API_BASE = '/api';

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in
    const token = localStorage.getItem('token');
    if (token) {
        // Verify token and get user info
        fetch(`${API_BASE}/dashboard/stats`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            if (response.ok) {
                // Token is valid, get user info from JWT payload
                const payload = JSON.parse(atob(token.split('.')[1]));
                currentUser = payload;
                showMainApp();
            } else {
                // Token is invalid, remove it and show login
                localStorage.removeItem('token');
            }
        })
        .catch(error => {
            console.error('Token verification failed:', error);
            localStorage.removeItem('token');
        });
    }

    // Setup event listeners
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Login form
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    
    // Refresh button
    document.getElementById('refreshBtn').addEventListener('click', refreshCurrentPage);
    
    // Menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            navigateToPage(page);
        });
    });
}

// Handle login
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('loginError');
    
    try {
        const response = await fetch(`${API_BASE}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Store token and user info
            localStorage.setItem('token', data.token);
            currentUser = data.user;
            
            // Show main application
            showMainApp();
        } else {
            // Show error message
            errorDiv.textContent = data.error || 'Login failed';
            errorDiv.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Login error:', error);
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('d-none');
    }
}

// Handle logout
function handleLogout() {
    localStorage.removeItem('token');
    currentUser = null;
    showLogin();
}

// Show login screen
function showLogin() {
    document.getElementById('loginContainer').classList.remove('d-none');
    document.getElementById('mainContainer').classList.add('d-none');
}

// Show main application
function showMainApp() {
    document.getElementById('loginContainer').classList.add('d-none');
    document.getElementById('mainContainer').classList.remove('d-none');
    
    // Update user info in sidebar
    document.getElementById('userName').textContent = currentUser.name;
    document.getElementById('userRole').textContent = currentUser.role;
    
    // Show/hide menu items based on role
    updateMenuVisibility();
    
    // Load dashboard
    navigateToPage('dashboard');
}

// Update menu visibility based on user role
function updateMenuVisibility() {
    // Hide all role-specific menus
    document.querySelectorAll('.user-menu, .staff-menu, .admin-menu').forEach(menu => {
        menu.classList.add('d-none');
    });
    
    // Show menus based on role
    document.querySelectorAll(`.${currentUser.role}-menu`).forEach(menu => {
        menu.classList.remove('d-none');
    });
    
    // User menu is visible to all roles
    document.querySelectorAll('.user-menu').forEach(menu => {
        menu.classList.remove('d-none');
    });
}

// Navigate to page
function navigateToPage(page) {
    currentPage = page;
    
    // Update active menu item
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-page="${page}"]`).classList.add('active');
    
    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'tickets': 'My Tickets',
        'create-ticket': 'Create Ticket',
        'assigned-tickets': 'Assigned Tickets',
        'all-tickets': 'All Tickets',
        'users': 'Users',
        'departments': 'Departments',
        'reports': 'Reports'
    };
    
    document.getElementById('pageTitle').textContent = titles[page] || 'Dashboard';
    
    // Load page content
    loadPageContent(page);
}

// Load page content
async function loadPageContent(page) {
    const contentDiv = document.getElementById('pageContent');
    contentDiv.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
    
    try {
        switch (page) {
            case 'dashboard':
                await loadDashboard();
                break;
            case 'tickets':
                await loadMyTickets();
                break;
            case 'create-ticket':
                await loadCreateTicket();
                break;
            case 'assigned-tickets':
                await loadAssignedTickets();
                break;
            case 'all-tickets':
                await loadAllTickets();
                break;
            case 'users':
                await loadUsers();
                break;
            case 'departments':
                await loadDepartments();
                break;
            case 'reports':
                await loadReports();
                break;
            default:
                contentDiv.innerHTML = '<p>Page not found</p>';
        }
    } catch (error) {
        console.error('Error loading page:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading page. Please try again.</div>';
    }
}

// Load dashboard
async function loadDashboard() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        // Get dashboard statistics
        const statsResponse = await fetch(`${API_BASE}/dashboard/stats`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const stats = await statsResponse.json();
        
        let dashboardHTML = '<div class="row">';
        
        // Generate dashboard cards based on user role
        if (currentUser.role === 'user') {
            dashboardHTML += `
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card primary">
                        <div class="card-icon text-primary">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="card-number">${stats.total_tickets || 0}</div>
                        <div class="card-label">Total Tickets</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card warning">
                        <div class="card-icon text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-number">${stats.pending || 0}</div>
                        <div class="card-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card info">
                        <div class="card-icon text-info">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="card-number">${stats.in_progress || 0}</div>
                        <div class="card-label">In Progress</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card success">
                        <div class="card-icon text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-number">${stats.resolved || 0}</div>
                        <div class="card-label">Resolved</div>
                    </div>
                </div>
            `;
        } else if (currentUser.role === 'staff') {
            dashboardHTML += `
                <div class="col-md-4 mb-4">
                    <div class="dashboard-card primary">
                        <div class="card-icon text-primary">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-number">${stats.assigned_tickets || 0}</div>
                        <div class="card-label">Assigned Tickets</div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="dashboard-card info">
                        <div class="card-icon text-info">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="card-number">${stats.in_progress || 0}</div>
                        <div class="card-label">In Progress</div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="dashboard-card success">
                        <div class="card-icon text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-number">${stats.completed || 0}</div>
                        <div class="card-label">Completed</div>
                    </div>
                </div>
            `;
        } else if (currentUser.role === 'admin') {
            dashboardHTML += `
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card primary">
                        <div class="card-icon text-primary">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="card-number">${stats.total_tickets || 0}</div>
                        <div class="card-label">Total Tickets</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card warning">
                        <div class="card-icon text-warning">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="card-number">${stats.pending_review || 0}</div>
                        <div class="card-label">Pending Review</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card info">
                        <div class="card-icon text-info">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="card-number">${stats.assigned || 0}</div>
                        <div class="card-label">Assigned</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="dashboard-card success">
                        <div class="card-icon text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-number">${stats.resolved || 0}</div>
                        <div class="card-label">Resolved</div>
                    </div>
                </div>
            `;
        }
        
        dashboardHTML += '</div>';
        
        // Add recent tickets section
        dashboardHTML += `
            <div class="table-container">
                <h3>Recent Tickets</h3>
                <div id="recentTickets">Loading...</div>
            </div>
        `;
        
        contentDiv.innerHTML = dashboardHTML;
        
        // Load recent tickets
        await loadRecentTickets();
        
    } catch (error) {
        console.error('Error loading dashboard:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading dashboard</div>';
    }
}

// Load recent tickets for dashboard
async function loadRecentTickets() {
    try {
        const response = await fetch(`${API_BASE}/tickets`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const tickets = await response.json();
        
        // Show only first 5 tickets
        const recentTickets = tickets.slice(0, 5);
        
        let ticketsHTML = `
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
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        recentTickets.forEach(ticket => {
            ticketsHTML += `
                <tr>
                    <td>${ticket.ticket_code}</td>
                    <td class="text-truncate" style="max-width: 200px;">${ticket.title}</td>
                    <td>${ticket.department_name}</td>
                    <td><span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></td>
                    <td><span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${new Date(ticket.created_at).toLocaleDateString()}</td>
                </tr>
            `;
        });
        
        ticketsHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        document.getElementById('recentTickets').innerHTML = ticketsHTML;
        
    } catch (error) {
        console.error('Error loading recent tickets:', error);
        document.getElementById('recentTickets').innerHTML = '<div class="alert alert-warning">Unable to load recent tickets</div>';
    }
}

// Load my tickets
async function loadMyTickets() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const response = await fetch(`${API_BASE}/tickets`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const tickets = await response.json();
        
        let ticketsHTML = `
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>My Tickets</h3>
                    <button class="btn btn-primary" onclick="navigateToPage('create-ticket')">
                        <i class="fas fa-plus"></i> Create New Ticket
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        tickets.forEach(ticket => {
            ticketsHTML += `
                <tr>
                    <td>${ticket.ticket_code}</td>
                    <td class="text-truncate" style="max-width: 200px;">${ticket.title}</td>
                    <td>${ticket.department_name}</td>
                    <td><span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></td>
                    <td><span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${ticket.assigned_staff_name || 'Unassigned'}</td>
                    <td>${new Date(ticket.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn view" onclick="viewTicket(${ticket.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        ticketsHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = ticketsHTML;
        
    } catch (error) {
        console.error('Error loading tickets:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading tickets</div>';
    }
}

// Load create ticket form
async function loadCreateTicket() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        // Get departments
        const departmentsResponse = await fetch(`${API_BASE}/departments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const departments = await departmentsResponse.json();
        
        let formHTML = `
            <div class="form-container">
                <h3>Create New Ticket</h3>
                <form id="createTicketForm">
                    <div class="form-section">
                        <div class="mb-3">
                            <label for="ticketTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="ticketTitle" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ticketDescription" class="form-label">Description *</label>
                            <textarea class="form-control" id="ticketDescription" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ticketDepartment" class="form-label">Department *</label>
                            <select class="form-select" id="ticketDepartment" required>
                                <option value="">Select Department</option>
        `;
        
        departments.forEach(dept => {
            formHTML += `<option value="${dept.id}">${dept.name}</option>`;
        });
        
        formHTML += `
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ticketPriority" class="form-label">Priority *</label>
                            <select class="form-select" id="ticketPriority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Ticket</button>
                        <button type="button" class="btn btn-secondary" onclick="navigateToPage('tickets')">Cancel</button>
                    </div>
                </form>
            </div>
        `;
        
        contentDiv.innerHTML = formHTML;
        
        // Setup form submission
        document.getElementById('createTicketForm').addEventListener('submit', handleCreateTicket);
        
    } catch (error) {
        console.error('Error loading create ticket form:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading form</div>';
    }
}

// Handle create ticket submission
async function handleCreateTicket(e) {
    e.preventDefault();
    
    const title = document.getElementById('ticketTitle').value;
    const description = document.getElementById('ticketDescription').value;
    const department_id = document.getElementById('ticketDepartment').value;
    const priority = document.getElementById('ticketPriority').value;
    
    try {
        const response = await fetch(`${API_BASE}/tickets`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                title,
                description,
                department_id,
                priority
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            alert('Ticket created successfully! Ticket ID: ' + data.ticketCode);
            navigateToPage('tickets');
        } else {
            alert('Error creating ticket: ' + data.error);
        }
    } catch (error) {
        console.error('Error creating ticket:', error);
        alert('Network error. Please try again.');
    }
}

// Load assigned tickets (for staff)
async function loadAssignedTickets() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const response = await fetch(`${API_BASE}/tickets`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const tickets = await response.json();
        
        // Filter tickets assigned to current staff or pending
        const assignedTickets = tickets.filter(ticket => 
            ticket.assigned_staff_id == currentUser.id || ticket.status === 'pending'
        );
        
        let ticketsHTML = `
            <div class="table-container">
                <h3>Assigned Tickets</h3>
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
        `;
        
        assignedTickets.forEach(ticket => {
            ticketsHTML += `
                <tr>
                    <td>${ticket.ticket_code}</td>
                    <td class="text-truncate" style="max-width: 200px;">${ticket.title}</td>
                    <td>${ticket.user_name}</td>
                    <td>${ticket.department_name}</td>
                    <td><span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></td>
                    <td><span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${new Date(ticket.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn view" onclick="viewTicket(${ticket.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${ticket.status === 'pending' ? `
                            <button class="action-btn edit" onclick="assignTicketToMe(${ticket.id})">
                                <i class="fas fa-user-plus"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        });
        
        ticketsHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = ticketsHTML;
        
    } catch (error) {
        console.error('Error loading assigned tickets:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading tickets</div>';
    }
}

// Load all tickets (for admin)
async function loadAllTickets() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const response = await fetch(`${API_BASE}/tickets`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const tickets = await response.json();
        
        let ticketsHTML = `
            <div class="table-container">
                <h3>All Tickets</h3>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Title</th>
                                <th>Submitted By</th>
                                <th>Department</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        tickets.forEach(ticket => {
            ticketsHTML += `
                <tr>
                    <td>${ticket.ticket_code}</td>
                    <td class="text-truncate" style="max-width: 200px;">${ticket.title}</td>
                    <td>${ticket.user_name}</td>
                    <td>${ticket.department_name}</td>
                    <td>${ticket.assigned_staff_name || 'Unassigned'}</td>
                    <td><span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></td>
                    <td><span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${new Date(ticket.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn view" onclick="viewTicket(${ticket.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editTicket(${ticket.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        ticketsHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = ticketsHTML;
        
    } catch (error) {
        console.error('Error loading all tickets:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading tickets</div>';
    }
}

// Load users management (admin only)
async function loadUsers() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const [usersResponse, departmentsResponse] = await Promise.all([
            fetch(`${API_BASE}/users`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }),
            fetch(`${API_BASE}/departments`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            })
        ]);
        
        const users = await usersResponse.json();
        const departments = await departmentsResponse.json();
        
        let usersHTML = `
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>User Management</h3>
                    <button class="btn btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        users.forEach(user => {
            usersHTML += `
                <tr>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td><span class="status-badge status-${user.role}">${user.role}</span></td>
                    <td>${user.department_name || 'N/A'}</td>
                    <td>
                        <button class="action-btn edit" onclick="editUser(${user.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteUser(${user.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        usersHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = usersHTML;
        
    } catch (error) {
        console.error('Error loading users:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading users</div>';
    }
}

// Load departments management (admin only)
async function loadDepartments() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const response = await fetch(`${API_BASE}/departments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const departments = await response.json();
        
        let departmentsHTML = `
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Department Management</h3>
                    <button class="btn btn-primary" onclick="showAddDepartmentModal()">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        departments.forEach(dept => {
            departmentsHTML += `
                <tr>
                    <td>${dept.name}</td>
                    <td>${dept.description || 'N/A'}</td>
                    <td>
                        <button class="action-btn delete" onclick="deleteDepartment(${dept.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        departmentsHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = departmentsHTML;
        
    } catch (error) {
        console.error('Error loading departments:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading departments</div>';
    }
}

// Load reports (admin only)
async function loadReports() {
    const contentDiv = document.getElementById('pageContent');
    
    try {
        const ticketsResponse = await fetch(`${API_BASE}/tickets`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const tickets = await ticketsResponse.json();
        
        // Calculate statistics
        const deptStats = {};
        const statusStats = {};
        const priorityStats = {};
        
        tickets.forEach(ticket => {
            // Department statistics
            if (!deptStats[ticket.department_name]) {
                deptStats[ticket.department_name] = 0;
            }
            deptStats[ticket.department_name]++;
            
            // Status statistics
            if (!statusStats[ticket.status]) {
                statusStats[ticket.status] = 0;
            }
            statusStats[ticket.status]++;
            
            // Priority statistics
            if (!priorityStats[ticket.priority]) {
                priorityStats[ticket.priority] = 0;
            }
            priorityStats[ticket.priority]++;
        });
        
        let reportsHTML = `
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="table-container">
                        <h4>Tickets by Department</h4>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Tickets</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;
        
        Object.entries(deptStats).forEach(([dept, count]) => {
            const percentage = ((count / tickets.length) * 100).toFixed(1);
            reportsHTML += `
                <tr>
                    <td>${dept}</td>
                    <td>${count}</td>
                    <td>${percentage}%</td>
                </tr>
            `;
        });
        
        reportsHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="table-container">
                        <h4>Tickets by Status</h4>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Tickets</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;
        
        Object.entries(statusStats).forEach(([status, count]) => {
            const percentage = ((count / tickets.length) * 100).toFixed(1);
            reportsHTML += `
                <tr>
                    <td><span class="status-badge status-${status}">${status.replace('_', ' ')}</span></td>
                    <td>${count}</td>
                    <td>${percentage}%</td>
                </tr>
            `;
        });
        
        reportsHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="table-container">
                        <h4>Tickets by Priority</h4>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Priority</th>
                                        <th>Tickets</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;
        
        Object.entries(priorityStats).forEach(([priority, count]) => {
            const percentage = ((count / tickets.length) * 100).toFixed(1);
            reportsHTML += `
                <tr>
                    <td><span class="priority-badge priority-${priority}">${priority}</span></td>
                    <td>${count}</td>
                    <td>${percentage}%</td>
                </tr>
            `;
        });
        
        reportsHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="table-container">
                        <h4>Summary Statistics</h4>
                        <div class="dashboard-card primary">
                            <div class="card-number">${tickets.length}</div>
                            <div class="card-label">Total Tickets</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = reportsHTML;
        
    } catch (error) {
        console.error('Error loading reports:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading reports</div>';
    }
}

// View ticket details
async function viewTicket(ticketId) {
    try {
        const response = await fetch(`${API_BASE}/tickets/${ticketId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const ticket = await response.json();
        
        let modalHTML = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Ticket ID:</strong> ${ticket.ticket_code}</p>
                    <p><strong>Title:</strong> ${ticket.title}</p>
                    <p><strong>Department:</strong> ${ticket.department_name}</p>
                    <p><strong>Priority:</strong> <span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></p>
                    <p><strong>Status:</strong> <span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Submitted By:</strong> ${ticket.user_name}</p>
                    <p><strong>Email:</strong> ${ticket.user_email}</p>
                    <p><strong>Assigned To:</strong> ${ticket.assigned_staff_name || 'Unassigned'}</p>
                    <p><strong>Created:</strong> ${new Date(ticket.created_at).toLocaleString()}</p>
                    <p><strong>Updated:</strong> ${new Date(ticket.updated_at).toLocaleString()}</p>
                </div>
            </div>
            
            <div class="mt-3">
                <p><strong>Description:</strong></p>
                <p>${ticket.description}</p>
            </div>
            
            <div class="ticket-notes">
                <h5>Notes</h5>
        `;
        
        if (ticket.notes && ticket.notes.length > 0) {
            ticket.notes.forEach(note => {
                modalHTML += `
                    <div class="note-item">
                        <div class="note-header">
                            <span>${note.staff_name}</span>
                            <span>${new Date(note.created_at).toLocaleString()}</span>
                        </div>
                        <div class="note-content">${note.note}</div>
                    </div>
                `;
            });
        } else {
            modalHTML += '<p>No notes yet.</p>';
        }
        
        modalHTML += `
            </div>
            
            <div class="mt-3">
                ${['staff', 'admin'].includes(currentUser.role) ? `
                    <div class="mb-3">
                        <label for="newNote" class="form-label">Add Note:</label>
                        <textarea class="form-control" id="newNote" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="addNote(${ticket.id})">Add Note</button>
                ` : ''}
            </div>
        `;
        
        document.getElementById('ticketModalBody').innerHTML = modalHTML;
        
        // Setup modal footer with actions based on role
        let footerHTML = '';
        
        if (currentUser.role === 'admin') {
            footerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="updateTicketStatus(${ticket.id}, 'pending')">Pending</button>
                <button type="button" class="btn btn-info" onclick="updateTicketStatus(${ticket.id}, 'assigned')">Assigned</button>
                <button type="button" class="btn btn-primary" onclick="updateTicketStatus(${ticket.id}, 'in_progress')">In Progress</button>
                <button type="button" class="btn btn-success" onclick="updateTicketStatus(${ticket.id}, 'resolved')">Resolved</button>
                <button type="button" class="btn btn-dark" onclick="updateTicketStatus(${ticket.id}, 'closed')">Closed</button>
            `;
        } else if (currentUser.role === 'staff') {
            if (ticket.status === 'pending') {
                footerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="assignTicketToMe(${ticket.id})">Assign to Me</button>
                `;
            } else if (ticket.assigned_staff_id == currentUser.id) {
                footerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateTicketStatus(${ticket.id}, 'in_progress')">Start Task</button>
                    <button type="button" class="btn btn-success" onclick="updateTicketStatus(${ticket.id}, 'resolved')">Mark Resolved</button>
                `;
            } else {
                footerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                `;
            }
        } else {
            footerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            `;
        }
        
        document.getElementById('ticketModalFooter').innerHTML = footerHTML;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
        modal.show();
        
    } catch (error) {
        console.error('Error viewing ticket:', error);
        alert('Error loading ticket details');
    }
}

// Add note to ticket
async function addNote(ticketId) {
    const noteText = document.getElementById('newNote').value;
    
    if (!noteText.trim()) {
        alert('Please enter a note');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/tickets/${ticketId}/notes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                note: noteText
            })
        });
        
        if (response.ok) {
            // Refresh ticket details
            document.getElementById('newNote').value = '';
            viewTicket(ticketId);
        } else {
            const error = await response.json();
            alert('Error adding note: ' + error.error);
        }
    } catch (error) {
        console.error('Error adding note:', error);
        alert('Network error. Please try again.');
    }
}

// Update ticket status
async function updateTicketStatus(ticketId, newStatus) {
    try {
        const response = await fetch(`${API_BASE}/tickets/${ticketId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                status: newStatus
            })
        });
        
        if (response.ok) {
            alert('Ticket status updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
            refreshCurrentPage();
        } else {
            const error = await response.json();
            alert('Error updating ticket: ' + error.error);
        }
    } catch (error) {
        console.error('Error updating ticket:', error);
        alert('Network error. Please try again.');
    }
}

// Assign ticket to current staff member
async function assignTicketToMe(ticketId) {
    try {
        const response = await fetch('/api/assign_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'ticket_id=' + ticketId
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Ticket assigned to you successfully');
            // Close modal if it exists
            const modal = document.getElementById('ticketModal');
            if (modal && bootstrap.Modal.getInstance(modal)) {
                bootstrap.Modal.getInstance(modal).hide();
            }
            // Refresh page
            if (typeof refreshCurrentPage === 'function') {
                refreshCurrentPage();
            } else {
                location.reload();
            }
        } else {
            alert('Error assigning ticket: ' + data.message);
        }
    } catch (error) {
        console.error('Error assigning ticket:', error);
        alert('Network error. Please try again.');
    }
}

// Refresh current page
function refreshCurrentPage() {
    loadPageContent(currentPage);
}

// Placeholder functions for admin features
function showAddUserModal() {
    alert('Add User functionality would be implemented here');
}

function showAddDepartmentModal() {
    alert('Add Department functionality would be implemented here');
}

function editUser(userId) {
    alert('Edit User functionality would be implemented here for user ID: ' + userId);
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        alert('Delete User functionality would be implemented here for user ID: ' + userId);
    }
}

function deleteDepartment(deptId) {
    if (confirm('Are you sure you want to delete this department?')) {
        alert('Delete Department functionality would be implemented here for department ID: ' + deptId);
    }
}

function editTicket(ticketId) {
    alert('Edit Ticket functionality would be implemented here for ticket ID: ' + ticketId);
}
