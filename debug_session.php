<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Start session
session_start();

// Require login
requireLogin();

echo "<h2>Session Debug Information</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Session Key</th><th>Value</th></tr>";

foreach ($_SESSION as $key => $value) {
    echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars(print_r($value, true)) . "</td></tr>";
}

echo "</table>";

// Get user details from database
require_once 'config/Database.php';
$db = new Database();

$db->query("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = :id");
$db->bind(':id', $_SESSION['user_id']);
$user = $db->single();

echo "<h2>Database User Information</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Field</th><th>Value</th></tr>";

echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
echo "<tr><td>Name</td><td>" . $user['name'] . "</td></tr>";
echo "<tr><td>Email</td><td>" . $user['email'] . "</td></tr>";
echo "<tr><td>Role</td><td>" . $user['role'] . "</td></tr>";
echo "<tr><td>Department ID</td><td>" . ($user['department_id'] ?? 'NULL') . "</td></tr>";
echo "<tr><td>Department Name</td><td>" . ($user['department_name'] ?? 'None') . "</td></tr>";

echo "</table>";

// Check recent tickets for this user
require_once 'models/Ticket.php';
$ticketModel = new Ticket();

echo "<h2>Ticket Filtering Test</h2>";

// Test without department filter
$allTickets = $ticketModel->getRecentTickets(10, $_SESSION['user_id'], $_SESSION['user_role']);
echo "<h3>All Tickets (No Department Filter): " . count($allTickets) . " tickets</h3>";
foreach ($allTickets as $ticket) {
    echo "- {$ticket['ticket_code']}: {$ticket['title']} (Dept: {$ticket['department_name']})<br>";
}

// Test with department filter
$deptTickets = $ticketModel->getRecentTickets(10, $_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['department_id']);
echo "<h3>Department Filtered Tickets: " . count($deptTickets) . " tickets</h3>";
foreach ($deptTickets as $ticket) {
    echo "- {$ticket['ticket_code']}: {$ticket['title']} (Dept: {$ticket['department_name']})<br>";
}

echo "<h2>Session vs Database Comparison</h2>";
echo "Session department_id: " . ($_SESSION['department_id'] ?? 'NOT SET') . "<br>";
echo "Database department_id: " . ($user['department_id'] ?? 'NULL') . "<br>";

if ($_SESSION['department_id'] != $user['department_id']) {
    echo "<strong style='color: red;'>MISMATCH DETECTED! Session and database department IDs don't match.</strong>";
} else {
    echo "<strong style='color: green;'>Session and database department IDs match.</strong>";
}
?>
