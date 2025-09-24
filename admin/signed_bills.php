<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle signed bill upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_signed_bill'])) {
    $bill_id = (int)$_POST['bill_id'];
    $notes = sanitizeInput($_POST['notes']);
    
    if (isset($_FILES['signed_pdf']) && $_FILES['signed_pdf']['error'] === 0) {
        $upload_dir = '../uploads/signed_bills/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Validate file
        $file_info = pathinfo($_FILES['signed_pdf']['name']);
        $extension = strtolower($file_info['extension']);
        
        if ($extension === 'pdf' && $_FILES['signed_pdf']['size'] <= 10485760) { // 10MB limit
            $invoice_number = $_POST['invoice_number'];
            $filename = 'signed_' . $invoice_number . '_' . time() . '.pdf';
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['signed_pdf']['tmp_name'], $target_path)) {
                // Update database
                $stmt = $db->prepare("
                    UPDATE signed_bills 
                    SET signed_pdf_path = ?, upload_status = 'uploaded', 
                        uploaded_by = ?, uploaded_at = NOW(), 
                        file_size = ?, notes = ?
                    WHERE bill_id = ?
                ");
                
                if ($stmt->execute([$target_path, $_SESSION['admin_id'], $_FILES['signed_pdf']['size'], $notes, $bill_id])) {
                    $message = 'Signed bill uploaded successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database update failed!';
                    $message_type = 'danger';
                }
            } else {
                $message = 'File upload failed!';
                $message_type = 'danger';
            }
        } else {
            $message = 'Please upload a PDF file under 10MB!';
            $message_type = 'danger';
        }
    }
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(b.invoice_number LIKE ? OR p.partner_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "sb.upload_status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(b.invoice_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(b.invoice_date) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get signed bills data
$sql = "
    SELECT 
        sb.*,
        b.invoice_number,
        b.invoice_date,
        b.total_amount,
        p.partner_name,
        au.name as uploaded_by_name
    FROM signed_bills sb
    LEFT JOIN bills b ON sb.bill_id = b.id
    LEFT JOIN partners p ON b.partner_id = p.id
    LEFT JOIN admin_users au ON sb.uploaded_by = au.id
    $where_clause
    ORDER BY sb.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$signed_bills = $stmt->fetchAll();

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total
    FROM signed_bills sb
    LEFT JOIN bills b ON sb.bill_id = b.id
    LEFT JOIN partners p ON b.partner_id = p.id
    $where_clause
";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get pending bills (bills without signed version)
$pending_sql = "
    SELECT b.*, p.partner_name,
           CASE WHEN sb.id IS NULL THEN 'not_added' ELSE sb.upload_status END as sign_status
    FROM bills b
    LEFT JOIN partners p ON b.partner_id = p.id
    LEFT JOIN signed_bills sb ON b.id = sb.bill_id
    WHERE sb.id IS NULL OR sb.upload_status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 10
";
$stmt = $db->prepare($pending_sql);
$stmt->execute();
$pending_bills = $stmt->fetchAll();

$page_title = 'Signed Bills Management - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-file-pdf-fill text-danger me-2"></i>Signed Bills Management
                    </h2>
                    <p class="text-muted mb-0">Upload and manage manually signed PDF invoices</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="refreshData()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Pending Bills for Upload -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-fill me-2"></i>Pending Sign Upload
                    </h5>
                    <small>Bills waiting for signed version</small>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <?php if (!empty($pending_bills)): ?>
                        <?php foreach ($pending_bills as $bill): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1"><?php echo $bill['invoice_number']; ?></h6>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($bill['partner_name'], 0, 30)); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?php echo formatIndianDate($bill['invoice_date']); ?>
                                        </small>
                                        <span class="fw-bold text-success">
                                            <?php echo formatCurrency($bill['total_amount']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-outline-primary btn-sm w-100" 
                                        onclick="openUploadModal(<?php echo $bill['id']; ?>, '<?php echo $bill['invoice_number']; ?>')">
                                    <i class="bi bi-upload me-1"></i>Upload Signed PDF
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">All bills are signed!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Signed Bills List -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-file-check-fill me-2"></i>Signed Bills Archive
                            </h5>
                            <small>Total: <?php echo number_format($total_records); ?> signed bills</small>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search invoice/partner..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="uploaded" <?php echo $status_filter === 'uploaded' ? 'selected' : ''; ?>>Uploaded</option>
                                <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?php echo $date_from; ?>" placeholder="From Date">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?php echo $date_to; ?>" placeholder="To Date">
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="signed_bills.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Bills Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice Details</th>
                                    <th>Partner</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Upload Info</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($signed_bills)): ?>
                                    <?php foreach ($signed_bills as $sb): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="fw-bold mb-1"><?php echo $sb['invoice_number']; ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?php echo formatIndianDate($sb['invoice_date']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($sb['partner_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                <?php echo formatCurrency($sb['total_amount']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'uploaded' => 'success',
                                                'verified' => 'primary'
                                            ];
                                            $status_icons = [
                                                'pending' => 'clock',
                                                'uploaded' => 'check-circle',
                                                'verified' => 'shield-check'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_colors[$sb['upload_status']]; ?>">
                                                <i class="bi bi-<?php echo $status_icons[$sb['upload_status']]; ?> me-1"></i>
                                                <?php echo ucfirst($sb['upload_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sb['uploaded_at']): ?>
                                                <div>
                                                    <small class="fw-semibold"><?php echo $sb['uploaded_by_name']; ?></small><br>
                                                    <small class="text-muted">
                                                        <?php echo date('d-m-Y H:i', strtotime($sb['uploaded_at'])); ?>
                                                    </small><br>
                                                    <?php if ($sb['file_size']): ?>
                                                        <small class="text-muted">
                                                            <?php echo number_format($sb['file_size'] / 1024, 1); ?> KB
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Not uploaded</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($sb['signed_pdf_path'] && file_exists($sb['signed_pdf_path'])): ?>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="viewSignedBill('<?php echo $sb['signed_pdf_path']; ?>')"
                                                            title="View Signed PDF">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="downloadSignedBill('<?php echo $sb['signed_pdf_path']; ?>', '<?php echo $sb['invoice_number']; ?>')"
                                                            title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-info" 
                                                        onclick="showBillDetails(<?php echo $sb['bill_id']; ?>)"
                                                        title="View Details">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">No signed bills found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>Upload Signed Bill
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="modal_bill_id">
                    <input type="hidden" name="invoice_number" id="modal_invoice_number">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Invoice Information</h6>
                            <div class="bg-light p-3 rounded">
                                <div class="mb-2">
                                    <strong>Invoice Number:</strong> <span id="display_invoice_number"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Process:</strong>
                                    <ol class="small mt-2 mb-0">
                                        <li>Download original PDF</li>
                                        <li>Print and manually sign</li>
                                        <li>Scan signed document</li>
                                        <li>Upload signed PDF here</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Upload Signed PDF</h6>
                            <div class="mb-3">
                                <label class="form-label">Signed PDF File *</label>
                                <input type="file" class="form-control" name="signed_pdf" 
                                       accept=".pdf" required id="pdfFile">
                                <div class="form-text">Max file size: 10MB</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" name="notes" rows="3" 
                                          placeholder="Any additional notes about the signed document..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_signed_bill" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Upload Signed Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUploadModal(billId, invoiceNumber) {
    document.getElementById('modal_bill_id').value = billId;
    document.getElementById('modal_invoice_number').value = invoiceNumber;
    document.getElementById('display_invoice_number').textContent = invoiceNumber;
    
    // Add entry to signed_bills table if not exists
    fetch('ajax_add_signed_bill.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `bill_id=${billId}&invoice_number=${invoiceNumber}`
    });
    
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

function viewSignedBill(filePath) {
    window.open(filePath, '_blank');
}

function downloadSignedBill(filePath, invoiceNumber) {
    const link = document.createElement('a');
    link.href = filePath;
    link.download = `Signed_${invoiceNumber}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showBillDetails(billId) {
    window.open(`view_bill.php?id=${billId}`, '_blank');
}

function refreshData() {
    location.reload();
}

// File size validation
document.getElementById('pdfFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.size > 10485760) { // 10MB
        alert('File size must be less than 10MB');
        e.target.value = '';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
