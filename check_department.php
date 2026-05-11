<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();

echo "<h2>Department Check for All Users</h2>";

$db->query("SELECT u.id, u.name, u.email, u.role, u.department_id, d.name as department_name 
           FROM users u 
           LEFT JOIN departments d ON u.department_id = d.id 
           WHERE u.role IN ('staff', 'admin')
           ORDER BY u.role, u.name");
$users = $db->resultSet();

echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department ID</th><th>Department Name</th></tr>";

foreach ($users as $user) {
    $deptId = $user['department_id'] ?? 'NULL';
    $deptName = $user['department_name'] ?? 'None';
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>$deptId</td>";
    echo "<td>$deptName</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Available Departments</h2>";

$db->query("SELECT * FROM departments ORDER BY name");
$departments = $db->resultSet();

echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";

foreach ($departments as $dept) {
    echo "<tr>";
    echo "<td>{$dept['id']}</td>";
    echo "<td>{$dept['name']}</td>";
    echo "<td>{$dept['description']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
