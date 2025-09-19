<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ../index.php'); // Main dashboard
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id, username, password, name FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_username'] = $user['username'];
        
        // Log successful login
        logActivity("Admin login successful: " . $user['username']);
        
        header('Location: ../index.php'); // Main dashboard
        exit;
    } else {
        $error_message = 'Invalid username or password!';
        logActivity("Failed login attempt: " . $username);
    }
}

$page_title = 'OneClick Insurance - Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card login-card border-0">
                        <div class="card-header bg-transparent text-center border-0 py-4">
                            <!-- <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i> -->
                             <img src="../assets/images/logo.jpg" width="150" alt="">
                            <h3 class="mt-3 mb-1">OneClick Insurance</h3>
                            <p class="text-muted">Admin Portal Login</p>
                        </div>
                        <div class="card-body px-4 py-4">
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="bi bi-person me-1"></i>Username
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="username" 
                                           name="username" placeholder="Enter username" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock me-1"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg" id="password" 
                                           name="password" placeholder="Enter password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Default credentials: admin / password
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <small class="text-white-50">
                            © <?php echo date('Y'); ?> OneClick Insurance. All rights reserved.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
