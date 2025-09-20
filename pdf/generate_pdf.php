<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';
redirectIfNotLoggedIn();




use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['bill_id'])) {
    die('Bill ID required');
}

$bill_id = (int)$_GET['bill_id'];

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

// Configure DOMPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// Load HTML template
ob_start();
include 'templates/invoice_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$filename = 'OneClick_Invoice_' . $bill['invoice_number'] . '.pdf';
$dompdf->stream($filename, array('Attachment' => false));
?>
