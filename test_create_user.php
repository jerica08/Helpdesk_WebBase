<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin User';
$_SESSION['user_email'] = 'admin@school.edu';
$_SESSION['user_role'] = 'admin';
$_SESSION['department_id'] = null;

echo "<h2>Test User Creation</h2>";

// Test data
$testUser = [
    'name' => 'Test User ' . date('His'),
    'email' => 'test' . date('His') . '@test.com',
    'password' => 'password123',
    'role' => 'user',
    'department_id' => 1
];

echo "Creating user with data:<br>";
echo "<pre>" . print_r($testUser, true) . "</pre>";

// Load models
require_once 'models/User.php';
$userModel = new User();

// Test user creation
$result = $userModel->register($testUser);

echo "<h3>Result:</h3>";
if ($result['success']) {
    echo "<span style='color: green;'>✅ User created successfully!</span><br>";
    echo "Message: " . $result['message'] . "<br>";
} else {
    echo "<span style='color: red;'>❌ User creation failed!</span><br>";
    echo "Message: " . $result['message'] . "<br>";
}

// Test if user was actually created
if ($result['success']) {
    echo "<h3>Verification:</h3>";
    $users = $userModel->getAllUsers();
    $found = false;
    foreach ($users as $user) {
        if ($user['email'] === $testUser['email']) {
            $found = true;
            echo "✅ User found in database: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";
            break;
        }
    }
    if (!$found) {
        echo "❌ User not found in database after creation<br>";
    }
}

echo "<br><a href='admin/users.php'>Go to Admin Users Page</a>";
?>
