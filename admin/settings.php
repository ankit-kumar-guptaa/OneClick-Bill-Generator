<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = 'New passwords do not match!';
            $message_type = 'danger';
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if (password_verify($current_password, $admin['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['admin_id']])) {
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error changing password!';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Current password is incorrect!';
                $message_type = 'danger';
            }
        }
    }
}

$page_title = 'Settings - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-gear"></i> Settings
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Change Password -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="bi bi-shield-lock"></i> Change Password
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" minlength="6" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" minlength="6" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="bi bi-check"></i> Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- System Info -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="bi bi-info-circle"></i> System Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Version:</strong></td>
                                            <td>1.0.0</td>
                                        </tr>
                                        <tr>
                                            <td><strong>PHP Version:</strong></td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Database:</strong></td>
                                            <td>MySQL</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Login:</strong></td>
                                            <td><?php echo date('d-m-Y H:i:s'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Backup & Export -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="bi bi-download"></i> Export Data
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Export your data for backup purposes.</p>
                                    <button class="btn btn-outline-success" onclick="exportData('partners')">
                                        <i class="bi bi-people"></i> Export Partners
                                    </button>
                                    <button class="btn btn-outline-info" onclick="exportData('bills')">
                                        <i class="bi bi-receipt"></i> Export Bills
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js = "
function exportData(type) {
    window.location.href = 'export_data.php?type=' + type;
}

// Password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
";
include '../includes/footer.php';
?>
