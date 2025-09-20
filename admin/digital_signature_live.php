<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle signature generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_signature'])) {
    $signer_name = sanitizeInput($_POST['signer_name']);
    $signature_style = $_POST['signature_style'] ?? 'style1';
    $include_timestamp = isset($_POST['include_timestamp']);
    
    // Generate live signature with fixed timezone
    $signature_path = generateLiveSignature($signer_name, $signature_style, $include_timestamp);
    
    if ($signature_path) {
        // Save signature for current admin
        $admin_signature_path = '../uploads/digital_signatures/signature_' . $_SESSION['admin_id'] . '.png';
        if (copy($signature_path, $admin_signature_path)) {
            $message = 'Live digital signature generated successfully!';
            $message_type = 'success';
        }
    } else {
        $message = 'Failed to generate signature!';
        $message_type = 'danger';
    }
}

function generateLiveSignature($name, $style, $includeTimestamp) {
    // Set timezone to India
    date_default_timezone_set('Asia/Kolkata');
    
    // Create image dimensions
    $width = 450;
    $height = 130;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $blue = imagecolorallocate($image, 0, 102, 204);
    $gray = imagecolorallocate($image, 102, 102, 102);
    $darkgray = imagecolorallocate($image, 68, 68, 68);
    
    // Make background transparent
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
    imagefill($image, 0, 0, $transparent);
    imagealphablending($image, true);
    
    // Current date and time (India timezone)
    $currentDate = date('Y.m.d');
    $currentTime = date('H:i:s');
    $timezone = '+05\'30\'';
    
    // Load font (use built-in fonts for compatibility)
    $nameFont = 5; // Large font
    $textFont = 3; // Medium font
    $smallFont = 2; // Small font
    
    // Generate signature based on style (exactly like sample image)
    switch ($style) {
        case 'style1': // Professional Style (Exact match to your sample)
            // Main signature name (Large, bold)
            imagestring($image, $nameFont, 15, 15, strtoupper($name), $black);
            
            // "Digitally signed by" text (Blue color)
            imagestring($image, $textFont, 15, 45, "Digitally signed by " . strtoupper($name), $blue);
            
            if ($includeTimestamp) {
                // Date line (Black)
                imagestring($image, $textFont, 15, 65, "Date: " . $currentDate, $black);
                
                // Time with timezone (Black)
                imagestring($image, $textFont, 15, 85, $currentTime . " " . $timezone, $black);
            }
            break;
            
        case 'style2': // Compact Style
            imagestring($image, $textFont, 15, 10, strtoupper($name), $blue);
            imagestring($image, $smallFont, 15, 35, "Digitally signed", $gray);
            if ($includeTimestamp) {
                imagestring($image, $smallFont, 15, 55, $currentDate . " " . $currentTime, $gray);
            }
            break;
            
        case 'style3': // Bordered Style
            // Draw border
            imagerectangle($image, 8, 8, $width-9, $height-9, $blue);
            imagestring($image, $textFont, 20, 20, strtoupper($name), $black);
            imagestring($image, $smallFont, 20, 45, "Digital Signature", $blue);
            if ($includeTimestamp) {
                imagestring($image, $smallFont, 20, 65, $currentDate . " " . $currentTime, $gray);
            }
            break;
    }
    
    // Add small verification mark
    imagestring($image, 1, $width-60, $height-12, "Verified", $gray);
    
    // Save image
    $filename = '../uploads/temp/live_signature_' . time() . '.png';
    $directory = dirname($filename);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (imagepng($image, $filename)) {
        imagedestroy($image);
        return $filename;
    }
    
    imagedestroy($image);
    return false;
}

$page_title = 'Live Digital Signature Generator - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<style>
.signature-preview {
    background: white;
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.signature-style-card {
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}

.signature-style-card:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
}

.signature-style-card.selected {
    border-color: #0d6efd;
    background: #e7f3ff;
}

.live-preview {
    background: linear-gradient(45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(-45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #f8f9fa 75%), 
                linear-gradient(-45deg, transparent 75%, #f8f9fa 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
}

.time-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #28a745;
    font-size: 1.2rem;
}

.date-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #007bff;
    font-size: 1.1rem;
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-gradient-primary text-white rounded-top-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-pen-fill me-3" style="font-size: 1.5rem;"></i>
                            <div>
                                <h4 class="mb-0">Live Digital Signature Generator</h4>
                                <p class="mb-0 opacity-90">Generate professional digital signatures with live timestamp</p>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="date-display" id="currentDate"><?php echo date('Y.m.d'); ?></div>
                            <div class="time-display" id="currentTime"><?php echo date('H:i:s'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Signature Generator -->
                        <div class="col-lg-6">
                            <div class="card border-0 bg-light">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">
                                        <i class="bi bi-gear me-2"></i>Signature Configuration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="signatureForm">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Signer Name</label>
                                            <input type="text" class="form-control form-control-lg" 
                                                   name="signer_name" id="signerName" 
                                                   value="SURAJ VERMA" 
                                                   placeholder="Enter full name"
                                                   onkeyup="updatePreview()" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Signature Style</label>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="card signature-style-card selected" onclick="selectStyle('style1')">
                                                        <div class="card-body p-3">
                                                            <input type="radio" name="signature_style" value="style1" checked hidden>
                                                            <div class="fw-bold text-primary">Professional Style</div>
                                                            <small class="text-muted">Full name with timestamp (Recommended)</small>
                                                            <div class="mt-2 p-2 bg-white rounded border">
                                                                <div style="font-family: monospace; font-size: 12px;">
                                                                    <div style="font-weight: bold;">SURAJ VERMA</div>
                                                                    <div style="color: #0066cc;">Digitally signed by SURAJ VERMA</div>
                                                                    <div>Date: <span id="sampleDate"><?php echo date('Y.m.d'); ?></span></div>
                                                                    <div><span id="sampleTime"><?php echo date('H:i:s'); ?></span> +05'30'</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <div class="card signature-style-card" onclick="selectStyle('style2')">
                                                        <div class="card-body p-3">
                                                            <input type="radio" name="signature_style" value="style2" hidden>
                                                            <div class="fw-bold text-success">Compact Style</div>
                                                            <small class="text-muted">Minimal space usage</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <div class="card signature-style-card" onclick="selectStyle('style3')">
                                                        <div class="card-body p-3">
                                                            <input type="radio" name="signature_style" value="style3" hidden>
                                                            <div class="fw-bold text-warning">Bordered Style</div>
                                                            <small class="text-muted">Professional with border</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="include_timestamp" id="includeTimestamp" 
                                                       checked onchange="updatePreview()">
                                                <label class="form-check-label fw-bold" for="includeTimestamp">
                                                    Include Live Timestamp
                                                </label>
                                                <div class="form-text">
                                                    Adds current date and time to signature
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="generate_signature" class="btn btn-primary btn-lg">
                                                <i class="bi bi-magic me-2"></i>Generate Live Signature
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Live Preview -->
                        <div class="col-lg-6">
                            <div class="card border-0">
                                <div class="card-header bg-gradient-success text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-eye me-2"></i>Live Preview
                                    </h5>
                                    <small class="opacity-90">Real-time signature preview</small>
                                </div>
                                <div class="card-body">
                                    <div class="live-preview p-4 rounded">
                                        <div class="signature-preview" id="signaturePreview">
                                            <canvas id="previewCanvas" width="400" height="120" style="border: 1px solid #dee2e6; border-radius: 5px; background: white;"></canvas>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    Updates every second
                                                </small>
                                                <div class="badge bg-success" id="liveIndicator">
                                                    <i class="bi bi-circle-fill me-1"></i>LIVE
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Current Signature Display -->
                                    <?php 
                                    $current_signature = '../uploads/digital_signatures/signature_' . $_SESSION['admin_id'] . '.png';
                                    if (file_exists($current_signature)): 
                                    ?>
                                    <div class="mt-4">
                                        <h6 class="fw-bold">Current Active Signature:</h6>
                                        <div class="p-3 bg-light rounded text-center">
                                            <img src="<?php echo $current_signature; ?>?<?php echo time(); ?>" 
                                                 alt="Current Signature" 
                                                 style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 5px;">
                                        </div>
                                        <div class="mt-2 text-center">
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Active on Invoices
                                            </span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let previewInterval;
let clockInterval;
let currentStyle = 'style1';

function updateClock() {
    const now = new Date();
    
    // Format date as Y.m.d
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateStr = year + '.' + month + '.' + day;
    
    // Format time as H:i:s
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeStr = hours + ':' + minutes + ':' + seconds;
    
    // Update displays
    document.getElementById('currentDate').textContent = dateStr;
    document.getElementById('currentTime').textContent = timeStr;
    document.getElementById('sampleDate').textContent = dateStr;
    document.getElementById('sampleTime').textContent = timeStr;
}

function selectStyle(style) {
    currentStyle = style;
    
    // Update radio button
    document.querySelector(`input[value="${style}"]`).checked = true;
    
    // Update card selection
    document.querySelectorAll('.signature-style-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`input[value="${style}"]`).closest('.signature-style-card').classList.add('selected');
    
    updatePreview();
}

function updatePreview() {
    const canvas = document.getElementById('previewCanvas');
    const ctx = canvas.getContext('2d');
    const signerName = document.getElementById('signerName').value.toUpperCase() || 'SURAJ VERMA';
    const includeTimestamp = document.getElementById('includeTimestamp').checked;
    
    // Clear canvas with white background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Get current date and time
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateStr = year + '.' + month + '.' + day;
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeStr = hours + ':' + minutes + ':' + seconds + " +05'30'";
    
    // Set font and colors
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    
    // Draw based on style
    switch (currentStyle) {
        case 'style1': // Professional Style (Exact match to sample)
            // Main signature name (Large, bold, black)
            ctx.font = 'bold 18px Arial';
            ctx.fillStyle = '#000000';
            ctx.fillText(signerName, 15, 15);
            
            // "Digitally signed by" text (Blue color)
            ctx.font = '13px Arial';
            ctx.fillStyle = '#0066cc';
            ctx.fillText('Digitally signed by ' + signerName, 15, 45);
            
            if (includeTimestamp) {
                // Date line (Black)
                ctx.font = '13px Arial';
                ctx.fillStyle = '#000000';
                ctx.fillText('Date: ' + dateStr, 15, 68);
                
                // Time with timezone (Black)
                ctx.fillText(timeStr, 15, 88);
            }
            break;
            
        case 'style2': // Compact Style
            ctx.font = 'bold 16px Arial';
            ctx.fillStyle = '#0066cc';
            ctx.fillText(signerName, 15, 15);
            
            ctx.font = '11px Arial';
            ctx.fillStyle = '#666666';
            ctx.fillText('Digitally signed', 15, 40);
            
            if (includeTimestamp) {
                ctx.fillText(dateStr + ' ' + timeStr, 15, 60);
            }
            break;
            
        case 'style3': // Bordered Style
            // Draw border
            ctx.strokeStyle = '#0066cc';
            ctx.lineWidth = 2;
            ctx.strokeRect(8, 8, canvas.width - 16, canvas.height - 16);
            
            ctx.font = 'bold 16px Arial';
            ctx.fillStyle = '#000000';
            ctx.fillText(signerName, 20, 20);
            
            ctx.font = '12px Arial';
            ctx.fillStyle = '#0066cc';
            ctx.fillText('Digital Signature', 20, 45);
            
            if (includeTimestamp) {
                ctx.font = '10px Arial';
                ctx.fillStyle = '#666666';
                ctx.fillText(dateStr + ' ' + timeStr, 20, 70);
            }
            break;
    }
    
    // Add verification mark
    ctx.font = '9px Arial';
    ctx.fillStyle = '#999999';
    ctx.textAlign = 'right';
    ctx.fillText('Verified', canvas.width - 10, canvas.height - 15);
}

// Start live preview and clock
function startLive() {
    updateClock();
    updatePreview();
    
    // Update clock every second
    clockInterval = setInterval(updateClock, 1000);
    
    // Update preview every second if timestamp is enabled
    previewInterval = setInterval(() => {
        if (document.getElementById('includeTimestamp').checked) {
            updatePreview();
        }
        
        // Animate live indicator
        const indicator = document.getElementById('liveIndicator');
        indicator.style.opacity = '0.5';
        setTimeout(() => {
            indicator.style.opacity = '1';
        }, 250);
    }, 1000);
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    startLive();
    
    // Update preview when form changes
    document.getElementById('signerName').addEventListener('input', updatePreview);
    document.getElementById('includeTimestamp').addEventListener('change', updatePreview);
});

// Cleanup intervals on page unload
window.addEventListener('beforeunload', function() {
    if (previewInterval) clearInterval(previewInterval);
    if (clockInterval) clearInterval(clockInterval);
});
</script>

<?php include '../includes/footer.php'; ?>
