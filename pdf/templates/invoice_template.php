<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - <?php echo $bill['invoice_number']; ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            background: white;
        }
        
        /* Header Section */
        .invoice-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 10px;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 8px;
        }
        
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c5aa0;
            margin: 0 0 5px 0;
        }
        
        .company-details {
            font-size: 9px;
            color: #666;
            line-height: 1.2;
        }
        
        .invoice-title {
            background: linear-gradient(135deg, #2c5aa0, #1e4080);
            color: white;
            padding: 8px 15px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        /* Two Column Layout */
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }
        
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
        }
        
        .info-title {
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .info-row {
            margin-bottom: 4px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
            font-size: 10px;
        }
        
        .info-value {
            font-size: 10px;
        }
        
        /* Items Table - Compact */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        
        .items-table th {
            background: #2c5aa0;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c5aa0;
        }
        
        .items-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .amount-right {
            text-align: right;
        }
        
        /* GST and Total Section - Side by Side */
        .calculation-section {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        
        .gst-section {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
            vertical-align: top;
        }
        
        .total-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .gst-box, .total-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
        }
        
        .gst-box {
            background: #e7f3ff;
        }
        
        .total-box {
            background: #f0f9ff;
        }
        
        .gst-title, .total-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
            color: #2c5aa0;
        }
        
        .gst-row, .total-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .row-label {
            display: table-cell;
            font-size: 10px;
        }
        
        .row-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
        }
        
        .grand-total {
            background: #2c5aa0;
            color: white;
            padding: 8px;
            border-radius: 4px;
            margin-top: 5px;
        }
        
        .grand-total .row-label,
        .grand-total .row-value {
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Amount in Words */
        .amount-words {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .words-label {
            font-weight: bold;
            color: #856404;
        }
        
        /* Footer Section - Compact */
        .footer-section {
            display: table;
            width: 100%;
            margin-top: 15px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        
        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .terms-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
        }
        
        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 10px;
            color: #2c5aa0;
        }
        
        .terms-list {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .bank-details {
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        .signature-area {
            text-align: center;
            margin-top: 15px;
        }
        
        .digital-signature {
            border: 1px solid #dee2e6;
            background: #ffffff;
            padding: 5px;
            margin: 10px auto;
            display: inline-block;
            border-radius: 4px;
        }
        
        .live-signature {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            text-align: left;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #ccc;
            border-radius: 3px;
            width: 200px;
            margin: 10px auto;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
        }
        
        .signature-by {
            color: #0066cc;
            margin-bottom: 2px;
        }
        
        .signature-date {
            margin: 1px 0;
        }
        
        .signature-text {
            font-size: 10px;
            font-weight: bold;
            color: #2c5aa0;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(44, 90, 160, 0.05);
            z-index: -1;
            font-weight: bold;
            letter-spacing: 3px;
        }
        
        /* Print Optimization */
        @media print {
            body { 
                -webkit-print-color-adjust: exact; 
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">ONECLICK INSURANCE</div>
    
    <!-- Header Section -->
    <div class="invoice-header">
        <div class="header-left">
            <?php 
            $logo_path = '../../assets/images/logo.png';
            if (file_exists($logo_path)): ?>
                <img src="<?php echo $logo_path; ?>" alt="OneClick Insurance" class="company-logo">
            <?php endif; ?>
            <div class="company-title">One click Insurance Web Aggregator Pvt Ltd.</div>
            <div class="company-details">
                Phone: 0120-4344333 | Email: info@oneclickinsurer.com<br>
                Website: www.oneclickinsurer.com | CIN: U67200UP2022PTC162272
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">TAX INVOICE</div>
            <div style="font-size: 10px; margin-top: 5px;">
                <strong>Invoice #:</strong> <?php echo $bill['invoice_number']; ?><br>
                <strong>Date:</strong> <?php echo date('d-m-Y', strtotime($bill['invoice_date'])); ?>
            </div>
        </div>
    </div>
    
    <!-- Invoice Information - Two Columns -->
    <div class="invoice-info">
        <div class="info-left">
            <div class="info-box">
                <div class="info-title">Our Details</div>
                <div class="info-row">
                    <span class="info-label">GSTIN:</span>
                    <span class="info-value">09AAKCO5406A1ZE</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PAN:</span>
                    <span class="info-value">AAKCO5406A</span>
                </div>
                <div class="info-row">
                    <span class="info-label">State Code:</span>
                    <span class="info-value">09 (Uttar Pradesh)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Place of Supply:</span>
                    <span class="info-value">Uttar Pradesh</span>
                </div>
            </div>
        </div>
        
        <div class="info-right">
            <div class="info-box">
                <div class="info-title">Bill To</div>
                <div style="font-weight: bold; margin-bottom: 5px; font-size: 11px;">
                    <?php echo htmlspecialchars($bill['partner_name']); ?>
                </div>
                <div style="margin-bottom: 8px; font-size: 10px; line-height: 1.2;">
                    <?php echo nl2br(htmlspecialchars($bill['company_address'])); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">GSTIN:</span>
                    <span class="info-value"><?php echo $bill['partner_gstin']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PAN:</span>
                    <span class="info-value"><?php echo $bill['partner_pan']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">State Code:</span>
                    <span class="info-value"><?php echo $bill['state_code']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Place:</span>
                    <span class="info-value"><?php echo $bill['place_of_supply']; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Items Table - Compact -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">S.No</th>
                <th style="width: 50%;">Description of Services</th>
                <th style="width: 10%;">HSN/SAC</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 13%;">Rate (₹)</th>
                <th style="width: 13%;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                <td style="text-align: center;">997158</td>
                <td style="text-align: center;">1</td>
                <td class="amount-right"><?php echo number_format($bill['commission_amount'], 2); ?></td>
                <td class="amount-right"><?php echo number_format($bill['commission_amount'], 2); ?></td>
            </tr>
            <tr style="background: #f0f9ff;">
                <td colspan="5" style="text-align: right; font-weight: bold;">Subtotal:</td>
                <td class="amount-right" style="font-weight: bold;">₹<?php echo number_format($bill['commission_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>
    
    <!-- GST and Total Section - Side by Side -->
    <div class="calculation-section">
        <div class="gst-section">
            <div class="gst-box">
                <div class="gst-title">GST Breakdown</div>
                <?php if ($bill['cgst_amount'] > 0): ?>
                    <div class="gst-row">
                        <div class="row-label">CGST @ 9%</div>
                        <div class="row-value">₹<?php echo number_format($bill['cgst_amount'], 2); ?></div>
                    </div>
                    <div class="gst-row">
                        <div class="row-label">SGST @ 9%</div>
                        <div class="row-value">₹<?php echo number_format($bill['sgst_amount'], 2); ?></div>
                    </div>
                <?php else: ?>
                    <div class="gst-row">
                        <div class="row-label">IGST @ 18%</div>
                        <div class="row-value">₹<?php echo number_format($bill['igst_amount'], 2); ?></div>
                    </div>
                <?php endif; ?>
                <div class="gst-row" style="border-top: 1px solid #ccc; padding-top: 5px; margin-top: 5px;">
                    <div class="row-label"><strong>Total GST</strong></div>
                    <div class="row-value"><strong>₹<?php echo number_format($bill['total_gst'], 2); ?></strong></div>
                </div>
            </div>
        </div>
        
        <div class="total-section">
            <div class="total-box">
                <div class="total-title">Amount Summary</div>
                <div class="total-row">
                    <div class="row-label">Taxable Amount</div>
                    <div class="row-value">₹<?php echo number_format($bill['commission_amount'], 2); ?></div>
                </div>
                <div class="total-row">
                    <div class="row-label">Total GST</div>
                    <div class="row-value">₹<?php echo number_format($bill['total_gst'], 2); ?></div>
                </div>
                <div class="grand-total">
                    <div class="total-row">
                        <div class="row-label">TOTAL AMOUNT</div>
                        <div class="row-value">₹<?php echo number_format($bill['total_amount'], 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Amount in Words -->
    <div class="amount-words">
        <span class="words-label">Amount in Words:</span> 
        <?php echo ucfirst($bill['amount_in_words']); ?>
    </div>
    
    <!-- Footer Section -->
    <div class="footer-section">
        <div class="footer-left">
            <div class="terms-box">
                <div class="terms-title">Terms & Conditions</div>
                <div class="terms-list">
                    1. Payment within 15 days of invoice date<br>
                    2. All disputes subject to Noida jurisdiction<br>
                    3. Computer generated invoice - no signature required
                </div>
            </div>
            
            <div class="bank-details" style="margin-top: 10px;">
                <strong style="font-size: 10px;">Bank Details:</strong><br>
                Bank: HDFC Bank | A/C: 1234567890<br>
                IFSC: HDFC0001234 | Branch: Sector 63, Noida
            </div>
        </div>
        
        <div class="footer-right">
            <div class="signature-area">
                <div style="margin-bottom: 5px; font-size: 10px;">For One click Insurance Web Aggregator Pvt Ltd.</div>
                
                <!-- Live Digital Signature Area -->
                <?php 
                $digital_signature_path = '../../uploads/digital_signatures/signature_' . ($_SESSION['admin_id'] ?? 'default') . '.png';
                if (file_exists($digital_signature_path)): ?>
                    <div class="digital-signature">
                        <img src="<?php echo $digital_signature_path; ?>?<?php echo time(); ?>" 
                             style="width: 200px; height: auto; max-height: 80px; object-fit: contain;" 
                             alt="Digital Signature">
                    </div>
                <?php else: ?>
                    <!-- Live signature generation with fixed timezone -->
                    <?php 
                    // Set timezone to India
                    date_default_timezone_set('Asia/Kolkata');
                    $currentDate = date('Y.m.d');
                    $currentTime = date('H:i:s');
                    $timezone = '+05\'30\'';
                    ?>
                    <div class="live-signature">
                        <div class="signature-name">SURAJ VERMA</div>
                        <div class="signature-by">Digitally signed by SURAJ VERMA</div>
                        <div class="signature-date">Date: <?php echo $currentDate; ?></div>
                        <div class="signature-date"><?php echo $currentTime; ?> <?php echo $timezone; ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="signature-text">
                    <strong>Suraj Verma</strong><br>
                    <span style="font-size: 9px;">Authorized Signatory</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
