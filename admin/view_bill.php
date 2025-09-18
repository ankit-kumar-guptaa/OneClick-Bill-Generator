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
        .invoice-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .invoice-title {
            background: #0d6efd;
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .bill-details table {
            width: 100%;
            margin-bottom: 1rem;
        }
        .bill-details th {
            background: #f8f9fa;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            font-weight: bold;
        }
        .bill-details td {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .invoice-container { box-shadow: none; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Print/PDF Actions -->
    <div class="container mt-3 no-print">
        <div class="row">
            <div class="col-12 text-end">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
                <button onclick="generatePDF(<?php echo $bill['id']; ?>)" class="btn btn-danger">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    <i class="bi bi-x"></i> Close
                </button>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="invoice-container">
                    <!-- Header -->
                    <div class="invoice-header">
                        <h2 class="text-primary mb-2">One click Insurance Web Aggregator Pvt .Ltd.</h2>
                        <p class="mb-0">
                            <strong>Phone:</strong> 0120-4344333 | 
                            <strong>Email:</strong> info@oneclickinsurer.com | 
                            <strong>Web:</strong> www.oneclickinsurer.com<br>
                            <strong>CIN No:</strong> U67200UP2022PTC162272
                        </p>
                    </div>

                    <!-- Invoice Title -->
                    <div class="invoice-title">TAX INVOICE</div>

                    <!-- Invoice Details -->
                    <div class="bill-details mb-4">
                        <table class="table">
                            <tr>
                                <th style="width: 20%;">Invoice Number:</th>
                                <td style="width: 30%;"><?php echo $bill['invoice_number']; ?></td>
                                <th style="width: 20%;">Invoice Date:</th>
                                <td style="width: 30%;"><?php echo date('d-m-Y', strtotime($bill['invoice_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Our GSTIN:</th>
                                <td>09AAKCO5406A1ZE</td>
                                <th>Our PAN:</th>
                                <td>AAKCO5406A</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Bill To -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary">Bill To:</h5>
                            <address>
                                <strong><?php echo htmlspecialchars($bill['partner_name']); ?></strong><br>
                                <?php echo nl2br(htmlspecialchars($bill['company_address'])); ?><br>
                                <strong>GSTIN:</strong> <?php echo $bill['partner_gstin']; ?><br>
                                <strong>PAN:</strong> <?php echo $bill['partner_pan']; ?>
                            </address>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary">Supply Details:</h5>
                            <p>
                                <strong>State Code:</strong> <?php echo $bill['state_code']; ?><br>
                                <strong>Place of Supply:</strong> <?php echo $bill['place_of_supply']; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>S.No.</th>
                                    <th>Description of Services</th>
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
                            </tbody>
                        </table>
                    </div>

                    <!-- GST & Total Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">GST Breakdown</h6>
                                </div>
                                <div class="card-body">
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
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td><strong>Taxable Amount:</strong></td>
                                    <td class="text-end">₹<?php echo number_format($bill['commission_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Total GST:</strong></td>
                                    <td class="text-end">₹<?php echo number_format($bill['total_gst'], 2); ?></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>TOTAL AMOUNT:</strong></td>
                                    <td class="text-end"><strong>₹<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Amount in Words -->
                    <div class="alert alert-info">
                        <strong>Amount in Words:</strong> <?php echo ucfirst($bill['amount_in_words']); ?>
                    </div>

                    <!-- Footer -->
                    <div class="row mt-4 pt-4 border-top">
                        <div class="col-md-6">
                            <h6>Terms & Conditions:</h6>
                            <small>
                                1. Payment should be made within 15 days.<br>
                                2. All disputes subject to Noida jurisdiction.<br>
                                3. Computer generated invoice.
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-5">
                                <strong>For One click Insurance Web Aggregator Pvt .Ltd.</strong>
                            </p>
                            <p><strong>Suraj Verma</strong><br>Authorized Signatory</p>
                        </div>
                    </div>
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
