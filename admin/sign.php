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
        $upload_dir = '../uploads/signatures/';
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['signature_file']['name']);
        $extension = strtolower($file_info['extension']);
        
        if (in_array($extension, $allowed_types)) {
            $filename = 'signature_manual.png';
            $target_path = $upload_dir . $filename;
            
            // Process image
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
                // Resize to signature dimensions (400x160)
                $resized = imagecreatetruecolor(400, 160);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefill($resized, 0, 0, $transparent);
                
                list($original_width, $original_height) = getimagesize($_FILES['signature_file']['tmp_name']);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, 400, 160, $original_width, $original_height);
                
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
    $signature_path = '../uploads/signatures/signature_manual.png';
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

$signature_exists = file_exists('../uploads/signatures/signature_manual.png');

$page_title = 'Digital Signature Upload - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4 p-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pen-fill me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h3 class="mb-1">Digital Signature Management</h3>
                            <p class="mb-0 opacity-90">Upload and manage digital signatures for PDF invoices</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Current Signature Display -->
                        <div class="col-lg-6 mb-4">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-header bg-transparent border-0 pb-2">
                                    <h5 class="mb-0 text-dark">
                                        <i class="bi bi-image me-2"></i>Current Signature
                                    </h5>
                                    <small class="text-muted">Active signature for all invoices</small>
                                </div>
                                <div class="card-body text-center">
                                    <div class="signature-display-area mb-3" 
                                         style="background: white; border: 2px dashed #dee2e6; border-radius: 10px; padding: 30px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                        <?php if ($signature_exists): ?>
                                            <img src="../uploads/signatures/signature_manual.png?<?php echo time(); ?>" 
                                                 class="img-fluid" 
                                                 style="max-width: 300px; max-height: 150px; object-fit: contain;"
                                                 alt="Current Digital Signature">
                                        <?php else: ?>
                                            <div class="text-center text-muted">
                                                <i class="bi bi-image" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-3 mb-0">No signature uploaded yet</p>
                                                <small>Upload a signature image to get started</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($signature_exists): ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the current signature?')">
                                                <button type="submit" name="delete_signature" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload New Signature -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary bg-opacity-10 border-0 pb-2">
                                    <h5 class="mb-0 text-primary">
                                        <i class="bi bi-upload me-2"></i>Upload New Signature
                                    </h5>
                                    <small class="text-muted">Replace or add digital signature</small>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="signatureUploadForm">
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">Choose Signature Image</label>
                                            <div class="file-drop-area" 
                                                 style="border: 2px dashed #0d6efd; border-radius: 10px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s ease;"
                                                 ondrop="dropHandler(event)" 
                                                 ondragover="dragOverHandler(event)"
                                                 ondragenter="dragEnterHandler(event)"
                                                 ondragleave="dragLeaveHandler(event)"
                                                 onclick="document.getElementById('signature_file').click()">
                                                <i class="bi bi-cloud-upload text-primary mb-3" style="font-size: 3rem;"></i>
                                                <p class="mb-2"><strong>Drop signature image here</strong></p>
                                                <p class="text-muted small mb-2">or click to browse files</p>
                                                <input type="file" 
                                                       class="d-none" 
                                                       id="signature_file"
                                                       name="signature_file" 
                                                       accept=".png,.jpg,.jpeg"
                                                       onchange="previewImage(event)" required>
                                            </div>
                                            <div class="form-text mt-2">
                                                <small>
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    <strong>Requirements:</strong><br>
                                                    • File formats: PNG, JPG, JPEG<br>
                                                    • Max file size: 2MB<br>
                                                    • Recommended: White/transparent background<br>
                                                    • Optimal size: 400x160 pixels
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Image Preview -->
                                        <div id="imagePreview" class="mb-4" style="display: none;">
                                            <h6 class="fw-semibold mb-2">Preview:</h6>
                                            <div class="preview-container p-3 bg-light border rounded text-center">
                                                <img id="previewImg" style="max-width: 100%; max-height: 120px; object-fit: contain;">
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="bi bi-lightbulb me-2"></i>
                                            <strong>Tips for best results:</strong><br>
                                            <small>
                                                • Use a clean, professional signature<br>
                                                • Scan or photograph against white background<br>
                                                • Keep signature dark and clear<br>
                                                • Avoid shadows or distortions
                                            </small>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="upload_signature" class="btn btn-primary btn-lg">
                                                <i class="bi bi-upload me-2"></i>Upload Signature
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usage Instructions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">
                                        <i class="bi bi-question-circle me-2"></i>How to Use
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center mb-3">
                                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-1-circle text-primary" style="font-size: 1.5rem;"></i>
                                                </div>
                                                <h6 class="mt-3 fw-semibold">Prepare Signature</h6>
                                                <p class="small text-muted">Sign on white paper and scan/photograph clearly</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center mb-3">
                                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-2-circle text-success" style="font-size: 1.5rem;"></i>
                                                </div>
                                                <h6 class="mt-3 fw-semibold">Upload Image</h6>
                                                <p class="small text-muted">Use the upload form above to select and upload your signature</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center mb-3">
                                                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-3-circle text-info" style="font-size: 1.5rem;"></i>
                                                </div>
                                                <h6 class="mt-3 fw-semibold">Auto Apply</h6>
                                                <p class="small text-muted">Signature will automatically appear on all new PDF invoices</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <a href="../index.php" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-house me-2"></i>Back to Dashboard
                            </a>
                            <a href="bills.php" class="btn btn-outline-primary">
                                <i class="bi bi-receipt me-2"></i>Generate Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}

// Drag and Drop functionality
function dragOverHandler(event) {
    event.preventDefault();
    event.currentTarget.style.backgroundColor = '#e7f3ff';
    event.currentTarget.style.borderColor = '#0d6efd';
}

function dragEnterHandler(event) {
    event.preventDefault();
}

function dragLeaveHandler(event) {
    event.currentTarget.style.backgroundColor = '';
    event.currentTarget.style.borderColor = '#0d6efd';
}

function dropHandler(event) {
    event.preventDefault();
    event.currentTarget.style.backgroundColor = '';
    
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = document.getElementById('signature_file');
        fileInput.files = files;
        previewImage({ target: { files: files } });
    }
}

// Form validation
document.getElementById('signatureUploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('signature_file');
    const file = fileInput.files[0];
    
    if (file) {
        // Check file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            e.preventDefault();
            alert('File size must be less than 2MB');
            return false;
        }
        
        // Check file type
        const allowedTypes = ['image/png', 'image/jpg', 'image/jpeg'];
        if (!allowedTypes.includes(file.type)) {
            e.preventDefault();
            alert('Please select a PNG, JPG, or JPEG image file');
            return false;
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
