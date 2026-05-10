<?php
require_once '../config/config.php';
require_once '../helpers/auth_helper.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'staff':
            header('Location: ../staff/dashboard.php');
            break;
        case 'user':
            header('Location: ../user/dashboard.php');
            break;
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        flashMessage('error', 'Please fill in all fields');
    } else {
        require_once '../models/User.php';
        $userModel = new User();
        $result = $userModel->login($email, $password);
        
        if ($result['success']) {
            flashMessage('success', 'Login successful!');
            $user = $result['user'];
            switch ($user['role']) {
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
                case 'staff':
                    header('Location: ../staff/dashboard.php');
                    break;
                case 'user':
                    header('Location: ../user/dashboard.php');
                    break;
            }
            exit();
        } else {
            flashMessage('error', $result['message']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-left">
            <div class="login-left-content">
                <img src="../rmmc.logo.png" alt="Company Logo" class="company-logo">
                <h1>Ramon Magsaysay Memorial Colleges</h1>
            </div>
        </div>
        <div class="login-right">
            <div class="login-box">
                <h2>Login</h2>
                <?php echo displayFlashMessages(); ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">User Name</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">LOGIN</button>
                </form>

                            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
