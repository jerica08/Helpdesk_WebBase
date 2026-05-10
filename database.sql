-- School Helpdesk System Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS helpdesk_system;
USE helpdesk_system;

-- Departments Table (must be created first - referenced by users table)
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'staff', 'admin') NOT NULL DEFAULT 'user',
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Tickets Table
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    assigned_staff_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'assigned', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id)
);

-- Ticket Notes Table
CREATE TABLE ticket_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    staff_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- Insert Sample Departments
INSERT INTO departments (name, description) VALUES
('IT Support', 'Computer hardware, software, and network issues'),
('HR Department', 'Human resources and personnel matters'),
('Finance', 'Financial aid, billing, and payment issues'),
('Academic Affairs', 'Course registration, grades, and academic policies'),
('Facilities', 'Building maintenance, classroom issues, and campus facilities'),
('Library', 'Library services, resources, and study spaces');

-- Insert Sample Users
INSERT INTO users (name, email, password, role, department_id) VALUES
('Admin User', 'admin@school.edu', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
('John Smith', 'john.smith@school.edu', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1),
('Jane Doe', 'jane.doe@school.edu', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 2),
('Mike Johnson', 'mike.johnson@school.edu', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 3),
('Sarah Wilson', 'sarah.wilson@school.edu', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 4);

-- Insert Sample Tickets
INSERT INTO tickets (ticket_code, user_id, department_id, title, description, priority, status) VALUES
('TK001', 4, 1, 'Computer won''t start', 'My laptop is not turning on. I''ve tried charging it but nothing happens.', 'high', 'pending'),
('TK002', 5, 2, 'Question about benefits', 'I need information about the health insurance benefits available to staff.', 'medium', 'assigned'),
('TK003', 4, 3, 'Tuition payment issue', 'I was charged twice for my tuition this semester.', 'high', 'in_progress'),
('TK004', 5, 4, 'Course registration problem', 'I can''t register for the advanced course because of a prerequisite error.', 'medium', 'resolved'),
('TK005', 4, 5, 'Classroom projector broken', 'The projector in Room 205 is not working properly.', 'low', 'closed');

-- Update assigned staff for some tickets
UPDATE tickets SET assigned_staff_id = 2 WHERE id IN (2, 3);
UPDATE tickets SET assigned_staff_id = 3 WHERE id = 4;

-- Insert Sample Ticket Notes
INSERT INTO ticket_notes (ticket_id, staff_id, note) VALUES
(2, 2, 'Contacted the user to gather more information about benefits.'),
(3, 2, 'User has provided receipt of duplicate payment. Working with finance team.'),
(4, 3, 'Prerequisite override has been approved. User can now register.');
