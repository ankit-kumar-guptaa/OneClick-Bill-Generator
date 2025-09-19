<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Date filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$partner_filter = $_GET['partner_id'] ?? '';

// Monthly Revenue Report
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(invoice_date, '%Y-%m') as month,
        COUNT(*) as total_bills,
        SUM(commission_amount) as total_commission,
        SUM(total_gst) as total_gst,
        SUM(total_amount) as total_revenue
    FROM bills 
    WHERE invoice_date >= ? AND invoice_date <= ?
    " . ($partner_filter ? " AND partner_id = ?" : "") . "
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$params = [$start_date, $end_date];
if ($partner_filter) $params[] = $partner_filter;
$stmt->execute($params);
$monthly_data = $stmt->fetchAll();

// Partner-wise Report
$stmt = $db->prepare("
    SELECT 
        p.partner_name,
        p.state_code,
        p.place_of_supply,
        COUNT(b.id) as total_bills,
        SUM(b.commission_amount) as total_commission,
        SUM(b.total_gst) as total_gst,
        SUM(b.total_amount) as total_revenue,
        MAX(b.invoice_date) as last_bill_date
    FROM partners p
    LEFT JOIN bills b ON p.id = b.partner_id 
    WHERE (b.invoice_date >= ? AND b.invoice_date <= ?) OR b.id IS NULL
    " . ($partner_filter ? " AND p.id = ?" : "") . "
    GROUP BY p.id, p.partner_name, p.state_code, p.place_of_supply
    ORDER BY total_revenue DESC
");
$stmt->execute($params);
$partner_data = $stmt->fetchAll();

// Summary Statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_bills,
        SUM(commission_amount) as total_commission,
        SUM(total_gst) as total_gst,
        SUM(total_amount) as total_revenue
    FROM bills 
    WHERE invoice_date >= ? AND invoice_date <= ?
    " . ($partner_filter ? " AND partner_id = ?" : "")
);
$stmt->execute($params);
$summary = $stmt->fetch();

// Get all partners for filter
$stmt = $db->prepare("SELECT id, partner_name FROM partners WHERE status = 'active' ORDER BY partner_name");
$stmt->execute();
$all_partners = $stmt->fetchAll();

$page_title = 'Reports & Analytics - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<style>
.report-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

.report-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.metric-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.metric-card:hover {
    border-color: #3498db;
    transform: translateY(-2px);
}

.chart-container {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.filter-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
}
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-dark">Reports & Analytics</h2>
                    <p class="text-muted mb-0">Business insights and performance metrics</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="exportReport('csv')">
                        <i class="bi bi-download me-2"></i>Export CSV
                    </button>
                    <button class="btn btn-outline-success" onclick="exportReport('pdf')">
                        <i class="bi bi-file-pdf me-2"></i>Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filter-section">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Partner</label>
                        <select class="form-select" name="partner_id">
                            <option value="">All Partners</option>
                            <?php foreach ($all_partners as $partner): ?>
                                <option value="<?php echo $partner['id']; ?>" 
                                        <?php echo $partner_filter == $partner['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($partner['partner_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card p-4 text-center">
                <i class="bi bi-receipt text-primary mb-3" style="font-size: 2.5rem;"></i>
                <h3 class="fw-bold text-dark"><?php echo $summary['total_bills'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Total Bills</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card p-4 text-center">
                <i class="bi bi-cash-coin text-success mb-3" style="font-size: 2.5rem;"></i>
                <h3 class="fw-bold text-dark"><?php echo formatCurrency($summary['total_commission'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Commission</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card p-4 text-center">
                <i class="bi bi-percent text-warning mb-3" style="font-size: 2.5rem;"></i>
                <h3 class="fw-bold text-dark"><?php echo formatCurrency($summary['total_gst'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Total GST</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card p-4 text-center">
                <i class="bi bi-currency-rupee text-info mb-3" style="font-size: 2.5rem;"></i>
                <h3 class="fw-bold text-dark"><?php echo formatCurrency($summary['total_revenue'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Total Revenue</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Trend -->
        <div class="col-lg-7 mb-4">
            <div class="report-card">
                <div class="card-header bg-white border-0">
                    <h5 class="fw-bold mb-0">Monthly Revenue Trend</h5>
                    <p class="text-muted mb-0 small">Revenue performance over time</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($monthly_data)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th>Bills</th>
                                    <th>Commission</th>
                                    <th>GST</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo date('M Y', strtotime($row['month'] . '-01')); ?></td>
                                    <td><?php echo $row['total_bills']; ?></td>
                                    <td><?php echo formatCurrency($row['total_commission']); ?></td>
                                    <td><?php echo formatCurrency($row['total_gst']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatCurrency($row['total_revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-graph-up text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-3">No data available for selected period</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Partner Performance -->
        <div class="col-lg-5 mb-4">
            <div class="report-card">
                <div class="card-header bg-white border-0">
                    <h5 class="fw-bold mb-0">Partner Performance</h5>
                    <p class="text-muted mb-0 small">Top performing partners</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($partner_data)): ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach (array_slice($partner_data, 0, 8) as $index => $partner): ?>
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <div class="badge bg-light text-dark rounded-circle" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                    <?php echo $index + 1; ?>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars(substr($partner['partner_name'], 0, 25)); ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo $partner['total_bills'] ?? 0; ?> bills</small>
                                    <span class="fw-bold text-success"><?php echo formatCurrency($partner['total_revenue'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-3">No partner data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Partner Report -->
    <div class="row">
        <div class="col-12">
            <div class="report-card">
                <div class="card-header bg-white border-0">
                    <h5 class="fw-bold mb-0">Detailed Partner Report</h5>
                    <p class="text-muted mb-0 small">Complete breakdown by partner</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="partnerReportTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Partner Name</th>
                                    <th>Location</th>
                                    <th>Bills</th>
                                    <th>Commission</th>
                                    <th>GST</th>
                                    <th>Revenue</th>
                                    <th>Last Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partner_data as $partner): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($partner['partner_name']); ?></div>
                                        <small class="text-muted">State: <?php echo $partner['state_code']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($partner['place_of_supply']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $partner['total_bills'] ?? 0; ?></span></td>
                                    <td><?php echo formatCurrency($partner['total_commission'] ?? 0); ?></td>
                                    <td><?php echo formatCurrency($partner['total_gst'] ?? 0); ?></td>
                                    <td class="fw-bold"><?php echo formatCurrency($partner['total_revenue'] ?? 0); ?></td>
                                    <td>
                                        <?php if ($partner['last_bill_date']): ?>
                                            <?php echo formatIndianDate($partner['last_bill_date']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No bills</span>
                                        <?php endif; ?>
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
function exportReport(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('export_report.php?' + params.toString(), '_blank');
}
";

include '../includes/footer.php';
?>
