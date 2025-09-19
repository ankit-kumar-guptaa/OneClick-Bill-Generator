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

// Get dashboard statistics (same as before)
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

<!-- Professional Minimal Styles -->
<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-bg: #ecf0f1;
    --white: #ffffff;
    --text-dark: #2c3e50;
    --text-muted: #7f8c8d;
    --border-color: #bdc3c7;
}

body {
    background-color: #f8f9fa;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    color: var(--text-dark);
}

.professional-card {
    background: var(--white);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

.professional-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.stat-card {
    background: var(--white);
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.bg-primary-subtle { background-color: #e8f4fd; color: var(--primary-color); }
.bg-success-subtle { background-color: #e8f5e8; color: var(--success-color); }
.bg-warning-subtle { background-color: #fef9e7; color: var(--warning-color); }
.bg-info-subtle { background-color: #e7f3ff; color: var(--accent-color); }

.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border-radius: 15px;
    color: white;
}

.action-card {
    background: var(--white);
    border: 2px solid transparent;
    border-radius: 10px;
    transition: all 0.2s ease;
    text-decoration: none;
    color: var(--text-dark);
}

.action-card:hover {
    border-color: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
    color: var(--text-dark);
}

.table-modern {
    background: var(--white);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.table-modern thead th {
    background-color: #f8f9fa;
    border: none;
    font-weight: 600;
    color: var(--text-dark);
    padding: 1rem;
}

.table-modern tbody tr {
    border: none;
    transition: background-color 0.2s ease;
}

.table-modern tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-minimal {
    border: 1px solid #e9ecef;
    background: white;
    color: var(--text-dark);
    border-radius: 8px;
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.btn-minimal:hover {
    border-color: var(--accent-color);
    color: var(--accent-color);
    background: white;
}
</style>

<div class="container-fluid py-4">
    <!-- Professional Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-banner p-4 shadow-sm">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h3 class="fw-bold mb-2">Good <?php echo (date('H') < 12) ? 'Morning' : ((date('H') < 17) ? 'Afternoon' : 'Evening'); ?>, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h3>
                        <p class="mb-0 opacity-90">Welcome to your OneClick Insurance dashboard</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="text-white-50">
                            <i class="bi bi-calendar3 me-2"></i><?php echo date('l, M d, Y'); ?>
                            <br>
                            <i class="bi bi-clock me-2"></i><?php echo date('h:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary-subtle me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 text-uppercase small fw-semibold">Partners</h6>
                            <h2 class="fw-bold mb-0"><?php echo $stats['total_partners']; ?></h2>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="admin/partners.php" class="text-decoration-none text-muted small">
                            View all partners <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success-subtle me-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 text-uppercase small fw-semibold">Total Bills</h6>
                            <h2 class="fw-bold mb-0"><?php echo $stats['total_bills']; ?></h2>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="admin/bills.php?view=all" class="text-decoration-none text-muted small">
                            View all bills <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info-subtle me-3">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 text-uppercase small fw-semibold">Total Revenue</h6>
                            <h2 class="fw-bold mb-0"><?php echo formatCurrency($stats['total_revenue']); ?></h2>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="admin/reports.php" class="text-decoration-none text-muted small">
                            View reports <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning-subtle me-3">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 text-uppercase small fw-semibold">This Month</h6>
                            <h2 class="fw-bold mb-0"><?php echo formatCurrency($stats['month_revenue']); ?></h2>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="admin/bills.php?month=current" class="text-decoration-none text-muted small">
                            Monthly bills <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card professional-card border-0">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">Quick Actions</h5>
                    <p class="text-muted mb-0">Common tasks and operations</p>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <a href="admin/bills.php" class="action-card d-block p-4 text-center">
                                <i class="bi bi-plus-circle text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h6 class="fw-semibold mb-2">Generate Bill</h6>
                                <p class="text-muted small mb-0">Create new invoice</p>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="admin/partners.php" class="action-card d-block p-4 text-center">
                                <i class="bi bi-people text-success mb-3" style="font-size: 2.5rem;"></i>
                                <h6 class="fw-semibold mb-2">Manage Partners</h6>
                                <p class="text-muted small mb-0">Add or edit partners</p>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="admin/bills.php?view=all" class="action-card d-block p-4 text-center">
                                <i class="bi bi-list-ul text-info mb-3" style="font-size: 2.5rem;"></i>
                                <h6 class="fw-semibold mb-2">View Bills</h6>
                                <p class="text-muted small mb-0">Browse all invoices</p>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="admin/reports.php" class="action-card d-block p-4 text-center">
                                <i class="bi bi-graph-up text-warning mb-3" style="font-size: 2.5rem;"></i>
                                <h6 class="fw-semibold mb-2">Reports</h6>
                                <p class="text-muted small mb-0">Analytics & insights</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Professional Recent Bills -->
        <div class="col-lg-8 mb-4">
            <div class="card professional-card border-0">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Recent Bills</h5>
                        <p class="text-muted mb-0 small">Latest invoice activity</p>
                    </div>
                    <a href="admin/bills.php?view=all" class="btn btn-minimal btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_bills)): ?>
                    <div class="table-modern">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Partner</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bills as $bill): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $bill['invoice_number']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($bill['partner_name'], 0, 30)); ?></td>
                                    <td class="text-muted"><?php echo formatIndianDate($bill['invoice_date']); ?></td>
                                    <td class="fw-bold"><?php echo formatCurrency($bill['total_amount']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-minimal" onclick="viewBill(<?php echo $bill['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-minimal" onclick="generatePDF(<?php echo $bill['id']; ?>)">
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
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <h6 class="text-muted mt-3">No bills generated yet</h6>
                        <a href="admin/bills.php" class="btn btn-primary btn-sm mt-2">Create First Bill</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Professional Top Partners -->
        <div class="col-lg-4 mb-4">
            <div class="card professional-card border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="fw-bold mb-0">Top Partners</h5>
                    <p class="text-muted mb-0 small">By revenue generated</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_partners)): ?>
                    <?php foreach ($top_partners as $index => $partner): ?>
                    <div class="d-flex align-items-center p-3 <?php echo $index < count($top_partners) - 1 ? 'border-bottom' : ''; ?>">
                        <div class="flex-shrink-0 me-3">
                            <div class="badge bg-light text-dark fw-bold rounded-pill" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                <?php echo $index + 1; ?>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars(substr($partner['partner_name'], 0, 25)); ?></h6>
                            <small class="text-muted"><?php echo $partner['bill_count']; ?> bills</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?php echo formatCurrency($partner['total_revenue'] ?? 0); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people text-muted" style="font-size: 2rem; opacity: 0.3;"></i>
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
";

include 'includes/footer.php';
?>
