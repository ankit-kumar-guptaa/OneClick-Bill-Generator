<?php
// setup.php - Auto setup database and tables
require_once '../config/database.php';

$database = new Database();
$messages = [];
$errors = [];

// Check if setup is requested
if (isset($_GET['action']) && $_GET['action'] === 'setup') {
    
    // Step 1: Check PHP extensions
    if (!extension_loaded('pdo_mysql')) {
        $errors[] = 'PDO MySQL extension is not loaded. Please enable it in php.ini';
    } else {
        $messages[] = 'PDO MySQL extension is loaded ✓';
    }
    
    // Step 2: Create database
    if ($database->createDatabase()) {
        $messages[] = 'Database created successfully ✓';
    } else {
        $errors[] = 'Failed to create database';
    }
    
    // Step 3: Test connection
    if ($database->testConnection()) {
        $messages[] = 'Database connection successful ✓';
        
        // Step 4: Create tables
        $db = $database->getConnection();
        if ($db) {
            $sql = file_get_contents('sql/database.sql');
            if ($sql) {
                try {
                    $db->exec($sql);
                    $messages[] = 'Tables created successfully ✓';
                    
                    // Redirect to login with success
                    header('Location: admin/login.php?setup=complete');
                    exit;
                    
                } catch (PDOException $e) {
                    $errors[] = 'Failed to create tables: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Could not read SQL file';
            }
        }
    } else {
        $errors[] = 'Database connection failed';
    }
}

$dbInfo = $database->getDatabaseInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneClick Insurance - Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-gear"></i> OneClick Insurance - Database Setup
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> Success Messages:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($messages as $message): ?>
                                        <li><?php echo $message; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle"></i> Error Messages:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <h5>System Information:</h5>
                        <table class="table table-striped">
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?php echo $dbInfo['php_version']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>PDO MySQL Extension:</strong></td>
                                <td>
                                    <?php if ($dbInfo['pdo_mysql_loaded']): ?>
                                        <span class="badge bg-success">Loaded ✓</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Not Loaded ✗</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Database Host:</strong></td>
                                <td><?php echo $dbInfo['host']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Database Name:</strong></td>
                                <td><?php echo $dbInfo['database']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Database Status:</strong></td>
                                <td>
                                    <?php if ($database->checkDatabase()): ?>
                                        <span class="badge bg-success">Exists ✓</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Found</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="mt-4">
                            <h5>Setup Instructions:</h5>
                            <ol>
                                <li><strong>Start XAMPP:</strong> Make sure Apache and MySQL are running</li>
                                <li><strong>Enable PDO MySQL:</strong> If not loaded, enable it in php.ini</li>
                                <li><strong>Run Setup:</strong> Click the setup button below</li>
                                <li><strong>Login:</strong> Use admin/password to access system</li>
                            </ol>
                        </div>
                        
                        <div class="text-center mt-4">
                            <?php if ($dbInfo['pdo_mysql_loaded']): ?>
                                <a href="?action=setup" class="btn btn-success btn-lg">
                                    <i class="bi bi-play-circle"></i> Run Automatic Setup
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <strong>PDO MySQL Extension Required:</strong><br>
                                    Please enable <code>extension=pdo_mysql</code> in your php.ini file and restart Apache.
                                </div>
                            <?php endif; ?>
                            
                            <a href="admin/login.php" class="btn btn-primary btn-lg ms-2">
                                <i class="bi bi-box-arrow-in-right"></i> Go to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
