<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - <?php echo $bill['invoice_number']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-logo {
            height: 50px;
            margin-bottom: 10px;
        }
        .company-info {
            color: #333;
        }
        .invoice-title {
            background: #0d6efd;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .invoice-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-details td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .invoice-details .label {
            background: #f8f9fa;
            font-weight: bold;
            width: 150px;
        }
        .bill-to {
            margin-bottom: 20px;
        }
        .bill-to h4 {
            margin: 0 0 10px 0;
            color: #0d6efd;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .items-table th {
            background: #0d6efd;
            color: white;
            font-weight: bold;
        }
        .amount-column {
            text-align: right !important;
        }
        .total-section {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .total-section td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .total-section .total-label {
            background: #f8f9fa;
            font-weight: bold;
        }
        .grand-total {
            background: #0d6efd !important;
            color: white !important;
            font-weight: bold;
        }
        .amount-words {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
        .footer {
            margin-top: 40px;
            border-top: 2px solid #0d6efd;
            padding-top: 20px;
        }
        .signature {
            text-align: right;
            margin-top: 50px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(13, 110, 253, 0.1);
            z-index: -1;
            font-weight: bold;
        }
        .gst-breakdown {
            background: #e3f2fd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">ONECLICK INSURANCE</div>
    
    <!-- Header -->
    <div class="header">
        <img src="../../assets/images/logo.jpg" alt="OneClick Insurance" class="company-logo" style="display: none;">
        <h2 style="margin: 0; color: #0d6efd;">One click Insurance Web Aggregator Pvt .Ltd.</h2>
        <div class="company-info">
            <strong>Phone:</strong> 0120-4344333 |
            <strong>E-mail:</strong> info@oneclickinsurer.com |
            <strong>Web:</strong> www.oneclickinsurer.com<br>
            <strong>CIN No:</strong> U67200UP2022PTC162272
        </div>
    </div>
    
    <!-- Invoice Title -->
    <div class="invoice-title">TAX INVOICE</div>
    
    <!-- Invoice Details -->
    <table class="invoice-details">
        <tr>
            <td class="label">Invoice Number:</td>
            <td><?php echo $bill['invoice_number']; ?></td>
            <td class="label">Invoice Date:</td>
            <td><?php echo date('d-m-Y', strtotime($bill['invoice_date'])); ?></td>
        </tr>
        <tr>
            <td class="label">Our GSTIN:</td>
            <td>09AAKCO5406A1ZE</td>
            <td class="label">Our PAN:</td>
            <td>AAKCO5406A</td>
        </tr>
        <tr>
            <td class="label">State Code:</td>
            <td>09 (Uttar Pradesh)</td>
            <td class="label">Place of Supply:</td>
            <td>Uttar Pradesh</td>
        </tr>
    </table>
    
    <!-- Bill To Section -->
    <div class="bill-to">
        <h4>Bill To:</h4>
        <strong><?php echo htmlspecialchars($bill['partner_name']); ?></strong><br>
        <?php echo nl2br(htmlspecialchars($bill['company_address'])); ?><br>
        <strong>GSTIN:</strong> <?php echo $bill['partner_gstin']; ?><br>
        <strong>PAN:</strong> <?php echo $bill['partner_pan']; ?><br>
        <strong>State Code:</strong> <?php echo $bill['state_code']; ?><br>
        <strong>Place of Supply:</strong> <?php echo $bill['place_of_supply']; ?>
    </div>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">S.No.</th>
                <th style="width: 55%;">Description of Services</th>
                <th style="width: 10%;">HSN/SAC</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 10%;">Rate (₹)</th>
                <th style="width: 10%;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                <td style="text-align: center;">997158</td>
                <td style="text-align: center;">1</td>
                <td class="amount-column"><?php echo number_format($bill['commission_amount'], 2); ?></td>
                <td class="amount-column"><?php echo number_format($bill['commission_amount'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: right; font-weight: bold;">Sub Total:</td>
                <td class="amount-column" style="font-weight: bold;">₹<?php echo number_format($bill['commission_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>
    
    <!-- GST Breakdown -->
    <div class="gst-breakdown">
        <h4 style="margin: 0 0 10px 0;">GST Breakdown:</h4>
        <?php if ($bill['cgst_amount'] > 0): ?>
            <div><strong>CGST (9%):</strong> ₹<?php echo number_format($bill['cgst_amount'], 2); ?></div>
            <div><strong>SGST (9%):</strong> ₹<?php echo number_format($bill['sgst_amount'], 2); ?></div>
        <?php else: ?>
            <div><strong>IGST (18%):</strong> ₹<?php echo number_format($bill['igst_amount'], 2); ?></div>
        <?php endif; ?>
        <div style="border-top: 1px solid #ccc; margin-top: 5px; padding-top: 5px;">
            <strong>Total GST:</strong> ₹<?php echo number_format($bill['total_gst'], 2); ?>
        </div>
    </div>
    
    <!-- Total Section -->
    <table class="total-section">
        <tr>
            <td class="total-label">Taxable Amount:</td>
            <td class="amount-column">₹<?php echo number_format($bill['commission_amount'], 2); ?></td>
        </tr>
        <tr>
            <td class="total-label">Total GST:</td>
            <td class="amount-column">₹<?php echo number_format($bill['total_gst'], 2); ?></td>
        </tr>
        <tr class="grand-total">
            <td>TOTAL AMOUNT:</td>
            <td class="amount-column">₹<?php echo number_format($bill['total_amount'], 2); ?></td>
        </tr>
    </table>
    
    <!-- Amount in Words -->
    <div class="amount-words">
        <strong>Amount in Words:</strong> <?php echo ucfirst($bill['amount_in_words']); ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div style="margin-bottom: 20px;">
            <strong>Terms & Conditions:</strong><br>
            1. Payment should be made within 15 days of invoice date.<br>
            2. All disputes are subject to Noida jurisdiction only.<br>
            3. This is a computer generated invoice and does not require physical signature.
        </div>
        
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; vertical-align: top;">
                <strong>Bank Details:</strong><br>
                Bank Name: HDFC Bank<br>
                Account No: 1234567890<br>
                IFSC Code: HDFC0001234<br>
                Branch: Sector 63, Noida
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <div class="signature">
                    <div style="margin-bottom: 50px;">&nbsp;</div>
                    <strong>For One click Insurance Web Aggregator Pvt .Ltd.</strong><br><br>
                    <strong>Suraj Verma</strong><br>
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
