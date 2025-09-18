<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_partner'])) {
        $partner_name = sanitizeInput($_POST['partner_name']);
        $company_address = sanitizeInput($_POST['company_address']);
        $gstin = sanitizeInput($_POST['gstin']);
        $pan = sanitizeInput($_POST['pan']);
        $state_code = sanitizeInput($_POST['state_code']);
        $place_of_supply = sanitizeInput($_POST['place_of_supply']);
        
        $stmt = $db->prepare("INSERT INTO partners (partner_name, company_address, gstin, pan, state_code, place_of_supply) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$partner_name, $company_address, $gstin, $pan, $state_code, $place_of_supply])) {
            $message = 'Partner added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error adding partner!';
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $partner_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM partners WHERE id = ?");
    if ($stmt->execute([$partner_id])) {
        $message = 'Partner deleted successfully!';
        $message_type = 'success';
    }
}

// Get all partners
$stmt = $db->prepare("SELECT * FROM partners ORDER BY partner_name ASC");
$stmt->execute();
$partners = $stmt->fetchAll();

$page_title = 'Manage Partners - OneClick Insurance';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$nav_home = '../index.php';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-people"></i> Manage Partners
                    </h4>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                        <i class="bi bi-plus"></i> Add New Partner
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="partnersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Partner Name</th>
                                    <th>GSTIN</th>
                                    <th>PAN</th>
                                    <th>State Code</th>
                                    <th>Place of Supply</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partners as $partner): ?>
                                <tr>
                                    <td><?php echo $partner['id']; ?></td>
                                    <td><?php echo htmlspecialchars($partner['partner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['gstin']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['pan']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['state_code']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['place_of_supply']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $partner['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($partner['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editPartnerModal" onclick="editPartner(<?php echo $partner['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete('partner', <?php echo $partner['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="text-muted">Total Partners: <?php echo count($partners); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-success" onclick="exportToExcel('partnersTable')">
                                <i class="bi bi-download"></i> Export to CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Partner Modal -->
<div class="modal fade" id="addPartnerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Add New Partner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="partner_name" class="form-label">Partner Name *</label>
                            <input type="text" class="form-control" id="partner_name" name="partner_name" required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="company_address" class="form-label">Company Address *</label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" required></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="gstin" class="form-label">GSTIN *</label>
                            <input type="text" class="form-control" id="gstin" name="gstin" maxlength="15" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pan" class="form-label">PAN *</label>
                            <input type="text" class="form-control" id="pan" name="pan" maxlength="10" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="state_code" class="form-label">State Code *</label>
                            <input type="text" class="form-control" id="state_code" name="state_code" maxlength="2" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="place_of_supply" class="form-label">Place of Supply *</label>
                            <input type="text" class="form-control" id="place_of_supply" name="place_of_supply" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_partner" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Partner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
function editPartner(id) {
    // Add edit functionality here
    console.log('Edit partner:', id);
}
";
include '../includes/footer.php';
?>
