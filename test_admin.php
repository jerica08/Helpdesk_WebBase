<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin User';
$_SESSION['user_email'] = 'admin@school.edu';
$_SESSION['user_role'] = 'admin';
$_SESSION['department_id'] = null;

echo "Session set as admin<br>";

// Test models
require_once 'models/Ticket.php';
require_once 'models/User.php';
require_once 'models/Department.php';

$ticketModel = new Ticket();
$userModel = new User();
$departmentModel = new Department();

echo "Models loaded successfully<br>";

// Test basic methods
$ticketStats = $ticketModel->getTicketStats();
$userStats = $userModel->getUserStats();
$departments = $departmentModel->getAllDepartments();

echo "Ticket Stats: " . print_r($ticketStats, true) . "<br>";
echo "User Stats: " . print_r($userStats, true) . "<br>";
echo "Departments count: " . count($departments) . "<br>";

echo "<br>System appears to be working correctly!";
?>
