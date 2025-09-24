<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submission (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate Bill
    if (isset($_POST['generate_bill'])) {
        $partner_id = (int)$_POST['partner_id'];
        $invoice_date = sanitizeInput($_POST['invoice_date']);
        $description = sanitizeInput($_POST['description']);
        $commission_amount = (float)$_POST['commission_amount'];
        $gst_type = $_POST['gst_type'];
        $cgst_rate = (float)($_POST['cgst_rate'] ?? 9);
        $sgst_rate = (float)($_POST['sgst_rate'] ?? 9);
        $igst_rate = (float)($_POST['igst_rate'] ?? 18);
        
        // Calculate GST
        if ($gst_type === 'cgst_sgst') {
            $cgst_amount = ($commission_amount * $cgst_rate) / 100;
            $sgst_amount = ($commission_amount * $sgst_rate) / 100;
            $igst_amount = 0;
            $total_gst = $cgst_amount + $sgst_amount;
        } else {
            $cgst_amount = 0;
            $sgst_amount = 0;
            $igst_amount = ($commission_amount * $igst_rate) / 100;
            $total_gst = $igst_amount;
        }
        
        $total_amount = $commission_amount + $total_gst;
        $amount_in_words = numberToWords($total_amount) . ' Rupees Only';
        
        // Generate unique invoice number
        $invoice_number = generateInvoiceNumber();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO bills (
                    partner_id, invoice_number, invoice_date, description, 
                    commission_amount, cgst_amount, sgst_amount, igst_amount, 
                    total_gst, total_amount, amount_in_words
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $partner_id, $invoice_number, $invoice_date, $description,
                $commission_amount, $cgst_amount, $sgst_amount, $igst_amount,
                $total_gst, $total_amount, $amount_in_words
            ]);
            
            $bill_id = $db->lastInsertId();
            
            $message = "Invoice generated successfully! Invoice Number: $invoice_number";
            $message_type = 'success';
            
            header("Location: bills.php?created=1&bill_id=$bill_id");
            exit;
            
        } catch (Exception $e) {
            $message = 'Error generating bill: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    // Update Bill
    if (isset($_POST['update_bill'])) {
        $bill_id = (int)$_POST['bill_id'];
        $partner_id = (int)$_POST['partner_id'];
        $invoice_date = sanitizeInput($_POST['invoice_date']);
        $description = sanitizeInput($_POST['description']);
        $commission_amount = (float)$_POST['commission_amount'];
        $gst_type = $_POST['gst_type'];
        $cgst_rate = (float)($_POST['cgst_rate'] ?? 9);
        $sgst_rate = (float)($_POST['sgst_rate'] ?? 9);
        $igst_rate = (float)($_POST['igst_rate'] ?? 18);
        
        // Calculate GST
        if ($gst_type === 'cgst_sgst') {
            $cgst_amount = ($commission_amount * $cgst_rate) / 100;
            $sgst_amount = ($commission_amount * $sgst_rate) / 100;
            $igst_amount = 0;
            $total_gst = $cgst_amount + $sgst_amount;
        } else {
            $cgst_amount = 0;
            $sgst_amount = 0;
            $igst_amount = ($commission_amount * $igst_rate) / 100;
            $total_gst = $igst_amount;
        }
        
        $total_amount = $commission_amount + $total_gst;
        $amount_in_words = numberToWords($total_amount) . ' Rupees Only';
        
        try {
            $stmt = $db->prepare("
                UPDATE bills SET 
                    partner_id = ?, invoice_date = ?, description = ?, 
                    commission_amount = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, 
                    total_gst = ?, total_amount = ?, amount_in_words = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $partner_id, $invoice_date, $description,
                $commission_amount, $cgst_amount, $sgst_amount, $igst_amount,
                $total_gst, $total_amount, $amount_in_words, $bill_id
            ]);
            
            $message = "Invoice updated successfully!";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Error updating bill: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    // Delete Bill
    if (isset($_POST['delete_bill'])) {
        $bill_id = (int)$_POST['bill_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM bills WHERE id = ?");
            $stmt->execute([$bill_id]);
            
            $message = "Invoice deleted successfully!";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Error deleting bill: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Check if bill was just created
if (isset($_GET['created']) && isset($_GET['bill_id'])) {
    $message = "Invoice generated successfully! Click 'View' to see the invoice.";
    $message_type = 'success';
}

// Get partners for dropdown
$stmt = $db->prepare("SELECT * FROM partners WHERE status = 'active' ORDER BY partner_name ASC");
$stmt->execute();
$partners = $stmt->fetchAll();

// Get recent bills for display
$recent_bills_sql = "
    SELECT b.*, p.partner_name 
    FROM bills b 
    LEFT JOIN partners p ON b.partner_id = p.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
";
$stmt = $db->prepare($recent_bills_sql);
$stmt->execute();
$recent_bills = $stmt->fetchAll();

// Get total bills count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM bills");
$stmt->execute();
$total_bills = $stmt->fetchColumn();

$page_title = 'Generate Bill - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<style>
body {
    background: #f5f6fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.main-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.page-header {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
}

.form-container, .bills-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.section-header {
    background: #2c5aa0;
    color: white;
    padding: 1.2rem 2rem;
    border-bottom: 3px solid #1e4080;
}

.section-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control, .form-select {
    border: 1px solid #ced4da;
    border-radius: 5px;
    padding: 0.75rem;
    font-size: 0.95rem;
    transition: border-color 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #2c5aa0;
    box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.gst-options {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.gst-option {
    flex: 1;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    text-align: center;
}

.gst-option:hover {
    border-color: #2c5aa0;
}

.gst-option.selected {
    border-color: #2c5aa0;
    background: #f8f9fa;
}

.rate-inputs {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 1rem;
    margin-top: 1rem;
}

.calculation-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #dee2e6;
}

.amount-row:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 1.1rem;
    color: #2c5aa0;
}

.btn-primary {
    background: #2c5aa0;
    border-color: #2c5aa0;
    padding: 0.75rem 2rem;
    font-weight: 600;
}

.btn-primary:hover {
    background: #1e4080;
    border-color: #1e4080;
}

.recommended-badge {
    background: #28a745;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    margin-left: 0.5rem;
}

.alternative-badge {
    background: #6c757d;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    margin-left: 0.5rem;
}

.bills-list {
    max-height: 400px;
    overflow-y: auto;
}

.bill-item {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    transition: background-color 0.3s ease;
}

.bill-item:hover {
    background: #f8f9fa;
}

.bill-item:last-child {
    border-bottom: none;
}

.stats-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    flex: 1;
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c5aa0;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.action-buttons .btn-group .btn {
    border-radius: 0;
}

.action-buttons .btn-group .btn:first-child {
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
}

.action-buttons .btn-group .btn:last-child {
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
}
</style>

<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1 fw-bold text-dark">
                    <i class="bi bi-receipt me-2"></i>Invoice Management
                </h2>
                <p class="text-muted mb-0">Generate new invoices and manage existing ones</p>
            </div>
            <div>
                <button class="btn btn-outline-primary" onclick="toggleBillsList()">
                    <i class="bi bi-list-ul me-2"></i>View All Bills (<?php echo $total_bills; ?>)
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <?php 
    // Get stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bills,
            SUM(total_amount) as total_revenue,
            SUM(total_gst) as total_gst
        FROM bills 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $today_stats = $stmt->fetch();
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($today_stats['total_bills']); ?></div>
            <div class="stat-label">Today's Bills</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">₹<?php echo number_format($today_stats['total_revenue'] ?? 0); ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_bills; ?></div>
            <div class="stat-label">Total Bills</div>
        </div>
    </div>

    <div class="row">
        <!-- Generate Bill Form -->
        <div class="col-lg-8">
            <div class="form-container">
                <div class="section-header">
                    <h4 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Generate New Invoice
                    </h4>
                </div>
                
                <div class="section-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="billForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-building text-muted me-1"></i>
                                        Select Partner *
                                    </label>
                                    <select class="form-select" name="partner_id" required>
                                        <option value="">Choose Partner Company...</option>
                                        <?php foreach ($partners as $partner): ?>
                                            <option value="<?php echo $partner['id']; ?>">
                                                <?php echo htmlspecialchars($partner['partner_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-calendar3 text-muted me-1"></i>
                                        Invoice Date *
                                    </label>
                                    <input type="date" class="form-control" name="invoice_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-card-text text-muted me-1"></i>
                                        Service Description *
                                    </label>
                                    <textarea class="form-control" name="description" rows="3" 
                                              placeholder="Enter service description..." required>Insurance Commission for the month of <?php echo date('F Y'); ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-currency-rupee text-muted me-1"></i>
                                        Commission Amount *
                                    </label>
                                    <input type="number" class="form-control" name="commission_amount" 
                                           placeholder="Enter commission amount" step="0.01" min="0" 
                                           id="commissionAmount" onchange="calculateTotals()" required>
                                    <small class="form-text text-muted">Enter amount without GST</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-percent text-muted me-1"></i>
                                        GST Type Selection *
                                    </label>
                                    
                                    <div class="gst-options">
                                        <div class="gst-option" onclick="selectGSTType('igst')">
                                            <input type="radio" name="gst_type" value="igst" checked style="display: none;">
                                            <div><strong>IGST (18%)</strong></div>
                                            <span class="recommended-badge">Recommended</span>
                                            <br><small class="text-muted">Inter-State Supply</small>
                                        </div>
                                        
                                        <div class="gst-option" onclick="selectGSTType('cgst_sgst')">
                                            <input type="radio" name="gst_type" value="cgst_sgst" style="display: none;">
                                            <div><strong>CGST + SGST</strong></div>
                                            <span class="alternative-badge">Alternative</span>
                                            <br><small class="text-muted">Intra-State Supply</small>
                                        </div>
                                    </div>

                                    <div class="rate-inputs" id="igst_rates">
                                        <label class="form-label">IGST Rate (%)</label>
                                        <input type="number" class="form-control" name="igst_rate" 
                                               value="18" min="0" max="28" onchange="calculateTotals()">
                                    </div>

                                    <div class="rate-inputs" id="cgst_sgst_rates" style="display: none;">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">CGST Rate (%)</label>
                                                <input type="number" class="form-control" name="cgst_rate" 
                                                       value="9" min="0" max="28" onchange="calculateTotals()">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">SGST Rate (%)</label>
                                                <input type="number" class="form-control" name="sgst_rate" 
                                                       value="9" min="0" max="28" onchange="calculateTotals()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Preview -->
                        <div class="calculation-preview">
                            <h6 class="mb-3">
                                <i class="bi bi-calculator me-2"></i>Calculation Preview
                            </h6>
                            
                            <div class="amount-row">
                                <span>Commission Amount:</span>
                                <span id="displayCommission">₹0.00</span>
                            </div>
                            
                            <div class="amount-row">
                                <span>GST Amount:</span>
                                <span id="displayGST">₹0.00</span>
                            </div>
                            <div id="gstBreakdown" class="small text-muted mb-2"></div>
                            
                            <div class="amount-row">
                                <span>Total Amount:</span>
                                <span id="displayTotal">₹0.00</span>
                            </div>
                            
                            <div class="mt-3">
                                <strong>Amount in Words:</strong>
                                <div id="amountInWords" class="text-muted">Enter commission amount to see preview</div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="generate_bill" class="btn btn-primary btn-lg" disabled>
                                <i class="bi bi-plus-circle me-2"></i>Generate Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Bills -->
        <div class="col-lg-4">
            <div class="bills-container">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Bills
                    </h5>
                </div>
                
                <div class="section-body p-0">
                    <?php if (!empty($recent_bills)): ?>
                        <div class="bills-list">
                            <?php foreach ($recent_bills as $bill): ?>
                            <div class="bill-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold"><?php echo $bill['invoice_number']; ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($bill['partner_name'], 0, 25)); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo formatIndianDate($bill['invoice_date']); ?>
                                            </small>
                                            <span class="fw-bold text-success">
                                                ₹<?php echo number_format($bill['total_amount'], 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm w-100 action-buttons">
                                        <button class="btn btn-outline-primary" onclick="viewBill(<?php echo $bill['id']; ?>)" title="View Bill">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="downloadPDF(<?php echo $bill['id']; ?>)" title="Download PDF">
                                            <i class="bi bi-download"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="editBill(<?php echo $bill['id']; ?>)" title="Edit Bill">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteBill(<?php echo $bill['id']; ?>, '<?php echo $bill['invoice_number']; ?>')" title="Delete Bill">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-receipt text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No bills generated yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Bills Modal -->
<div class="modal fade" id="allBillsModal" tabindex="-1" aria-labelledby="allBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="allBillsModalLabel">
                    <i class="bi bi-list-ul me-2"></i>All Invoices
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Search and Filter -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchBills" placeholder="Search invoice/partner...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterMonth">
                            <option value="">All Months</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo date('m') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterYear">
                            <option value="">All Years</option>
                            <?php for ($year = date('Y'); $year >= 2020; $year--): ?>
                                <option value="<?php echo $year; ?>" <?php echo date('Y') == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterPartner">
                            <option value="">All Partners</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?php echo $partner['id']; ?>"><?php echo htmlspecialchars($partner['partner_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="loadAllBills()">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>

                <!-- Bills Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice No.</th>
                                <th>Date</th>
                                <th>Partner</th>
                                <th>Amount</th>
                                <th>GST</th>
                                <th>Total</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="billsTableBody">
                            <!-- Bills will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <div id="billsLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal fade" id="editBillModal" tabindex="-1" aria-labelledby="editBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editBillModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Invoice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBillForm">
                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="edit_bill_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Select Partner *</label>
                                <select class="form-select" name="partner_id" id="edit_partner_id" required>
                                    <option value="">Choose Partner Company...</option>
                                    <?php foreach ($partners as $partner): ?>
                                        <option value="<?php echo $partner['id']; ?>">
                                            <?php echo htmlspecialchars($partner['partner_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" name="invoice_date" id="edit_invoice_date" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Service Description *</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Commission Amount *</label>
                                <input type="number" class="form-control" name="commission_amount" 
                                       id="edit_commission_amount" step="0.01" min="0" 
                                       onchange="calculateEditTotals()" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">GST Type Selection *</label>
                                
                                <div class="gst-options">
                                    <div class="gst-option" onclick="selectEditGSTType('igst')">
                                        <input type="radio" name="gst_type" value="igst" id="edit_igst" style="display: none;">
                                        <div><strong>IGST (18%)</strong></div>
                                        <br><small class="text-muted">Inter-State Supply</small>
                                    </div>
                                    
                                    <div class="gst-option" onclick="selectEditGSTType('cgst_sgst')">
                                        <input type="radio" name="gst_type" value="cgst_sgst" id="edit_cgst_sgst" style="display: none;">
                                        <div><strong>CGST + SGST</strong></div>
                                        <br><small class="text-muted">Intra-State Supply</small>
                                    </div>
                                </div>

                                <div class="rate-inputs" id="edit_igst_rates" style="display: none;">
                                    <label class="form-label">IGST Rate (%)</label>
                                    <input type="number" class="form-control" name="igst_rate" 
                                           value="18" min="0" max="28" onchange="calculateEditTotals()">
                                </div>

                                <div class="rate-inputs" id="edit_cgst_sgst_rates" style="display: none;">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">CGST Rate (%)</label>
                                            <input type="number" class="form-control" name="cgst_rate" 
                                                   value="9" min="0" max="28" onchange="calculateEditTotals()">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">SGST Rate (%)</label>
                                            <input type="number" class="form-control" name="sgst_rate" 
                                                   value="9" min="0" max="28" onchange="calculateEditTotals()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Calculation Preview -->
                    <div class="calculation-preview">
                        <h6 class="mb-3">Updated Calculation</h6>
                        
                        <div class="amount-row">
                            <span>Commission Amount:</span>
                            <span id="editDisplayCommission">₹0.00</span>
                        </div>
                        
                        <div class="amount-row">
                            <span>GST Amount:</span>
                            <span id="editDisplayGST">₹0.00</span>
                        </div>
                        <div id="editGstBreakdown" class="small text-muted mb-2"></div>
                        
                        <div class="amount-row">
                            <span>Total Amount:</span>
                            <span id="editDisplayTotal">₹0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_bill" class="btn btn-warning">
                        <i class="bi bi-check-circle me-2"></i>Update Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Bill Modal -->
<div class="modal fade" id="deleteBillModal" tabindex="-1" aria-labelledby="deleteBillModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteBillModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Invoice
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="deleteBillForm">
                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="delete_bill_id">
                    
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Are you sure?</h5>
                        <p class="text-muted">
                            You are about to delete invoice <strong id="delete_invoice_number"></strong>. 
                            This action cannot be undone.
                        </p>
                        
                        <div class="alert alert-danger">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                This will permanently remove the invoice from the system.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_bill" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ... (Previous JavaScript functions remain the same) ...
// GST Type Selection for main form
function selectGSTType(type) {
    document.querySelectorAll('input[name="gst_type"]').forEach(radio => {
        radio.checked = false;
    });
    document.querySelector(`input[value="${type}"]`).checked = true;
    
    document.querySelectorAll('.gst-option').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`input[value="${type}"]`).closest('.gst-option').classList.add('selected');
    
    if (type === 'cgst_sgst') {
        document.getElementById('cgst_sgst_rates').style.display = 'block';
        document.getElementById('igst_rates').style.display = 'none';
    } else {
        document.getElementById('cgst_sgst_rates').style.display = 'none';
        document.getElementById('igst_rates').style.display = 'block';
    }
    
    calculateTotals();
}

// GST Type Selection for edit form
function selectEditGSTType(type) {
    document.getElementById('edit_igst').checked = false;
    document.getElementById('edit_cgst_sgst').checked = false;
    document.getElementById(`edit_${type}`).checked = true;
    
    document.querySelectorAll('#editBillModal .gst-option').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById(`edit_${type}`).closest('.gst-option').classList.add('selected');
    
    if (type === 'cgst_sgst') {
        document.getElementById('edit_cgst_sgst_rates').style.display = 'block';
        document.getElementById('edit_igst_rates').style.display = 'none';
    } else {
        document.getElementById('edit_cgst_sgst_rates').style.display = 'none';
        document.getElementById('edit_igst_rates').style.display = 'block';
    }
    
    calculateEditTotals();
}

function calculateTotals() {
    const commissionAmount = parseFloat(document.getElementById('commissionAmount').value) || 0;
    const gstType = document.querySelector('input[name="gst_type"]:checked').value;
    
    let gstAmount = 0;
    let gstBreakdown = '';
    
    if (gstType === 'cgst_sgst') {
        const cgstRate = parseFloat(document.querySelector('input[name="cgst_rate"]').value) || 9;
        const sgstRate = parseFloat(document.querySelector('input[name="sgst_rate"]').value) || 9;
        
        const cgstAmount = (commissionAmount * cgstRate) / 100;
        const sgstAmount = (commissionAmount * sgstRate) / 100;
        gstAmount = cgstAmount + sgstAmount;
        
        gstBreakdown = `CGST (${cgstRate}%): ₹${cgstAmount.toFixed(2)} + SGST (${sgstRate}%): ₹${sgstAmount.toFixed(2)}`;
    } else {
        const igstRate = parseFloat(document.querySelector('input[name="igst_rate"]').value) || 18;
        gstAmount = (commissionAmount * igstRate) / 100;
        
        gstBreakdown = `IGST (${igstRate}%): ₹${gstAmount.toFixed(2)}`;
    }
    
    const totalAmount = commissionAmount + gstAmount;
    
    document.getElementById('displayCommission').textContent = `₹${commissionAmount.toFixed(2)}`;
    document.getElementById('displayGST').textContent = `₹${gstAmount.toFixed(2)}`;
    document.getElementById('displayTotal').textContent = `₹${totalAmount.toFixed(2)}`;
    document.getElementById('gstBreakdown').textContent = gstBreakdown;
    
    if (totalAmount > 0) {
        document.getElementById('amountInWords').textContent = numberToWords(Math.floor(totalAmount)) + ' Rupees Only';
        document.querySelector('button[name="generate_bill"]').disabled = false;
    } else {
        document.getElementById('amountInWords').textContent = 'Enter commission amount to see preview';
        document.querySelector('button[name="generate_bill"]').disabled = true;
    }
}

function calculateEditTotals() {
    const commissionAmount = parseFloat(document.getElementById('edit_commission_amount').value) || 0;
    const gstType = document.querySelector('#editBillModal input[name="gst_type"]:checked')?.value || 'igst';
    
    let gstAmount = 0;
    let gstBreakdown = '';
    
    if (gstType === 'cgst_sgst') {
        const cgstRate = parseFloat(document.querySelector('#editBillModal input[name="cgst_rate"]').value) || 9;
        const sgstRate = parseFloat(document.querySelector('#editBillModal input[name="sgst_rate"]').value) || 9;
        
        const cgstAmount = (commissionAmount * cgstRate) / 100;
        const sgstAmount = (commissionAmount * sgstRate) / 100;
        gstAmount = cgstAmount + sgstAmount;
        
        gstBreakdown = `CGST (${cgstRate}%): ₹${cgstAmount.toFixed(2)} + SGST (${sgstRate}%): ₹${sgstAmount.toFixed(2)}`;
    } else {
        const igstRate = parseFloat(document.querySelector('#editBillModal input[name="igst_rate"]').value) || 18;
        gstAmount = (commissionAmount * igstRate) / 100;
        
        gstBreakdown = `IGST (${igstRate}%): ₹${gstAmount.toFixed(2)}`;
    }
    
    const totalAmount = commissionAmount + gstAmount;
    
    document.getElementById('editDisplayCommission').textContent = `₹${commissionAmount.toFixed(2)}`;
    document.getElementById('editDisplayGST').textContent = `₹${gstAmount.toFixed(2)}`;
    document.getElementById('editDisplayTotal').textContent = `₹${totalAmount.toFixed(2)}`;
    document.getElementById('editGstBreakdown').textContent = gstBreakdown;
}

function toggleBillsList() {
    const modal = new bootstrap.Modal(document.getElementById('allBillsModal'));
    modal.show();
    loadAllBills();
}

function loadAllBills() {
    const loading = document.getElementById('billsLoading');
    const tbody = document.getElementById('billsTableBody');
    
    loading.style.display = 'block';
    tbody.innerHTML = '';
    
    const search = document.getElementById('searchBills').value;
    const month = document.getElementById('filterMonth').value;
    const year = document.getElementById('filterYear').value;
    const partner = document.getElementById('filterPartner').value;
    
    const params = new URLSearchParams({
        search: search,
        month: month,
        year: year,
        partner: partner
    });
    
    fetch(`ajax_load_bills.php?${params}`)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success && data.bills.length > 0) {
                data.bills.forEach(bill => {
                    const row = createBillRow(bill);
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <br>No bills found matching your criteria
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('Error loading bills:', error);
        });
}

function createBillRow(bill) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="fw-bold">${bill.invoice_number}</div>
        </td>
        <td>
            <div>${bill.invoice_date}</div>
        </td>
        <td>
            <div class="text-truncate" style="max-width: 200px;" title="${bill.partner_name}">
                ${bill.partner_name}
            </div>
        </td>
        <td class="text-end">₹${parseFloat(bill.commission_amount).toLocaleString()}</td>
        <td class="text-end">₹${parseFloat(bill.total_gst).toLocaleString()}</td>
        <td class="text-end fw-bold">₹${parseFloat(bill.total_amount).toLocaleString()}</td>
        <td class="text-center">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="viewBill(${bill.id})" title="View Bill">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-outline-success" onclick="downloadPDF(${bill.id})" title="Download PDF">
                    <i class="bi bi-download"></i>
                </button>
                <button class="btn btn-outline-warning" onclick="editBillFromTable(${bill.id}, '${bill.partner_id}', '${bill.invoice_date_input}', '${bill.description.replace(/'/g, "\\'")}', ${bill.commission_amount}, ${bill.cgst_amount}, ${bill.sgst_amount}, ${bill.igst_amount})" title="Edit Bill">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" onclick="deleteBill(${bill.id}, '${bill.invoice_number}')" title="Delete Bill">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    `;
    return tr;
}

function viewBill(billId) {
    window.open(`view_bill.php?id=${billId}`, '_blank');
}

function downloadPDF(billId) {
    window.open(`../pdf/generate_pdf.php?bill_id=${billId}`, '_blank');
}

function editBill(billId) {
    // For recent bills - need to fetch bill details
    fetch(`ajax_get_bill.php?id=${billId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditModal(data.bill);
            }
        });
}

function editBillFromTable(billId, partnerId, invoiceDate, description, commissionAmount, cgstAmount, sgstAmount, igstAmount) {
    const bill = {
        id: billId,
        partner_id: partnerId,
        invoice_date: invoiceDate,
        description: description,
        commission_amount: commissionAmount,
        cgst_amount: cgstAmount,
        sgst_amount: sgstAmount,
        igst_amount: igstAmount
    };
    populateEditModal(bill);
}

function populateEditModal(bill) {
    document.getElementById('edit_bill_id').value = bill.id;
    document.getElementById('edit_partner_id').value = bill.partner_id;
    document.getElementById('edit_invoice_date').value = bill.invoice_date;
    document.getElementById('edit_description').value = bill.description;
    document.getElementById('edit_commission_amount').value = bill.commission_amount;
    
    // Determine GST type
    if (parseFloat(bill.cgst_amount) > 0) {
        selectEditGSTType('cgst_sgst');
    } else {
        selectEditGSTType('igst');
    }
    
    calculateEditTotals();
    
    const modal = new bootstrap.Modal(document.getElementById('editBillModal'));
    modal.show();
}

function deleteBill(billId, invoiceNumber) {
    document.getElementById('delete_bill_id').value = billId;
    document.getElementById('delete_invoice_number').textContent = invoiceNumber;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteBillModal'));
    modal.show();
}

function numberToWords(num) {
    if (num === 0) return 'Zero';
    
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if (num < 10) return ones[num];
    if (num < 20) return teens[num - 10];
    if (num < 100) return tens[Math.floor(num / 10)] + (num % 10 ? ' ' + ones[num % 10] : '');
    if (num < 1000) return ones[Math.floor(num / 100)] + ' Hundred' + (num % 100 ? ' ' + numberToWords(num % 100) : '');
    if (num < 100000) return numberToWords(Math.floor(num / 1000)) + ' Thousand' + (num % 1000 ? ' ' + numberToWords(num % 1000) : '');
    if (num < 10000000) return numberToWords(Math.floor(num / 100000)) + ' Lakh' + (num % 100000 ? ' ' + numberToWords(num % 100000) : '');
    
    return 'Number too large';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    selectGSTType('igst');
    
    <?php if (isset($_GET['created'])): ?>
        setTimeout(() => {
            toggleBillsList();
        }, 2000);
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>
