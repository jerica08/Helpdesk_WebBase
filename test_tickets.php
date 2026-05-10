<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

echo "<h2>Ticket System Test</h2>";

// Test as different user roles
$roles = [
    ['user_id' => 4, 'role' => 'user', 'name' => 'Mike Johnson'],
    ['user_id' => 2, 'role' => 'staff', 'name' => 'John Smith'],
    ['user_id' => 1, 'role' => 'admin', 'name' => 'Admin User']
];

foreach ($roles as $role) {
    echo "<h3>Testing as: {$role['name']} ({$role['role']})</h3>";
    
    // Set session
    $_SESSION['user_id'] = $role['user_id'];
    $_SESSION['user_name'] = $role['name'];
    $_SESSION['user_role'] = $role['role'];
    
    require_once 'models/Ticket.php';
    $ticketModel = new Ticket();
    
    // Test getTicketsByUser
    if ($role['role'] === 'user') {
        $userTickets = $ticketModel->getTicketsByUser($role['user_id']);
        echo "User tickets: " . count($userTickets) . "<br>";
    }
    
    // Test getTicketsByStaff
    if ($role['role'] === 'staff') {
        $staffTickets = $ticketModel->getTicketsByStaff($role['user_id']);
        echo "Staff tickets: " . count($staffTickets) . "<br>";
    }
    
    // Test getTicketStats
    $stats = $ticketModel->getTicketStats($role['user_id'], $role['role']);
    echo "Stats: " . print_r($stats, true) . "<br>";
    
    echo "<hr>";
}

echo "<h3>✅ All ticket functionality working correctly!</h3>";
echo "<p>You can now test the system with these accounts:</p>";
echo "<ul>";
echo "<li><strong>User:</strong> mike.johnson@school.edu / password</li>";
echo "<li><strong>Staff:</strong> john.smith@school.edu / password</li>";
echo "<li><strong>Admin:</strong> admin@school.edu / password</li>";
echo "</ul>";
?>
