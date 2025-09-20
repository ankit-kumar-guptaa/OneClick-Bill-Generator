<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Get export parameters
$export_type = $_GET['export'] ?? 'csv';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$partner_filter = $_GET['partner_id'] ?? '';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Only allow CSV export for now
if ($export_type !== 'csv') {
    die('Only CSV export is available. PDF export requires additional setup.');
}

// Build query based on filters
$sql = "
    SELECT 
        b.invoice_number,
        b.invoice_date,
        p.partner_name,
        p.company_address,
        p.gstin as partner_gstin,
        p.pan as partner_pan,
        p.state_code,
        p.place_of_supply,
        b.description,
        b.commission_amount,
        b.cgst_amount,
        b.sgst_amount,
        b.igst_amount,
        b.total_gst,
        b.total_amount,
        b.amount_in_words,
        b.created_at
    FROM bills b 
    LEFT JOIN partners p ON b.partner_id = p.id 
    WHERE b.invoice_date >= ? AND b.invoice_date <= ?
";

$params = [$start_date, $end_date];

if ($partner_filter) {
    $sql .= " AND b.partner_id = ?";
    $params[] = $partner_filter;
}

$sql .= " ORDER BY b.invoice_date DESC, b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Get summary data
$summary_sql = "
    SELECT 
        COUNT(*) as total_bills,
        SUM(commission_amount) as total_commission,
        SUM(cgst_amount) as total_cgst,
        SUM(sgst_amount) as total_sgst,
        SUM(igst_amount) as total_igst,
        SUM(total_gst) as total_gst,
        SUM(total_amount) as total_amount
    FROM bills b 
    WHERE b.invoice_date >= ? AND b.invoice_date <= ?
";

$summary_params = [$start_date, $end_date];
if ($partner_filter) {
    $summary_sql .= " AND b.partner_id = ?";
    $summary_params[] = $partner_filter;
}

$stmt = $db->prepare($summary_sql);
$stmt->execute($summary_params);
$summary = $stmt->fetch();

// Generate CSV Report
generateCSVReport($data, $summary, $start_date, $end_date, $partner_filter);

function generateCSVReport($data, $summary, $start_date, $end_date, $partner_filter) {
    // Generate filename
    $filename = 'OneClick_Insurance_Report_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 (for Excel compatibility)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['OneClick Insurance Web Aggregator Pvt Ltd']);
    fputcsv($output, ['CIN: U67200UP2022PTC162272']);
    fputcsv($output, ['Phone: 0120-4344333 | Email: info@oneclickinsurer.com']);
    fputcsv($output, ['Website: www.oneclickinsurer.com']);
    fputcsv($output, []);
    fputcsv($output, ['BUSINESS PERFORMANCE REPORT']);
    fputcsv($output, ['Generated on: ' . date('d-m-Y H:i:s') . ' IST']);
    fputcsv($output, ['Report Period: ' . date('d-m-Y', strtotime($start_date)) . ' to ' . date('d-m-Y', strtotime($end_date))]);
    fputcsv($output, []);
    
    // Summary section
    fputcsv($output, ['EXECUTIVE SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Bills Generated', number_format($summary['total_bills'])]);
    fputcsv($output, ['Total Commission Earned', '₹ ' . number_format($summary['total_commission'], 2)]);
    fputcsv($output, ['Total CGST Collected', '₹ ' . number_format($summary['total_cgst'], 2)]);
    fputcsv($output, ['Total SGST Collected', '₹ ' . number_format($summary['total_sgst'], 2)]);
    fputcsv($output, ['Total IGST Collected', '₹ ' . number_format($summary['total_igst'], 2)]);
    fputcsv($output, ['Total GST Amount', '₹ ' . number_format($summary['total_gst'], 2)]);
    fputcsv($output, ['TOTAL BUSINESS REVENUE', '₹ ' . number_format($summary['total_amount'], 2)]);
    
    if ($summary['total_bills'] > 0) {
        $avg_per_bill = $summary['total_amount'] / $summary['total_bills'];
        fputcsv($output, ['Average Revenue per Bill', '₹ ' . number_format($avg_per_bill, 2)]);
    }
    
    fputcsv($output, []);
    
    // Data headers
    fputcsv($output, [
        'Sr. No.',
        'Invoice Number',
        'Invoice Date',
        'Partner Company Name',
        'Partner GSTIN',
        'Partner PAN Number',
        'State Code',
        'Place of Supply',
        'Service Description',
        'Commission Amount (₹)',
        'CGST Amount (₹)',
        'SGST Amount (₹)',
        'IGST Amount (₹)',
        'Total GST (₹)',
        'Grand Total Amount (₹)',
        'Amount in Words',
        'Bill Generated Date & Time'
    ]);
    
    // Data rows
    if (!empty($data)) {
        $srno = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $srno++,
                $row['invoice_number'],
                date('d-m-Y', strtotime($row['invoice_date'])),
                $row['partner_name'],
                $row['partner_gstin'],
                $row['partner_pan'],
                $row['state_code'],
                $row['place_of_supply'],
                $row['description'],
                number_format($row['commission_amount'], 2),
                number_format($row['cgst_amount'], 2),
                number_format($row['sgst_amount'], 2),
                number_format($row['igst_amount'], 2),
                number_format($row['total_gst'], 2),
                number_format($row['total_amount'], 2),
                $row['amount_in_words'],
                date('d-m-Y H:i:s', strtotime($row['created_at']))
            ]);
        }
        
        // Add totals row
        fputcsv($output, []);
        fputcsv($output, [
            '', '', '', '', '', '', '', '', 'GRAND TOTALS:',
            number_format($summary['total_commission'], 2),
            number_format($summary['total_cgst'], 2),
            number_format($summary['total_sgst'], 2),
            number_format($summary['total_igst'], 2),
            number_format($summary['total_gst'], 2),
            number_format($summary['total_amount'], 2),
            '', ''
        ]);
    } else {
        fputcsv($output, ['', '', '', 'No data found for selected period', '', '', '', '', '', '', '', '', '', '', '', '', '']);
    }
    
    // Footer section
    fputcsv($output, []);
    fputcsv($output, ['REPORT FOOTER']);
    fputcsv($output, ['Report Generated By', 'OneClick Insurance Management System']);
    fputcsv($output, ['Generated On', date('d-m-Y H:i:s') . ' IST']);
    fputcsv($output, ['Authorized By', 'Suraj Verma - Managing Director']);
    fputcsv($output, ['Digital Signature', 'Digitally signed on ' . date('Y.m.d H:i:s') . ' +05\'30\'']);
    fputcsv($output, []);
    fputcsv($output, ['Contact Information']);
    fputcsv($output, ['Email', 'info@oneclickinsurer.com']);
    fputcsv($output, ['Phone', '+91-120-4344333']);
    fputcsv($output, ['Website', 'www.oneclickinsurer.com']);
    fputcsv($output, ['Address', 'Sector 63, Noida, Uttar Pradesh, India']);
    
    fclose($output);
    exit;
}
?>
