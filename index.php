<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect to admin login if not logged in
if (!isLoggedIn()) {
    header('Location: admin/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = array();

// Total Partners
$stmt = $db->prepare("SELECT COUNT(*) as total FROM partners WHERE status = 'active'");
$stmt->execute();
$stats['total_partners'] = $stmt->fetch()['total'];

// Total Bills
$stmt = $db->prepare("SELECT COUNT(*) as total FROM bills");
$stmt->execute();
$stats['total_bills'] = $stmt->fetch()['total'];

// Total Revenue
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM bills");
$stmt->execute();
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// This Month Revenue
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM bills WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())");
$stmt->execute();
$stats['month_revenue'] = $stmt->fetch()['total'] ?? 0;

// Recent Bills (Last 5)
$stmt = $db->prepare("
    SELECT b.*, p.partner_name 
    FROM bills b 
    LEFT JOIN partners p ON b.partner_id = p.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_bills = $stmt->fetchAll();

// Top Partners by Revenue
$stmt = $db->prepare("
    SELECT p.partner_name, COUNT(b.id) as bill_count, SUM(b.total_amount) as total_revenue
    FROM partners p 
    LEFT JOIN bills b ON p.id = b.partner_id 
    WHERE p.status = 'active'
    GROUP BY p.id, p.partner_name 
    ORDER BY total_revenue DESC 
    LIMIT 5
");
$stmt->execute();
$top_partners = $stmt->fetchAll();

$page_title = 'OneClick Insurance - Dashboard';
$css_path = 'assets/css/style.css';
$js_path = 'assets/js/script.js';
$nav_home = 'index.php';

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-primary border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle me-3" style="font-size: 2rem;"></i>
                    <div>
                        <h4 class="alert-heading mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h4>
                        <p class="mb-0">Here's your OneClick Insurance business overview for today.</p>
                    </div>
                    <div class="ms-auto text-end">
                        <small class="text-muted">
                            <i class="bi bi-calendar3"></i> <?php echo date('l, F d, Y'); ?><br>
                            <i class="bi bi-clock"></i> <?php echo date('h:i A'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-primary text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-white-75 fw-bold text-xs mb-1">Total Partners</div>
                            <div class="h2 mb-0 fw-bold"><?php echo $stats['total_partners']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-white-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-white stretched-link" href="admin/partners.php">View Details</a>
                    <div class="text-white"><i class="bi bi-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-success text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-white-75 fw-bold text-xs mb-1">Total Bills</div>
                            <div class="h2 mb-0 fw-bold"><?php echo $stats['total_bills']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-receipt-cutoff fa-2x text-white-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-white stretched-link" href="admin/bills.php?view=all">View All Bills</a>
                    <div class="text-white"><i class="bi bi-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-info text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-white-75 fw-bold text-xs mb-1">Total Revenue</div>
                            <div class="h2 mb-0 fw-bold"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-rupee fa-2x text-white-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-white stretched-link" href="admin/reports.php">View Reports</a>
                    <div class="text-white"><i class="bi bi-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-gradient-warning text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-white-75 fw-bold text-xs mb-1">This Month</div>
                            <div class="h2 mb-0 fw-bold"><?php echo formatCurrency($stats['month_revenue']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-month fa-2x text-white-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-white stretched-link" href="admin/bills.php?month=current">View Monthly Bills</a>
                    <div class="text-white"><i class="bi bi-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning-charge"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="admin/bills.php" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="bi bi-plus-circle mb-2" style="font-size: 2rem;"></i>
                                <span>Generate New Bill</span>
                                <small class="text-muted">Create invoice for partners</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="admin/partners.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="bi bi-people mb-2" style="font-size: 2rem;"></i>
                                <span>Manage Partners</span>
                                <small class="text-muted">Add or edit partners</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="admin/bills.php?view=all" class="btn btn-outline-info btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="bi bi-list-ul mb-2" style="font-size: 2rem;"></i>
                                <span>View All Bills</span>
                                <small class="text-muted">Browse bill history</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="admin/reports.php" class="btn btn-outline-secondary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="bi bi-graph-up mb-2" style="font-size: 2rem;"></i>
                                <span>Reports</span>
                                <small class="text-muted">Revenue analytics</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bills -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Recent Bills
                    </h5>
                    <a href="admin/bills.php?view=all" class="btn btn-light btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_bills)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice No.</th>
                                    <th>Partner</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bills as $bill): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $bill['invoice_number']; ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($bill['partner_name']); ?></td>
                                    <td><?php echo formatIndianDate($bill['invoice_date']); ?></td>
                                    <td><?php echo formatCurrency($bill['total_amount']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewBill(<?php echo $bill['id']; ?>)" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="generatePDF(<?php echo $bill['id']; ?>)" title="PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No bills generated yet</p>
                        <a href="admin/bills.php" class="btn btn-primary">Create First Bill</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Partners -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-award"></i> Top Partners
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_partners)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_partners as $index => $partner): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                            <div class="me-auto">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2"><?php echo $index + 1; ?></span>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars(substr($partner['partner_name'], 0, 30)); ?></h6>
                                        <small class="text-muted"><?php echo $partner['bill_count']; ?> bills</small>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong><?php echo formatCurrency($partner['total_revenue'] ?? 0); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">No partner data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js = "
function viewBill(id) {
    window.open('admin/view_bill.php?id=' + id, '_blank', 'width=1000,height=700,scrollbars=yes');
}

function generatePDF(billId) {
    showLoading();
    window.open('pdf/generate_pdf.php?bill_id=' + billId, '_blank');
    setTimeout(hideLoading, 2000);
}

// Auto-refresh stats every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
";

include 'includes/footer.php';
?>
