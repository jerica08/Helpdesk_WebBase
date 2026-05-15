// Global variables
let currentPage = '';

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide flash messages
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-success')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});

// View ticket details
function viewTicket(ticketId) {
    const modalBody = document.getElementById('ticketModalBody');
    const modalFooter = document.getElementById('ticketModalFooter');
    
    // Show loading
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner"></div></div>';
    
    // Fetch ticket details
    fetch(`../api/ticket_details.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTicketDetails(data.ticket);
                setupTicketActions(data.ticket);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
                modal.show();
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading ticket details</div>';
        });
}

// Display ticket details in modal
function displayTicketDetails(ticket) {
    const modalBody = document.getElementById('ticketModalBody');
    
    let notesHtml = '';
    if (ticket.notes && ticket.notes.length > 0) {
        notesHtml = '<div class="ticket-notes"><h5>Notes</h5>';
        ticket.notes.forEach(note => {
            notesHtml += `
                <div class="note-item">
                    <div class="note-header">
                        <span>${note.staff_name}</span>
                        <span>${formatDate(note.created_at)}</span>
                    </div>
                    <div class="note-content">${note.note}</div>
                </div>
            `;
        });
        notesHtml += '</div>';
    } else {
        notesHtml = '<div class="ticket-notes"><h5>Notes</h5><p>No notes yet.</p></div>';
    }
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Ticket ID:</strong> ${ticket.ticket_code}</p>
                <p><strong>Title:</strong> ${ticket.title}</p>
                <p><strong>Department:</strong> ${ticket.department_name}</p>
                <p><strong>Priority:</strong> ${getPriorityBadge(ticket.priority)}</p>
                <p><strong>Status:</strong> ${getStatusBadge(ticket.status)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Submitted By:</strong> ${ticket.user_name}</p>
                <p><strong>Email:</strong> ${ticket.user_email}</p>
                <p><strong>Assigned To:</strong> ${ticket.assigned_staff_name || 'Unassigned'}</p>
                <p><strong>Created:</strong> ${formatDate(ticket.created_at)}</p>
                <p><strong>Updated:</strong> ${formatDate(ticket.updated_at)}</p>
            </div>
        </div>
        
        <div class="mt-3">
            <p><strong>Description:</strong></p>
            <p>${ticket.description}</p>
        </div>
        
        ${notesHtml}
        
        <div class="mt-3">
            ${window.userRole === 'staff' || window.userRole === 'admin' ? `
                <div class="mb-3">
                    <label for="newNote" class="form-label">Add Note:</label>
                    <textarea class="form-control" id="newNote" rows="3"></textarea>
                </div>
                <button class="btn btn-primary btn-sm" onclick="addNote(${ticket.id})">Add Note</button>
            ` : ''}
        </div>
    `;
}

// Setup ticket action buttons
function setupTicketActions(ticket) {
    const modalFooter = document.getElementById('ticketModalFooter');
    let footerHtml = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    
    if (window.userRole === 'admin') {
        footerHtml += `
            <button type="button" class="btn btn-warning btn-sm" onclick="updateTicketStatus(${ticket.id}, 'pending')">Pending</button>
            <button type="button" class="btn btn-info btn-sm" onclick="updateTicketStatus(${ticket.id}, 'assigned')">Assigned</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="updateTicketStatus(${ticket.id}, 'in_progress')">In Progress</button>
            <button type="button" class="btn btn-success btn-sm" onclick="updateTicketStatus(${ticket.id}, 'resolved')">Resolved</button>
            <button type="button" class="btn btn-dark btn-sm" onclick="updateTicketStatus(${ticket.id}, 'closed')">Closed</button>
        `;
    } else if (window.userRole === 'staff') {
        if (ticket.status === 'pending') {
            footerHtml += `<button type="button" class="btn btn-primary btn-sm" onclick="assignTicketToMe(${ticket.id})">Assign to Me</button>`;
        } else if (ticket.assigned_staff_id == window.userId) {
            footerHtml += `
                <button type="button" class="btn btn-warning btn-sm" onclick="updateTicketStatus(${ticket.id}, 'pending')">Pending</button>
                <button type="button" class="btn btn-info btn-sm" onclick="updateTicketStatus(${ticket.id}, 'assigned')">Assigned</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="updateTicketStatus(${ticket.id}, 'in_progress')">In Progress</button>
                <button type="button" class="btn btn-success btn-sm" onclick="updateTicketStatus(${ticket.id}, 'resolved')">Resolved</button>
                <button type="button" class="btn btn-dark btn-sm" onclick="updateTicketStatus(${ticket.id}, 'closed')">Closed</button>
            `;
        }
    }
    
    modalFooter.innerHTML = footerHtml;
}

// Add note to ticket
function addNote(ticketId) {
    const noteText = document.getElementById('newNote').value;
    
    if (!noteText.trim()) {
        alert('Please enter a note');
        return;
    }
    
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('note', noteText);
    
    fetch('../api/add_note.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('newNote').value = '';
            viewTicket(ticketId); // Refresh ticket details
        } else {
            alert('Error adding note: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}

// Update ticket status
function updateTicketStatus(ticketId, newStatus) {
    if (!confirm(`Are you sure you want to change the status to "${newStatus.replace('_', ' ')}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('status', newStatus);
    
    fetch('../api/update_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket status updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
            location.reload(); // Refresh page
        } else {
            alert('Error updating ticket: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}

// Assign ticket to current staff member
function assignTicketToMe(ticketId) {
    if (!confirm('Are you sure you want to assign this ticket to yourself?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('assign_to_me', '1');
    
    fetch('../api/assign_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket assigned to you successfully');
            bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
            location.reload(); // Refresh page
        } else {
            alert('Error assigning ticket: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}

// Delete ticket (admin only)
function deleteTicket(ticketId) {
    if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
        return;
    }
    
    fetch(`../api/delete_ticket.php?id=${ticketId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket deleted successfully');
            location.reload(); // Refresh page
        } else {
            alert('Error deleting ticket: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="status-badge status-pending">Pending</span>',
        'assigned': '<span class="status-badge status-assigned">Assigned</span>',
        'in_progress': '<span class="status-badge status-in-progress">In Progress</span>',
        'resolved': '<span class="status-badge status-resolved">Resolved</span>',
        'closed': '<span class="status-badge status-closed">Closed</span>'
    };
    return badges[status] || '<span class="status-badge status-unknown">Unknown</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="priority-badge priority-low">Low</span>',
        'medium': '<span class="priority-badge priority-medium">Medium</span>',
        'high': '<span class="priority-badge priority-high">High</span>'
    };
    return badges[priority] || '<span class="priority-badge priority-unknown">Unknown</span>';
}

// Search functionality
function setupSearch(searchInput, resultsContainer, searchFunction) {
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchFunction(query);
        }, 300);
    });
}

// Form validation
function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Auto-resize textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});

// Print ticket details
function printTicket(ticketId) {
    window.open(`print_ticket.php?id=${ticketId}`, '_blank');
}

// Export data to CSV
function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function convertToCSV(data) {
    if (data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = data.map(row => {
        return headers.map(header => {
            const value = row[header];
            return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
        }).join(',');
    });
    
    return csvHeaders + '\n' + csvRows.join('\n');
}
