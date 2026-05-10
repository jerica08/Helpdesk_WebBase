<?php
require_once 'config/config.php';

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "Database connection successful!<br>";
    
    $stmt = $db->query("SELECT email, password FROM users WHERE email = 'admin@school.edu'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Found admin user: " . $user['email'] . "<br>";
        echo "Password hash: " . $user['password'] . "<br>";
        
        if (password_verify('password', $user['password'])) {
            echo "Password verification SUCCESSFUL!";
        } else {
            echo "Password verification FAILED!";
        }
    } else {
        echo "Admin user not found in database!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
