<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    die('Bill ID required');
}

$bill_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get bill details with partner info
$stmt = $db->prepare("
    SELECT b.*, p.partner_name, p.company_address, p.gstin as partner_gstin, 
           p.pan as partner_pan, p.state_code, p.place_of_supply
    FROM bills b 
    LEFT JOIN partners p ON b.partner_id = p.id 
    WHERE b.id = ?
");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if (!$bill) {
    die('Bill not found');
}

$page_title = 'View Bill - ' . $bill['invoice_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: #f5f6fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .invoice-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 900px;
        }
        
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .logo-fallback {
            width: 120px;
            height: 60px;
            background: #2c5aa0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 1rem;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .invoice-title {
            background: #2c5aa0;
            color: white;
            padding: 0.8rem 2rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 1rem 0;
            border-radius: 5px;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-title {
            color: #2c5aa0;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .table-modern {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-modern th {
            background: #2c5aa0;
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .amount-highlight {
            background: #e8f5e8;
            padding: 0.5rem;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .action-buttons {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        
        .signature-space {
            border: 2px dashed #dee2e6;
            padding: 2rem;
            text-align: center;
            border-radius: 8px;
            background: #fafafa;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .invoice-container { box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Action Buttons -->
    <div class="container no-print">
        <div class="action-buttons text-center">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="bi bi-printer me-1"></i>Print Invoice
            </button>
            <button onclick="generatePDF(<?php echo $bill['id']; ?>)" class="btn btn-success me-2">
                <i class="bi bi-file-pdf me-1"></i>Download PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Close
            </button>
        </div>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <?php 
            $logo_paths = [
                '../assets/images/logo.jpg',
                '../assets/images/logo.png'
            ];
            
            $logo_found = false;
            foreach ($logo_paths as $logo_path) {
                if (file_exists($logo_path)) {
                    echo '<img src="' . $logo_path . '?v=' . time() . '" alt="OneClick Insurance" class="company-logo">';
                    $logo_found = true;
                    break;
                }
            }
            
            if (!$logo_found) {
                echo '<div class="logo-fallback">OneClick<br>Insurance</div>';
            }
            ?>
            <h3 class="text-primary mb-2">One click Insurance Web Aggregator Pvt Ltd.</h3>
            <p class="mb-0 text-muted">
                <strong>Phone:</strong> 0120-4344333 | <strong>Email:</strong> info@oneclickinsurer.com<br>
                <strong>Website:</strong> www.oneclickinsurer.com | <strong>CIN:</strong> U67200UP2022PTC162272
            </p>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">TAX INVOICE</div>

        <!-- Invoice Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-section">
                    <h6 class="info-title">Invoice Details</h6>
                    <div class="row">
                        <div class="col-6"><strong>Invoice No:</strong></div>
                        <div class="col-6"><?php echo $bill['invoice_number']; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Invoice Date:</strong></div>
                        <div class="col-6"><?php echo formatIndianDate($bill['invoice_date']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Our GSTIN:</strong></div>
                        <div class="col-6">09AAKCO5406A1ZE</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Our PAN:</strong></div>
                        <div class="col-6">AAKCO5406A</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-section">
                    <h6 class="info-title">Bill To</h6>
                    <div><strong><?php echo htmlspecialchars($bill['partner_name']); ?></strong></div>
                    <div class="mb-2"><?php echo nl2br(htmlspecialchars($bill['company_address'])); ?></div>
                    <div class="row">
                        <div class="col-6"><strong>GSTIN:</strong></div>
                        <div class="col-6"><?php echo $bill['partner_gstin']; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>PAN:</strong></div>
                        <div class="col-6"><?php echo $bill['partner_pan']; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>State:</strong></div>
                        <div class="col-6"><?php echo $bill['state_code']; ?> - <?php echo $bill['place_of_supply']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Description</th>
                        <th>HSN/SAC</th>
                        <th>Qty</th>
                        <th class="text-end">Rate (₹)</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?php echo htmlspecialchars($bill['description']); ?></td>
                        <td>997158</td>
                        <td>1</td>
                        <td class="text-end"><?php echo number_format($bill['commission_amount'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($bill['commission_amount'], 2); ?></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                        <td class="text-end fw-bold">₹<?php echo number_format($bill['commission_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Calculations -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-section">
                    <h6 class="info-title">GST Breakdown</h6>
                    <?php if ($bill['cgst_amount'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>CGST (9%):</span>
                            <span>₹<?php echo number_format($bill['cgst_amount'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>SGST (9%):</span>
                            <span>₹<?php echo number_format($bill['sgst_amount'], 2); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between">
                            <span>IGST (18%):</span>
                            <span>₹<?php echo number_format($bill['igst_amount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total GST:</span>
                        <span>₹<?php echo number_format($bill['total_gst'], 2); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-section">
                    <h6 class="info-title">Total Amount</h6>
                    <div class="d-flex justify-content-between">
                        <span>Taxable Amount:</span>
                        <span>₹<?php echo number_format($bill['commission_amount'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total GST:</span>
                        <span>₹<?php echo number_format($bill['total_gst'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="amount-highlight text-center">
                        <strong>TOTAL: ₹<?php echo number_format($bill['total_amount'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Amount in Words -->
        <div class="info-section">
            <h6 class="info-title">Amount in Words</h6>
            <p class="mb-0"><?php echo ucfirst($bill['amount_in_words']); ?></p>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="info-section">
                    <h6 class="info-title">Terms & Conditions</h6>
                    <small>
                        1. Payment within 15 days of invoice date<br>
                        2. All disputes subject to Noida jurisdiction<br>
                        3. Computer generated invoice
                    </small>
                </div>
                
                <div class="info-section">
                    <h6 class="info-title">Bank Details</h6>
                    <small>
                        <strong>Bank:</strong> HDFC Bank<br>
                        <strong>A/C No:</strong> 1234567890<br>
                        <strong>IFSC:</strong> HDFC0001234<br>
                        <strong>Branch:</strong> Sector 63, Noida
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-center">
                    <p><strong>For One click Insurance Web Aggregator Pvt Ltd.</strong></p>
                    
                    <div class="signature-space my-4">
                        <small class="text-muted">Digital Signature Space</small>
                    </div>
                    
                    <p class="fw-bold">
                        <strong>Suraj Verma</strong><br>
                        <small>Authorized Signatory</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generatePDF(billId) {
            window.open('../pdf/generate_pdf.php?bill_id=' + billId, '_blank');
        }
    </script>
</body>
</html>
