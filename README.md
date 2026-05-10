# School Helpdesk and Issue Reporting System

A comprehensive web-based helpdesk system for schools that allows students, faculty, and staff to submit and track support tickets across different departments.

## Features

### Role-Based Access
- **Users (Students/Faculty)**: Submit tickets, view their tickets, track status
- **Staff**: View assigned tickets, manage ticket lifecycle, add notes
- **Admin**: Full system access, user management, department management, reports

### Ticket Management
- Auto-generated unique ticket IDs
- Ticket lifecycle: Pending → Assigned → In Progress → Resolved → Closed
- Priority levels: Low, Medium, High
- Department-based routing
- Real-time status updates
- Staff notes and comments

### Dashboard Features
- Role-specific dashboard cards with statistics
- Recent tickets overview
- Responsive design for all devices
- Clean, modern UI with Bootstrap

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: Node.js, Express.js
- **Database**: MySQL
- **Authentication**: JWT (JSON Web Tokens)
- **Security**: bcryptjs for password hashing

## Project Structure

```
Helpdesk_Web/
├── server.js              # Main server file
├── package.json           # Dependencies and scripts
├── .env                   # Environment variables
├── database.sql           # Database schema and sample data
├── public/                # Frontend files
│   ├── index.html         # Main HTML file
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── app.js         # Frontend JavaScript
└── README.md              # This file
```

## Installation and Setup

### Prerequisites
- Node.js (v14 or higher)
- MySQL Server
- npm or yarn

### Step 1: Database Setup

1. Start your MySQL server
2. Create the database and tables by running the SQL script:

```bash
mysql -u root -p < database.sql
```

Or manually execute the SQL commands in `database.sql`.

### Step 2: Configure Environment Variables

1. Copy the `.env` file and update it with your database credentials:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_mysql_password
DB_NAME=helpdesk_system

# JWT Secret (change this to a secure random string)
JWT_SECRET=your_jwt_secret_key_here

# Server Configuration
PORT=3000
```

### Step 3: Install Dependencies

```bash
npm install
```

### Step 4: Start the Application

```bash
# For development
npm run dev

# For production
npm start
```

The application will be available at `http://localhost:3000`

## Demo Accounts

The system comes with pre-configured demo accounts (password: `password` for all):

### Admin Account
- **Email**: admin@school.edu
- **Role**: Admin
- **Access**: Full system administration, user management, reports

### Staff Accounts
- **Email**: john.smith@school.edu
- **Role**: Staff (IT Support)
- **Access**: Manage assigned tickets, add notes

- **Email**: jane.doe@school.edu  
- **Role**: Staff (HR Department)
- **Access**: Manage assigned tickets, add notes

### User Accounts
- **Email**: mike.johnson@school.edu
- **Role**: User (Student)
- **Access**: Submit tickets, view own tickets

- **Email**: sarah.wilson@school.edu
- **Role**: User (Faculty)
- **Access**: Submit tickets, view own tickets

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/register` - User registration

### Tickets
- `GET /api/tickets` - Get tickets (filtered by user role)
- `GET /api/tickets/:id` - Get ticket details
- `POST /api/tickets` - Create new ticket
- `PUT /api/tickets/:id` - Update ticket status/assignment
- `POST /api/tickets/:id/notes` - Add note to ticket

### Users (Admin only)
- `GET /api/users` - Get all users
- `POST /api/users` - Create new user
- `PUT /api/users/:id` - Update user
- `DELETE /api/users/:id` - Delete user
- `GET /api/staff` - Get staff members

### Departments
- `GET /api/departments` - Get all departments
- `POST /api/departments` - Create new department (Admin only)

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User full name
- `email` - Unique email address
- `password` - Hashed password
- `role` - ENUM('user', 'staff', 'admin')
- `department_id` - Foreign key to departments

### Departments Table
- `id` - Primary key
- `name` - Department name
- `description` - Department description

### Tickets Table
- `id` - Primary key
- `ticket_code` - Unique ticket identifier
- `user_id` - Foreign key to users (ticket creator)
- `department_id` - Foreign key to departments
- `assigned_staff_id` - Foreign key to users (assigned staff)
- `title` - Ticket title
- `description` - Ticket description
- `priority` - ENUM('low', 'medium', 'high')
- `status` - ENUM('pending', 'assigned', 'in_progress', 'resolved', 'closed')
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Ticket_Notes Table
- `id` - Primary key
- `ticket_id` - Foreign key to tickets
- `staff_id` - Foreign key to users (note author)
- `note` - Note content
- `created_at` - Creation timestamp

## Features by Role

### User (Student/Faculty)
- View personal dashboard with ticket statistics
- Submit new support tickets
- View and track their own tickets
- Receive status updates
- View ticket details and staff notes

### Staff
- View dashboard with assigned ticket statistics
- See pending tickets available for assignment
- Assign tickets to themselves
- Update ticket status (Start Task, Mark Resolved)
- Add notes to tickets
- View ticket history

### Admin
- Full system oversight
- View all tickets and statistics
- Manage user accounts (Create, Edit, Delete)
- Manage departments
- Generate reports (by department, status, priority)
- Assign tickets to any staff member
- Update any ticket status

## Security Features

- JWT-based authentication
- Password hashing with bcryptjs
- Role-based access control
- Input validation and sanitization
- SQL injection prevention with parameterized queries
- CORS protection

## Responsive Design

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile devices

## Future Enhancements

Potential features to add:
- Email notifications
- File attachments
- Real-time updates with Socket.io
- Advanced search and filtering
- Ticket templates
- SLA (Service Level Agreement) tracking
- Knowledge base integration
- Multi-language support

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL server is running
   - Check database credentials in `.env` file
   - Verify database exists

2. **Login Failed**
   - Check demo account credentials
   - Ensure password is "password"
   - Verify user exists in database

3. **Port Already in Use**
   - Change PORT in `.env` file
   - Kill process using the port

### Development Tips

- Use `npm run dev` for development with auto-restart
- Check browser console for JavaScript errors
- Review server logs for backend issues
- Test with different user roles

## License

This project is licensed under the MIT License.

## Support

For issues and questions, please refer to the demo accounts and documentation provided.
