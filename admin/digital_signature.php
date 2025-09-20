<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$message = '';
$message_type = '';

// Handle signature upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_signature'])) {
    if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === 0) {
        $allowed_types = ['png', 'jpg', 'jpeg'];
        $upload_dir = '../uploads/digital_signatures/';
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['signature_file']['name']);
        $extension = strtolower($file_info['extension']);
        
        if (in_array($extension, $allowed_types)) {
            $filename = 'signature_' . $_SESSION['admin_id'] . '.png';
            $target_path = $upload_dir . $filename;
            
            // Convert to PNG and resize
            $image = null;
            switch($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($_FILES['signature_file']['tmp_name']);
                    break;
                case 'png':
                    $image = imagecreatefrompng($_FILES['signature_file']['tmp_name']);
                    break;
            }
            
            if ($image) {
                // Resize image to signature size (300x150)
                $resized = imagecreatetruecolor(300, 150);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefill($resized, 0, 0, $transparent);
                
                list($original_width, $original_height) = getimagesize($_FILES['signature_file']['tmp_name']);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, 300, 150, $original_width, $original_height);
                
                if (imagepng($resized, $target_path)) {
                    $message = 'Digital signature uploaded successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to save signature image.';
                    $message_type = 'danger';
                }
                
                imagedestroy($image);
                imagedestroy($resized);
            }
        } else {
            $message = 'Please upload PNG, JPG, or JPEG image only.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Please select a signature image file.';
        $message_type = 'danger';
    }
}

// Handle signature deletion
if (isset($_POST['delete_signature'])) {
    $signature_path = '../uploads/digital_signatures/signature_' . $_SESSION['admin_id'] . '.png';
    if (file_exists($signature_path)) {
        if (unlink($signature_path)) {
            $message = 'Digital signature deleted successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete signature.';
            $message_type = 'danger';
        }
    }
}

$signature_exists = file_exists('../uploads/digital_signatures/signature_' . $_SESSION['admin_id'] . '.png');

$page_title = 'Digital Signature - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4">
                    <h4 class="mb-0">
                        <i class="bi bi-pen me-2"></i>Digital Signature Management
                    </h4>
                    <p class="mb-0 opacity-90">Upload your digital signature for invoices</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0">Current Signature</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($signature_exists): ?>
                                        <div class="signature-preview mb-3">
                                            <img src="../uploads/digital_signatures/signature_<?php echo $_SESSION['admin_id']; ?>.png?<?php echo time(); ?>" 
                                                 class="img-fluid border rounded" 
                                                 style="max-width: 200px; height: 100px; object-fit: contain; background: white;"
                                                 alt="Digital Signature">
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <button type="submit" name="delete_signature" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete your signature?')">
                                                <i class="bi bi-trash me-1"></i>Delete Signature
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">No signature uploaded yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0 text-primary">Upload New Signature</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Signature Image</label>
                                            <input type="file" class="form-control" name="signature_file" 
                                                   accept=".png,.jpg,.jpeg" required>
                                            <div class="form-text">
                                                <small class="text-muted">
                                                    • PNG, JPG, JPEG formats only<br>
                                                    • Recommended size: 300x150 pixels<br>
                                                    • Transparent background preferred
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <small>
                                                <strong>Tips for best results:</strong><br>
                                                • Use a clean, professional signature<br>
                                                • Ensure good contrast against white background<br>
                                                • Keep signature simple and readable
                                            </small>
                                        </div>
                                        
                                        <button type="submit" name="upload_signature" class="btn btn-primary w-100">
                                            <i class="bi bi-upload me-2"></i>Upload Signature
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- USB Digital Signature Option -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0 text-success">
                                        <i class="bi bi-usb-drive me-2"></i>USB Digital Signature Support
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>USB Digital Signature Device Integration:</strong><br>
                                        For advanced USB digital signature devices, additional browser plugins or desktop software may be required.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Manual Process:</h6>
                                            <ol class="small">
                                                <li>Generate signature using your USB device</li>
                                                <li>Save signature as PNG/JPG image</li>
                                                <li>Upload the image using form above</li>
                                            </ol>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Supported Devices:</h6>
                                            <ul class="small">
                                                <li>ePass Digital Signature Tokens</li>
                                                <li>USB Signature Pads</li>
                                                <li>Smart Card Readers</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-outline-success" onclick="detectUSBSignature()">
                                        <i class="bi bi-search me-2"></i>Detect USB Signature Device
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Sample -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">Invoice Preview</h5>
                                    <small class="text-muted">How your signature will appear on invoices</small>
                                </div>
                                <div class="card-body">
                                    <div class="invoice-preview-sample border rounded p-3 bg-white">
                                        <div class="text-end">
                                            <div class="mb-2"><small>For One click Insurance Web Aggregator Pvt Ltd.</small></div>
                                            
                                            <?php if ($signature_exists): ?>
                                                <img src="../uploads/digital_signatures/signature_<?php echo $_SESSION['admin_id']; ?>.png?<?php echo time(); ?>" 
                                                     style="width: 120px; height: 60px; object-fit: contain;" alt="Signature Preview">
                                            <?php else: ?>
                                                <div style="width: 120px; height: 60px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 0.75rem; color: #666;">
                                                    Digital Signature
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-2">
                                                <strong>Suraj Verma</strong><br>
                                                <small>Authorized Signatory</small>
                                            </div>
                                        </div>
                                    </div>
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
function detectUSBSignature() {
    // Basic USB device detection
    if (navigator.usb) {
        navigator.usb.getDevices().then(devices => {
            if (devices.length > 0) {
                alert('USB devices detected! Please use your device software to generate signature and save as image.');
            } else {
                alert('No USB signature devices detected. Please ensure your device is connected and drivers are installed.');
            }
        }).catch(err => {
            alert('USB detection not supported in this browser. Please use Chrome/Edge for USB device detection.');
        });
    } else {
        alert('USB Web API not supported. Please save your signature as image manually and upload.');
    }
}

// File preview
document.querySelector('input[name=\"signature_file\"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Show preview logic could be added here
            console.log('File selected:', file.name);
        };
        reader.readAsDataURL(file);
    }
});
";

include '../includes/footer.php';
?>
