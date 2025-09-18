<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submission for new bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bill'])) {
    $partner_id = (int)$_POST['partner_id'];
    $invoice_number = generateInvoiceNumber();
    $invoice_date = $_POST['invoice_date'];
    $description = sanitizeInput($_POST['description']);
    $commission_amount = (float)$_POST['commission_amount'];
    
    // Calculate GST based on partner's state
    $stmt = $db->prepare("SELECT state_code FROM partners WHERE id = ?");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch();
    
    $cgst_amount = 0;
    $sgst_amount = 0;
    $igst_amount = 0;
    
    if ($partner['state_code'] === '09') { // Same state (UP)
        $cgst_amount = $commission_amount * 0.09;
        $sgst_amount = $commission_amount * 0.09;
    } else { // Different state
        $igst_amount = $commission_amount * 0.18;
    }
    
    $total_gst = $cgst_amount + $sgst_amount + $igst_amount;
    $total_amount = $commission_amount + $total_gst;
    $amount_in_words = numberToWords(floor($total_amount)) . ' rupees only';
    
    $stmt = $db->prepare("INSERT INTO bills (partner_id, invoice_number, invoice_date, description, commission_amount, cgst_amount, sgst_amount, igst_amount, total_gst, total_amount, amount_in_words) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$partner_id, $invoice_number, $invoice_date, $description, $commission_amount, $cgst_amount, $sgst_amount, $igst_amount, $total_gst, $total_amount, $amount_in_words])) {
        $message = 'Bill generated successfully! Invoice Number: ' . $invoice_number;
        $message_type = 'success';
    } else {
        $message = 'Error generating bill!';
        $message_type = 'danger';
    }
}

// Get all partners for dropdown
$stmt = $db->prepare("SELECT * FROM partners WHERE status = 'active' ORDER BY partner_name ASC");
$stmt->execute();
$partners = $stmt->fetchAll();

// Get all bills for listing
$stmt = $db->prepare("SELECT b.*, p.partner_name FROM bills b LEFT JOIN partners p ON b.partner_id = p.id ORDER BY b.created_at DESC");
$stmt->execute();
$bills = $stmt->fetchAll();

$page_title = 'Generate Bills - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Bill Generation Form -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-plus-circle"></i> Generate New Bill
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="billForm" onsubmit="return validateBillForm()">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="partner_id" class="form-label">Select Partner *</label>
                                <select class="form-select" id="partner_id" name="partner_id" onchange="calculateGST()" required>
                                    <option value="">Choose Partner...</option>
                                    <?php foreach ($partners as $partner): ?>
                                        <option value="<?php echo $partner['id']; ?>" 
                                                data-state-code="<?php echo $partner['state_code']; ?>"
                                                data-partner-name="<?php echo htmlspecialchars($partner['partner_name']); ?>">
                                            <?php echo htmlspecialchars($partner['partner_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Insurance Commission for policy..." required></textarea>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="commission_amount" class="form-label">Commission Amount (₹) *</label>
                                <input type="number" class="form-control" id="commission_amount" name="commission_amount" 
                                       step="0.01" min="0" onchange="formatCurrency(this)" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="cgst_amount" class="form-label">CGST Amount (₹)</label>
                                <input type="number" class="form-control" id="cgst_amount" step="0.01" readonly>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="sgst_amount" class="form-label">SGST Amount (₹)</label>
                                <input type="number" class="form-control" id="sgst_amount" step="0.01" readonly>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="igst_amount" class="form-label">IGST Amount (₹)</label>
                                <input type="number" class="form-control" id="igst_amount" step="0.01" readonly>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="total_gst" class="form-label">Total GST (₹)</label>
                                <input type="number" class="form-control" id="total_gst" step="0.01" readonly>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="total_amount" class="form-label">Total Amount (₹)</label>
                                <input type="number" class="form-control" id="total_amount" step="0.01" readonly>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" name="generate_bill" class="btn btn-primary btn-lg">
                                    <i class="bi bi-receipt"></i> Generate Bill
                                </button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Bill Preview -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-eye"></i> Bill Preview
                    </h5>
                </div>
                <div class="card-body" id="billPreview">
                    <div class="text-center mb-3">
                        <img src="../assets/images/logo.png" alt="OneClick Insurance" style="height: 40px;" 
                             onerror="this.style.display='none'">
                        <h6 class="mt-2">OneClick Insurance Web Aggregator Pvt Ltd.</h6>
                        <small class="text-muted">Phone: 0120-4344333</small>
                    </div>
                    
                    <div class="preview-content">
                        <p><strong>Partner:</strong> <span id="preview-partner">Select Partner</span></p>
                        <p><strong>Invoice Date:</strong> <span id="preview-date"><?php echo date('d-m-Y'); ?></span></p>
                        <p><strong>Description:</strong> <span id="preview-description">-</span></p>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td>Commission:</td>
                                    <td class="text-end preview-commission">₹0.00</td>
                                </tr>
                                <tr>
                                    <td>GST:</td>
                                    <td class="text-end preview-gst">₹0.00</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total:</strong></td>
                                    <td class="text-end preview-total"><strong>₹0.00</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Bills -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-list"></i> Recent Bills
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice No.</th>
                                    <th>Partner</th>
                                    <th>Date</th>
                                    <th>Commission</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($bills, 0, 10) as $bill): ?>
                                <tr>
                                    <td><?php echo $bill['invoice_number']; ?></td>
                                    <td><?php echo htmlspecialchars($bill['partner_name']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($bill['invoice_date'])); ?></td>
                                    <td><?php echo formatIndianCurrency($bill['commission_amount']); ?></td>
                                    <td><?php echo formatIndianCurrency($bill['total_amount']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="generatePDF(<?php echo $bill['id']; ?>)">
                                            <i class="bi bi-file-pdf"></i> PDF
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewBill(<?php echo $bill['id']; ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js = "
// Update preview when form changes
document.getElementById('partner_id').addEventListener('change', function() {
    document.getElementById('preview-partner').textContent = this.options[this.selectedIndex].text || 'Select Partner';
});

document.getElementById('invoice_date').addEventListener('change', function() {
    if (this.value) {
        const date = new Date(this.value);
        document.getElementById('preview-date').textContent = date.toLocaleDateString('en-GB');
    }
});

document.getElementById('description').addEventListener('input', function() {
    document.getElementById('preview-description').textContent = this.value || '-';
});

function viewBill(id) {
    window.open('view_bill.php?id=' + id, '_blank', 'width=800,height=600');
}
";
include '../includes/footer.php';
?>
