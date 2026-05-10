const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
const bodyParser = require('body-parser');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Serve static files
app.use(express.static('public'));

// Database connection
const dbConfig = {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME
};

let db;

async function initDB() {
    try {
        db = await mysql.createConnection(dbConfig);
        console.log('Connected to MySQL database');
    } catch (error) {
        console.error('Database connection failed:', error);
        process.exit(1);
    }
}

// JWT Middleware
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }

    jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
        if (err) {
            return res.status(403).json({ error: 'Invalid token' });
        }
        req.user = user;
        next();
    });
}

// Role-based access middleware
function authorizeRole(roles) {
    return (req, res, next) => {
        if (!roles.includes(req.user.role)) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        next();
    };
}

// Generate unique ticket code
function generateTicketCode() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return `TK${year}${month}${day}${random}`;
}

// AUTH ROUTES
app.post('/api/login', async (req, res) => {
    try {
        const { email, password } = req.body;
        
        const [users] = await db.execute(
            'SELECT * FROM users WHERE email = ?',
            [email]
        );

        if (users.length === 0) {
            return res.status(401).json({ error: 'Invalid credentials' });
        }

        const user = users[0];
        const isValidPassword = await bcrypt.compare(password, user.password);

        if (!isValidPassword) {
            return res.status(401).json({ error: 'Invalid credentials' });
        }

        const token = jwt.sign(
            { 
                id: user.id, 
                email: user.email, 
                role: user.role,
                name: user.name
            },
            process.env.JWT_SECRET,
            { expiresIn: '24h' }
        );

        res.json({
            token,
            user: {
                id: user.id,
                name: user.name,
                email: user.email,
                role: user.role,
                department_id: user.department_id
            }
        });
    } catch (error) {
        console.error('Login error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/register', async (req, res) => {
    try {
        const { name, email, password, role = 'user', department_id } = req.body;

        // Check if user already exists
        const [existingUsers] = await db.execute(
            'SELECT id FROM users WHERE email = ?',
            [email]
        );

        if (existingUsers.length > 0) {
            return res.status(400).json({ error: 'User already exists' });
        }

        // Hash password
        const hashedPassword = await bcrypt.hash(password, 10);

        // Create user
        const [result] = await db.execute(
            'INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)',
            [name, email, hashedPassword, role, department_id]
        );

        res.status(201).json({ 
            message: 'User created successfully',
            userId: result.insertId 
        });
    } catch (error) {
        console.error('Registration error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// DEPARTMENTS ROUTES
app.get('/api/departments', authenticateToken, async (req, res) => {
    try {
        const [departments] = await db.execute(
            'SELECT * FROM departments ORDER BY name'
        );
        res.json(departments);
    } catch (error) {
        console.error('Departments error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/departments', authenticateToken, authorizeRole(['admin']), async (req, res) => {
    try {
        const { name, description } = req.body;

        const [result] = await db.execute(
            'INSERT INTO departments (name, description) VALUES (?, ?)',
            [name, description]
        );

        res.status(201).json({
            message: 'Department created successfully',
            departmentId: result.insertId
        });
    } catch (error) {
        console.error('Department creation error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// USERS ROUTES
app.get('/api/users', authenticateToken, authorizeRole(['admin']), async (req, res) => {
    try {
        const [users] = await db.execute(`
            SELECT u.id, u.name, u.email, u.role, u.department_id, d.name as department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            ORDER BY u.name
        `);
        res.json(users);
    } catch (error) {
        console.error('Users error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/staff', authenticateToken, async (req, res) => {
    try {
        const [staff] = await db.execute(`
            SELECT id, name, email, department_id
            FROM users
            WHERE role = 'staff'
            ORDER BY name
        `);
        res.json(staff);
    } catch (error) {
        console.error('Staff error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/users', authenticateToken, authorizeRole(['admin']), async (req, res) => {
    try {
        const { name, email, password, role, department_id } = req.body;

        // Check if user already exists
        const [existingUsers] = await db.execute(
            'SELECT id FROM users WHERE email = ?',
            [email]
        );

        if (existingUsers.length > 0) {
            return res.status(400).json({ error: 'User already exists' });
        }

        // Hash password
        const hashedPassword = await bcrypt.hash(password, 10);

        // Create user
        const [result] = await db.execute(
            'INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)',
            [name, email, hashedPassword, role, department_id]
        );

        res.status(201).json({
            message: 'User created successfully',
            userId: result.insertId
        });
    } catch (error) {
        console.error('User creation error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.put('/api/users/:id', authenticateToken, authorizeRole(['admin']), async (req, res) => {
    try {
        const { id } = req.params;
        const { name, email, role, department_id } = req.body;

        await db.execute(
            'UPDATE users SET name = ?, email = ?, role = ?, department_id = ? WHERE id = ?',
            [name, email, role, department_id, id]
        );

        res.json({ message: 'User updated successfully' });
    } catch (error) {
        console.error('User update error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.delete('/api/users/:id', authenticateToken, authorizeRole(['admin']), async (req, res) => {
    try {
        const { id } = req.params;

        await db.execute('DELETE FROM users WHERE id = ?', [id]);

        res.json({ message: 'User deleted successfully' });
    } catch (error) {
        console.error('User deletion error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// TICKETS ROUTES
app.get('/api/tickets', authenticateToken, async (req, res) => {
    try {
        let query = `
            SELECT t.*, u.name as user_name, d.name as department_name, 
                   s.name as assigned_staff_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN departments d ON t.department_id = d.id
            LEFT JOIN users s ON t.assigned_staff_id = s.id
        `;

        const params = [];

        // Filter based on user role
        if (req.user.role === 'user') {
            query += ' WHERE t.user_id = ?';
            params.push(req.user.id);
        } else if (req.user.role === 'staff') {
            query += ' WHERE t.assigned_staff_id = ? OR t.status = "pending"';
            params.push(req.user.id);
        }

        query += ' ORDER BY t.created_at DESC';

        const [tickets] = await db.execute(query, params);
        res.json(tickets);
    } catch (error) {
        console.error('Tickets error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.get('/api/tickets/:id', authenticateToken, async (req, res) => {
    try {
        const { id } = req.params;

        const [tickets] = await db.execute(`
            SELECT t.*, u.name as user_name, u.email as user_email, 
                   d.name as department_name, s.name as assigned_staff_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN departments d ON t.department_id = d.id
            LEFT JOIN users s ON t.assigned_staff_id = s.id
            WHERE t.id = ?
        `, [id]);

        if (tickets.length === 0) {
            return res.status(404).json({ error: 'Ticket not found' });
        }

        // Get ticket notes
        const [notes] = await db.execute(`
            SELECT tn.*, u.name as staff_name
            FROM ticket_notes tn
            JOIN users u ON tn.staff_id = u.id
            WHERE tn.ticket_id = ?
            ORDER BY tn.created_at ASC
        `, [id]);

        const ticket = tickets[0];
        ticket.notes = notes;

        res.json(ticket);
    } catch (error) {
        console.error('Ticket details error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/tickets', authenticateToken, async (req, res) => {
    try {
        const { title, description, department_id, priority = 'medium' } = req.body;
        
        const ticketCode = generateTicketCode();

        const [result] = await db.execute(`
            INSERT INTO tickets (ticket_code, user_id, department_id, title, description, priority)
            VALUES (?, ?, ?, ?, ?, ?)
        `, [ticketCode, req.user.id, department_id, title, description, priority]);

        res.status(201).json({
            message: 'Ticket created successfully',
            ticketId: result.insertId,
            ticketCode: ticketCode
        });
    } catch (error) {
        console.error('Ticket creation error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.put('/api/tickets/:id', authenticateToken, async (req, res) => {
    try {
        const { id } = req.params;
        const { status, assigned_staff_id } = req.body;

        // Check permissions
        if (req.user.role === 'user') {
            return res.status(403).json({ error: 'Users cannot update tickets' });
        }

        let query = 'UPDATE tickets SET status = ?';
        const params = [status];

        if (assigned_staff_id) {
            query += ', assigned_staff_id = ?';
            params.push(assigned_staff_id);
        }

        query += ' WHERE id = ?';
        params.push(id);

        await db.execute(query, params);

        res.json({ message: 'Ticket updated successfully' });
    } catch (error) {
        console.error('Ticket update error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.post('/api/tickets/:id/notes', authenticateToken, async (req, res) => {
    try {
        const { id } = req.params;
        const { note } = req.body;

        // Only staff and admin can add notes
        if (!['staff', 'admin'].includes(req.user.role)) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }

        const [result] = await db.execute(
            'INSERT INTO ticket_notes (ticket_id, staff_id, note) VALUES (?, ?, ?)',
            [id, req.user.id, note]
        );

        res.status(201).json({
            message: 'Note added successfully',
            noteId: result.insertId
        });
    } catch (error) {
        console.error('Note creation error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// Dashboard Statistics
app.get('/api/dashboard/stats', authenticateToken, async (req, res) => {
    try {
        let statsQuery = '';
        let params = [];

        if (req.user.role === 'user') {
            statsQuery = `
                SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM tickets WHERE user_id = ?
            `;
            params = [req.user.id];
        } else if (req.user.role === 'staff') {
            statsQuery = `
                SELECT 
                    COUNT(*) as assigned_tickets,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed
                FROM tickets WHERE assigned_staff_id = ?
            `;
            params = [req.user.id];
        } else if (req.user.role === 'admin') {
            statsQuery = `
                SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM tickets
            `;
        }

        const [stats] = await db.execute(statsQuery, params);
        res.json(stats[0]);
    } catch (error) {
        console.error('Dashboard stats error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// Initialize database and start server
initDB().then(() => {
    app.listen(PORT, () => {
        console.log(`Server running on port ${PORT}`);
        console.log(`Access the application at: http://localhost:${PORT}`);
    });
});
