# School Helpdesk System - PHP Version

A comprehensive PHP-based helpdesk system for schools with MVC architecture, role-based access control, and modern responsive design.

## Features

### 🎯 Role-Based Access Control
- **Users (Students/Faculty)**: Submit tickets, track status, view own tickets
- **Staff**: Manage assigned tickets, add notes, update status
- **Admin**: Full system administration, user management, reports

### 📊 Dashboard Features
- **User Dashboard**: Personal ticket statistics, quick actions
- **Staff Dashboard**: Assigned tickets, pending assignments, workflow management
- **Admin Dashboard**: System overview, user management, comprehensive statistics

### 🎫 Ticket Management
- Auto-generated unique ticket IDs
- Complete ticket lifecycle: Pending → Assigned → In Progress → Resolved → Closed
- Priority levels: Low, Medium, High
- Department-based routing
- Staff notes and communication history
- Real-time status updates

### 🔧 Technical Features
- **MVC Architecture**: Clean separation of concerns
- **Secure Authentication**: Session-based with role validation
- **Database Security**: Prepared statements, SQL injection prevention
- **Responsive Design**: Bootstrap 5 with custom CSS
- **Modern UI**: Clean, professional interface

## Tech Stack

- **Backend**: PHP 8.0+, MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Database**: MySQL 5.7+
- **Security**: bcrypt password hashing, session management
- **Architecture**: MVC (Model-View-Controller)

## Project Structure

```
Helpdesk_Web/
├── index.php                 # Main entry point (role-based redirect)
├── config/
│   ├── config.php           # Application configuration
│   └── Database.php         # Database connection class
├── models/
│   ├── User.php             # User model (authentication, CRUD)
│   ├── Ticket.php           # Ticket model (CRUD, statistics)
│   └── Department.php       # Department model
├── helpers/
│   └── auth_helper.php      # Authentication helpers
├── includes/
│   ├── header.php           # Common header template
│   └── footer.php           # Common footer template
├── auth/
│   ├── login.php            # Login page
│   └── logout.php           # Logout handler
├── user/                    # User dashboard and pages
│   ├── dashboard.php        # User dashboard
│   ├── create-ticket.php    # Create ticket form
│   └── tickets.php          # User tickets list
├── staff/                   # Staff dashboard and pages
│   ├── dashboard.php        # Staff dashboard
│   └── assigned-tickets.php # Assigned tickets
├── admin/                   # Admin dashboard and pages
│   ├── dashboard.php        # Admin dashboard
│   ├── users.php            # User management
│   ├── departments.php      # Department management
│   └── reports.php          # System reports
├── api/                     # AJAX endpoints
│   ├── ticket_details.php   # Get ticket details
│   ├── add_note.php         # Add ticket note
│   ├── update_ticket.php    # Update ticket status
│   └── assign_ticket.php    # Assign ticket to staff
├── assets/
│   ├── css/
│   │   └── style.css        # Custom styles
│   └── js/
│       └── app.js           # Frontend JavaScript
└── database.sql             # Database schema and sample data
```

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for future dependencies)

### Step 1: Database Setup

1. Create a MySQL database
2. Import the database schema:

```bash
mysql -u root -p helpdesk_system < database.sql
```

### Step 2: Configuration

1. Copy and edit the configuration file:

```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'helpdesk_system');
```

### Step 3: Web Server Setup

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Step 4: File Permissions

```bash
chmod 755 -R .
chmod 644 -R *.php
```

## Demo Accounts

The system includes pre-configured demo accounts:

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| Admin | admin@school.edu | password | Full system access |
| Staff | john.smith@school.edu | password | IT Support staff |
| Staff | jane.doe@school.edu | password | HR Department staff |
| User | mike.johnson@school.edu | password | Student account |
| User | sarah.wilson@school.edu | password | Faculty account |

## User Guides

### For Users (Students/Faculty)
1. **Login**: Use your email and password
2. **Create Ticket**: Click "Create Ticket" from dashboard
3. **Track Progress**: View status updates in "My Tickets"
4. **View Details**: Click on any ticket to see full details and staff notes

### For Staff
1. **View Dashboard**: See assigned tickets and pending assignments
2. **Assign Tickets**: Click "Assign to Me" on pending tickets
3. **Update Status**: Change status from "Assigned" → "In Progress" → "Resolved"
4. **Add Notes**: Communicate with users through ticket notes

### For Admins
1. **System Overview**: Monitor all tickets and user activity
2. **User Management**: Add, edit, or delete user accounts
3. **Department Management**: Create and manage support departments
4. **Reports**: View comprehensive system statistics

## Security Features

- **Password Security**: bcrypt hashing with cost factor 12
- **Session Management**: Secure session handling with expiration
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based request validation
- **Role-Based Access**: Strict permission checking for all actions

## API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `GET /auth/logout.php` - User logout

### Ticket Management
- `GET /api/ticket_details.php?id={id}` - Get ticket details
- `POST /api/add_note.php` - Add note to ticket
- `POST /api/update_ticket.php` - Update ticket status
- `POST /api/assign_ticket.php` - Assign ticket to staff

### User Management (Admin only)
- `GET /admin/users.php` - List all users
- `POST /admin/users.php` - Create new user
- `PUT /admin/users.php` - Update user
- `DELETE /admin/users.php` - Delete user

## Customization

### Adding New Roles
1. Update the `role` ENUM in the database
2. Modify role checks in `auth_helper.php`
3. Add role-specific menu items in `header.php`
4. Create role-specific views and controllers

### Custom Fields
1. Add columns to database tables
2. Update model classes to handle new fields
3. Modify forms to include new fields
4. Update display templates

### Email Notifications
1. Configure SMTP settings in `config.php`
2. Create email templates
3. Add email triggers in model methods
4. Implement email queue system for bulk notifications

## Performance Optimization

### Database Indexing
```sql
-- Add indexes for better performance
CREATE INDEX idx_tickets_user_id ON tickets(user_id);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_department_id ON tickets(department_id);
CREATE INDEX idx_users_email ON users(email);
```

### Caching
- Implement Redis/Memcached for session storage
- Cache frequently accessed data (departments, user lists)
- Use browser caching for static assets

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/config.php`
   - Verify MySQL server is running
   - Ensure database exists and user has permissions

2. **Login Issues**
   - Verify demo accounts exist in database
   - Check session configuration in `php.ini`
   - Ensure proper file permissions

3. **Permission Denied Errors**
   - Check user role in database
   - Verify role-based access logic
   - Review session data

### Debug Mode
Enable error reporting for development:
```php
// config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Future Enhancements

### Planned Features
- [ ] Email notifications system
- [ ] File attachments for tickets
- [ ] Real-time updates with WebSocket
- [ ] Advanced search and filtering
- [ ] SLA (Service Level Agreement) tracking
- [ ] Knowledge base integration
- [ ] Mobile app (React Native)
- [ ] Multi-language support
- [ ] API documentation (Swagger)
- [ ] Automated testing suite

### Integration Possibilities
- LDAP/Active Directory authentication
- Single Sign-On (SSO) with SAML
- Calendar integration for appointments
- Chat system for real-time support
- Payment gateway integration
- Learning Management System (LMS) integration

## Support

For technical support:
1. Check the troubleshooting section
2. Review error logs in web server and PHP
3. Verify database configuration
4. Test with demo accounts

## Contributing

1. Follow PSR-12 coding standards
2. Use meaningful variable and function names
3. Add comments for complex logic
4. Test all changes thoroughly
5. Update documentation for new features

## License

This project is licensed under the MIT License. See LICENSE file for details.

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Compatible**: PHP 8.0+, MySQL 5.7+
