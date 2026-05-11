<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();

echo "<h2>Correct Department Mapping</h2>";

// First, let's see the correct department mapping
$db->query("SELECT * FROM departments ORDER BY id");
$departments = $db->resultSet();

echo "<h3>Available Departments:</h3>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Name</th></tr>";

foreach ($departments as $dept) {
    echo "<tr><td>{$dept['id']}</td><td>{$dept['name']}</td></tr>";
}
echo "</table>";

echo "<br>";

// Now fix the staff assignments with correct department IDs
// Based on the output, IT Support should be the correct ID for IT staff
$db->query("SELECT id, name FROM departments WHERE name LIKE '%IT%' OR name LIKE '%Support%'");
$itDept = $db->single();

if ($itDept) {
    $itDeptId = $itDept['id'];
    $itDeptName = $itDept['name'];
    echo "<h3>Found IT Department: $itDeptName (ID: $itDeptId)</h3>";
    
    // Update Jisoo Kim to correct IT department
    $db->query("UPDATE users SET department_id = :dept_id WHERE name = 'Jisoo Kim' AND role = 'staff'");
    $db->bind(':dept_id', $itDeptId);
    if ($db->execute()) {
        echo "✅ Updated Jisoo Kim's department_id to $itDeptId ($itDeptName)<br>";
    }
} else {
    echo "❌ IT Support department not found!<br>";
}

// Find Finance department
$db->query("SELECT id, name FROM departments WHERE name LIKE '%Finance%'");
$financeDept = $db->single();

if ($financeDept) {
    $financeDeptId = $financeDept['id'];
    $financeDeptName = $financeDept['name'];
    echo "<h3>Found Finance Department: $financeDeptName (ID: $financeDeptId)</h3>";
    
    // Update Lisa Manoban to correct Finance department
    $db->query("UPDATE users SET department_id = :dept_id WHERE name = 'Lisa Manoban' AND role = 'staff'");
    $db->bind(':dept_id', $financeDeptId);
    if ($db->execute()) {
        echo "✅ Updated Lisa Manoban's department_id to $financeDeptId ($financeDeptName)<br>";
    }
} else {
    echo "❌ Finance department not found!<br>";
}

echo "<h3>Final Verification:</h3>";
$db->query("SELECT u.id, u.name, u.email, u.role, u.department_id, d.name as department_name 
           FROM users u 
           LEFT JOIN departments d ON u.department_id = d.id 
           WHERE u.role = 'staff'
           ORDER BY u.name");
$users = $db->resultSet();

echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Name</th><th>Department ID</th><th>Department Name</th></tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['department_id']}</td>";
    echo "<td>{$user['department_name']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
