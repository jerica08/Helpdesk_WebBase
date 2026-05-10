<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

echo "<h2>Login Debug Test</h2>";

// Test direct login
$email = 'admin@school.edu';
$password = 'password';

echo "Testing login with:<br>";
echo "Email: $email<br>";
echo "Password: $password<br><br>";

require_once 'models/User.php';
$userModel = new User();
$result = $userModel->login($email, $password);

echo "Login result:<br>";
echo "<pre>";
print_r($result);
echo "</pre><br>";

echo "Session data after login:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre><br>";

echo "Is logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "Current user:<br>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}
?>
